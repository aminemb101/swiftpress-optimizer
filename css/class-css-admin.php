<?php

        namespace SwiftPress\Optimizer;

        class CSS_Admin
        {
            public const SECTION_ID       = 'swiftpress_css_section';
            public const OPTION_CRITICAL  = 'swiftpress_css_critical';
            public const OPTION_MINIMAL   = 'swiftpress_css_minimal';
            public const OPTION_API_URI   = 'swiftpress_css_api_uri';
            public const DEFAULT_API_URI  = 'http://localhost:3000/api/css/jobs';

            public function init(): void
            {
                add_action('admin_init', [$this, 'register_settings']);
            }

            public function register_settings(): void
            {
                register_setting(Core_Admin::OPTION_GROUP, self::OPTION_API_URI, [
                        'type'              => 'string',
                        'sanitize_callback' => [$this, 'sanitize_api_uri'],
                        'default'           => self::DEFAULT_API_URI,
                ]);

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
                        self::OPTION_API_URI,
                        __('CSS Service API URI', 'swiftpress-optimizer'),
                        [$this, 'render_api_uri_field'],
                        Core_Admin::PAGE_SLUG,
                        self::SECTION_ID
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

            public function sanitize_api_uri(mixed $value): string
            {
                $url = trim((string) $value);
                if ($url === '') {
                    return self::DEFAULT_API_URI;
                }

                $sanitized = esc_url_raw($url, ['http', 'https']);
                return filter_var($sanitized, FILTER_VALIDATE_URL) ? $sanitized : self::DEFAULT_API_URI;
            }

            public function render_section_description(): void
            {
                echo '<p>' . esc_html__('Configure the external CSS generator endpoint and how stylesheets are delivered on the frontend.', 'swiftpress-optimizer') . '</p>';
            }

            public function render_api_uri_field(): void
            {
                $api_uri = self::get_api_uri();
                ?>
                <input
                        type="url"
                        id="<?php echo esc_attr(self::OPTION_API_URI); ?>"
                        name="<?php echo esc_attr(self::OPTION_API_URI); ?>"
                        value="<?php echo esc_attr($api_uri); ?>"
                        class="regular-text code"
                        placeholder="<?php echo esc_attr(self::DEFAULT_API_URI); ?>"
                />
                <p class="description">
                    <?php esc_html_e('Endpoint URL for the Node CSS generator (e.g. your Cloudflare Tunnel URL or http://localhost:3000/api/css/jobs).', 'swiftpress-optimizer'); ?>
                </p>
                <?php
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

            public static function get_api_uri(): string
            {
                $uri = (string) get_option(self::OPTION_API_URI, self::DEFAULT_API_URI);
                return $uri !== '' ? $uri : self::DEFAULT_API_URI;
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