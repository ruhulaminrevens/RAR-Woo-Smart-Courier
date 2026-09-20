<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class RWSC_Engine {
    const OPTION_KEY = 'nabiad_smart_courier_settings'; // Keep legacy option key for seamless v1.x migration.
    const VERSION_KEY = 'rwsc_schema_version';
    const RATE_PREFIX = 'rwsc_';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate() {
        $settings = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $settings ) || empty( $settings ) ) {
            update_option( self::OPTION_KEY, self::defaults(), false );
        }
        update_option( self::VERSION_KEY, RWSC_VERSION, false );
    }

    private function __construct() {
        $this->maybe_upgrade();

        add_filter( 'woocommerce_package_rates', array( $this, 'filter_package_rates' ), 9999, 2 );
        add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_label' ), 9999, 2 );
        add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'store_shipping_item_meta' ), 20, 4 );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'store_order_meta' ), 20, 2 );
        add_action( 'wp_head', array( $this, 'frontend_css' ), 99 );
    }

    public static function defaults() {
        return array(
            'enabled'         => 'yes',
            'test_mode'       => 'yes',
            'fallback_weight' => 1.0,
            'show_rec'        => 'yes',
            'recommendation'  => 'smart',
            'couriers'        => array(
                'pathao' => array(
                    'enabled' => 'yes', 'name' => 'Pathao', 'priority' => 10,
                    'dhaka_1' => 70, 'dhaka_2' => 90, 'dhaka_extra' => 15,
                    'nearby_1' => 100, 'nearby_2' => 130, 'nearby_extra' => 25,
                    'outside_1' => 130, 'outside_2' => 170, 'outside_extra' => 25,
                    'eta_dhaka' => '1–2 business days', 'eta_nearby' => '1–3 business days', 'eta_outside' => '2–3 business days',
                ),
                'paperfly' => array(
                    'enabled' => 'yes', 'name' => 'Paperfly', 'priority' => 20,
                    'dhaka_1' => 70, 'dhaka_2' => 90, 'dhaka_extra' => 20,
                    'nearby_1' => 110, 'nearby_2' => 130, 'nearby_extra' => 20,
                    'outside_1' => 130, 'outside_2' => 150, 'outside_extra' => 20,
                    'eta_dhaka' => '1–2 business days', 'eta_nearby' => '1–3 business days', 'eta_outside' => '1–3 business days',
                ),
                'steadfast' => array(
                    'enabled' => 'yes', 'name' => 'Steadfast', 'priority' => 30,
                    'dhaka_1' => 70, 'dhaka_2' => 90, 'dhaka_extra' => 20,
                    'nearby_1' => 100, 'nearby_2' => 130, 'nearby_extra' => 30,
                    'outside_1' => 130, 'outside_2' => 170, 'outside_extra' => 30,
                    'eta_dhaka' => '1–3 business days', 'eta_nearby' => '1–3 business days', 'eta_outside' => '1–4 business days',
                ),
                'redx' => array(
                    'enabled' => 'yes', 'name' => 'Redx', 'priority' => 40,
                    'dhaka_1' => 80, 'dhaka_2' => 100, 'dhaka_extra' => 20,
                    'nearby_1' => 100, 'nearby_2' => 130, 'nearby_extra' => 30,
                    'outside_1' => 120, 'outside_2' => 150, 'outside_extra' => 30,
                    'eta_dhaka' => '1–3 business days', 'eta_nearby' => '1–3 business days', 'eta_outside' => '1–3 business days',
                ),
                'sundarban' => array(
                    'enabled' => 'yes', 'name' => 'Sundarban', 'priority' => 50,
                    'dhaka_1' => 110, 'dhaka_2' => 150, 'dhaka_extra' => 40,
                    'nearby_1' => 130, 'nearby_2' => 170, 'nearby_extra' => 40,
                    'outside_1' => 150, 'outside_2' => 190, 'outside_extra' => 40,
                    'eta_dhaka' => '1–3 business days', 'eta_nearby' => '1–3 business days', 'eta_outside' => '2–4 business days',
                ),
                'custom1' => array(
                    'enabled' => 'no', 'name' => '', 'priority' => 60,
                    'dhaka_1' => 0, 'dhaka_2' => 0, 'dhaka_extra' => 0,
                    'nearby_1' => 0, 'nearby_2' => 0, 'nearby_extra' => 0,
                    'outside_1' => 0, 'outside_2' => 0, 'outside_extra' => 0,
                    'eta_dhaka' => '', 'eta_nearby' => '', 'eta_outside' => '',
                ),
            ),
        );
    }

    public static function settings() {
        $saved = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        return self::deep_merge( self::defaults(), $saved );
    }

    private static function deep_merge( $defaults, $saved ) {
        foreach ( $saved as $key => $value ) {
            if ( is_array( $value ) && isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ) {
                $defaults[ $key ] = self::deep_merge( $defaults[ $key ], $value );
            } else {
                $defaults[ $key ] = $value;
            }
        }
        return $defaults;
    }

    private function maybe_upgrade() {
        $current = (string) get_option( self::VERSION_KEY, '' );
        if ( version_compare( $current ?: '0.0.0', RWSC_VERSION, '>=' ) ) {
            return;
        }

        $settings = self::settings();

        // v1.3.0 presentation/ETA migration. Preserve merchant-specific rates.
        $fixed = array(
            'pathao'    => array( 'name' => 'Pathao',    'priority' => 10, 'eta_dhaka' => '1–2 business days' ),
            'paperfly'  => array( 'name' => 'Paperfly',  'priority' => 20, 'eta_dhaka' => '1–2 business days' ),
            'steadfast' => array( 'name' => 'Steadfast', 'priority' => 30, 'eta_dhaka' => '1–3 business days' ),
            'redx'      => array( 'name' => 'Redx',      'priority' => 40, 'eta_dhaka' => '1–3 business days' ),
            'sundarban' => array( 'name' => 'Sundarban', 'priority' => 50, 'eta_dhaka' => '1–3 business days' ),
        );
        foreach ( $fixed as $key => $changes ) {
            if ( ! isset( $settings['couriers'][ $key ] ) ) {
                continue;
            }
            foreach ( $changes as $field => $value ) {
                $settings['couriers'][ $key ][ $field ] = $value;
            }
        }
        if ( empty( $settings['couriers']['custom1'] ) ) {
            $settings['couriers']['custom1'] = self::defaults()['couriers']['custom1'];
        }
        $settings['show_rec'] = isset( $settings['show_rec'] ) ? $settings['show_rec'] : 'yes';
        $settings['recommendation'] = isset( $settings['recommendation'] ) ? $settings['recommendation'] : 'smart';

        update_option( self::OPTION_KEY, $settings, false );
        update_option( self::VERSION_KEY, RWSC_VERSION, false );
    }

    public function filter_package_rates( $rates, $package ) {
        $settings = self::settings();

        if ( 'yes' !== $settings['enabled'] ) {
            return $rates;
        }
        if ( 'yes' === $settings['test_mode'] && ! current_user_can( 'manage_woocommerce' ) ) {
            return $rates;
        }
        if ( empty( $rates ) || ! is_array( $rates ) ) {
            return $rates;
        }

        $base_rate      = null;
        $free_available = false;

        foreach ( $rates as $rate ) {
            if ( ! is_object( $rate ) || ! is_callable( array( $rate, 'get_method_id' ) ) ) {
                continue;
            }
            $method_id = (string) $rate->get_method_id();
            if ( 'flat_rate' === $method_id && null === $base_rate ) {
                $base_rate = $rate;
            }
            if ( 'free_shipping' === $method_id ) {
                $free_available = true;
            }
        }

        // Keep the live WooCommerce setup untouched if there is no compatible base Flat Rate.
        if ( null === $base_rate ) {
            return $rates;
        }

        $zone       = $this->detect_zone( $package );
        $weight     = $this->package_weight( $package, (float) $settings['fallback_weight'] );
        $candidates = array();

        foreach ( (array) $settings['couriers'] as $key => $courier ) {
            if ( 'yes' !== ( $courier['enabled'] ?? 'no' ) ) {
                continue;
            }
            $name = trim( (string) ( $courier['name'] ?? '' ) );
            if ( '' === $name ) {
                continue;
            }

            $normal_cost = $this->calculate_cost( $courier, $zone, $weight );
            if ( $normal_cost < 0 ) {
                continue;
            }

            $eta = trim( (string) ( $courier[ 'eta_' . $zone ] ?? '' ) );
            $eta_score = $this->eta_score( $eta );

            $candidates[] = array(
                'key'         => sanitize_key( $key ),
                'name'        => $name,
                'normal_cost' => $normal_cost,
                'cost'        => $free_available ? 0.0 : $normal_cost,
                'eta'         => $eta,
                'eta_score'   => $eta_score,
                'priority'    => max( 1, (int) ( $courier['priority'] ?? 100 ) ),
            );
        }

        if ( empty( $candidates ) ) {
            return $rates;
        }

        $mode = (string) ( $settings['recommendation'] ?? 'smart' );
        usort(
            $candidates,
            static function ( $a, $b ) use ( $mode ) {
                if ( 'priority' === $mode ) {
                    if ( $a['priority'] !== $b['priority'] ) {
                        return $a['priority'] <=> $b['priority'];
                    }
                    if ( $a['normal_cost'] !== $b['normal_cost'] ) {
                        return $a['normal_cost'] <=> $b['normal_cost'];
                    }
                    return strcmp( $a['name'], $b['name'] );
                }

                if ( $a['eta_score'][0] !== $b['eta_score'][0] ) {
                    return $a['eta_score'][0] <=> $b['eta_score'][0];
                }
                if ( $a['eta_score'][1] !== $b['eta_score'][1] ) {
                    return $a['eta_score'][1] <=> $b['eta_score'][1];
                }
                if ( $a['normal_cost'] !== $b['normal_cost'] ) {
                    return $a['normal_cost'] <=> $b['normal_cost'];
                }
                if ( $a['priority'] !== $b['priority'] ) {
                    return $a['priority'] <=> $b['priority'];
                }
                return strcmp( $a['name'], $b['name'] );
            }
        );

        $new_rates   = array();
        $instance_id = is_callable( array( $base_rate, 'get_instance_id' ) ) ? (int) $base_rate->get_instance_id() : 0;
        $tax_status  = is_callable( array( $base_rate, 'get_tax_status' ) ) ? (string) $base_rate->get_tax_status() : 'none';

        foreach ( $candidates as $index => $candidate ) {
            $id = self::RATE_PREFIX . $candidate['key'] . ':' . $instance_id;
            $rate_taxes = array();
            if ( 'taxable' === $tax_status && $candidate['cost'] > 0 && class_exists( 'WC_Tax' ) ) {
                $rate_taxes = WC_Tax::calc_shipping_tax( $candidate['cost'], WC_Tax::get_shipping_tax_rates() );
            }
            $stored_label = $candidate['name'] . ( '' !== $candidate['eta'] ? ' · ' . $candidate['eta'] : '' );
            $rate = new WC_Shipping_Rate(
                $id,
                $stored_label,
                $candidate['cost'],
                $rate_taxes,
                'flat_rate',
                $instance_id,
                $tax_status
            );

            $rate->add_meta_data( 'rwsc_courier_key', $candidate['key'] );
            $rate->add_meta_data( 'rwsc_courier_name', $candidate['name'] );
            $rate->add_meta_data( 'rwsc_eta', $candidate['eta'] );
            $rate->add_meta_data( 'rwsc_normal_cost', (string) $candidate['normal_cost'] );
            $rate->add_meta_data( 'rwsc_free_applied', $free_available ? 'yes' : 'no' );
            $rate->add_meta_data( 'rwsc_recommended', 0 === $index ? 'yes' : 'no' );
            $rate->add_meta_data( 'rwsc_zone', $zone );
            $rate->add_meta_data( 'rwsc_weight', (string) $weight );

            $new_rates[ $id ] = $rate;
        }

        return $new_rates;
    }

    public function shipping_label( $label, $method ) {
        if ( ! is_object( $method ) || ! is_callable( array( $method, 'get_id' ) ) ) {
            return $label;
        }
        if ( 0 !== strpos( (string) $method->get_id(), self::RATE_PREFIX ) ) {
            return $label;
        }

        $meta = is_callable( array( $method, 'get_meta_data' ) ) ? (array) $method->get_meta_data() : array();
        $name = (string) ( $meta['rwsc_courier_name'] ?? preg_replace( '/\s+·.*$/u', '', (string) $method->get_label() ) );
        $eta  = (string) ( $meta['rwsc_eta'] ?? '' );
        $normal_cost = (float) ( $meta['rwsc_normal_cost'] ?? $method->get_cost() );
        $free = 'yes' === (string) ( $meta['rwsc_free_applied'] ?? 'no' );
        $recommended = 'yes' === (string) ( $meta['rwsc_recommended'] ?? 'no' );
        $settings = self::settings();

        $out = '<span class="rwsc-name">' . esc_html( $name ) . '</span>';
        if ( '' !== $eta ) {
            $out .= '<span class="rwsc-sep"> · </span><span class="rwsc-eta">' . esc_html( $eta ) . '</span>';
        }
        $out .= '<span class="rwsc-colon">: </span>';

        if ( $free ) {
            $out .= '<del class="rwsc-old-price">' . wp_kses_post( wc_price( $normal_cost ) ) . '</del>';
        } else {
            $out .= '<span class="rwsc-price">' . wp_kses_post( wc_price( (float) $method->get_cost() ) ) . '</span>';
        }

        if ( $recommended && 'yes' === ( $settings['show_rec'] ?? 'yes' ) ) {
            $out .= '<span class="rwsc-rec"> · Rec</span>';
        }

        return $out;
    }

    public function store_shipping_item_meta( $item, $package_key, $package, $order ) {
        if ( ! $item instanceof WC_Order_Item_Shipping ) {
            return;
        }
        $method_id = (string) $item->get_method_id();
        $instance  = (string) $item->get_instance_id();

        // The selected rate ID is available on the package chosen_method session value.
        $chosen = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();
        $selected_id = isset( $chosen[ $package_key ] ) ? (string) $chosen[ $package_key ] : '';
        if ( 0 !== strpos( $selected_id, self::RATE_PREFIX ) ) {
            return;
        }

        $rates = WC()->shipping() ? WC()->shipping()->get_packages() : array();
        $selected_rate = $rates[ $package_key ]['rates'][ $selected_id ] ?? null;
        if ( ! $selected_rate || ! is_object( $selected_rate ) ) {
            return;
        }
        $meta = (array) $selected_rate->get_meta_data();

        $name = (string) ( $meta['rwsc_courier_name'] ?? '' );
        $eta  = (string) ( $meta['rwsc_eta'] ?? '' );
        $normal_cost = (float) ( $meta['rwsc_normal_cost'] ?? 0 );
        $free = 'yes' === (string) ( $meta['rwsc_free_applied'] ?? 'no' );

        if ( '' !== $name ) {
            $item->add_meta_data( '_rwsc_courier_name', $name, true );
        }
        if ( '' !== $eta ) {
            $item->add_meta_data( '_rwsc_eta', $eta, true );
        }
        $item->add_meta_data( '_rwsc_normal_rate', wc_format_decimal( $normal_cost ), true );
        $item->add_meta_data( '_rwsc_free_shipping', $free ? 'yes' : 'no', true );
        $item->add_meta_data( '_rwsc_selected_rate_id', $selected_id, true );
        $item->add_meta_data( '_rwsc_base_method_id', $method_id, true );
        $item->add_meta_data( '_rwsc_base_instance_id', $instance, true );
    }

    public function store_order_meta( $order, $data ) {
        if ( ! $order instanceof WC_Order || ! WC()->session ) {
            return;
        }
        $chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
        if ( empty( $chosen ) ) {
            return;
        }
        foreach ( $chosen as $selected_id ) {
            if ( 0 !== strpos( (string) $selected_id, self::RATE_PREFIX ) ) {
                continue;
            }
            $packages = WC()->shipping() ? WC()->shipping()->get_packages() : array();
            foreach ( $packages as $package ) {
                $rate = $package['rates'][ $selected_id ] ?? null;
                if ( ! $rate || ! is_object( $rate ) ) {
                    continue;
                }
                $meta = (array) $rate->get_meta_data();
                $name = (string) ( $meta['rwsc_courier_name'] ?? '' );
                if ( '' !== $name ) {
                    $order->update_meta_data( '_rwsc_courier_name', $name );
                    $order->update_meta_data( '_nabiad_courier', $name ); // Legacy compatibility for existing admin/order UI.
                }
                $order->update_meta_data( '_rwsc_eta', (string) ( $meta['rwsc_eta'] ?? '' ) );
                $order->update_meta_data( '_rwsc_normal_rate', (string) ( $meta['rwsc_normal_cost'] ?? '' ) );
                $order->update_meta_data( '_rwsc_free_shipping', (string) ( $meta['rwsc_free_applied'] ?? 'no' ) );
                break 2;
            }
        }
    }

    private function detect_zone( $package ) {
        $destination = isset( $package['destination'] ) && is_array( $package['destination'] ) ? $package['destination'] : array();
        $country = strtoupper( (string) ( $destination['country'] ?? 'BD' ) );
        $state   = (string) ( $destination['state'] ?? '' );
        $city    = (string) ( $destination['city'] ?? '' );

        $state_label = '';
        if ( 'BD' === $country && function_exists( 'WC' ) && WC()->countries ) {
            $states = (array) WC()->countries->get_states( 'BD' );
            if ( isset( $states[ $state ] ) ) {
                $state_label = (string) $states[ $state ];
            }
        }

        $district = $this->normalize_location( $state_label ?: $state );
        if ( '' !== $district ) {
            if ( 'dhaka' === $district ) {
                return 'dhaka';
            }
            if ( in_array( $district, array( 'gazipur', 'narayanganj' ), true ) ) {
                return 'nearby';
            }
            return 'outside';
        }

        $city_n = $this->normalize_location( $city );
        if ( 'dhaka' === $city_n ) {
            return 'dhaka';
        }
        if ( in_array( $city_n, array( 'gazipur', 'narayanganj', 'savar', 'ashulia', 'keraniganj', 'dohar', 'dhamrai', 'nawabganj' ), true ) ) {
            return 'nearby';
        }
        return 'outside';
    }

    private function normalize_location( $value ) {
        $value = strtolower( trim( wp_strip_all_tags( (string) $value ) ) );
        $value = preg_replace( '/[^a-z]+/', '', $value );
        return $value ?: '';
    }

    private function package_weight( $package, $fallback ) {
        $weight = 0.0;
        foreach ( (array) ( $package['contents'] ?? array() ) as $line ) {
            $product = $line['data'] ?? null;
            if ( ! $product || ! is_object( $product ) || ! is_callable( array( $product, 'get_weight' ) ) ) {
                continue;
            }
            $raw = $product->get_weight();
            if ( '' === $raw || null === $raw ) {
                continue;
            }
            $kg = (float) wc_get_weight( (float) $raw, 'kg' );
            if ( $kg > 0 ) {
                $weight += $kg * max( 1, (int) ( $line['quantity'] ?? 1 ) );
            }
        }
        if ( $weight <= 0 ) {
            $weight = max( 0.1, $fallback );
        }
        return round( $weight, 3 );
    }

    private function calculate_cost( $courier, $zone, $weight ) {
        $one   = max( 0, (float) ( $courier[ $zone . '_1' ] ?? 0 ) );
        $two   = max( 0, (float) ( $courier[ $zone . '_2' ] ?? $one ) );
        $extra = max( 0, (float) ( $courier[ $zone . '_extra' ] ?? 0 ) );

        if ( $weight <= 1.0 ) {
            return $one;
        }
        if ( $weight <= 2.0 ) {
            return $two;
        }
        return $two + ( ceil( $weight - 2.0 ) * $extra );
    }

    private function eta_score( $eta ) {
        if ( preg_match_all( '/\d+/', (string) $eta, $m ) && ! empty( $m[0] ) ) {
            $nums = array_map( 'intval', $m[0] );
            $low  = $nums[0];
            $high = isset( $nums[1] ) ? $nums[1] : $low;
            return array( $low, $high );
        }
        return array( 999, 999 );
    }

    public function frontend_css() {
        if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) ) {
            return;
        }
        ?>
        <style id="rwsc-frontend-css">
            .woocommerce-shipping-methods li{margin:0 0 6px!important;padding:0!important;line-height:1.25!important}
            .woocommerce-shipping-methods label{display:inline!important;font-size:13px!important;line-height:1.35!important;font-weight:400!important}
            .rwsc-name{font-weight:600}.rwsc-price{font-weight:600;color:#16856f}.rwsc-old-price{font-weight:500;color:#7b818a;text-decoration-thickness:1.5px}.rwsc-rec{font-size:.86em;font-weight:700;color:#08753d;white-space:nowrap}.rwsc-sep,.rwsc-colon{color:#6b7280}
            @media(max-width:767px){.woocommerce-shipping-methods li{margin-bottom:4px!important}.woocommerce-shipping-methods label{font-size:12px!important;line-height:1.3!important}.rwsc-rec{font-size:.82em}}
        </style>
        <?php
    }
}
