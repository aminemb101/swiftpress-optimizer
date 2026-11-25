<?php

namespace SwiftPress\Optimizer;

class Core_Admin
{
    public const PAGE_SLUG    = 'swiftpress-optimizer';
    public const OPTION_GROUP = 'swiftpress_optimizer_settings';

    public function init(): void
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu(): void
    {
        add_menu_page(
            __('SwiftPress Optimizer', 'swiftpress-optimizer'),
            __('SwiftPress', 'swiftpress-optimizer'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_settings_page'],
            'dashicons-performance',
            80
        );
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors(); ?>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Save Settings', 'swiftpress-optimizer'));
                ?>
            </form>
        </div>
        <?php
    }
}