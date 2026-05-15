<?php
/**
 * Payments Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Payments {

    public function __construct() {
        add_shortcode('dl_payment_success', array($this, 'render_payment_success'));
        add_shortcode('dl_payment_failed', array($this, 'render_payment_failed'));
        
        // Handle Stripe return (when user comes back from payment)
        add_action('template_redirect', array($this, 'handle_payment_return'));
    }

    /**
     * Handle return from payment gateway
     */
    public function handle_payment_return() {
        $page_ids = get_option('dl_page_ids', array());
        $success_page_id = isset($page_ids['payment_success']) ? $page_ids['payment_success'] : 0;
        
        if (!is_page($success_page_id)) {
            return;
        }

        $order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
        
        if (!$order_id) {
            return;
        }

        $order = DL_Checkout::get_order($order_id);
        
        if (!$order || $order->user_id != get_current_user_id()) {
            return;
        }

        // If order is still processing (Stripe), check and complete it
        if ($order->status === 'processing' && $order->payment_method === 'stripe') {
            // The payment was successful if user reached success page
            // Stripe redirects here only on success
            self::complete_payment($order_id, $order->transaction_id);
        }
    }

    /**
     * Complete payment and record purchases
     */
    public static function complete_payment($order_id, $transaction_id = null) {
        global $wpdb;

        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            error_log('DL Payments: Order not found - ' . $order_id);
            return false;
        }

        // Prevent double completion
        if ($order->status === 'completed') {
            error_log('DL Payments: Order already completed - ' . $order_id);
            return true;
        }

        // Update order status
        DL_Checkout::update_order_status($order_id, 'completed', $transaction_id);

        // Record purchases
        $purchases_table = $wpdb->prefix . 'dl_purchases';

        foreach ($order->items as $item) {
            // Check if purchase already exists (prevent duplicates)
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $purchases_table WHERE user_id = %d AND lesson_id = %d",
                $order->user_id,
                $item->lesson_id
            ));

            if (!$exists) {
                $wpdb->insert(
                    $purchases_table,
                    array(
                        'user_id' => $order->user_id,
                        'lesson_id' => $item->lesson_id,
                        'order_id' => $order_id,
                        'price' => $item->price,
                        'purchased_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%d', '%f', '%s')
                );
            }
        }

        // Clear basket
        $basket = new DL_Basket();
        $basket->clear($order->user_id);

        // Send confirmation emails
        $emails = new DL_Emails();
        $emails->send_purchase_confirmation($order_id);
        
        if (get_option('dl_admin_notification_enabled')) {
            $emails->send_admin_notification($order_id);
        }

        // Log
        self::log('payment_completed', "Order #{$order->order_number} completed", array(
            'order_id' => $order_id,
            'user_id' => $order->user_id,
            'total' => $order->total,
            'payment_method' => $order->payment_method
        ));

        return true;
    }

    /**
     * Mark payment as failed
     */
    public static function fail_payment($order_id, $reason = '') {
        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            return false;
        }

        DL_Checkout::update_order_status($order_id, 'failed');

        self::log('payment_failed', "Order #{$order->order_number} failed: $reason", array(
            'order_id' => $order_id,
            'reason' => $reason
        ));

        return true;
    }

    /**
     * Render payment success page
     */
    public function render_payment_success() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in.', 'developer-lessons') . '</p>';
        }

        $order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
        $order = DL_Checkout::get_order($order_id);

        if (!$order || $order->user_id != get_current_user_id()) {
            return '<p>' . __('Order not found.', 'developer-lessons') . '</p>';
        }

        ob_start();
        include DL_PLUGIN_DIR . 'public/partials/payment-success.php';
        return ob_get_clean();
    }

    /**
     * Render payment failed page
     */
    public function render_payment_failed() {
        $order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
        $order = $order_id ? DL_Checkout::get_order($order_id) : null;

        ob_start();
        include DL_PLUGIN_DIR . 'public/partials/payment-failed.php';
        return ob_get_clean();
    }

    /**
     * Log payment events
     */
    public static function log($type, $message, $data = array()) {
        global $wpdb;

        $logs_table = $wpdb->prefix . 'dl_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$logs_table'") !== $logs_table) {
            return;
        }

        $wpdb->insert(
            $logs_table,
            array(
                'type' => $type,
                'message' => $message,
                'data' => json_encode($data),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Format price
     */
    public static function format_price($amount, $currency = null) {
        if (!$currency) {
            $currency = get_option('dl_currency_code', 'CZK');
        }

        $symbol = get_option('dl_currency_symbol', 'Kč');
        $position = get_option('dl_currency_position', 'after');

        $formatted = number_format((float)$amount, 2, ',', ' ');

        if ($position === 'before') {
            return $symbol . ' ' . $formatted;
        }

        return $formatted . ' ' . $symbol;
    }
}
