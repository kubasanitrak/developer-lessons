<?php
/**
 * Stripe Payment Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Stripe {

    private $secret_key;
    private $publishable_key;
    private $test_mode;
    private $webhook_secret;

    public function __construct() {
        $this->test_mode = get_option('dl_stripe_test_mode', true);
        
        if ($this->test_mode) {
            $this->secret_key = get_option('dl_stripe_test_secret_key', '');
            $this->publishable_key = get_option('dl_stripe_test_publishable_key', '');
        } else {
            $this->secret_key = get_option('dl_stripe_live_secret_key', '');
            $this->publishable_key = get_option('dl_stripe_live_publishable_key', '');
        }
        
        $this->webhook_secret = get_option('dl_stripe_webhook_secret', '');

        // Register webhook endpoint
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
        
        // Enqueue Stripe JS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_stripe_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_dl_create_stripe_session', array($this, 'ajax_create_checkout_session'));
        add_action('wp_ajax_dl_stripe_payment_intent', array($this, 'ajax_create_payment_intent'));
    }

    /**
     * Check if Stripe is enabled and configured
     */
    public function is_enabled() {
        return get_option('dl_stripe_enabled') && !empty($this->secret_key) && !empty($this->publishable_key);
    }

    /**
     * Get publishable key for frontend
     */
    public function get_publishable_key() {
        return $this->publishable_key;
    }

    /**
     * Enqueue Stripe scripts on checkout page
     */
    public function enqueue_stripe_scripts() {
        if (!$this->is_enabled()) {
            return;
        }

        $page_ids = get_option('dl_page_ids', array());
        $checkout_page_id = isset($page_ids['checkout']) ? $page_ids['checkout'] : 0;

        if (!is_page($checkout_page_id)) {
            return;
        }

        // Stripe.js
        wp_enqueue_script(
            'stripe-js',
            'https://js.stripe.com/v3/',
            array(),
            null,
            true
        );

        // Our Stripe handler
        wp_enqueue_script(
            'dl-stripe',
            DL_PLUGIN_URL . 'public/js/stripe.js',
            array('jquery', 'stripe-js'),
            DL_VERSION,
            true
        );

        wp_localize_script('dl-stripe', 'dl_stripe', array(
            'publishable_key' => $this->publishable_key,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dl_stripe_nonce'),
            'return_url' => $this->get_return_url(),
            'strings' => array(
                'processing' => __('Processing payment...', 'developer-lessons'),
                'error' => __('Payment failed. Please try again.', 'developer-lessons'),
            )
        ));
    }

    /**
     * Create Stripe Checkout Session
     */
    public function create_checkout_session($order_id) {
        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            return array('error' => __('Order not found.', 'developer-lessons'));
        }

        $user = get_user_by('id', $order->user_id);
        $page_ids = get_option('dl_page_ids');

        // Build line items
        $line_items = array();
        
        foreach ($order->items as $item) {
            $line_items[] = array(
                'price_data' => array(
                    'currency' => strtolower($order->currency),
                    'product_data' => array(
                        'name' => $item->lesson_title,
                    ),
                    'unit_amount' => intval($item->price * 100), // Amount in cents
                ),
                'quantity' => 1,
            );
        }

        // Add discount as negative line item if applicable
        if ($order->discount > 0) {
            $line_items[] = array(
                'price_data' => array(
                    'currency' => strtolower($order->currency),
                    'product_data' => array(
                        'name' => __('Bundle Discount', 'developer-lessons'),
                    ),
                    'unit_amount' => -intval($order->discount * 100),
                ),
                'quantity' => 1,
            );
        }

        $session_data = array(
            'payment_method_types' => array('card'),
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => add_query_arg(array(
                'order' => $order_id,
                'session_id' => '{CHECKOUT_SESSION_ID}'
            ), get_permalink($page_ids['payment_success'])),
            'cancel_url' => add_query_arg('order', $order_id, get_permalink($page_ids['payment_failed'])),
            'customer_email' => $user->user_email,
            'client_reference_id' => $order->order_number,
            'metadata' => array(
                'order_id' => $order_id,
                'order_number' => $order->order_number,
            ),
        );

        $response = $this->api_request('checkout/sessions', $session_data);

        if (isset($response['id'])) {
            // Update order with session ID
            DL_Checkout::update_order_status($order_id, 'processing', $response['id']);

            return array(
                'session_id' => $response['id'],
                'url' => $response['url'],
            );
        }

        $error_message = isset($response['error']['message']) ? $response['error']['message'] : __('Failed to create payment session.', 'developer-lessons');
        
        DL_Payments::log('stripe_error', $error_message, array(
            'order_id' => $order_id,
            'response' => $response
        ));

        return array('error' => $error_message);
    }

    /**
     * Create Payment Intent (for embedded payment form)
     */
    public function create_payment_intent($order_id) {
        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            return array('error' => __('Order not found.', 'developer-lessons'));
        }

        $user = get_user_by('id', $order->user_id);

        $intent_data = array(
            'amount' => intval($order->total * 100), // Amount in cents
            'currency' => strtolower($order->currency),
            'description' => sprintf(__('Order %s', 'developer-lessons'), $order->order_number),
            'metadata' => array(
                'order_id' => $order_id,
                'order_number' => $order->order_number,
            ),
            'receipt_email' => $user->user_email,
        );

        $response = $this->api_request('payment_intents', $intent_data);

        if (isset($response['client_secret'])) {
            // Update order with payment intent ID
            DL_Checkout::update_order_status($order_id, 'processing', $response['id']);

            return array(
                'client_secret' => $response['client_secret'],
                'payment_intent_id' => $response['id'],
            );
        }

        $error_message = isset($response['error']['message']) ? $response['error']['message'] : __('Failed to create payment.', 'developer-lessons');

        return array('error' => $error_message);
    }

    /**
     * AJAX: Create Checkout Session
     */
    public function ajax_create_checkout_session() {
        check_ajax_referer('dl_stripe_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'developer-lessons')));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order.', 'developer-lessons')));
        }

        $result = $this->create_checkout_session($order_id);

        if (isset($result['error'])) {
            wp_send_json_error(array('message' => $result['error']));
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Create Payment Intent
     */
    public function ajax_create_payment_intent() {
        check_ajax_referer('dl_stripe_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'developer-lessons')));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order.', 'developer-lessons')));
        }

        $result = $this->create_payment_intent($order_id);

        if (isset($result['error'])) {
            wp_send_json_error(array('message' => $result['error']));
        }

        wp_send_json_success($result);
    }

    /**
     * Register webhook endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('developer-lessons/v1', '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle Stripe webhook
     */
    public function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');

        // Verify webhook signature if secret is set
        if ($this->webhook_secret) {
            $event = $this->verify_webhook_signature($payload, $sig_header);
            
            if (!$event) {
                return new WP_REST_Response(array('error' => 'Invalid signature'), 400);
            }
        } else {
            $event = json_decode($payload, true);
        }

        if (!$event || !isset($event['type'])) {
            return new WP_REST_Response(array('error' => 'Invalid payload'), 400);
        }

        DL_Payments::log('stripe_webhook', 'Received webhook: ' . $event['type'], array(
            'event_id' => $event['id'] ?? '',
        ));

        switch ($event['type']) {
            case 'checkout.session.completed':
                $this->handle_checkout_completed($event['data']['object']);
                break;

            case 'payment_intent.succeeded':
                $this->handle_payment_succeeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handle_payment_failed($event['data']['object']);
                break;
        }

        return new WP_REST_Response(array('received' => true), 200);
    }

    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($payload, $sig_header) {
        if (!$sig_header) {
            return null;
        }

        $elements = explode(',', $sig_header);
        $timestamp = null;
        $signatures = array();

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            return null;
        }

        // Check timestamp (allow 5 minutes tolerance)
        if (abs(time() - intval($timestamp)) > 300) {
            return null;
        }

        // Compute expected signature
        $signed_payload = $timestamp . '.' . $payload;
        $expected_signature = hash_hmac('sha256', $signed_payload, $this->webhook_secret);

        // Compare signatures
        foreach ($signatures as $signature) {
            if (hash_equals($expected_signature, $signature)) {
                return json_decode($payload, true);
            }
        }

        return null;
    }

    /**
     * Handle checkout.session.completed
     */
    private function handle_checkout_completed($session) {
        $order_id = isset($session['metadata']['order_id']) ? intval($session['metadata']['order_id']) : 0;

        if (!$order_id) {
            // Try to find order by client_reference_id
            $order = DL_Checkout::get_order_by_number($session['client_reference_id']);
            if ($order) {
                $order_id = $order->id;
            }
        }

        if ($order_id && $session['payment_status'] === 'paid') {
            DL_Payments::complete_payment($order_id, $session['payment_intent'] ?? $session['id']);
        }
    }

    /**
     * Handle payment_intent.succeeded
     */
    private function handle_payment_succeeded($payment_intent) {
        $order_id = isset($payment_intent['metadata']['order_id']) ? intval($payment_intent['metadata']['order_id']) : 0;

        if ($order_id) {
            DL_Payments::complete_payment($order_id, $payment_intent['id']);
        }
    }

    /**
     * Handle payment_intent.payment_failed
     */
    private function handle_payment_failed($payment_intent) {
        $order_id = isset($payment_intent['metadata']['order_id']) ? intval($payment_intent['metadata']['order_id']) : 0;

        if ($order_id) {
            $error = isset($payment_intent['last_payment_error']['message']) 
                ? $payment_intent['last_payment_error']['message'] 
                : 'Payment failed';
            
            DL_Payments::fail_payment($order_id, $error);
        }
    }

    /**
     * Make API request to Stripe
     */
    private function api_request($endpoint, $data = array(), $method = 'POST') {
        $url = 'https://api.stripe.com/v1/' . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'timeout' => 30,
        );

        if (!empty($data)) {
            $args['body'] = $this->encode_params($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array('error' => array('message' => $response->get_error_message()));
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Encode params for Stripe API (handles nested arrays)
     */
    private function encode_params($data, $prefix = '') {
        $result = array();

        foreach ($data as $key => $value) {
            $new_key = $prefix ? $prefix . '[' . $key . ']' : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->encode_params($value, $new_key));
            } else {
                $result[$new_key] = $value;
            }
        }

        return $result;
    }

    /**
     * Get return URL for redirect after payment
     */
    private function get_return_url() {
        $page_ids = get_option('dl_page_ids');
        return get_permalink($page_ids['payment_success']);
    }

    /**
     * Retrieve payment status
     */
    public function get_payment_status($session_id) {
        $response = $this->api_request('checkout/sessions/' . $session_id, array(), 'GET');
        
        if (isset($response['payment_status'])) {
            return $response['payment_status'];
        }

        return null;
    }
}
