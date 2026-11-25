<?php

namespace SwiftPress\Optimizer;

class CSS_Admin
{
    public const SECTION_ID       = 'swiftpress_css_section';
    public const OPTION_CRITICAL  = 'swiftpress_css_critical';
    public const OPTION_MINIMAL   = 'swiftpress_css_minimal';

    public function init(): void
    {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings(): void
    {
        register_setting(Core_Admin::OPTION_GROUP, self::OPTION_CRITICAL, [
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ]);

        register_setting(Core_Admin::OPTION_GROUP, self::OPTION_MINIMAL, [
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ]);

        add_settings_section(
            self::SECTION_ID,
            __('CSS Optimization', 'swiftpress-optimizer'),
            [$this, 'render_section_description'],
            Core_Admin::PAGE_SLUG
        );

        add_settings_field(
            self::OPTION_CRITICAL,
            __('Critical CSS', 'swiftpress-optimizer'),
            [$this, 'render_critical_toggle'],
            Core_Admin::PAGE_SLUG,
            self::SECTION_ID
        );

        add_settings_field(
            self::OPTION_MINIMAL,
            __('Minimal CSS', 'swiftpress-optimizer'),
            [$this, 'render_minimal_toggle'],
            Core_Admin::PAGE_SLUG,
            self::SECTION_ID
        );
    }

    public function render_section_description(): void
    {
        echo '<p>' . esc_html__('Configure how stylesheets are generated and delivered on the frontend.', 'swiftpress-optimizer') . '</p>';
    }

    public function render_critical_toggle(): void
    {
        $enabled = (bool) get_option(self::OPTION_CRITICAL, false);
        ?>
        <label for="<?php echo esc_attr(self::OPTION_CRITICAL); ?>">
            <input
                type="checkbox"
                id="<?php echo esc_attr(self::OPTION_CRITICAL); ?>"
                name="<?php echo esc_attr(self::OPTION_CRITICAL); ?>"
                value="1"
                <?php checked($enabled); ?>
            />
            <?php esc_html_e('Inline above-the-fold Critical CSS and load remaining stylesheets asynchronously.', 'swiftpress-optimizer'); ?>
        </label>
        <?php
    }

    public function render_minimal_toggle(): void
    {
        $enabled = (bool) get_option(self::OPTION_MINIMAL, false);
        ?>
        <label for="<?php echo esc_attr(self::OPTION_MINIMAL); ?>">
            <input
                type="checkbox"
                id="<?php echo esc_attr(self::OPTION_MINIMAL); ?>"
                name="<?php echo esc_attr(self::OPTION_MINIMAL); ?>"
                value="1"
                <?php checked($enabled); ?>
            />
            <?php esc_html_e('Strip unused CSS and replace stylesheets with a minimal page stylesheet.', 'swiftpress-optimizer'); ?>
        </label>
        <?php
    }

    /**
     * Helper to resolve the active processor mode ('critical' | 'minimal' | 'both' | null).
     */
    public static function get_active_mode(): ?string
    {
        $critical = (bool) get_option(self::OPTION_CRITICAL, false);
        $minimal  = (bool) get_option(self::OPTION_MINIMAL, false);

        return match (true) {
            $critical && $minimal => 'both',
            $critical             => 'critical',
            $minimal              => 'minimal',
            default               => null,
        };
    }
}