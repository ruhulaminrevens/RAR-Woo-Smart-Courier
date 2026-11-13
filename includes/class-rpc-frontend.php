<?php
/**
 * Customer-facing tracking: My Account / thank-you order details and emails.
 *
 * @package RAR_Courier_Suite
 */

defined( 'ABSPATH' ) || exit;

final class RPC_Frontend {

	public static function init() {
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'order_details' ), 20 );
		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'email' ), 20, 4 );
	}

	private static function block( WC_Order $order ) {
		$rows = RPC_Couriers::rows( $order );
		if ( ! $rows ) {
			return '';
		}
		$out = '<h2>' . esc_html__( 'Courier tracking', 'rar-courier-suite' ) . '</h2>';
		foreach ( $rows as $r ) {
			$out .= '<p>' . esc_html__( 'Courier:', 'rar-courier-suite' ) . ' <strong>' . esc_html( $r['courier'] ) . '</strong><br>'
				. esc_html__( 'Consignment ID:', 'rar-courier-suite' ) . ' <strong>' . esc_html( $r['id'] ) . '</strong><br>'
				. esc_html__( 'Status:', 'rar-courier-suite' ) . ' <strong>' . esc_html( $r['status'] ) . '</strong>';
			if ( ! empty( $r['url'] ) ) {
				$out .= '<br><a href="' . esc_url( $r['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Track your parcel', 'rar-courier-suite' ) . '</a>';
			}
			$out .= '</p>';
		}
		return $out;
	}

	public static function order_details( $order ) {
		if ( ! RPC_Settings::yes( 'show_my_account' ) || ! $order instanceof WC_Order ) {
			return;
		}
		echo '<section class="rpc-tracking">' . self::block( $order ) . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public static function email( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $sent_to_admin || ! RPC_Settings::yes( 'show_in_emails' ) || ! $order instanceof WC_Order ) {
			return;
		}
		if ( $plain_text ) {
			foreach ( RPC_Couriers::rows( $order ) as $r ) {
				echo "\n" . esc_html( sprintf( '%s consignment: %s', $r['courier'], $r['id'] ) ) . "\n" . ( ! empty( $r['url'] ) ? esc_url( $r['url'] ) . "\n" : '' );
			}
			return;
		}
		echo self::block( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
