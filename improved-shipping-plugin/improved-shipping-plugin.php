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

                $distance = json_decode(wp_remote_retrieve_body(wp_remote_get($url)));
                $distance = $distance->rows[0]->elements[0]->distance->value;

                // Now use the algorithm to calculate the shipping cost.
                $rate = array(
                    'id' => $this->id,
                    'label' => 'Distance shipping',
                    'cost' => $this->getCost($totalWeight, $distance),
                    'calc_tax' => 'per_item'
                );

                // Register the rate
                $this->add_rate( $rate );
            }

            private function getCost($weight, $distance)
            {
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
        }
    }
}

add_action( 'woocommerce_shipping_init', 'improvedShippingInit' );

function add_improved_shipping_method( $methods ) {
    $methods['improved_shipping'] = 'ImprovedShipping';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_improved_shipping_method' );
