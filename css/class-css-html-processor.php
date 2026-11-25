<?php

namespace SwiftPress\Optimizer;

use WP_HTML_Tag_Processor;

class CSS_HTML_Processor
{
    private string $mode;

    /**
     * @param string $mode 'critical' | 'minimal' | 'both'
     */
    public function __construct(string $mode = 'critical')
    {
        $this->mode = in_array($mode, ['critical', 'minimal', 'both'], true) ? $mode : 'critical';
    }

    public function init(): void
    {
        add_action('template_redirect', [$this, 'start_buffer'], 1);
    }

    public function start_buffer(): void
    {
        if (
            is_admin()
            || is_feed()
            || is_preview()
            || is_customize_preview()
            || is_robots()
            || is_trackback()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
        ) {
            return;
        }

        $url = $this->current_url();
        if (!$url || !$this->has_required_cache($url)) {
            return;
        }

        ob_start(fn(string $html): string => $this->process_html($html, $url));
    }

    public function process_html(string $html, string $url): string
    {
        if (trim($html) === '' || stripos($html, '</head>') === false) {
            return $html;
        }

        $critical_path = $this->cache_path($url, 'critical');
        $minimal_path  = $this->cache_path($url, 'minimal');

        if ($this->mode === 'critical') {
            if (!$this->is_valid_cache_file($critical_path)) {
                return $html;
            }

            $critical_css = file_get_contents($critical_path);
            if ($critical_css === false || trim($critical_css) === '') {
                return $html;
            }

            $html = $this->make_stylesheets_async($html);
            return $this->inject_into_head($html, '<style id="swiftpress-critical-css">' . $critical_css . '</style>', true);
        }

        if ($this->mode === 'minimal') {
            if (!$this->is_valid_cache_file($minimal_path)) {
                return $html;
            }

            $html = $this->remove_all_css($html);
            $tag  = sprintf(
                '<link rel="stylesheet" id="swiftpress-minimal-css" href="%s">',
                esc_url($this->cache_url($minimal_path))
            );
            return $this->inject_into_head($html, $tag, false);
        }

        if ($this->mode === 'both') {
            if (!$this->is_valid_cache_file($critical_path) || !$this->is_valid_cache_file($minimal_path)) {
                return $html;
            }

            $critical_css = file_get_contents($critical_path);
            if ($critical_css === false || trim($critical_css) === '') {
                return $html;
            }

            $html = $this->remove_all_css($html);
            $href = esc_url($this->cache_url($minimal_path));

            $html = $this->inject_into_head(
                $html,
                '<style id="swiftpress-critical-css">' . $critical_css . '</style>',
                true
            );

            $async_minimal = sprintf(
                '<link rel="stylesheet" id="swiftpress-minimal-css" href="%1$s" media="print" onload="this.onload=null;this.media=\'all\'"><noscript><link rel="stylesheet" href="%1$s"></noscript>',
                $href
            );

            return $this->inject_into_head($html, $async_minimal, false);
        }

        return $html;
    }

    /**
     * Uses WordPress's native WP_HTML_Tag_Processor to rewrite stylesheet attributes safely.
     */
    private function make_stylesheets_async(string $html): string
    {
        if (!class_exists(WP_HTML_Tag_Processor::class)) {
            return $html;
        }

        $tags = new WP_HTML_Tag_Processor($html);

        while ($tags->next_tag(['tag_name' => 'LINK'])) {
            $rel = strtolower((string) $tags->get_attribute('rel'));
            if ($rel !== 'stylesheet') {
                continue;
            }

            $href = (string) $tags->get_attribute('href');
            if ($href === '') {
                continue;
            }

            $original_media = (string) $tags->get_attribute('media');
            $target_media   = ($original_media !== '' && $original_media !== 'print') ? $original_media : 'all';

            $tags->set_attribute('media', 'print');
            $tags->set_attribute('onload', "this.onload=null;this.media='" . esc_attr($target_media) . "'");
        }

        return $tags->get_updated_html();
    }

    /**
     * Strips all <link rel="stylesheet"> and <style> blocks for minimal / both modes.
     */
    private function remove_all_css(string $html): string
    {
        // Remove <link ... rel="stylesheet" ...> (attribute order agnostic)
        $html = preg_replace(
            '/<link\b(?=[^>]*\brel\s*=\s*["\']?stylesheet["\']?)[^>]*>/i',
            '',
            $html
        ) ?? $html;

        // Remove all <style>...</style> blocks
        $html = preg_replace(
            '/<style\b[^>]*>.*?<\/style>/is',
            '',
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Injects markup right after <head> ($prepend = true) or right before </head> ($prepend = false).
     */
    private function inject_into_head(string $html, string $snippet, bool $prepend): string
    {
        if ($prepend) {
            return preg_replace('/(<head\b[^>]*>)/i', '$1' . "\n" . $snippet, $html, 1) ?? $html;
        }

        return preg_replace('/(<\/head>)/i', $snippet . "\n" . '$1', $html, 1) ?? $html;
    }

    private function is_valid_cache_file(string $path): bool
    {
        return is_readable($path) && filesize($path) > 0;
    }

    private function has_required_cache(string $url): bool
    {
        return match ($this->mode) {
            'critical' => $this->is_valid_cache_file($this->cache_path($url, 'critical')),
            'minimal'  => $this->is_valid_cache_file($this->cache_path($url, 'minimal')),
            'both'     => $this->is_valid_cache_file($this->cache_path($url, 'critical'))
                && $this->is_valid_cache_file($this->cache_path($url, 'minimal')),
        };
    }

    private function cache_url(string $file_path): string
    {
        return content_url('cache/wpx-css/' . basename($file_path));
    }

    private function cache_path(string $url, string $strategy = 'critical'): string
    {
        $cache_dir = WP_CONTENT_DIR . '/cache/wpx-css';

        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }

        $hash = md5($url . '|' . $strategy);
        return $cache_dir . '/' . $hash . '.css';
    }

    private function current_url(): ?string
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($method) !== 'GET') {
            return null;
        }

        $url = home_url(wp_unslash($_SERVER['REQUEST_URI']));
        $url = remove_query_arg(['preview', 'preview_id', 'preview_nonce'], $url);
        $url = esc_url_raw($url);

        return (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) ? $url : null;
    }
}