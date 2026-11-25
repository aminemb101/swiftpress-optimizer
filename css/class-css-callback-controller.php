<?php

namespace SwiftPress\Optimizer;

use WP_REST_Request;
use WP_REST_Response;

class CSS_Callback_Controller
{
    private const NAMESPACE = 'swiftpress/v1';
    private const ROUTE     = '/css-callback';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_callback'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_callback(WP_REST_Request $request): WP_REST_Response
    {
        if (function_exists('xdebug_break')) {
            xdebug_break();
        }
        $data   = $request->get_json_params();
        $status = $data['status'] ?? '';
        $url    = $data['url'] ?? '';

        if (empty($url) || empty($status)) {
            return new WP_REST_Response(['error' => 'Missing url or status'], 400);
        }
        $path = $this->cache_path($url);
        if (file_exists($path)) {
            return new WP_REST_Response(['already_exists' => true], 200);
        }

        if ($status === 'failed') {
            $error = $data['error'] ?? 'Unknown error';
            error_log(sprintf('[SwiftPress] Critical CSS failed for %s: %s', $url, $error));

            return new WP_REST_Response(['received' => true], 200);
        }

        if ($status === 'completed') {
            $result_url = $data['resultUrl'] ?? '';
            if (empty($result_url)) {
                return new WP_REST_Response(['error' => 'Missing resultUrl'], 400);
            }

            $css_response = wp_remote_get($result_url, ['timeout' => 20]);
            if (is_wp_error($css_response) || wp_remote_retrieve_response_code($css_response) !== 200) {
                return new WP_REST_Response(['error' => 'Failed to download CSS from resultUrl'], 502);
            }

            $css = wp_remote_retrieve_body($css_response);
            if ($css === '') {
                return new WP_REST_Response(['error' => 'Empty CSS response'], 422);
            }

            wp_mkdir_p(dirname($path));
            file_put_contents($path, $css);

            return new WP_REST_Response(['saved' => true], 200);
        }

        return new WP_REST_Response(['error' => 'Invalid status'], 400);
    }

    private function cache_path($url, $strategy = 'critical'): string
    {
        $cache_dir = WP_CONTENT_DIR . '/cache/wpx-css';

        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }

        $hash = md5($url . '|' . $strategy);
        return $cache_dir . '/' . $hash . '.css';
    }


}