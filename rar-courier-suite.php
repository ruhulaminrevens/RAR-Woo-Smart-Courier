<?php
/**
 * Plugin Name: RAR Courier Suite
 * Plugin URI:  https://github.com/ruhulaminrevens/RAR-Courier-Suite
 * Description: One professional courier control centre for WooCommerce Bangladesh — Pathao, RedX, SteadFast, Paperfly and Sundarban Courier. Unified dashboard and parcel list, safe single/bulk dispatch, duplicate-send protection, status sync, hardened webhooks, COD / advance-payment awareness and RAR Woo Smart Courier routing. HPOS ready. Independent product; not affiliated with or endorsed by any courier company.
 * Version:     5.1.0
 * Author:      Ruhul Amin
 * Author URI:  https://nabiad.com
 * Text Domain: rar-courier-suite
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 7.9
 * WC tested up to: 10.0
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package RAR_Courier_Suite
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'RPC_VERSION' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>RAR Courier Suite:</strong> RAR Pathao Courier v3 is still active. Deactivate it first (settings and parcel data are reused), then activate RAR Courier Suite.</p></div>';
		}
	);
	return;
}

define( 'RPC_VERSION', '5.1.0' );
define( 'RPC_FILE', __FILE__ );
define( 'RPC_DIR', plugin_dir_path( __FILE__ ) );
define( 'RPC_URL', plugin_dir_url( __FILE__ ) );
define( 'RPC_BASENAME', plugin_basename( __FILE__ ) );

/*
 * Never run next to the original Pathao plugin: it registers the same webhook
 * route and AJAX actions. The original defines PTC_PLUGIN_FILE on load.
 */
if ( defined( 'PTC_PLUGIN_FILE' ) || defined( 'REDX_TRACKING_BASENAME' ) || defined( 'STDF_PLUGIN_FILE' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>RAR Courier Suite:</strong> an original "Pathao Courier", "Redx for WooCommerce" or "SteadFast API" plugin is still active (duplicate buttons/webhook routes). Deactivate those first - existing parcel data is kept and can be imported from Tools, then activate RAR Courier Suite.</p></div>';
		}
	);
	return;
}

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RPC_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', RPC_FILE, true );
		}
	}
);

$rpc_includes = array(
	'class-rpc-settings',
	'class-rpc-logger',
	'class-rpc-lock',
	'class-rpc-status',
	'class-rpc-couriers',
	'class-rpc-courier-base',
	'class-rpc-redx',
	'class-rpc-steadfast',
	'class-rpc-paperfly',
	'class-rpc-sundarban',
	'class-rpc-api',
	'class-rpc-locations',
	'class-rpc-order-data',
	'class-rpc-orders',
	'class-rpc-webhook',
	'class-rpc-cron',
	'class-rpc-ajax',
	'class-rpc-admin-orders',
	'class-rpc-admin-pages',
	'class-rpc-frontend',
	'class-rpc-plugin',
);
foreach ( $rpc_includes as $rpc_file ) {
	require_once RPC_DIR . 'includes/' . $rpc_file . '.php';
}
unset( $rpc_includes, $rpc_file );

register_activation_hook( __FILE__, array( 'RPC_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RPC_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'RPC_Plugin', 'boot' ), 20 );
