<?php
/**
 * Cron Jobs
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Cron {

    public function __construct() {
        add_action('dl_hourly_cron', array($this, 'hourly_tasks'));
        add_action('dl_daily_cron', array($this, 'daily_tasks'));
    }

    /**
     * Hourly tasks
     */
    public function hourly_tasks() {
        $this->check_expiring_orders();
        $this->expire_old_orders();
    }

    /**
     * Daily tasks
     */
    public function daily_tasks() {
        $this->cleanup_old_baskets();
        $this->cleanup_old_logs();
    }

    /**
     * Check for expiring orders and send notifications
     */
    private function check_expiring_orders() {
        if (!get_option('dl_order_expiry_notification')) {
            return;
        }

        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';

        // Get orders expiring in the next 2 hours
        $expiring_orders = $wpdb->get_results(
            "SELECT * FROM $orders_table 
             WHERE status = 'awaiting_payment' 
             AND expires_at IS NOT NULL 
             AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
             AND id NOT IN (
                SELECT DISTINCT JSON_EXTRACT(data, '$.order_id') 
                FROM {$wpdb->prefix}dl_logs 
                WHERE type = 'expiry_notification_sent'
             )"
        );

        $emails = new DL_Emails();

        foreach ($expiring_orders as $order) {
            $emails->send_expiry_notification($order->id);

            DL_Payments::log('expiry_notification_sent', "Expiry notification sent for order #{$order->order_number}", array(
                'order_id' => $order->id
            ));
        }
    }

    /**
     * Expire old pending orders
     */
    private function expire_old_orders() {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';

        $wpdb->query(
            "UPDATE $orders_table 
             SET status = 'expired', updated_at = NOW() 
             WHERE status IN ('pending', 'awaiting_payment') 
             AND expires_at IS NOT NULL 
             AND expires_at < NOW()"
        );
    }

    /**
     * Cleanup old basket items
     */
    private function cleanup_old_baskets() {
        global $wpdb;

        $basket_table = $wpdb->prefix . 'dl_basket';
        $cleanup_hours = get_option('dl_basket_cleanup_time', 72);

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $basket_table WHERE added_at < DATE_SUB(NOW(), INTERVAL %d HOUR)",
            $cleanup_hours
        ));
    }

    /**
     * Cleanup old logs
     */
    private function cleanup_old_logs() {
        global $wpdb;

        $logs_table = $wpdb->prefix . 'dl_logs';

        // Keep logs for 90 days
        $wpdb->query(
            "DELETE FROM $logs_table WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
    }

    /**
     * Get cron status
     */
    public static function get_cron_status() {
        return array(
            'hourly' => array(
                'hook' => 'dl_hourly_cron',
                'next_run' => wp_next_scheduled('dl_hourly_cron'),
                'next_run_formatted' => wp_next_scheduled('dl_hourly_cron') 
                    ? date('Y-m-d H:i:s', wp_next_scheduled('dl_hourly_cron')) 
                    : __('Not scheduled', 'developer-lessons')
            ),
            'daily' => array(
                'hook' => 'dl_daily_cron',
                'next_run' => wp_next_scheduled('dl_daily_cron'),
                'next_run_formatted' => wp_next_scheduled('dl_daily_cron') 
                    ? date('Y-m-d H:i:s', wp_next_scheduled('dl_daily_cron')) 
                    : __('Not scheduled', 'developer-lessons')
            )
        );
    }
}
