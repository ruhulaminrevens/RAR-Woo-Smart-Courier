<?php
/**
 * Uninstall: removes plugin options only when "Delete data on uninstall" is enabled.
 * Order meta (consignment IDs, statuses) is NEVER deleted.
 *
 * @package RAR_Courier_Suite
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$rpc_settings = get_option( 'pt_hms_settings', array() );
if ( is_array( $rpc_settings ) && isset( $rpc_settings['delete_data_on_uninstall'] ) && 'yes' === $rpc_settings['delete_data_on_uninstall'] ) {
	foreach ( array( 'pt_hms_settings', 'pt_hms_token_data', 'rpc_version', 'rpc_last_sync_run', 'rpc_last_webhook', 'rpc_last_webhook_sf', 'rpc_installed_from' ) as $rpc_option ) {
		delete_option( $rpc_option );
	}
	delete_transient( 'rpc_stats' );
	delete_transient( 'rpc_suite_summary' );
	delete_transient( 'rpc_redx_areas' );
	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_rpc\\_%' OR option_name LIKE '\\_transient\\_timeout\\_rpc\\_%' OR option_name LIKE 'rpc\\_lock\\_%'" );
	// phpcs:enable
}
wp_clear_scheduled_hook( 'rpc_sync_statuses' );
wp_clear_scheduled_hook( 'rpc_auto_send_order' );
