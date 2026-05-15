<?php
/**
 * Email Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Emails {

    private $sender_name;
    private $sender_email;
    private $template_type;
    private $pending_attachments = array();

    public function __construct() {
        $this->sender_name = get_option('dl_email_sender_name', get_bloginfo('name'));
        $this->sender_email = get_option('dl_email_sender_email', get_option('admin_email'));
        $this->template_type = get_option('dl_email_template_type', 'html');
    }

    /**
     * Order placed: customer + optional admin, pro-forma PDF
     */
    public function send_order_placed_notifications($order_id) {
        $order = DL_Checkout::get_order($order_id);
        if (!$order || !empty($order->order_placed_email_sent_at)) {
            return false;
        }

        $proforma = DL_Invoices::ensure_proforma($order_id);
        $attachments = $proforma ? array($proforma) : array();

        $user = get_user_by('id', $order->user_id);
        if ($user) {
            $this->send_customer_email(
                $order_id,
                'order_placed',
                sprintf(
                    __('Order received - awaiting payment (%s)', 'developer-lessons'),
                    $order->order_number
                ),
                $attachments
            );
        }

        if (get_option('dl_admin_notification_enabled')) {
            $this->send_admin_email(
                $order_id,
                'order_placed',
                sprintf(
                    __('New order awaiting payment - %s', 'developer-lessons'),
                    $order->order_number
                ),
                $attachments
            );
        }

        $this->mark_email_sent($order_id, 'order_placed');
        return true;
    }

    /**
     * Payment confirmed: customer + optional admin, pro-forma + invoice PDFs
     */
    public function send_payment_confirmed_notifications($order_id) {
        $order = DL_Checkout::get_order($order_id);
        if (!$order || !empty($order->payment_confirmed_email_sent_at)) {
            return false;
        }

        $proforma = DL_Invoices::ensure_proforma($order_id);
        $invoice = DL_Invoices::ensure_invoice($order_id);
        $attachments = array_filter(array($proforma, $invoice));

        $user = get_user_by('id', $order->user_id);
        if ($user) {
            $this->send_customer_email(
                $order_id,
                'payment_confirmed',
                sprintf(
                    __('Your Purchase Confirmation - Order %s', 'developer-lessons'),
                    $order->order_number
                ),
                $attachments
            );
        }

        if (get_option('dl_admin_notification_enabled')) {
            $this->send_admin_email(
                $order_id,
                'payment_confirmed',
                sprintf(
                    __('New Purchase - Order %s', 'developer-lessons'),
                    $order->order_number
                ),
                $attachments
            );
        }

        $this->mark_email_sent($order_id, 'payment_confirmed');
        return true;
    }

    /**
     * @deprecated Use send_payment_confirmed_notifications()
     */
    public function send_purchase_confirmation($order_id) {
        return $this->send_payment_confirmed_notifications($order_id);
    }

    /**
     * @deprecated Use send_payment_confirmed_notifications()
     */
    public function send_admin_notification($order_id) {
        if (!get_option('dl_admin_notification_enabled')) {
            return false;
        }
        $order = DL_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }
        $proforma = DL_Invoices::ensure_proforma($order_id);
        $invoice = DL_Invoices::ensure_invoice($order_id);
        return $this->send_admin_email(
            $order_id,
            'payment_confirmed',
            sprintf(__('New Purchase - Order %s', 'developer-lessons'), $order->order_number),
            array_filter(array($proforma, $invoice))
        );
    }

    /**
     * Send order expiry notification
     */
    public function send_expiry_notification($order_id) {
        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            return false;
        }

        $user = get_user_by('id', $order->user_id);
        $to = $user->user_email;
        $subject = sprintf(__('Your Order %s is Expiring Soon', 'developer-lessons'), $order->order_number);

        $page_ids = get_option('dl_page_ids');
        $checkout_url = add_query_arg(array(
            'order' => $order->id,
            'method' => 'bank_transfer'
        ), get_permalink($page_ids['checkout']));

        $template_vars = array(
            'order' => $order,
            'user' => $user,
            'site_name' => get_bloginfo('name'),
            'checkout_url' => $checkout_url,
            'email_phase' => 'expiry',
        );

        $message = $this->get_template('order-expiry', $template_vars);
        return $this->mail($to, $subject, $message, $this->get_html_headers());
    }

    private function send_customer_email($order_id, $email_phase, $subject, $attachments) {
        $order = DL_Checkout::get_order($order_id);
        $user = get_user_by('id', $order->user_id);
        if (!$user) {
            return false;
        }

        $template_vars = $this->build_template_vars($order, $user, $email_phase);
        $template = ($this->template_type === 'html') ? 'purchase-confirmation' : 'purchase-confirmation-plain';
        $headers = ($this->template_type === 'html') ? $this->get_html_headers() : $this->get_plain_headers();
        $message = $this->get_template($template, $template_vars);

        return $this->mail($user->user_email, $subject, $message, $headers, $attachments);
    }

    private function send_admin_email($order_id, $email_phase, $subject, $attachments) {
        $order = DL_Checkout::get_order($order_id);
        $user = get_user_by('id', $order->user_id);
        $to = get_option('dl_admin_notification_email', get_option('admin_email'));

        $template_vars = $this->build_template_vars($order, $user, $email_phase);
        $message = $this->get_template('admin-notification', $template_vars);

        return $this->mail($to, $subject, $message, $this->get_html_headers(), $attachments);
    }

    private function build_template_vars($order, $user, $email_phase) {
        return array(
            'order' => $order,
            'user' => $user,
            'site_name' => get_bloginfo('name'),
            'dashboard_url' => $this->get_dashboard_url(),
            'email_phase' => $email_phase,
            'payment_instructions' => $this->get_payment_instructions_html($order, $email_phase),
            'checkout_url' => $this->get_order_checkout_url($order),
        );
    }

    /**
     * Payment instructions block for order-placed emails
     */
    public function get_payment_instructions_html($order, $email_phase) {
        if ($email_phase !== 'order_placed') {
            return '';
        }

        ob_start();

        if ($order->payment_method === 'bank_transfer') {
            $bank = DL_Seller::get_bank();
            ?>
            <h3><?php esc_html_e('Payment instructions', 'developer-lessons'); ?></h3>
            <p><?php esc_html_e('Please pay by bank transfer using the details below. Use your order number as the payment reference.', 'developer-lessons'); ?></p>
            <ul>
                <li><strong><?php esc_html_e('Amount:', 'developer-lessons'); ?></strong> <?php echo esc_html(DL_Payments::format_price($order->total, $order->currency)); ?></li>
                <li><strong><?php esc_html_e('Reference:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></li>
                <?php if ($bank['account_name']) : ?>
                    <li><strong><?php esc_html_e('Account name:', 'developer-lessons'); ?></strong> <?php echo esc_html($bank['account_name']); ?></li>
                <?php endif; ?>
                <?php if ($bank['account_number'] && $bank['bank_code']) : ?>
                    <li><strong><?php esc_html_e('Account:', 'developer-lessons'); ?></strong> <?php echo esc_html($bank['account_number'] . '/' . $bank['bank_code']); ?></li>
                <?php endif; ?>
                <?php if ($bank['iban']) : ?>
                    <li><strong>IBAN:</strong> <?php echo esc_html($bank['iban']); ?></li>
                <?php endif; ?>
                <?php if ($bank['bic']) : ?>
                    <li><strong>BIC:</strong> <?php echo esc_html($bank['bic']); ?></li>
                <?php endif; ?>
            </ul>
            <?php
        } elseif (in_array($order->payment_method, array('stripe', 'comgate'), true)) {
            ?>
            <h3><?php esc_html_e('Payment instructions', 'developer-lessons'); ?></h3>
            <p><?php esc_html_e('Your card payment is being processed. If you have not completed payment yet, return to checkout to finish.', 'developer-lessons'); ?></p>
            <?php if ($this->get_order_checkout_url($order)) : ?>
                <p><a href="<?php echo esc_url($this->get_order_checkout_url($order)); ?>"><?php esc_html_e('Return to checkout', 'developer-lessons'); ?></a></p>
            <?php endif; ?>
            <?php
        }

        return ob_get_clean();
    }

    private function get_order_checkout_url($order) {
        $page_ids = get_option('dl_page_ids');
        if (empty($page_ids['checkout'])) {
            return '';
        }
        return add_query_arg(array(
            'order' => $order->id,
            'method' => $order->payment_method,
        ), get_permalink($page_ids['checkout']));
    }

    private function mark_email_sent($order_id, $type) {
        global $wpdb;
        $field = ($type === 'order_placed') ? 'order_placed_email_sent_at' : 'payment_confirmed_email_sent_at';
        $wpdb->update(
            $wpdb->prefix . 'dl_orders',
            array($field => current_time('mysql')),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
    }

    private function mail($to, $subject, $message, $headers, $attachments = array()) {
        $this->pending_attachments = is_array($attachments) ? array_values(array_filter($attachments)) : array();

        if (!empty($this->pending_attachments)) {
            add_action('phpmailer_init', array($this, 'attach_files_to_phpmailer'));
        }

        $sent = wp_mail($to, $subject, $message, $headers);

        remove_action('phpmailer_init', array($this, 'attach_files_to_phpmailer'));
        $this->pending_attachments = array();

        return $sent;
    }

    public function attach_files_to_phpmailer($phpmailer) {
        foreach ($this->pending_attachments as $path) {
            if (is_readable($path)) {
                $phpmailer->addAttachment($path);
            }
        }
    }

    /**
     * Get email template
     */
    private function get_template($template_name, $vars) {
        extract($vars);

        $template_file = DL_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';

        if ($template_name === 'purchase-confirmation-plain') {
            $template_file = DL_PLUGIN_DIR . 'templates/emails/' . $template_name . '.txt';
            if (file_exists($template_file)) {
                $content = file_get_contents($template_file);
                foreach ($vars as $key => $value) {
                    if (is_string($value)) {
                        $content = str_replace('{' . $key . '}', $value, $content);
                    }
                }
                return $content;
            }
        }

        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }

        return '';
    }

    private function get_html_headers() {
        return array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->sender_name . ' <' . $this->sender_email . '>',
        );
    }

    private function get_plain_headers() {
        return array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $this->sender_name . ' <' . $this->sender_email . '>',
        );
    }

    private function get_dashboard_url() {
        $page_ids = get_option('dl_page_ids');
        return get_permalink($page_ids['dashboard']);
    }
}
