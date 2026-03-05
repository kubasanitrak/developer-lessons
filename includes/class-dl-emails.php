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

    public function __construct() {
        $this->sender_name = get_option('dl_email_sender_name', get_bloginfo('name'));
        $this->sender_email = get_option('dl_email_sender_email', get_option('admin_email'));
        $this->template_type = get_option('dl_email_template_type', 'html');
    }

    /**
     * Send purchase confirmation to customer
     */
    public function send_purchase_confirmation($order_id) {
        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            return false;
        }

        $user = get_user_by('id', $order->user_id);
        $to = $user->user_email;
        $subject = sprintf(__('Your Purchase Confirmation - Order %s', 'developer-lessons'), $order->order_number);

        $template_vars = array(
            'order' => $order,
            'user' => $user,
            'site_name' => get_bloginfo('name'),
            'dashboard_url' => $this->get_dashboard_url()
        );

        if ($this->template_type === 'html') {
            $message = $this->get_template('purchase-confirmation', $template_vars);
            $headers = $this->get_html_headers();
        } else {
            $message = $this->get_template('purchase-confirmation-plain', $template_vars);
            $headers = $this->get_plain_headers();
        }

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Send admin notification
     */
    public function send_admin_notification($order_id) {
        $order = DL_Checkout::get_order($order_id);

        if (!$order) {
            return false;
        }

        $user = get_user_by('id', $order->user_id);
        $to = get_option('dl_admin_notification_email', get_option('admin_email'));
        $subject = sprintf(__('New Purchase - Order %s', 'developer-lessons'), $order->order_number);

        $template_vars = array(
            'order' => $order,
            'user' => $user,
            'site_name' => get_bloginfo('name')
        );

        $message = $this->get_template('admin-notification', $template_vars);
        $headers = $this->get_html_headers();

        return wp_mail($to, $subject, $message, $headers);
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
            'checkout_url' => $checkout_url
        );

        $message = $this->get_template('order-expiry', $template_vars);
        $headers = $this->get_html_headers();

        return wp_mail($to, $subject, $message, $headers);
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
                
                // Simple variable replacement
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

    /**
     * Get HTML email headers
     */
    private function get_html_headers() {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->sender_name . ' <' . $this->sender_email . '>'
        );

        return $headers;
    }

    /**
     * Get plain text email headers
     */
    private function get_plain_headers() {
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $this->sender_name . ' <' . $this->sender_email . '>'
        );

        return $headers;
    }

    /**
     * Get dashboard URL
     */
    private function get_dashboard_url() {
        $page_ids = get_option('dl_page_ids');
        return get_permalink($page_ids['dashboard']);
    }
}
