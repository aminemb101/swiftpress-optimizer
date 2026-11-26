<?php

namespace SwiftPress\Optimizer;

class CSS_Settings
{
    public static function get_api_url(): string
    {
        $uri = trim((string) get_option(CSS_Admin::OPTION_API_URI, CSS_Admin::DEFAULT_API_URI));

        return $uri !== '' ? $uri : CSS_Admin::DEFAULT_API_URI;
    }

    public static function get_mode(): string
    {
        $critical = (bool) get_option(CSS_Admin::OPTION_CRITICAL, false);
        $minimal  = (bool) get_option(CSS_Admin::OPTION_MINIMAL, false);

        return match (true) {
            $critical && $minimal => 'both',
            $critical             => 'critical',
            $minimal              => 'minimal',
            default               => 'none',
        };
    }

    public static function is_critical_enabled(): bool
    {
        return (bool) get_option(CSS_Admin::OPTION_CRITICAL, false);
    }

    public static function is_minimal_enabled(): bool
    {
        return (bool) get_option(CSS_Admin::OPTION_MINIMAL, false);
    }
}