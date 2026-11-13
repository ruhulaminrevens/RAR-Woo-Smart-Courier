<?php
/**
 * Bootstrap / wiring.
 *
 * @package RAR_Courier_Suite
 */

defined( 'ABSPATH' ) || exit;

final class RPC_Plugin {

	public static function boot() {
		load_plugin_textdomain( 'rar-courier-suite', false, dirname( RPC_BASENAME ) . '/languages' );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'RAR Courier Suite requires WooCommerce to be active.', 'rar-courier-suite' ) . '</p></div>';
				}
			);
			return;
		}

		RPC_Webhook::init();
		RPC_SteadFast::init();
		RPC_Cron::init();
		RPC_Frontend::init();
		if ( is_admin() ) {
			RPC_Ajax::init();
			RPC_Admin_Orders::init();
			RPC_Admin_Pages::init();
		}
		add_filter( 'plugin_action_links_' . RPC_BASENAME, array( __CLASS__, 'action_links' ) );
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=pt_hms_settings' ) ) . '">' . esc_html__( 'Settings', 'rar-courier-suite' ) . '</a>' );
		return $links;
	}

	public static function activate() {
		if ( false === get_option( RPC_Settings::OPTION ) ) {
			add_option( RPC_Settings::OPTION, array(), '', false );
		}
		update_option( 'rpc_version', RPC_VERSION, false );
		update_option( 'rpc_installed_from', get_option( 'rpc_installed_from', RPC_VERSION ), false );
		if ( ! wp_next_scheduled( RPC_Cron::HOOK_SYNC ) ) {
			wp_schedule_event( time() + 300, 'rpc_30min', RPC_Cron::HOOK_SYNC );
		}
	}

	public static function deactivate() {
		RPC_Cron::clear();
	}
}
