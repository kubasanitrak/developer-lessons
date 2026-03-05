<?php
/**
 * Checkout Functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Checkout {

    public function __construct() {
        add_shortcode('dl_checkout', array($this, 'render_checkout'));
        add_action('wp_ajax_dl_process_checkout', array($this, 'ajax_process_checkout'));
    }

    /**
     * Render checkout page
     */
    public function render_checkout($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access checkout.', 'developer-lessons') . '</p>';
        }

        $basket = new DL_Basket();
        $items = $basket->get_items();

        if (empty($items)) {
            ob_start();
            ?>
            <div class="dl-checkout-empty">
                <p><?php _e('Your basket is empty.', 'developer-lessons'); ?></p>
                <a href="<?php echo get_post_type_archive_link('lesson'); ?>" class="dl-btn">
                    <?php _e('Browse Lessons', 'developer-lessons'); ?>
                </a>
            </div>
            <?php
            return ob_get_clean();
        }

        $currency_symbol = get_option('dl_currency_symbol', 'Kč');
        $currency_position = get_option('dl_currency_position', 'after');
        $subtotal = $basket->get_total();
        $discount = $basket->calculate_discount();
        $total = $basket->get_final_total();
        $comgate_enabled = get_option('dl_comgate_enabled');
        $bank_transfer_enabled = get_option('dl_bank_transfer_enabled');
        $terms_page = get_option('dl_terms_page');

        ob_start();
        include DL_PLUGIN_DIR . 'public/partials/checkout-page.php';
        return ob_get_clean();
    }

    /**
     * Process checkout
     */
    public function ajax_process_checkout() {
        check_ajax_referer('dl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'developer-lessons')));
        }

        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        $agree_terms = isset($_POST['agree_terms']) ? (bool)$_POST['agree_terms'] : false;

        // Validate payment method
        if (!in_array($payment_method, array('comgate', 'bank_transfer'))) {
            wp_send_json_error(array('message' => __('Invalid payment method.', 'developer-lessons')));
        }

        // Check if payment method is enabled
        if ($payment_method === 'comgate' && !get_option('dl_comgate_enabled')) {
            wp_send_json_error(array('message' => __('Card payments are not available.', 'developer-lessons')));
        }

        if ($payment_method === 'bank_transfer' && !get_option('dl_bank_transfer_enabled')) {
            wp_send_json_error(array('message' => __('Bank transfer is not available.', 'developer-lessons')));
        }

        // Check terms agreement
        $terms_page = get_option('dl_terms_page');
        if ($terms_page && !$agree_terms) {
            wp_send_json_error(array('message' => __('Please agree to the terms and conditions.', 'developer-lessons')));
        }

        // Create order
        $order_id = $this->create_order($payment_method);

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Failed to create order.', 'developer-lessons')));
        }

        // Process payment
        if ($payment_method === 'comgate') {
            $comgate = new DL_Comgate();
            $result = $comgate->create_payment($order_id);

            if (isset($result['redirect'])) {
                wp_send_json_success(array('redirect' => $result['redirect']));
            } else {
                wp_send_json_error(array('message' => $result['error'] ?? __('Payment initialization failed.', 'developer-lessons')));
            }
        } else {
            // Bank transfer
            $bank_transfer = new DL_Bank_Transfer();
            $result = $bank_transfer->process_order($order_id);

            $page_ids = get_option('dl_page_ids');
            $checkout_url = get_permalink($page_ids['checkout']);
            
            wp_send_json_success(array(
                'redirect' => add_query_arg(array(
                    'order' => $order_id,
                    'method' => 'bank_transfer'
                ), $checkout_url)
            ));
        }
    }

    /**
     * Create order from basket
     */
    private function create_order($payment_method) {
        global $wpdb;

        $user_id = get_current_user_id();
        $basket = new DL_Basket();
        $items = $basket->get_items();

        if (empty($items)) {
            return false;
        }

        $orders_table = $wpdb->prefix . 'dl_orders';
        $order_items_table = $wpdb->prefix . 'dl_order_items';

        $subtotal = $basket->get_total();
        $discount = $basket->calculate_discount();
        $total = $basket->get_final_total();
        $currency = get_option('dl_currency_code', 'CZK');
        $order_number = $this->generate_order_number();
        $expiry_hours = get_option('dl_order_expiry_time', 24);

        // Insert order
        $wpdb->insert(
            $orders_table,
            array(
                'user_id' => $user_id,
                'order_number' => $order_number,
                'status' => 'pending',
                'payment_method' => $payment_method,
                'total' => $total,
                'discount' => $discount['amount'],
                'currency' => $currency,
                'created_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"))
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s')
        );

        $order_id = $wpdb->insert_id;

        if (!$order_id) {
            return false;
        }

        // Insert order items
        foreach ($items as $item) {
            $wpdb->insert(
                $order_items_table,
                array(
                    'order_id' => $order_id,
                    'lesson_id' => $item->lesson_id,
                    'price' => $item->price
                ),
                array('%d', '%d', '%f')
            );
        }

        return $order_id;
    }

    /**
     * Generate unique order number
     */
    private function generate_order_number() {
        return 'DL-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }

    /**
     * Get order by ID
     */
    public static function get_order($order_id) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';
        $order_items_table = $wpdb->prefix . 'dl_order_items';

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE id = %d",
            $order_id
        ));

        if (!$order) {
            return null;
        }

        $order->items = $wpdb->get_results($wpdb->prepare(
            "SELECT oi.*, p.post_title as lesson_title 
             FROM $order_items_table oi 
             LEFT JOIN {$wpdb->posts} p ON oi.lesson_id = p.ID 
             WHERE oi.order_id = %d",
            $order_id
        ));

        return $order;
    }

    /**
     * Get order by order number
     */
    public static function get_order_by_number($order_number) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';

        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $orders_table WHERE order_number = %s",
            $order_number
        ));

        if ($order_id) {
            return self::get_order($order_id);
        }

        return null;
    }

    /**
     * Update order status
     */
    public static function update_order_status($order_id, $status, $transaction_id = null) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';

        $data = array('status' => $status, 'updated_at' => current_time('mysql'));
        $format = array('%s', '%s');

        if ($transaction_id) {
            $data['transaction_id'] = $transaction_id;
            $format[] = '%s';
        }

        if ($status === 'completed') {
            $data['paid_at'] = current_time('mysql');
            $format[] = '%s';
        }

        return $wpdb->update(
            $orders_table,
            $data,
            array('id' => $order_id),
            $format,
            array('%d')
        );
    }
}
