<?php
/**
 * User Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_User {

    public function __construct() {
        add_shortcode('dl_dashboard', array($this, 'render_dashboard'));
    }

    /**
     * Render user dashboard
     */
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your lessons.', 'developer-lessons') . '</p>';
        }

        $user_id = get_current_user_id();
        $purchased_lessons = $this->get_purchased_lessons($user_id);
        $pending_orders = $this->get_pending_orders($user_id);

        ob_start();
        include DL_PLUGIN_DIR . 'public/partials/dashboard-page.php';
        return ob_get_clean();
    }

    /**
     * Get user's purchased lessons
     */
    public function get_purchased_lessons($user_id) {
        global $wpdb;

        $purchases_table = $wpdb->prefix . 'dl_purchases';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, post.post_title as lesson_title 
             FROM $purchases_table p
             LEFT JOIN {$wpdb->posts} post ON p.lesson_id = post.ID
             WHERE p.user_id = %d
             ORDER BY p.purchased_at DESC",
            $user_id
        ));
    }

    /**
     * Get user's pending orders
     */
    public function get_pending_orders($user_id) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $orders_table 
             WHERE user_id = %d AND status IN ('pending', 'awaiting_payment', 'processing')
             ORDER BY created_at DESC",
            $user_id
        ));
    }

    /**
     * Get user's order history
     */
    public function get_order_history($user_id) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $orders_table 
             WHERE user_id = %d
             ORDER BY created_at DESC",
            $user_id
        ));
    }
}
