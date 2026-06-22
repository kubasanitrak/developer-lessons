<?php
/**
 * Comgate Payment Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Comgate {

    private $merchant_id;
    private $secret_key;
    private $test_mode;
    private $api_url;

    public function __construct() {
        $this->merchant_id = get_option('dl_comgate_merchant_id');
        $this->secret_key = get_option('dl_comgate_secret_key');
        $this->test_mode = filter_var(get_option('dl_comgate_test_mode', true), FILTER_VALIDATE_BOOLEAN);

        $this->api_url = 'https://payments.comgate.cz/v1.0';

        add_action('init', array($this, 'handle_callback'), 0);
        add_action('rest_api_init', array($this, 'register_callback_endpoint'));
    }

    /**
     * Create payment
     */
    public function create_payment($order_id) {
        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            return array('error' => __('Order not found.', 'developer-lessons'));
        }

        $user = get_user_by('id', $order->user_id);

        $params = array(
            'merchant' => $this->merchant_id,
            'test' => $this->test_mode ? 'true' : 'false',
            'price' => intval($order->total * 100), // Amount in cents
            'curr' => $order->currency,
            'label' => sprintf(__('Order %s', 'developer-lessons'), $order->order_number),
            'refId' => $order->order_number,
            'email' => $user->user_email,
            'prepareOnly' => 'true',
            'country' => 'CZ',
            'lang' => 'cs',
            'method' => 'ALL',
            'url_paid' => $this->get_return_url($order_id, 'success'),
            'url_cancelled' => $this->get_return_url($order_id, 'failed'),
            'url_pending' => $this->get_return_url($order_id, 'success'),
        );

        $params['secret'] = $this->secret_key;

        $response = $this->api_request('create', $params);

        if (isset($response['code']) && (int) $response['code'] === 0) {
            DL_Checkout::update_order_status($order_id, 'processing', $response['transId']);

            return array(
                'redirect' => $response['redirect']
            );
        }

        $error_message = isset($response['message']) ? $response['message'] : __('Payment creation failed.', 'developer-lessons');

        DL_Payments::log('comgate_error', $error_message, array(
            'order_id' => $order_id,
            'response' => $response
        ));

        return array('error' => $error_message);
    }

    /**
     * Register REST callback endpoint
     */
    public function register_callback_endpoint() {
        register_rest_route('developer-lessons/v1', '/comgate-callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_rest_callback'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle callback via REST API (recommended for production)
     */
    public function handle_rest_callback($request) {
        $params = $request->get_body_params();

        if (empty($params)) {
            parse_str($request->get_body(), $params);
        }

        $result = $this->process_callback_request($params);

        return new WP_REST_Response(
            $result['body'],
            $result['code'],
            array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8')
        );
    }

    /**
     * Handle payment callback on homepage query arg (legacy)
     */
    public function handle_callback() {
        if (!isset($_GET['dl_comgate_callback'])) {
            return;
        }

        $result = $this->process_callback_request($_POST);

        status_header($result['code']);
        header('Content-Type: application/x-www-form-urlencoded; charset=utf-8');
        echo $result['body'];
        exit;
    }

    /**
     * Process callback from Comgate
     */
    public function process_callback_request($params) {
        $trans_id = isset($params['transId']) ? sanitize_text_field(wp_unslash($params['transId'])) : '';
        $ref_id = isset($params['refId']) ? sanitize_text_field(wp_unslash($params['refId'])) : '';
        $post_secret = isset($params['secret']) ? sanitize_text_field(wp_unslash($params['secret'])) : '';
        $post_merchant = isset($params['merchant']) ? sanitize_text_field(wp_unslash($params['merchant'])) : '';
        $post_status = isset($params['status']) ? sanitize_text_field(wp_unslash($params['status'])) : '';

        if (!$trans_id || !$ref_id) {
            DL_Payments::log('comgate_callback_error', 'Missing callback parameters', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
            ));

            return $this->callback_response(400, 'code=1400&message=Missing parameters');
        }

        if ($post_merchant && (string) $this->merchant_id !== $post_merchant) {
            DL_Payments::log('comgate_callback_error', 'Merchant ID mismatch', array(
                'expected' => $this->merchant_id,
                'received' => $post_merchant,
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
            ));

            return $this->callback_response(403, 'code=1400&message=Invalid merchant');
        }

        if (!$this->is_valid_callback_secret($post_secret)) {
            DL_Payments::log('comgate_callback_error', 'Invalid callback secret', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
            ));

            return $this->callback_response(403, 'code=1400&message=Invalid secret');
        }

        $order = $this->find_order_for_callback($ref_id, $trans_id);

        if (!$order) {
            DL_Payments::log('comgate_callback_error', 'Order not found for callback', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
                'post_status' => $post_status,
            ));

            return $this->callback_response(404, 'code=1400&message=Order not found');
        }

        $status = $this->get_transaction_status($trans_id);

        if ($status === null) {
            DL_Payments::log('comgate_callback_error', 'Payment verification failed', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
                'order_id' => $order->id,
            ));

            return $this->callback_response(400, 'code=1400&message=Verification failed');
        }

        switch ($status) {
            case 'PAID':
            case 'AUTHORIZED':
                DL_Payments::complete_payment($order->id, $trans_id);
                break;

            case 'CANCELLED':
            case 'FAILED':
                DL_Payments::fail_payment($order->id, $status);
                break;
        }

        return $this->callback_response(200, 'code=0&message=OK');
    }

    /**
     * Find order for callback, with a short retry for DB replication lag
     */
    private function find_order_for_callback($ref_id, $trans_id) {
        $order = DL_Checkout::resolve_order_for_callback($ref_id, $trans_id);

        if ($order) {
            return $order;
        }

        usleep(500000);

        return DL_Checkout::resolve_order_for_callback($ref_id, $trans_id);
    }

    /**
     * Validate callback secret (API secret and optional push secret)
     */
    private function is_valid_callback_secret($post_secret) {
        if ($post_secret === '') {
            return false;
        }

        $secrets = array_filter(array(
            $this->secret_key,
            get_option('dl_comgate_push_secret'),
        ));

        foreach ($secrets as $secret) {
            if (hash_equals((string) $secret, $post_secret)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build callback HTTP response payload
     */
    private function callback_response($code, $body) {
        return array(
            'code' => (int) $code,
            'body' => $body,
        );
    }

    /**
     * Get payment status from Comgate
     */
    public function get_transaction_status($trans_id) {
        $params = array(
            'merchant' => $this->merchant_id,
            'transId' => $trans_id,
            'secret' => $this->secret_key,
        );

        $response = $this->api_request('status', $params);

        if (!isset($response['code']) || (int) $response['code'] !== 0) {
            return null;
        }

        return isset($response['status']) ? $response['status'] : null;
    }

    /**
     * Complete order after payer returns from Comgate (fallback when callback is delayed)
     */
    public function complete_order_from_return($order) {
        if (!$order || $order->payment_method !== 'comgate') {
            return false;
        }

        if ($order->status === 'completed') {
            return true;
        }

        $trans_id = $order->transaction_id;

        if (!$trans_id && isset($_GET['id'])) {
            $trans_id = sanitize_text_field(wp_unslash($_GET['id']));
        } elseif (!$trans_id && isset($_GET['transId'])) {
            $trans_id = sanitize_text_field(wp_unslash($_GET['transId']));
        }

        if (!$trans_id) {
            return false;
        }

        $status = $this->get_transaction_status($trans_id);

        if ($status === 'PAID' || $status === 'AUTHORIZED') {
            return DL_Payments::complete_payment($order->id, $trans_id);
        }

        if ($status === 'CANCELLED' || $status === 'FAILED') {
            return DL_Payments::fail_payment($order->id, $status);
        }

        return false;
    }

    /**
     * API request to Comgate
     */
    private function api_request($endpoint, $params) {
        $url = $this->api_url . '/' . $endpoint;

        $response = wp_remote_post($url, array(
            'body' => $params,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);

        parse_str($body, $result);

        return $result;
    }

    /**
     * Get return URL
     */
    public function get_return_url($order_id, $status = 'success') {
        $page_ids = get_option('dl_page_ids');

        if ($status === 'success') {
            $page_id = $page_ids['payment_success'];
        } else {
            $page_id = $page_ids['payment_failed'];
        }

        return add_query_arg('order', $order_id, get_permalink($page_id));
    }

    /**
     * Get callback URL (REST endpoint, recommended)
     */
    public static function get_callback_url() {
        return rest_url('developer-lessons/v1/comgate-callback');
    }

    /**
     * Get legacy callback URL
     */
    public static function get_legacy_callback_url() {
        return add_query_arg('dl_comgate_callback', '1', home_url('/'));
    }
}
