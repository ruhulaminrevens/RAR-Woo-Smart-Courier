<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class RWSC_Admin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ), 60 );
        add_action( 'admin_post_rwsc_save', array( $this, 'save' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( RWSC_FILE ), array( $this, 'action_links' ) );
    }

    public function action_links( $links ) {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=rar-woo-smart-courier' ) ) . '">Settings</a>' );
        return $links;
    }

    public function menu() {
        add_submenu_page(
            'woocommerce',
            'RAR Woo Smart Courier',
            'Smart Courier',
            'manage_woocommerce',
            'rar-woo-smart-courier',
            array( $this, 'page' )
        );
        // Keep the previous Nabiad URL working after upgrade/bookmarks.
        add_submenu_page(
            null,
            'RAR Woo Smart Courier',
            'RAR Woo Smart Courier',
            'manage_woocommerce',
            'nabiad-smart-courier',
            array( $this, 'page' )
        );
    }

    public function save() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied.' );
        }
        check_admin_referer( 'rwsc_save_settings' );

        $old = RWSC_Engine::settings();
        $posted = isset( $_POST['rwsc'] ) && is_array( $_POST['rwsc'] ) ? wp_unslash( $_POST['rwsc'] ) : array();

        $new = $old;
        $new['enabled'] = isset( $posted['enabled'] ) ? 'yes' : 'no';
        $new['test_mode'] = isset( $posted['test_mode'] ) ? 'yes' : 'no';
        $new['show_rec'] = isset( $posted['show_rec'] ) ? 'yes' : 'no';
        $new['recommendation'] = in_array( ( $posted['recommendation'] ?? 'smart' ), array( 'smart', 'priority' ), true ) ? $posted['recommendation'] : 'smart';
        $new['fallback_weight'] = max( 0.1, (float) ( $posted['fallback_weight'] ?? 1 ) );

        $fixed_names = array(
            'pathao' => 'Pathao', 'paperfly' => 'Paperfly', 'steadfast' => 'Steadfast', 'redx' => 'Redx', 'sundarban' => 'Sundarban',
        );
        $numeric_fields = array( 'priority','dhaka_1','dhaka_2','dhaka_extra','nearby_1','nearby_2','nearby_extra','outside_1','outside_2','outside_extra' );
        $eta_fields = array( 'eta_dhaka','eta_nearby','eta_outside' );

        foreach ( $new['couriers'] as $key => &$courier ) {
            $row = isset( $posted['couriers'][ $key ] ) && is_array( $posted['couriers'][ $key ] ) ? $posted['couriers'][ $key ] : array();
            $courier['enabled'] = isset( $row['enabled'] ) ? 'yes' : 'no';
            $courier['name'] = isset( $fixed_names[ $key ] ) ? $fixed_names[ $key ] : sanitize_text_field( (string) ( $row['name'] ?? '' ) );
            foreach ( $numeric_fields as $field ) {
                if ( 'priority' === $field ) {
                    $courier[ $field ] = max( 1, (int) ( $row[ $field ] ?? 100 ) );
                } else {
                    $courier[ $field ] = max( 0, (float) ( $row[ $field ] ?? 0 ) );
                }
            }
            foreach ( $eta_fields as $field ) {
                $courier[ $field ] = sanitize_text_field( (string) ( $row[ $field ] ?? '' ) );
            }
        }
        unset( $courier );

        update_option( RWSC_Engine::OPTION_KEY, $new, false );
        update_option( RWSC_Engine::VERSION_KEY, RWSC_VERSION, false );
        wc_delete_shop_order_transients();

        wp_safe_redirect( add_query_arg( array( 'page' => 'rar-woo-smart-courier', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        $s = RWSC_Engine::settings();
        $labels = array(
            'pathao' => 'Pathao', 'paperfly' => 'Paperfly', 'steadfast' => 'Steadfast', 'redx' => 'Redx', 'sundarban' => 'Sundarban', 'custom1' => 'Custom Courier',
        );
        ?>
        <div class="wrap rwsc-admin">
            <h1>RAR Woo Smart Courier <small>v<?php echo esc_html( RWSC_VERSION ); ?> Final</small></h1>
            <p class="description">Dynamic courier pricing for WooCommerce. Smart recommendation = faster ETA → lower rate → Priority. Lower Priority number wins only when ETA and rate are tied.</p>
            <?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Courier settings saved.</p></div><?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="rwsc_save">
                <?php wp_nonce_field( 'rwsc_save_settings' ); ?>

                <table class="form-table" role="presentation">
                    <tr><th>Engine</th><td><label><input type="checkbox" name="rwsc[enabled]" <?php checked( 'yes', $s['enabled'] ); ?>> Enabled</label></td></tr>
                    <tr><th>Safe Test Mode</th><td><label><input type="checkbox" name="rwsc[test_mode]" <?php checked( 'yes', $s['test_mode'] ); ?>> Admins only</label><p class="description">Keep ON while testing. Turn OFF to make Smart Courier public.</p></td></tr>
                    <tr><th>Fallback parcel weight</th><td><input type="number" min="0.1" step="0.1" name="rwsc[fallback_weight]" value="<?php echo esc_attr( $s['fallback_weight'] ); ?>" style="width:100px"> kg <p class="description">Used only when cart products do not have usable weights.</p></td></tr>
                    <tr><th>Recommendation</th><td>
                        <select name="rwsc[recommendation]">
                            <option value="smart" <?php selected( 'smart', $s['recommendation'] ); ?>>Smart: ETA → rate → priority</option>
                            <option value="priority" <?php selected( 'priority', $s['recommendation'] ); ?>>Manual: priority → rate</option>
                        </select>
                        <label style="margin-left:14px"><input type="checkbox" name="rwsc[show_rec]" <?php checked( 'yes', $s['show_rec'] ); ?>> Show “· Rec” at end</label>
                    </td></tr>
                </table>

                <h2>Courier pricing, ETA & tie priority</h2>
                <p>First five names are standardized for storefront display. The sixth row is an optional custom courier: enter a name, rates/ETA, then enable it.</p>
                <div style="overflow:auto;max-width:100%">
                <table class="widefat striped rwsc-grid" style="min-width:1450px">
                    <thead><tr>
                        <th>On</th><th>Courier</th><th>Priority</th>
                        <th>Dhaka ≤1kg</th><th>Dhaka ≤2kg</th><th>Extra/kg</th>
                        <th>Nearby ≤1kg</th><th>Nearby ≤2kg</th><th>Extra/kg</th>
                        <th>Outside ≤1kg</th><th>Outside ≤2kg</th><th>Extra/kg</th>
                        <th>ETA Dhaka</th><th>ETA Nearby</th><th>ETA Outside</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $s['couriers'] as $key => $c ) : ?>
                        <tr>
                            <td><input type="checkbox" name="rwsc[couriers][<?php echo esc_attr( $key ); ?>][enabled]" <?php checked( 'yes', $c['enabled'] ); ?>></td>
                            <td>
                                <?php if ( 'custom1' === $key ) : ?>
                                    <input type="text" name="rwsc[couriers][<?php echo esc_attr( $key ); ?>][name]" value="<?php echo esc_attr( $c['name'] ); ?>" placeholder="Custom Courier" style="width:130px">
                                <?php else : ?>
                                    <strong><?php echo esc_html( $labels[ $key ] ); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td><input type="number" min="1" step="1" name="rwsc[couriers][<?php echo esc_attr( $key ); ?>][priority]" value="<?php echo esc_attr( $c['priority'] ); ?>" style="width:65px"></td>
                            <?php foreach ( array('dhaka_1','dhaka_2','dhaka_extra','nearby_1','nearby_2','nearby_extra','outside_1','outside_2','outside_extra') as $f ) : ?>
                                <td><input type="number" min="0" step="0.01" name="rwsc[couriers][<?php echo esc_attr( $key ); ?>][<?php echo esc_attr( $f ); ?>]" value="<?php echo esc_attr( $c[ $f ] ); ?>" style="width:78px"></td>
                            <?php endforeach; ?>
                            <?php foreach ( array('eta_dhaka','eta_nearby','eta_outside') as $f ) : ?>
                                <td><input type="text" name="rwsc[couriers][<?php echo esc_attr( $key ); ?>][<?php echo esc_attr( $f ); ?>]" value="<?php echo esc_attr( $c[ $f ] ); ?>" placeholder="1–3 business days" style="width:145px"></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <p class="submit"><button type="submit" class="button button-primary">Save Courier Settings</button></p>
            </form>

            <hr>
            <h2>How it works</h2>
            <p><strong>Zones:</strong> Dhaka district → Dhaka; Gazipur/Narayanganj → Nearby; all other Bangladesh districts → Outside. City is used only as a fallback when District/State is missing.</p>
            <p><strong>Free Shipping:</strong> when native WooCommerce Free Shipping is available, every enabled courier stays selectable at ৳0 and the normal courier amount is shown with strike-through. No duplicate “Free Delivery” text is added.</p>
            <p><strong>Compatibility:</strong> generated choices retain the matched WooCommerce Flat Rate method/instance identity. If no compatible Flat Rate exists, original WooCommerce rates remain untouched.</p>
        </div>
        <style>
            .rwsc-admin h1 small{font-size:13px;color:#666}.rwsc-grid th{white-space:nowrap}.rwsc-grid td{vertical-align:middle}.rwsc-grid input[type=number],.rwsc-grid input[type=text]{max-width:100%}
        </style>
        <?php
    }
}
