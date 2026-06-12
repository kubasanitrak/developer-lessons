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
        $this->test_mode = get_option('dl_comgate_test_mode', true);
        
        $this->api_url = $this->test_mode 
            ? 'https://payments.comgate.cz/v1.0'
            : 'https://payments.comgate.cz/v1.0';

        add_action('init', array($this, 'handle_callback'));
        add_action('wp_ajax_nopriv_dl_comgate_callback', array($this, 'process_callback'));
        add_action('wp_ajax_dl_comgate_callback', array($this, 'process_callback'));
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
        $page_ids = get_option('dl_page_ids');

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

        // Generate signature
        $params['secret'] = $this->secret_key;
        
        $response = $this->api_request('create', $params);

        if (isset($response['code']) && $response['code'] == 0) {
            // Update order with transaction ID
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
     * Handle payment callback
     */
    public function handle_callback() {
        if (!isset($_GET['dl_comgate_callback'])) {
            return;
        }

        $this->process_callback();
    }

    /**
     * Process callback from Comgate
     */
    public function process_callback() {
        $trans_id = isset($_POST['transId']) ? sanitize_text_field(wp_unslash($_POST['transId'])) : '';
        $ref_id = isset($_POST['refId']) ? sanitize_text_field(wp_unslash($_POST['refId'])) : '';
        $post_secret = isset($_POST['secret']) ? sanitize_text_field(wp_unslash($_POST['secret'])) : '';

        if (!$trans_id || !$ref_id) {
            DL_Payments::log('comgate_callback_error', 'Missing callback parameters', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
            ));
            http_response_code(400);
            exit('Missing parameters');
        }

        if ($post_secret !== $this->secret_key) {
            DL_Payments::log('comgate_callback_error', 'Invalid callback secret', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
            ));
            http_response_code(403);
            exit('Invalid secret');
        }

        $status = $this->get_transaction_status($trans_id);

        if ($status === null) {
            DL_Payments::log('comgate_callback_error', 'Payment verification failed', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
            ));
            http_response_code(400);
            exit('Verification failed');
        }

        $order = DL_Checkout::get_order_by_number($ref_id);

        if (!$order) {
            DL_Payments::log('comgate_callback_error', 'Order not found for callback', array(
                'trans_id' => $trans_id,
                'ref_id' => $ref_id,
            ));
            http_response_code(404);
            exit('Order not found');
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

        header('Content-Type: application/x-www-form-urlencoded; charset=utf-8');
        http_response_code(200);
        exit('code=0&message=OK');
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
        
        // Parse response
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
     * Get callback URL
     */
    public function get_callback_url() {
        return add_query_arg('dl_comgate_callback', '1', home_url('/'));
    }
}
