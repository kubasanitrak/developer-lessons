<?php
/**
 * Basket Functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Basket {

    public function __construct() {
        add_action('wp_ajax_dl_add_to_basket', array($this, 'ajax_add_to_basket'));
        add_action('wp_ajax_dl_remove_from_basket', array($this, 'ajax_remove_from_basket'));
        add_action('wp_ajax_dl_get_basket', array($this, 'ajax_get_basket'));
        add_action('wp_ajax_dl_get_basket_count', array($this, 'ajax_get_basket_count'));
    }

    /**
     * Add item to basket
     */
    public function add_item($lesson_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$lesson_id) {
            return false;
        }

        // Check if lesson exists and is published
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson' || $lesson->post_status !== 'publish') {
            return false;
        }

        // Check if already purchased
        $access_control = new DL_Access_Control();
        if ($access_control->user_has_purchased($lesson_id, $user_id)) {
            return array('error' => 'already_purchased');
        }

        // Check if already in basket
        if ($this->is_in_basket($lesson_id, $user_id)) {
            return array('error' => 'already_in_basket');
        }

        $basket_table = $wpdb->prefix . 'dl_basket';

        $result = $wpdb->insert(
            $basket_table,
            array(
                'user_id' => $user_id,
                'lesson_id' => $lesson_id,
                'added_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );

        return $result !== false;
    }

    /**
     * Remove item from basket
     */
    public function remove_item($lesson_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $basket_table = $wpdb->prefix . 'dl_basket';

        return $wpdb->delete(
            $basket_table,
            array(
                'user_id' => $user_id,
                'lesson_id' => $lesson_id
            ),
            array('%d', '%d')
        );
    }

    /**
     * Get basket items
     */
    public function get_items($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $basket_table = $wpdb->prefix . 'dl_basket';

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, p.post_title as lesson_title 
             FROM $basket_table b 
             LEFT JOIN {$wpdb->posts} p ON b.lesson_id = p.ID 
             WHERE b.user_id = %d 
             ORDER BY b.added_at DESC",
            $user_id
        ));

        foreach ($items as &$item) {
            $item->price = get_post_meta($item->lesson_id, '_dl_price', true);
            $item->permalink = get_permalink($item->lesson_id);
        }

        return $items;
    }

    /**
     * Get basket count
     */
    public function get_count($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $basket_table = $wpdb->prefix . 'dl_basket';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $basket_table WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Get basket total
     */
    public function get_total($user_id = null) {
        $items = $this->get_items($user_id);
        $total = 0;

        foreach ($items as $item) {
            $total += floatval($item->price);
        }

        return $total;
    }

    /**
     * Calculate discount based on item count
     */
    public function calculate_discount($user_id = null) {
        $count = $this->get_count($user_id);
        $total = $this->get_total($user_id);
        
        $discount_5 = get_option('dl_bundle_5_discount', 10);
        $discount_10 = get_option('dl_bundle_10_discount', 20);

        if ($count >= 10) {
            return array(
                'percentage' => $discount_10,
                'amount' => $total * ($discount_10 / 100)
            );
        } elseif ($count >= 5) {
            return array(
                'percentage' => $discount_5,
                'amount' => $total * ($discount_5 / 100)
            );
        }

        return array(
            'percentage' => 0,
            'amount' => 0
        );
    }

    /**
     * Get final total after discount
     */
    public function get_final_total($user_id = null) {
        $total = $this->get_total($user_id);
        $discount = $this->calculate_discount($user_id);

        return $total - $discount['amount'];
    }

    /**
     * Check if item is in basket
     */
    public function is_in_basket($lesson_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $basket_table = $wpdb->prefix . 'dl_basket';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $basket_table WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        ));

        return !empty($exists);
    }

    /**
     * Clear basket
     */
    public function clear($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $basket_table = $wpdb->prefix . 'dl_basket';

        return $wpdb->delete(
            $basket_table,
            array('user_id' => $user_id),
            array('%d')
        );
    }

    /**
     * AJAX: Add to basket
     */
    public function ajax_add_to_basket() {
        check_ajax_referer('dl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in to add items to basket.', 'developer-lessons')));
        }

        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;

        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid lesson.', 'developer-lessons')));
        }

        $result = $this->add_item($lesson_id);

        if (is_array($result) && isset($result['error'])) {
            if ($result['error'] === 'already_purchased') {
                wp_send_json_error(array('message' => __('You have already purchased this lesson.', 'developer-lessons')));
            } elseif ($result['error'] === 'already_in_basket') {
                wp_send_json_error(array('message' => __('This lesson is already in your basket.', 'developer-lessons')));
            }
        }

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Lesson added to basket!', 'developer-lessons'),
                'count' => $this->get_count()
            ));
        }

        wp_send_json_error(array('message' => __('Failed to add lesson to basket.', 'developer-lessons')));
    }

    /**
     * AJAX: Remove from basket
     */
    public function ajax_remove_from_basket() {
        check_ajax_referer('dl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'developer-lessons')));
        }

        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;

        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid lesson.', 'developer-lessons')));
        }

        if ($this->remove_item($lesson_id)) {
            wp_send_json_success(array(
                'message' => __('Item removed from basket.', 'developer-lessons'),
                'count' => $this->get_count()
            ));
        }

        wp_send_json_error(array('message' => __('Failed to remove item.', 'developer-lessons')));
    }

    /**
     * AJAX: Get basket
     */
    public function ajax_get_basket() {
        check_ajax_referer('dl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'developer-lessons')));
        }

        $items = $this->get_items();
        $discount = $this->calculate_discount();
        
        wp_send_json_success(array(
            'items' => $items,
            'count' => count($items),
            'subtotal' => $this->get_total(),
            'discount' => $discount,
            'total' => $this->get_final_total()
        ));
    }

    /**
     * AJAX: Get basket count
     */
    public function ajax_get_basket_count() {
        check_ajax_referer('dl_nonce', 'nonce');

        wp_send_json_success(array(
            'count' => is_user_logged_in() ? $this->get_count() : 0
        ));
    }
}
