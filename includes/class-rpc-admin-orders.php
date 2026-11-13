<?php
/**
 * WooCommerce order screens (HPOS and legacy): list column, bulk actions,
 * order meta box, and admin asset loading.
 *
 * @package RAR_Courier_Suite
 */

defined( 'ABSPATH' ) || exit;

final class RPC_Admin_Orders {

	public static function init() {
		// Columns: HPOS + legacy.
		add_filter( 'woocommerce_shop_order_list_table_columns', array( __CLASS__, 'columns' ) );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'columns' ) );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( __CLASS__, 'column_hpos' ), 10, 2 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'column_legacy' ), 10, 2 );

		// Bulk actions: HPOS + legacy.
		foreach ( array( 'woocommerce_page_wc-orders', 'edit-shop_order' ) as $screen ) {
			add_filter( 'bulk_actions-' . $screen, array( __CLASS__, 'bulk_actions' ) );
			add_filter( 'handle_bulk_actions-' . $screen, array( __CLASS__, 'handle_bulk' ), 10, 3 );
		}

		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'admin_notices', array( __CLASS__, 'bulk_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function columns( $columns ) {
		$columns['rpc_courier'] = __( 'Courier', 'rar-courier-suite' );
		return $columns;
	}

	public static function column_hpos( $column, $order ) {
		// rpc_pathao / rpc_couriers are the pre-v5 column ids; they render the same unified cell.
		if ( in_array( $column, array( 'rpc_courier', 'rpc_pathao', 'rpc_couriers' ), true ) && $order instanceof WC_Order ) {
			echo RPC_Couriers::cell_wrap( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	public static function column_legacy( $column, $post_id ) {
		if ( in_array( $column, array( 'rpc_courier', 'rpc_pathao', 'rpc_couriers' ), true ) ) {
			$order = wc_get_order( $post_id );
			if ( $order ) {
				self::column_hpos( $column, $order );
			}
		}
	}

	public static function bulk_actions( $actions ) {
		if ( current_user_can( RPC_Ajax::operator_cap() ) ) {
			if ( RPC_Settings::is_configured() ) {
				$actions['rpc_send']    = __( 'Send with Pathao', 'rar-courier-suite' );
				$actions['rpc_refresh'] = __( 'Refresh Pathao status', 'rar-courier-suite' );
			}
			foreach ( RPC_Couriers::modules() as $slug => $m ) {
				if ( $m->enabled() && ! $m->manual() ) {
					/* translators: %s: courier */
					$actions[ 'rpc_send_' . $slug ]    = sprintf( __( 'Send with %s', 'rar-courier-suite' ), $m->label() );
					/* translators: %s: courier */
					$actions[ 'rpc_refresh_' . $slug ] = sprintf( __( 'Refresh %s status', 'rar-courier-suite' ), $m->label() );
				}
			}
		}
		return $actions;
	}

	/**
	 * The modal does the work in the browser; we only hand it the selection.
	 */
	public static function handle_bulk( $redirect_to, $action, $ids ) {
		if ( preg_match( '/^rpc_(send|refresh)_([a-z]+)$/', (string) $action, $mm ) && RPC_Couriers::get( $mm[2] ) && ! RPC_Couriers::get( $mm[2] )->manual() && current_user_can( RPC_Ajax::operator_cap() ) ) {
			$res = RPC_Couriers::bulk( $mm[2], $mm[1], (array) $ids, get_current_user_id() );
			return add_query_arg(
				'rpc_bulk',
				rawurlencode( implode( '|', array( $mm[2], $mm[1], $res['ok'], $res['fail'], $res['first_error'] ) ) ),
				$redirect_to
			);
		}
		if ( ! in_array( $action, array( 'rpc_send', 'rpc_refresh' ), true ) || ! current_user_can( RPC_Ajax::operator_cap() ) ) {
			return $redirect_to;
		}
		return add_query_arg(
			array(
				'rpc_do'  => 'rpc_send' === $action ? 'send' : 'refresh',
				'rpc_ids' => implode( ',', array_map( 'absint', (array) $ids ) ),
			),
			$redirect_to
		);
	}

	public static function meta_box() {
		$screen = 'shop_order';
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screen = wc_get_page_screen_id( 'shop-order' );
		}
		add_meta_box( 'rpc-courier-suite', __( 'Courier Suite', 'rar-courier-suite' ), array( __CLASS__, 'render_box' ), $screen, 'side', 'high' );
	}

	public static function render_suite_box( $post_or_order ) {
		$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		echo RPC_Couriers::cell_wrap( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public static function bulk_notice() {
		if ( empty( $_GET['rpc_bulk'] ) || ! current_user_can( RPC_Ajax::operator_cap() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$p = explode( '|', sanitize_text_field( rawurldecode( wp_unslash( $_GET['rpc_bulk'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( count( $p ) < 4 ) {
			return;
		}
		$ok  = (int) $p[2];
		$bad = (int) $p[3];
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			$bad ? 'notice-warning' : 'notice-success',
			esc_html(
				sprintf(
					/* translators: 1: courier 2: action 3: ok 4: failed 5: first error */
					__( '%1$s %2$s: %3$d done, %4$d failed. %5$s', 'rar-courier-suite' ),
					RPC_Couriers::label( $p[0] ),
					'send' === $p[1] ? __( 'send', 'rar-courier-suite' ) : __( 'refresh', 'rar-courier-suite' ),
					$ok,
					$bad,
					isset( $p[4] ) ? $p[4] : ''
				)
			)
		);
	}

	public static function render_box( $post_or_order ) {
		$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		echo '<div class="rpc-box">' . RPC_Couriers::cell_wrap( $order ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

		$chose = RPC_Order_Data::selected_courier( $order );
		if ( '' !== $chose ) {
			echo '<p class="rpc-muted">' . esc_html( sprintf( /* translators: %s: courier */ __( 'Checkout courier: %s', 'rar-courier-suite' ), $chose ) ) . '</p>';
		}
		$via = RPC_Couriers::sent_via( $order );
		if ( 'pathao' === $via && RPC_Orders::has_real_consignment( $order ) ) {
			echo '<p><button type="button" class="button rpc-refresh" data-order-id="' . esc_attr( $order->get_id() ) . '">' . esc_html__( 'Refresh status', 'rar-courier-suite' ) . '</button> ';
			$track = RPC_Orders::tracking_url( $order );
			if ( $track ) {
				echo '<a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $track ) . '">' . esc_html__( 'Track', 'rar-courier-suite' ) . '</a>';
			}
			echo '</p>';
		}
		$items = array();
		if ( 'pathao' === $via ) {
			foreach ( array_reverse( RPC_Orders::events( $order ) ) as $e ) {
				$items[] = array( RPC_Status::info( $e['s'] )['label'], $e );
			}
		} elseif ( '' !== $via ) {
			$m = RPC_Couriers::get( $via );
			$u = $m->tracking_url( $order );
			if ( $u ) {
				echo '<p><a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $u ) . '">' . esc_html__( 'Track', 'rar-courier-suite' ) . '</a></p>';
			}
			foreach ( array_reverse( (array) $m->events( $order ) ) as $e ) {
				$items[] = array( $m->normalise( isset( $e['s'] ) ? $e['s'] : '' )['label'], $e );
			}
		}
		if ( $items ) {
			echo '<ul class="rpc-timeline">';
			foreach ( array_slice( $items, 0, 12 ) as $it ) {
				$e = $it[1];
				echo '<li><strong>' . esc_html( $it[0] ) . '</strong> <span class="rpc-muted">' . esc_html( wp_date( 'd M, h:i a', (int) ( isset( $e['t'] ) ? $e['t'] : 0 ) ) . ' · ' . ( isset( $e['src'] ) ? $e['src'] : '' ) . ( ! empty( $e['r'] ) ? ' · ' . $e['r'] : '' ) ) . '</span></li>';
			}
			echo '</ul>';
		}
	}

	public static function is_plugin_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = $screen ? (string) $screen->id : '';
		if ( in_array( $id, array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order' ), true ) ) {
			return true;
		}
		return false !== strpos( $id, 'pt_hms' ) || false !== strpos( $id, 'rpc-' );
	}

	public static function assets() {
		if ( ! self::is_plugin_screen() || ! current_user_can( RPC_Ajax::operator_cap() ) ) {
			return;
		}
		wp_enqueue_style( 'rpc-admin', RPC_URL . 'assets/css/rpc-admin.css', array(), RPC_VERSION );
		wp_enqueue_script( 'rpc-admin', RPC_URL . 'assets/js/rpc-admin.js', array(), RPC_VERSION, true );

		$do  = isset( $_GET['rpc_do'] ) ? sanitize_key( wp_unslash( $_GET['rpc_do'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$ids = isset( $_GET['rpc_ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['rpc_ids'] ) ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification

		wp_localize_script(
			'rpc-admin',
			'RPC',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( RPC_Ajax::NONCE ),
				'canSettings'   => current_user_can( 'manage_options' ),
				'autoDo'        => in_array( $do, array( 'send', 'refresh' ), true ) ? $do : '',
				'autoIds'       => array_values( $ids ),
				'deliveryTypes' => array(
					48 => __( 'Normal delivery', 'rar-courier-suite' ),
					12 => __( 'On-demand', 'rar-courier-suite' ),
				),
				'itemTypes'     => array(
					2 => __( 'Parcel', 'rar-courier-suite' ),
					1 => __( 'Document', 'rar-courier-suite' ),
				),
				'i18n'          => array(
					'title'      => __( 'Send to Pathao', 'rar-courier-suite' ),
					'loading'    => __( 'Loading orders…', 'rar-courier-suite' ),
					'sendAll'    => __( 'Send %d order(s)', 'rar-courier-suite' ),
					'cancel'     => __( 'Close', 'rar-courier-suite' ),
					'sending'    => __( 'Sending…', 'rar-courier-suite' ),
					'sent'       => __( 'Sent', 'rar-courier-suite' ),
					'skipped'    => __( 'Skipped', 'rar-courier-suite' ),
					'failed'     => __( 'Failed', 'rar-courier-suite' ),
					'estimate'   => __( 'Estimated fee', 'rar-courier-suite' ),
					'suggested'  => __( 'Suggested', 'rar-courier-suite' ),
					'apply'      => __( 'Apply', 'rar-courier-suite' ),
					'refreshing' => __( 'Refreshing…', 'rar-courier-suite' ),
					'done'       => __( 'Done', 'rar-courier-suite' ),
					'enterCn'     => __( 'Enter the Sundarban CN (consignment note) number for order #%s', 'rar-courier-suite' ),
					'confirmSend' => __( 'Send order #%1$s with %2$s?', 'rar-courier-suite' ),
					'confirmBulk' => __( 'Send %1$s order(s) with %2$s?', 'rar-courier-suite' ),
					'netError'   => __( 'Network error. If a parcel was being created, check the courier panel before retrying.', 'rar-courier-suite' ),
				),
			)
		);
	}
}
