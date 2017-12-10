<?php
/**
* Plugin Name: Improved Shipping Plugin
*/

function improvedShippingInit() {
    if ( ! class_exists( 'ImprovedShipping' ) ) {
        class ImprovedShipping extends WC_Shipping_Method {
            public function __construct() {
                $this->id                 = 'improved_shipping';
                $this->method_title       = __( 'Improved Shipping' );
                $this->method_description = __( 'Does some magic stuff to calculate shipping.' );

                $this->enabled            = "yes";
                $this->title              = "Improved Shipping";

                $this->init();
            }

            function init() {
                $this->init_form_fields();
                $this->init_settings();

                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            public function init_form_fields() {
                $this->form_fields =  [
                    'shopcode' => [
                        'title' => 'Shop zipcode',
                        'type' => 'text',
                        'description' => 'For this plugin, you need a shop zipcode.',
                        'default' => '41265'
                    ]
                ];
            }

            public function calculate_shipping( $package = []) {
                // First, let's calculate the weight of the cart.
                global $woocommerce;
                $totalWeight = $woocommerce->cart->cart_contents_weight;

                // Then calculate the distance to the customer.
                $customerCode = $package['destination']['postcode'];
                $customerCode = substr_replace($customerCode, "%20", 3, 0);

                $shopCode = substr_replace($this->get_option('shopcode'), "%20", 3, 0);

                $key = 'AIzaSyALKvUxRK3y5KHlkdCh9DfXb6L80qOJYwY';
                $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$shopCode}&destinations={$customerCode}&region=SE&units=metric&key={$key}";

                // Check for cache!
                if (get_transient('regular' . $customerCode . $shopCode) !== false) {
                    $distance = get_transient('regular' . $customerCode . $shopCode);
                } else {
                    $distance = json_decode(wp_remote_retrieve_body(wp_remote_get($url)));
                    $distance = $distance->rows[0]->elements[0]->distance->value;
                    set_transient('regular' . $customerCode . $shopCode, $distance);
                }

                // Lägg till rate flre man byter distance.

                // Make distance into km.
                $distance = $distance / 1000;

                // Check if bicycle shipping can be applied.
                if ($totalWeight < 5 && $distance < 10) {
                    $url .= '&mode=bicycling';

                    // Check for cache!
                    if (get_transient('bicycle' . $customerCode . $shopCode) !== false) {
                        $distance = get_transient('bicycle' . $customerCode . $shopCode);
                    } else {
                        $distance = json_decode(wp_remote_retrieve_body(wp_remote_get($url)));
                        $distance = $distance->rows[0]->elements[0]->distance->value;
                        set_transient('bicycle' . $customerCode . $shopCode, $distance);
                    }

                    // Make distance into km.
                    $distance = $distance / 1000;

                    // Register the rate for bikes
                    $this->add_rate(array(
                        'id' => $this->id . 'bike',
                        'label' => 'Bike shipping',
                        'cost' => $this->getBikeCost($totalWeight),
                        'calc_tax' => 'per_item'
                    ));

                }

                if ($distance < 10) {
                    $distance = 10;
                }

                // Register the normal rate
                $this->add_rate(array(
                    'id' => $this->id,
                    'label' => 'Normal shipping',
                    'cost' => $this->getCost($totalWeight, $distance),
                    'calc_tax' => 'per_item'
                ));
            }

            private function getCost($weight, $distance)
            {
                // GÖR OM
                if ($weight < 1) {
                    return 30 * $distance;
                } elseif ($weight < 5) {
                    return 60 * $distance;
                } elseif ($weight < 10) {
                    return 100 * $distance;
                } elseif ($weight < 20) {
                    return 200 * $distance;
                }

                return $weight * 100;
            }

            private function getBikeCost($weight)
            {
                // GÖR OM
                if ($weight < 1) {
                    return 15;
                } elseif ($weight < 5) {
                    return 30;
                }
            }
        }
    }
}

add_action( 'woocommerce_shipping_init', 'improvedShippingInit' );

function add_improved_shipping_method( $methods ) {
    $methods['improved_shipping'] = 'ImprovedShipping';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_improved_shipping_method' );
