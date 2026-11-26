<?php

namespace SwiftPress\Optimizer;

if (!defined('ABSPATH')) {
    exit;
}

final class CSS_Cache
{
    private const GROUP = 'swiftpress-optimizer';
    private const FETCH_HOOK = 'swiftpress_fetch_css';

    public function __construct()
    {
        add_action('wp', function () {
            $mode = CSS_Settings::get_mode();
            $this->check_cache($mode);
        }, 20);
        add_action(self::FETCH_HOOK, array($this, 'fetch_css'), 10, 3);

    }

    public function init()
    {
        add_action('wp', array($this, 'check_cache'), 20);
        add_action(self::FETCH_HOOK, array($this, 'fetch_css'), 10, 3);
    }

    public function check_cache(string $mode): void
    {
        $strategies = match ($mode) {
            'both'     => ['critical', 'minimal'],
            'critical' => ['critical'],
            'minimal'  => ['minimal'],
            default    => [],
        };

        if (empty($strategies)) {
            return;
        }

        if (is_admin() || is_feed() || is_preview() || is_customize_preview() || is_robots() || is_trackback()) {
            return;
        }

        $url = $this->current_url();
        if (!$url || !function_exists('as_enqueue_async_action')) {
            return;
        }

        foreach ($strategies as $strategy) {
            $path = $this->cache_path($url, $strategy);

            $args = [
                'url'      => $url,
                'strategy' => $strategy,
                'path'     => $path,
            ];

            if (file_exists($path) && is_readable($path)) {
                as_unschedule_all_actions(self::FETCH_HOOK, $args, self::GROUP);
                continue;
            }

            if (!as_has_scheduled_action(self::FETCH_HOOK, $args, self::GROUP)) {
                as_enqueue_async_action(self::FETCH_HOOK, $args, self::GROUP, true);
            }
        }
    }

    public function fetch_css(string $url,
                              string $strategy,
                              string $path): bool
    {
        $api = CSS_Settings::get_api_url();
        $base = 'http://host.docker.internal:3000';

        $response = wp_remote_post($api, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'url' => $url,
                'strategy' => $strategy,
                'callbackUrl' => rest_url('swiftpress/v1/css-callback')
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 202) {
            return false;
        }

        $job = json_decode(wp_remote_retrieve_body($response), true);
        $status_url = $job['statusUrl'] ?? '';

        if (empty($status_url)) {
            return false;
        }

        return true;
    }



    public function current_url(): ?string
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || is_404()
            || is_feed()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || (defined('WP_CLI') && WP_CLI)
            || !isset($_SERVER['REQUEST_URI'])
        ) {
            return null;
        }

        // Only allow standard GET page views
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($method) !== 'GET') {
            return null;
        }

        $request_uri = wp_unslash($_SERVER['REQUEST_URI']);
        $path        = parse_url($request_uri, PHP_URL_PATH) ?? '';

        // Reject static assets & system endpoints (e.g. /favicon.ico, .map, .xml, wp-login.php)
        if (
            preg_match('/\.(ico|xml|txt|json|map|css|js|png|jpe?g|gif|webp|svg|woff2?|ttf|eot|pdf|zip)$/i', $path)
            || str_contains($path, '/wp-json/')
            || str_ends_with($path, '/wp-login.php')
        ) {
            return null;
        }

        // Build URL from $path only so all query strings are stripped
        $url = esc_url_raw(home_url($path));

        // Ensure final output is a valid HTTP/HTTPS URL
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
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
