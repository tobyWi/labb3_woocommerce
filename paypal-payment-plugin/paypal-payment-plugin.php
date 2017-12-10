<?php

/**
 * Plugin Name: Paypal Payment Plugin
 */

add_action('plugins_loaded', function() {
    class PaypalPayment extends WC_Payment_Gateway
    {
        /**
         * Create a new PaypalPayment instance.
         */
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

        /**
         * Add administrator fields.
         *
         * @return void
         */
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
                )
            );
        }

        /**
         * Process the order and create an invoid.
         *
         * @param  int $order_id
         * @return mixed
         */
        public function process_payment($order_id) {
            global $woocommerce;
            $order = new WC_Order($order_id);

            if ($this->get_option('enabled')) {
                $this->createPayPalInvoice($order);

                $order->update_status('on-hold', __('Received (Awaiting payment)', 'woocommerce'));
                $order->reduce_order_stock();
                $woocommerce->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }

            wc_add_notice('Wrong paypal stuff.', 'error');
            return;
        }

        /**
         * Create an invoice through PayPal.
         *
         * @param  mixed $order
         * @return void
         */
        private function createPayPalInvoice($order)
        {
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

            $response = wp_remote_retrieve_body(wp_remote_post($this->invoiceUrl, [
                'method' => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($body)
            ]));

            $response = json_decode($response, true);

            update_post_meta($order->get_id(), 'paypal_invoice_id', $response['id']);
        }

        /**
         * Get the paypal access token.
         *
         * @return string
         */
        private function getAccessToken()
        {
            if (time() < $this->get_option('paypal_access_token_expires_at')) {
                return $this->get_option('paypal_access_token');
            }

            $response = wp_remote_retrieve_body(wp_remote_post('https://api.sandbox.paypal.com/v1/oauth2/token', [
                'method' => 'POST',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->get_option('client_id') . ':' . $this->get_option('client_secret'))
                ],
                'body' => [
                    'client_id' => $this->get_option('client_id'),
                    'client_secret' => $this->get_option('client_secret'),
                    'grant_type' => 'client_credentials'
                ]
            ]));

            $response = json_decode($response, true);

            add_option('paypal_access_token', $response['access_token']);
            add_option('paypal_access_token_expires_at', time() + $response['expires_in']);

            return $response['access_token'];
        }
    }
});

function add_paypal_payment($methods) {
    $methods[] = 'PaypalPayment';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_paypal_payment');
