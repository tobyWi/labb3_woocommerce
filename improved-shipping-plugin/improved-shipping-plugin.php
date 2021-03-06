<?php
/**
* Plugin Name: Improved Shipping Plugin
*/

function improvedShippingInit() {
    if ( ! class_exists( 'ImprovedShipping' ) ) {
        class ImprovedShipping extends WC_Shipping_Method {
            /**
             * The Google API key.
             *
             * @var string
             */
            private $key;

            /**
             * The shop zip code.
             *
             * @var string
             */
            private $shopZipCode;

            /**
             * The customer zip code.
             *
             * @var string
             */
            private $customerZipCode;

            /**
             * The total weight of the cart.
             *
             * @var int
             */
            private $weight;

            /**
             * Create a new ImprovedShipping instance.
             *
             * @return void
             */
            public function __construct() {
                $this->id                 = 'improved_shipping';
                $this->method_title       = __( 'Improved Shipping' );
                $this->method_description = __( 'Does some magic stuff to calculate shipping.' );

                $this->enabled            = "yes";
                $this->title              = "Improved Shipping";
                $this->key = 'AIzaSyALKvUxRK3y5KHlkdCh9DfXb6L80qOJYwY';

                $this->init();
            }

            /**
             * Initialize the shipping plugin.
             *
             * @return void
             */
            public function init() {
                $this->init_form_fields();
                $this->init_settings();

                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            /**
             * Initialize the form fields.
             *
             * @return void
             */
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

            /**
             * Calculate the shipping cost.
             *
             * @param  array  $package
             * @return void
             */
            public function calculate_shipping($package = []) {
                $this->setShippingMeta($package);

                $regularDistance = $this->calculateRegularDistance();

                // Register the normal rate.
                $this->add_rate([
                    'id' => $this->id,
                    'label' => 'Normal shipping',
                    'cost' => $this->getCost($regularDistance < 10 ? 10 : $regularDistance),
                    'calc_tax' => 'per_item'
                ]);

                // Check if bicycle shipping can be applied.
                if ($this->weight < 5 && $regularDistance < 10) {
                    // Register the rate for bikes.
                    $this->add_rate([
                        'id' => $this->id . 'bike',
                        'label' => 'Bike shipping',
                        'cost' => $this->getBikeCost($this->calculateBikeDistance()),
                        'calc_tax' => 'per_item'
                    ]);
                }
            }

            /**
             * Calculate distance for bike shipping.
             *
             * @return int
             */
            private function calculateBikeDistance()
            {
                $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$this->shopZipCode}&destinations={$this->customerZipCode}&region=SE&units=metric&key={$this->key}&mode=bicycling";

                // Check for cache!
                if (get_transient('bicycle' . $this->customerZipCode . $this->shopZipCode) !== false) {
                    $bikeDistance = get_transient('bicycle' . $this->customerZipCode . $this->shopZipCode);
                } else {
                    $bikeDistance = json_decode(wp_remote_retrieve_body(wp_remote_get($url)));
                    $bikeDistance = $bikeDistance->rows[0]->elements[0]->distance->value;
                    set_transient('bicycle' . $this->customerZipCode . $this->shopZipCode, $bikeDistance);
                }

                // Make distance into km.
                return $bikeDistance / 1000;
            }

            /**
             * Calculate the distance for regular shipping.
             *
             * @return int
             */
            private function calculateRegularDistance()
            {
                $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$this->shopZipCode}&destinations={$this->customerZipCode}&region=SE&units=metric&key={$this->key}";

                // Check for cache!
                if (get_transient('regular' . $this->customerZipCode . $this->shopZipCode) !== false) {
                    $distance = get_transient('regular' . $this->customerZipCode . $this->shopZipCode);
                } else {
                    $distance = json_decode(wp_remote_retrieve_body(wp_remote_get($url)));
                    $distance = $distance->rows[0]->elements[0]->distance->value;
                    set_transient('regular' . $this->customerZipCode . $this->shopZipCode, $distance);
                }

                // Make distance into km.
                return $distance / 1000;
            }

            /**
             * Set shipping meta.
             *
             * @param array $package
             */
            private function setShippingMeta(array $package)
            {
                global $woocommerce;
                $this->weight = $woocommerce->cart->cart_contents_weight;

                $this->customerZipCode = $package['destination']['postcode'];
                $this->customerZipCode = substr_replace($this->customerZipCode, "%20", 3, 0);

                $this->shopZipCode = substr_replace($this->get_option('shopcode'), "%20", 3, 0);
            }

            /**
             * Get the normal shipping cost.
             *
             * @param  int $distance
             * @return int
             */
            private function getCost($distance)
            {
                if ($this->weight < 1) {
                    return 30 * ($distance / 10);
                } elseif ($this->weight < 5) {
                    return 60 * ($distance / 10);
                } elseif ($this->weight < 10) {
                    return 100 * ($distance / 10);
                } elseif ($this->weight < 20) {
                    return 200 * ($distance / 10);
                }

                return ($this->weight * 10) / ($distance / 10);
            }

            /**
             * Get the bike shipping cost.
             *
             * @return int
             */
            private function getBikeCost()
            {
                if ($this->weight < 1) {
                    return 15;
                }

                return 30;
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
