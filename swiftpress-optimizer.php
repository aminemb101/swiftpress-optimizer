<?php
/**
 * Plugin Name: SwiftPress Optimizer
 * Description: Modular performance optimizations for WordPress.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action_scheduler = __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
if ( is_readable( $action_scheduler ) ) {
	require_once $action_scheduler;
}

require_once __DIR__ . '/css/class-css-cache.php';
require_once __DIR__ . '/css/class-css-callback-controller.php';
require_once __DIR__ . '/css/class-css-html-processor.php';
require_once __DIR__ . '/css/class-css-admin.php';
require_once __DIR__ . '/core/class-admin.php';
add_action(
	'plugins_loaded',
	static function () {
        if (is_admin()) {
            (new \SwiftPress\Optimizer\Core_Admin())->init();
            (new \SwiftPress\Optimizer\CSS_Admin())->init();
        }
		new \SwiftPress\Optimizer\CSS_Cache();
        (new \SwiftPress\Optimizer\CSS_Callback_Controller())->register();
        (new \SwiftPress\Optimizer\CSS_HTML_Processor())->init();
	},
	20
);
