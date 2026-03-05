<?php
/**
 * Bank Transfer Payment
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Bank_Transfer {

    public function __construct() {
        add_action('init', array($this, 'check_bank_transfer_page'));
        add_action('wp_ajax_dl_confirm_bank_transfer', array($this, 'ajax_confirm_payment'));
    }

    /**
     * Process order for bank transfer
     */
    public function process_order($order_id) {
        DL_Checkout::update_order_status($order_id, 'awaiting_payment');

        DL_Payments::log('bank_transfer_initiated', "Order #$order_id awaiting bank transfer", array(
            'order_id' => $order_id
        ));

        return true;
    }

    /**
     * Check if we're on bank transfer page
     */
    public function check_bank_transfer_page() {
        if (!isset($_GET['order']) || !isset($_GET['method']) || $_GET['method'] !== 'bank_transfer') {
            return;
        }

        // Display will be handled by checkout shortcode
    }

    /**
     * Render bank transfer info
     */
    public static function render_transfer_info($order_id) {
        $order = DL_Checkout::get_order($order_id);

        if (!$order || $order->user_id != get_current_user_id()) {
            return '<p>' . __('Order not found.', 'developer-lessons') . '</p>';
        }

        $account_name = get_option('dl_bank_account_name');
        $account_number = get_option('dl_bank_account_number');
        $bank_code = get_option('dl_bank_code');
        $iban = get_option('dl_bank_iban');
        $bic = get_option('dl_bank_bic');

        // Generate QR code
        $qr_generator = new DL_QR_Generator();
        $qr_code = $qr_generator->generate_payment_qr(
            $iban,
            $order->total,
            $order->currency,
            $order->order_number
        );

        ob_start();
        ?>
        <div class="dl-bank-transfer-info">
            <h2><?php _e('Bank Transfer Payment', 'developer-lessons'); ?></h2>
            
            <div class="dl-order-summary">
                <p><strong><?php _e('Order Number:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
                <p><strong><?php _e('Amount:', 'developer-lessons'); ?></strong> <?php echo DL_Payments::format_price($order->total); ?></p>
            </div>

            <div class="dl-bank-details">
                <h3><?php _e('Bank Account Details', 'developer-lessons'); ?></h3>
                
                <table class="dl-bank-details-table">
                    <tr>
                        <th><?php _e('Account Name:', 'developer-lessons'); ?></th>
                        <td><?php echo esc_html($account_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Account Number:', 'developer-lessons'); ?></th>
                        <td><?php echo esc_html($account_number . '/' . $bank_code); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('IBAN:', 'developer-lessons'); ?></th>
                        <td><?php echo esc_html($iban); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('BIC/SWIFT:', 'developer-lessons'); ?></th>
                        <td><?php echo esc_html($bic); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Variable Symbol:', 'developer-lessons'); ?></th>
                        <td><strong><?php echo esc_html($order->order_number); ?></strong></td>
                    </tr>
                </table>
            </div>

            <?php if ($qr_code): ?>
            <div class="dl-qr-payment">
                <h3><?php _e('Pay with QR Code', 'developer-lessons'); ?></h3>
                <p><?php _e('Scan this QR code with your banking app:', 'developer-lessons'); ?></p>
                <div class="dl-qr-code">
                    <img src="<?php echo esc_url($qr_code); ?>" alt="<?php _e('Payment QR Code', 'developer-lessons'); ?>">
                </div>
            </div>
            <?php endif; ?>

            <div class="dl-payment-notes">
                <p><?php _e('Your order will be completed once we receive your payment. This usually takes 1-2 business days.', 'developer-lessons'); ?></p>
                <p><strong><?php _e('Important: Use the order number as the variable symbol/reference for your payment.', 'developer-lessons'); ?></strong></p>
            </div>

            <div class="dl-payment-actions">
                <?php
                $page_ids = get_option('dl_page_ids');
                $dashboard_url = get_permalink($page_ids['dashboard']);
                ?>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="dl-btn">
                    <?php _e('Go to My Lessons', 'developer-lessons'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Admin: Confirm bank transfer payment
     */
    public function ajax_confirm_payment() {
        check_ajax_referer('dl_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'developer-lessons')));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order.', 'developer-lessons')));
        }

        $result = DL_Payments::complete_payment($order_id, 'manual_bank_transfer');

        if ($result) {
            wp_send_json_success(array('message' => __('Payment confirmed and order completed.', 'developer-lessons')));
        }

        wp_send_json_error(array('message' => __('Failed to confirm payment.', 'developer-lessons')));
    }
}
