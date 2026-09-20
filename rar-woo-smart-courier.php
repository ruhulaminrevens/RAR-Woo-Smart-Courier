<?php
/**
 * Plugin Name: RAR Woo Smart Courier
 * Plugin URI:  https://github.com/ruhulaminrevens/RAR-Woo-Smart-Courier
 * Description: Smart multi-courier shipping selector for WooCommerce with dynamic zone/weight pricing, ETA-aware recommendation, free-shipping strike-through pricing, custom courier support, and order metadata.
 * Version:     1.3.0
 * Author:      Ruhul Amin
 * Author URI:  https://github.com/ruhulaminrevens
 * Text Domain: rar-woo-smart-courier
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RWSC_VERSION', '1.3.0' );
define( 'RWSC_FILE', __FILE__ );
define( 'RWSC_DIR', plugin_dir_path( __FILE__ ) );
define( 'RWSC_URL', plugin_dir_url( __FILE__ ) );

require_once RWSC_DIR . 'includes/class-rwsc-engine.php';
require_once RWSC_DIR . 'includes/class-rwsc-admin.php';

register_activation_hook( __FILE__, array( 'RWSC_Engine', 'activate' ) );

add_action(
    'before_woocommerce_init',
    static function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
        }
    }
);

add_action(
    'plugins_loaded',
    static function () {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action(
                'admin_notices',
                static function () {
                    if ( current_user_can( 'activate_plugins' ) ) {
                        echo '<div class="notice notice-error"><p><strong>RAR Woo Smart Courier:</strong> WooCommerce must be active.</p></div>';
                    }
                }
            );
            return;
        }

        RWSC_Engine::instance();
        if ( is_admin() ) {
            RWSC_Admin::instance();
        }
    },
    20
);
