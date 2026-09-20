<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class RAR_Woo_Smart_Courier {
    const VERSION = '1.3.0';
    const OPTION_KEY = 'rwsc_settings';
    const PREFIX = 'rwsc_';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'boot' ) );
    }

    public function boot() {
        if ( ! class_exists( 'WooCommerce' ) ) { return; }

        add_filter( 'woocommerce_package_rates', array( $this, 'filter_rates' ), 100, 2 );
        add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_label' ), 100, 2 );
        add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'store_shipping_item_meta' ), 10, 4 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'store_order_meta' ), 10, 2 );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'admin_order_courier' ) );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'order_list_columns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'order_list_column_content' ), 10, 2 );

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'save_settings' ) );
        add_action( 'wp_head', array( $this, 'frontend_css' ), 99 );
    }

    private function defaults() {
        return array(
            'enabled' => 'yes',
            'test_mode' => 'yes',
            'fallback_weight' => 1,
            'nearby_districts' => array( 'Gazipur', 'Narayanganj' ),
            'nearby_cities' => array( 'Savar', 'Ashulia', 'Keraniganj', 'Dohar', 'Dhamrai', 'Nawabganj' ),
            'couriers' => array(
                'pathao' => array(
                    'enabled'=>'yes','name'=>'Pathao','priority'=>10,
                    'dhaka_1'=>70,'dhaka_2'=>90,'dhaka_extra'=>15,
                    'nearby_1'=>100,'nearby_2'=>130,'nearby_extra'=>25,
                    'outside_1'=>130,'outside_2'=>170,'outside_extra'=>25,
                    'eta_dhaka'=>'1–2 business days','eta_nearby'=>'1–3 business days','eta_outside'=>'2–3 business days'
                ),
                'paperfly' => array(
                    'enabled'=>'yes','name'=>'Paperfly','priority'=>20,
                    'dhaka_1'=>70,'dhaka_2'=>90,'dhaka_extra'=>20,
                    'nearby_1'=>110,'nearby_2'=>130,'nearby_extra'=>20,
                    'outside_1'=>130,'outside_2'=>150,'outside_extra'=>20,
                    'eta_dhaka'=>'1–2 business days','eta_nearby'=>'1–3 business days','eta_outside'=>'1–3 business days'
                ),
                'steadfast' => array(
                    'enabled'=>'yes','name'=>'Steadfast','priority'=>30,
                    'dhaka_1'=>70,'dhaka_2'=>90,'dhaka_extra'=>20,
                    'nearby_1'=>100,'nearby_2'=>130,'nearby_extra'=>30,
                    'outside_1'=>130,'outside_2'=>170,'outside_extra'=>30,
                    'eta_dhaka'=>'1–3 business days','eta_nearby'=>'1–3 business days','eta_outside'=>'1–4 business days'
                ),
                'redx' => array(
                    'enabled'=>'yes','name'=>'Redx','priority'=>40,
                    'dhaka_1'=>80,'dhaka_2'=>100,'dhaka_extra'=>20,
                    'nearby_1'=>100,'nearby_2'=>130,'nearby_extra'=>30,
                    'outside_1'=>120,'outside_2'=>150,'outside_extra'=>30,
                    'eta_dhaka'=>'1–3 business days','eta_nearby'=>'1–3 business days','eta_outside'=>'1–3 business days'
                ),
                'sundarban' => array(
                    'enabled'=>'yes','name'=>'Sundarban','priority'=>50,
                    'dhaka_1'=>110,'dhaka_2'=>150,'dhaka_extra'=>40,
                    'nearby_1'=>130,'nearby_2'=>170,'nearby_extra'=>40,
                    'outside_1'=>150,'outside_2'=>190,'outside_extra'=>40,
                    'eta_dhaka'=>'1–3 business days','eta_nearby'=>'1–3 business days','eta_outside'=>'2–4 business days'
                ),
                'custom' => array(
                    'enabled'=>'no','name'=>'','priority'=>60,
                    'dhaka_1'=>0,'dhaka_2'=>0,'dhaka_extra'=>0,
                    'nearby_1'=>0,'nearby_2'=>0,'nearby_extra'=>0,
                    'outside_1'=>0,'outside_2'=>0,'outside_extra'=>0,
                    'eta_dhaka'=>'','eta_nearby'=>'','eta_outside'=>''
                ),
            ),
        );
    }

    private function settings() {
        $saved = get_option( self::OPTION_KEY, array() );
        return wp_parse_args( $saved, $this->defaults() );
    }

    private function deep_merge_couriers( $saved, $defaults ) {
        $out = $defaults;
        foreach ( $defaults as $key => $def ) {
            if ( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) {
                $out[ $key ] = wp_parse_args( $saved[ $key ], $def );
            }
        }
        return $out;
    }

    private function customer_can_see() {
        $s = $this->settings();
        if ( 'yes' !== $s['enabled'] ) { return false; }
        if ( 'yes' === $s['test_mode'] ) {
            return current_user_can( 'manage_woocommerce' ) || current_user_can( 'administrator' );
        }
        return true;
    }

    private function get_weight() {
        $s = $this->settings();
        $weight = 0.0;
        if ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $item ) {
                if ( empty( $item['data'] ) || ! is_object( $item['data'] ) ) { continue; }
                $w = (float) $item['data']->get_weight();
                if ( $w <= 0 ) { $w = (float) $s['fallback_weight']; }
                $weight += $w * max( 1, (int) $item['quantity'] );
            }
        }
        return max( 0.01, $weight ?: (float) $s['fallback_weight'] );
    }

    private function zone_from_package( $package ) {
        $dest = isset( $package['destination'] ) ? $package['destination'] : array();
        $state = isset( $dest['state'] ) ? trim( (string) $dest['state'] ) : '';
        $city = isset( $dest['city'] ) ? trim( (string) $dest['city'] ) : '';
        $s = $this->settings();

        if ( 0 === strcasecmp( $state, 'Dhaka' ) ) { return 'dhaka'; }
        foreach ( (array) $s['nearby_districts'] as $d ) {
            if ( '' !== $d && 0 === strcasecmp( $state, $d ) ) { return 'nearby'; }
        }
        foreach ( (array) $s['nearby_cities'] as $c ) {
            if ( '' !== $c && 0 === strcasecmp( $city, $c ) ) { return 'nearby'; }
        }
        return 'outside';
    }

    private function courier_cost( $c, $zone, $weight ) {
        $prefix = 'dhaka' === $zone ? 'dhaka' : ( 'nearby' === $zone ? 'nearby' : 'outside' );
        $one = isset( $c[ $prefix . '_1' ] ) ? (float) $c[ $prefix . '_1' ] : 0;
        $two = isset( $c[ $prefix . '_2' ] ) ? (float) $c[ $prefix . '_2' ] : $one;
        $extra = isset( $c[ $prefix . '_extra' ] ) ? (float) $c[ $prefix . '_extra' ] : 0;
        if ( $weight <= 1 ) { return $one; }
        if ( $weight <= 2 ) { return $two; }
        return $two + ( max( 0, ceil( $weight - 2 ) ) * $extra );
    }

    private function courier_eta( $c, $zone ) {
        $field = 'dhaka' === $zone ? 'eta_dhaka' : ( 'nearby' === $zone ? 'eta_nearby' : 'eta_outside' );
        return isset( $c[ $field ] ) ? trim( (string) $c[ $field ] ) : '';
    }

    private function eta_score( $eta ) {
        if ( preg_match_all( '/\d+/', (string) $eta, $m ) && ! empty( $m[0] ) ) {
            return (int) min( array_map( 'intval', $m[0] ) );
        }
        return 999;
    }

    private function find_base_rate( $rates ) {
        foreach ( $rates as $rate ) {
            if ( is_object( $rate ) && method_exists( $rate, 'get_method_id' ) && 'flat_rate' === $rate->get_method_id() ) {
                return $rate;
            }
        }
        foreach ( $rates as $rate ) {
            if ( is_object( $rate ) && method_exists( $rate, 'get_method_id' ) && 'free_shipping' !== $rate->get_method_id() ) {
                return $rate;
            }
        }
        return null;
    }

    public function filter_rates( $rates, $package ) {
        if ( ! $this->customer_can_see() ) { return $rates; }

        $has_native_free = false;
        foreach ( $rates as $rate ) {
            if ( is_object( $rate ) && method_exists( $rate, 'get_method_id' ) && 'free_shipping' === $rate->get_method_id() ) {
                $has_native_free = true;
                break;
            }
        }

        $base = $this->find_base_rate( $rates );
        if ( ! $base ) { return $rates; }

        $s = $this->settings();
        $couriers = $this->deep_merge_couriers( isset( $s['couriers'] ) ? $s['couriers'] : array(), $this->defaults()['couriers'] );
        $zone = $this->zone_from_package( $package );
        $weight = $this->get_weight();

        $candidates = array();
        foreach ( $couriers as $key => $c ) {
            if ( 'yes' !== $c['enabled'] ) { continue; }
            $name = trim( (string) $c['name'] );
            if ( '' === $name ) { continue; }
            $normal_cost = $this->courier_cost( $c, $zone, $weight );
            if ( $normal_cost < 0 ) { continue; }
            $eta = $this->courier_eta( $c, $zone );
            $candidates[] = array(
                'key'=>sanitize_key($key), 'name'=>$name, 'eta'=>$eta,
                'normal_cost'=>$normal_cost, 'cost'=>$has_native_free ? 0 : $normal_cost,
                'priority'=>isset($c['priority']) ? (int)$c['priority'] : 100,
                'eta_score'=>$this->eta_score($eta),
            );
        }

        if ( empty( $candidates ) ) { return $rates; }

        usort( $candidates, function( $a, $b ) {
            if ( $a['eta_score'] !== $b['eta_score'] ) { return $a['eta_score'] <=> $b['eta_score']; }
            if ( $a['normal_cost'] !== $b['normal_cost'] ) { return $a['normal_cost'] <=> $b['normal_cost']; }
            if ( $a['priority'] !== $b['priority'] ) { return $a['priority'] <=> $b['priority']; }
            return strcmp( $a['name'], $b['name'] );
        } );

        $new_rates = array();
        $instance_id = method_exists( $base, 'get_instance_id' ) ? (int) $base->get_instance_id() : 0;
        $method_id = method_exists( $base, 'get_method_id' ) ? (string) $base->get_method_id() : 'flat_rate';
        $taxes = method_exists( $base, 'get_taxes' ) ? $base->get_taxes() : array();

        foreach ( $candidates as $i => $c ) {
            $rate_id = self::PREFIX . $c['key'] . '_' . $instance_id;
            $rate = new WC_Shipping_Rate( $rate_id, $c['name'], $c['cost'], $taxes, $method_id, $instance_id );
            if ( method_exists( $rate, 'set_delivery_time' ) && '' !== $c['eta'] ) { $rate->set_delivery_time( $c['eta'] ); }
            $rate->add_meta_data( 'rwsc_courier_key', $c['key'] );
            $rate->add_meta_data( 'rwsc_courier_name', $c['name'] );
            $rate->add_meta_data( 'rwsc_eta', $c['eta'] );
            $rate->add_meta_data( 'rwsc_normal_cost', (string) $c['normal_cost'] );
            $rate->add_meta_data( 'rwsc_free_applied', $has_native_free ? 'yes' : 'no' );
            $rate->add_meta_data( 'rwsc_recommended', 0 === $i ? 'yes' : 'no' );
            $new_rates[ $rate_id ] = $rate;
        }

        return $new_rates;
    }

    private function rate_meta( $method ) {
        $raw = method_exists( $method, 'get_meta_data' ) ? $method->get_meta_data() : array();
        $out = array();
        foreach ( (array) $raw as $k => $v ) {
            if ( is_string( $k ) ) { $out[ $k ] = $v; }
            elseif ( is_array( $v ) && isset( $v['key'] ) ) { $out[ $v['key'] ] = isset( $v['value'] ) ? $v['value'] : ''; }
        }
        return $out;
    }

    public function shipping_label( $label, $method ) {
        if ( ! is_object( $method ) || ! method_exists( $method, 'get_id' ) || 0 !== strpos( (string) $method->get_id(), self::PREFIX ) ) {
            return $label;
        }

        $m = $this->rate_meta( $method );
        $name = isset( $m['rwsc_courier_name'] ) ? (string) $m['rwsc_courier_name'] : $method->get_label();
        $eta = isset( $m['rwsc_eta'] ) ? trim( (string) $m['rwsc_eta'] ) : '';
        $normal = isset( $m['rwsc_normal_cost'] ) ? (float) $m['rwsc_normal_cost'] : (float) $method->get_cost();
        $free = isset( $m['rwsc_free_applied'] ) && 'yes' === (string) $m['rwsc_free_applied'];
        $rec = isset( $m['rwsc_recommended'] ) && 'yes' === (string) $m['rwsc_recommended'];

        $out = '<span class="rwsc-line"><span class="rwsc-name">' . esc_html( $name ) . '</span>';
        if ( '' !== $eta ) { $out .= ' <span class="rwsc-sep">·</span> <span class="rwsc-eta">' . esc_html( $eta ) . '</span>'; }
        $out .= '<span class="rwsc-colon">:</span> ';
        if ( $free ) {
            $out .= '<del class="rwsc-old-price">' . wp_kses_post( wc_price( $normal ) ) . '</del>';
        } else {
            $out .= '<span class="rwsc-price">' . wp_kses_post( wc_price( (float) $method->get_cost() ) ) . '</span>';
        }
        if ( $rec ) { $out .= ' <span class="rwsc-rec">· Rec</span>'; }
        $out .= '</span>';
        return $out;
    }

    public function store_shipping_item_meta( $item, $package_key, $package, $order ) {
        $chosen = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
        $chosen_id = isset( $chosen[ $package_key ] ) ? (string) $chosen[ $package_key ] : '';
        if ( 0 !== strpos( $chosen_id, self::PREFIX ) ) { return; }

        $key_part = substr( $chosen_id, strlen( self::PREFIX ) );
        $key = preg_replace( '/_\d+$/', '', $key_part );
        $s = $this->settings();
        $couriers = $this->deep_merge_couriers( isset( $s['couriers'] ) ? $s['couriers'] : array(), $this->defaults()['couriers'] );
        if ( ! isset( $couriers[ $key ] ) ) { return; }

        $courier = $couriers[ $key ];
        $name = trim( (string) $courier['name'] );
        $zone = $this->zone_from_package( $package );
        $eta = $this->courier_eta( $courier, $zone );

        $item->add_meta_data( '_rwsc_selected_courier', $name, true );
        $item->add_meta_data( '_rwsc_eta', $eta, true );
        $item->add_meta_data( '_rwsc_zone', $zone, true );
    }

    public function store_order_meta( $order_id, $data ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) { return; }
        foreach ( $order->get_shipping_methods() as $ship ) {
            $name = $ship->get_meta( '_rwsc_selected_courier', true );
            if ( $name ) {
                $order->update_meta_data( '_rwsc_selected_courier', sanitize_text_field( $name ) );
                $order->save();
                break;
            }
        }
    }

    public function admin_order_courier( $order ) {
        if ( ! $order instanceof WC_Order ) { return; }
        $courier = $order->get_meta( '_rwsc_selected_courier', true );
        if ( $courier ) { echo '<p><strong>Courier:</strong> ' . esc_html( $courier ) . '</p>'; }
    }

    public function order_list_columns( $columns ) {
        $new = array();
        foreach ( $columns as $k => $v ) {
            $new[$k] = $v;
            if ( 'shipping_address' === $k ) { $new['rwsc_courier'] = 'Courier'; }
        }
        if ( ! isset( $new['rwsc_courier'] ) ) { $new['rwsc_courier'] = 'Courier'; }
        return $new;
    }

    public function order_list_column_content( $column, $post_id ) {
        if ( 'rwsc_courier' !== $column ) { return; }
        $order = wc_get_order( $post_id );
        if ( ! $order ) { return; }
        echo esc_html( $order->get_meta( '_rwsc_selected_courier', true ) ?: '—' );
    }

    public function frontend_css() {
        if ( ! ( is_cart() || is_checkout() ) ) { return; }
        ?>
        <style id="rwsc-css">
            .woocommerce-shipping-methods li{margin:0 0 5px!important;padding:0!important;line-height:1.3!important}
            .woocommerce-shipping-methods label{display:inline!important;font-size:13px!important;line-height:1.35!important;font-weight:400!important}
            .rwsc-name{font-weight:600}.rwsc-sep,.rwsc-colon{color:#6b7280}.rwsc-price{font-weight:600;color:#16856f}
            .rwsc-old-price{font-weight:500;color:#7b818a;text-decoration-thickness:1.5px}
            .rwsc-rec{font-size:10px;font-weight:700;color:#16856f;white-space:nowrap}
            @media(max-width:767px){.woocommerce-shipping-methods label{font-size:12px!important;line-height:1.28!important}.rwsc-rec{font-size:9px}}
        </style>
        <?php
    }

    public function admin_menu() {
        add_submenu_page( 'woocommerce', 'RAR Woo Smart Courier', 'Smart Courier', 'manage_woocommerce', 'rar-woo-smart-courier', array( $this, 'settings_page' ) );
    }

    public function save_settings() {
        if ( ! is_admin() || ! isset( $_POST['rwsc_save'] ) ) { return; }
        if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
        check_admin_referer( 'rwsc_save_settings' );

        $defaults = $this->defaults();
        $settings = $this->settings();
        $settings['enabled'] = isset( $_POST['enabled'] ) ? 'yes' : 'no';
        $settings['test_mode'] = isset( $_POST['test_mode'] ) ? 'yes' : 'no';
        $settings['fallback_weight'] = isset( $_POST['fallback_weight'] ) ? max( 0.01, (float) $_POST['fallback_weight'] ) : 1;
        $settings['nearby_districts'] = isset( $_POST['nearby_districts'] ) ? array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['nearby_districts'] ) ) ) ) ) : array();
        $settings['nearby_cities'] = isset( $_POST['nearby_cities'] ) ? array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['nearby_cities'] ) ) ) ) ) : array();

        $posted = isset( $_POST['couriers'] ) && is_array( $_POST['couriers'] ) ? $_POST['couriers'] : array();
        $couriers = array();
        foreach ( $defaults['couriers'] as $key => $def ) {
            $p = isset( $posted[$key] ) && is_array( $posted[$key] ) ? $posted[$key] : array();
            $c = $def;
            $c['enabled'] = isset( $p['enabled'] ) ? 'yes' : 'no';
            $c['name'] = isset( $p['name'] ) ? sanitize_text_field( wp_unslash( $p['name'] ) ) : $def['name'];
            $c['priority'] = isset( $p['priority'] ) ? max( 1, (int) $p['priority'] ) : $def['priority'];
            foreach ( array('dhaka_1','dhaka_2','dhaka_extra','nearby_1','nearby_2','nearby_extra','outside_1','outside_2','outside_extra') as $f ) {
                $c[$f] = isset( $p[$f] ) ? max( 0, (float) $p[$f] ) : $def[$f];
            }
            foreach ( array('eta_dhaka','eta_nearby','eta_outside') as $f ) {
                $c[$f] = isset( $p[$f] ) ? sanitize_text_field( wp_unslash( $p[$f] ) ) : $def[$f];
            }
            $couriers[$key] = $c;
        }
        $settings['couriers'] = $couriers;
        update_option( self::OPTION_KEY, $settings, false );
        add_settings_error( 'rwsc', 'saved', 'Courier settings saved.', 'updated' );
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
        $s = $this->settings();
        $couriers = $this->deep_merge_couriers( isset( $s['couriers'] ) ? $s['couriers'] : array(), $this->defaults()['couriers'] );
        settings_errors( 'rwsc' );
        ?>
        <div class="wrap">
            <h1>RAR Woo Smart Courier <small style="font-size:13px;color:#666">v<?php echo esc_html( self::VERSION ); ?></small></h1>
            <p>Dynamic courier selector for Cart & Checkout. Recommendation order: faster ETA → lower rate → Priority. Lower Priority number wins only when ETA and rate tie.</p>
            <form method="post">
                <?php wp_nonce_field( 'rwsc_save_settings' ); ?>
                <table class="form-table"><tbody>
                    <tr><th>Engine</th><td><label><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 'yes' ); ?>> Enabled</label></td></tr>
                    <tr><th>Safe Test Mode</th><td><label><input type="checkbox" name="test_mode" <?php checked( $s['test_mode'], 'yes' ); ?>> Admins only</label><p class="description">Keep ON while testing. Turn OFF to make Smart Courier public.</p></td></tr>
                    <tr><th>Fallback parcel weight</th><td><input type="number" min="0.01" step="0.01" name="fallback_weight" value="<?php echo esc_attr( $s['fallback_weight'] ); ?>"> kg</td></tr>
                    <tr><th>Nearby districts</th><td><input class="regular-text" name="nearby_districts" value="<?php echo esc_attr( implode( ', ', (array) $s['nearby_districts'] ) ); ?>"><p class="description">Comma-separated. Example: Gazipur, Narayanganj</p></td></tr>
                    <tr><th>Nearby cities/areas</th><td><input class="regular-text" name="nearby_cities" value="<?php echo esc_attr( implode( ', ', (array) $s['nearby_cities'] ) ); ?>"><p class="description">Comma-separated. Example: Savar, Ashulia, Keraniganj, Dohar, Dhamrai, Nawabganj</p></td></tr>
                </tbody></table>

                <h2>Courier pricing, ETA & tie priority</h2>
                <div style="overflow:auto;max-width:100%">
                <table class="widefat striped" style="min-width:1500px"><thead><tr>
                    <th>On</th><th>Courier</th><th>Priority</th>
                    <th>Dhaka ≤1kg</th><th>Dhaka ≤2kg</th><th>Extra/kg</th>
                    <th>Nearby ≤1kg</th><th>Nearby ≤2kg</th><th>Extra/kg</th>
                    <th>Outside ≤1kg</th><th>Outside ≤2kg</th><th>Extra/kg</th>
                    <th>ETA Dhaka</th><th>ETA Nearby</th><th>ETA Outside</th>
                </tr></thead><tbody>
                <?php foreach ( $couriers as $key => $c ) : ?>
                    <tr>
                        <td><input type="checkbox" name="couriers[<?php echo esc_attr($key); ?>][enabled]" <?php checked( $c['enabled'], 'yes' ); ?>></td>
                        <td><input style="width:120px" name="couriers[<?php echo esc_attr($key); ?>][name]" value="<?php echo esc_attr($c['name']); ?>" placeholder="<?php echo 'custom' === $key ? 'Custom Courier' : ''; ?>"></td>
                        <td><input style="width:64px" type="number" min="1" name="couriers[<?php echo esc_attr($key); ?>][priority]" value="<?php echo esc_attr($c['priority']); ?>"></td>
                        <?php foreach ( array('dhaka_1','dhaka_2','dhaka_extra','nearby_1','nearby_2','nearby_extra','outside_1','outside_2','outside_extra') as $f ) : ?>
                            <td><input style="width:72px" type="number" min="0" step="0.01" name="couriers[<?php echo esc_attr($key); ?>][<?php echo esc_attr($f); ?>]" value="<?php echo esc_attr($c[$f]); ?>"></td>
                        <?php endforeach; ?>
                        <td><input style="width:150px" name="couriers[<?php echo esc_attr($key); ?>][eta_dhaka]" value="<?php echo esc_attr($c['eta_dhaka']); ?>"></td>
                        <td><input style="width:150px" name="couriers[<?php echo esc_attr($key); ?>][eta_nearby]" value="<?php echo esc_attr($c['eta_nearby']); ?>"></td>
                        <td><input style="width:150px" name="couriers[<?php echo esc_attr($key); ?>][eta_outside]" value="<?php echo esc_attr($c['eta_outside']); ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>

                <p><strong>Display format:</strong> Pathao · 1–2 business days: 70.00৳ · Rec</p>
                <p><strong>Free Shipping:</strong> native WooCommerce Free Shipping keeps every courier selectable at ৳0 while its normal charge is shown strike-through. No repeated “Free Delivery” text.</p>
                <p><strong>Custom courier:</strong> sixth row is disabled and blank by default. Add a name, rates, ETA and enable it whenever needed.</p>
                <p class="submit"><button class="button button-primary" type="submit" name="rwsc_save" value="1">Save Courier Settings</button></p>
            </form>
        </div>
        <?php
    }
}
