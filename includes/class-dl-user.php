<?php
/**
 * User Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_User {

    const DASHBOARD_LESSONS_VIEW_META = 'dl_dashboard_lessons_view';

    public function __construct() {
        add_shortcode('dl_dashboard', array($this, 'render_dashboard'));
        add_action('wp_ajax_dl_save_dashboard_lessons_view', array($this, 'ajax_save_dashboard_lessons_view'));
    }

    /**
     * Render user dashboard
     */
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<p class="plain mar-block-E_2">' . __('Please log in to view your lessons.', 'developer-lessons') . '</p>';
        }

        $user_id = get_current_user_id();
        $purchased_lessons = $this->get_purchased_lessons($user_id);
        $pending_orders = $this->get_pending_orders($user_id);
        $lessons_view = $this->get_dashboard_lessons_view($user_id);

        ob_start();
        include DL_PLUGIN_DIR . 'public/partials/dashboard-page.php';
        return ob_get_clean();
    }

    /**
     * Dashboard lessons overview layout: grid (default) or list.
     */
    public function get_dashboard_lessons_view($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $view = get_user_meta($user_id, self::DASHBOARD_LESSONS_VIEW_META, true);

        return in_array($view, array('grid', 'list'), true) ? $view : 'grid';
    }

    /**
     * Save dashboard lessons view preference (AJAX).
     */
    public function ajax_save_dashboard_lessons_view() {
        check_ajax_referer('dl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'developer-lessons')));
        }

        $view = isset($_POST['view']) ? sanitize_key(wp_unslash($_POST['view'])) : '';

        if (!in_array($view, array('grid', 'list'), true)) {
            wp_send_json_error(array('message' => __('Invalid view.', 'developer-lessons')));
        }

        update_user_meta(get_current_user_id(), self::DASHBOARD_LESSONS_VIEW_META, $view);

        wp_send_json_success(array('view' => $view));
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
