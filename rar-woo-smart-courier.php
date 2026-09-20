<?php
/**
 * Plugin Name: RAR Woo Smart Courier
 * Plugin URI:  https://github.com/ruhulaminrevens/RAR-Woo-Smart-Courier
 * Description: Smart multi-courier shipping selector for WooCommerce with configurable zone/weight pricing, ETA-aware recommendation, native free-shipping presentation, custom courier support, and order metadata.
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

require_once RWSC_DIR . 'includes/class-rwsc-plugin.php';

RAR_Woo_Smart_Courier::instance();
