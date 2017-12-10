<?php

/**
 * Plugin Name: Paypal Payment Plugin
 */

add_action('plugins_loaded', function() {
    class PaypalPayment extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'paypal_payment';
            $this->method_title = 'Paypal Payment';
            $this->title = 'Paypal Payment';
            $this->has_fields = false;
            $this->invoiceUrl = "https://api.sandbox.paypal.com/v1/invoicing/invoices/";

            $this->init_form_fields();
            $this->init_settings();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable Paypal Payment', 'woocommerce' ),
                    'default' => 'yes'
                ),
                'client_id' => array(
                    'title' => __( 'Client ID', 'woocommerce' ),
                    'type' => 'text',
                ),
                'client_secret' => array(
                    'title' => __( 'Client Secret', 'woocommerce' ),
                    'type' => 'text',
                ),
                'sandbox_token' => array(
                    'title' => __( 'Sandbox Access Token', 'woocommerce' ),
                    'type' => 'text',
                ),
            );
        }

        public function process_payment( $order_id ) {
            global $woocommerce;
            $order = new WC_Order( $order_id );

            if ($this->get_option('enabled')) {
                $order->update_status('on-hold', __( 'Awaiting paypal payment', 'woocommerce' ));

                $this->createPayPalInvoice($order);

                $order->reduce_order_stock();
                $woocommerce->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                );
            }

            wc_add_notice('Wrong paypal stuff.', 'error' );
            return;
        }

        private function createPayPalInvoice($order)
        {
            $token = $this->getAccessToken();

            $body = [
                'merchant_info' => [
                    'email' => 'jakobjohansson2-facilitator@icloud.com',
                    'first_name' => 'Jakob',
                    'last_name' => 'Johansson',
                    'business_name' => 'Woo'
                ],
                'shipping_info' => [
                    'first_name' => $order->get_shipping_first_name(),
                    'last_name' => $order->get_shipping_last_name(),
                    'address' => [
                        'line1' => $order->get_shipping_address_1(),
                        'city' => $order->get_shipping_city(),
                        'state' => $order->get_shipping_state(),
                    ]
                ],
                'shipping_cost' => [
                    'amount' => [
                        'currency' => $order->get_currency(),
                        'value' => $order->get_total()
                    ]
                ]
            ];

            $response = wp_remote_post($this->invoiceUrl, [
                'method' => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->get_option('sandbox_token'),
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($body)
            ]);

            $response = json_decode($response['body'], true);

            // PUT THIS IN META FIELD!
            // Sätt order som Mottage(inväntar betalning)
            $invoiceId = $response['id'];
        }

        private function getAccessToken()
        {
            // Kolla om access token finns i databasen
            // Kolla om access token har gått ut
            // Annars gör en request och få en ny access token

            $response = wp_remote_post('https://api.sandbox.paypal.com/v1/oauth2/token', [
                'method' => 'POST',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($this->get_option('client_id') . ':' . $this->get_option('client_secret'))
                ],
                'body' => [
                    'client_id' => $this->get_option('client_id'),
                    'client_secret' => $this->get_option('client_secret'),
                    'grant_type' => 'client_credentials'
                ]
            ]);

            set_transient('paypal_access_token', $response['body']['Access-Token']);

            return $response['body'];
        }
    }
});

function add_paypal_payment( $methods ) {
    $methods[] = 'PaypalPayment';
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_paypal_payment' );
