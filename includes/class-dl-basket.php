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
        
        // Add all lessons to basket
        add_action('wp_ajax_dl_add_all_to_basket', array($this, 'ajax_add_all_to_basket'));
        
        // Shortcodes
        add_shortcode('dl_buy_all_lessons', array($this, 'render_buy_all_shortcode'));
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
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$basket_table'") !== $basket_table) {
            return 0;
        }

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
     * Get all purchasable lessons for a user (not free, not already purchased)
     */
    public function get_purchasable_lessons($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $access_control = new DL_Access_Control();
        $purchased_lessons = DL_Access_Control::get_user_purchased_lessons($user_id);

        // Get all published paid lessons
        $args = array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_dl_access_type',
                    'value' => 'paid',
                    'compare' => '='
                ),
                array(
                    'key' => '_dl_price',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC'
                )
            ),
            'fields' => 'ids'
        );

        // Exclude already purchased
        if (!empty($purchased_lessons)) {
            $args['post__not_in'] = $purchased_lessons;
        }

        $lessons = get_posts($args);

        return $lessons;
    }

    /**
     * Get summary of all purchasable lessons
     */
    public function get_all_lessons_summary($user_id = null) {
        $lessons = $this->get_purchasable_lessons($user_id);
        
        $total = 0;
        $count = count($lessons);
        
        foreach ($lessons as $lesson_id) {
            $price = get_post_meta($lesson_id, '_dl_price', true);
            $total += floatval($price);
        }

        // Calculate discount
        $discount_5 = get_option('dl_bundle_5_discount', 10);
        $discount_10 = get_option('dl_bundle_10_discount', 20);

        if ($count >= 10) {
            $discount_percentage = $discount_10;
        } elseif ($count >= 5) {
            $discount_percentage = $discount_5;
        } else {
            $discount_percentage = 0;
        }

        $discount_amount = $total * ($discount_percentage / 100);
        $final_total = $total - $discount_amount;

        return array(
            'lessons' => $lessons,
            'count' => $count,
            'subtotal' => $total,
            'discount_percentage' => $discount_percentage,
            'discount_amount' => $discount_amount,
            'total' => $final_total
        );
    }

    /**
     * Add all purchasable lessons to basket
     */
    public function add_all_lessons($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $lessons = $this->get_purchasable_lessons($user_id);
        $added = 0;
        $skipped = 0;

        foreach ($lessons as $lesson_id) {
            // Skip if already in basket
            if ($this->is_in_basket($lesson_id, $user_id)) {
                $skipped++;
                continue;
            }

            $result = $this->add_item($lesson_id, $user_id);
            
            if ($result === true) {
                $added++;
            } else {
                $skipped++;
            }
        }

        return array(
            'added' => $added,
            'skipped' => $skipped,
            'total' => count($lessons)
        );
    }

    /**
     * AJAX: Add all lessons to basket
     */
    public function ajax_add_all_to_basket() {
        check_ajax_referer('dl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in to add items to basket.', 'developer-lessons')));
        }

        $result = $this->add_all_lessons();

        if ($result['added'] > 0) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('%d lessons added to basket!', 'developer-lessons'),
                    $result['added']
                ),
                'added' => $result['added'],
                'skipped' => $result['skipped'],
                'count' => $this->get_count()
            ));
        } elseif ($result['skipped'] > 0) {
            wp_send_json_error(array(
                'message' => __('All available lessons are already in your basket.', 'developer-lessons')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No lessons available to purchase.', 'developer-lessons')
            ));
        }
    }

    /**
     * Shortcode: Buy All Lessons Button
     * Usage: [dl_buy_all_lessons]
     * Attributes: 
     *   - show_count: true/false (default: true)
     *   - show_discount: true/false (default: true)
     *   - button_text: custom button text
     *   - class: additional CSS classes
     */
    public function render_buy_all_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_count' => 'true',
            'show_discount' => 'true',
            'button_text' => '',
            'class' => '',
        ), $atts);

        // Not logged in message
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(get_permalink());
            return '<div class="dl-buy-all-box dl-login-required">
                <p>' . __('Please log in to purchase lessons.', 'developer-lessons') . '</p>
                <a href="' . esc_url($login_url) . '" class="dl-btn">' . __('Log In', 'developer-lessons') . '</a>
            </div>';
        }

        $summary = $this->get_all_lessons_summary();

        // No lessons available
        if ($summary['count'] === 0) {
            return '<div class="dl-buy-all-box dl-all-purchased">
                <p>✓ ' . __('You already own all available lessons!', 'developer-lessons') . '</p>
            </div>';
        }

        // Build the box
        $show_count = filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN);
        $show_discount = filter_var($atts['show_discount'], FILTER_VALIDATE_BOOLEAN);
        $button_text = !empty($atts['button_text']) 
            ? $atts['button_text'] 
            : sprintf(__('Add All %d Lessons to Basket', 'developer-lessons'), $summary['count']);
        $extra_class = !empty($atts['class']) ? ' ' . esc_attr($atts['class']) : '';

        ob_start();
        ?>
        <div class="dl-buy-all-box<?php echo $extra_class; ?>">
            <div class="dl-buy-all-header">
                <h3><?php _e('Get All Lessons', 'developer-lessons'); ?></h3>
                <?php if ($show_count): ?>
                    <span class="dl-lesson-count"><?php printf(__('%d lessons', 'developer-lessons'), $summary['count']); ?></span>
                <?php endif; ?>
            </div>

            <div class="dl-buy-all-pricing">
                <?php if ($show_discount && $summary['discount_percentage'] > 0): ?>
                    <div class="dl-original-price">
                        <span class="dl-strikethrough"><?php echo DL_Payments::format_price($summary['subtotal']); ?></span>
                        <span class="dl-discount-badge">-<?php echo $summary['discount_percentage']; ?>%</span>
                    </div>
                <?php endif; ?>
                
                <div class="dl-final-price">
                    <?php echo DL_Payments::format_price($summary['total']); ?>
                </div>

                <?php if ($show_discount && $summary['discount_percentage'] > 0): ?>
                    <p class="dl-savings">
                        <?php printf(
                            __('You save %s with bundle discount!', 'developer-lessons'),
                            DL_Payments::format_price($summary['discount_amount'])
                        ); ?>
                    </p>
                <?php elseif ($show_discount && $summary['count'] >= 3): ?>
                    <p class="dl-discount-hint">
                        <?php 
                        $needed_for_5 = 5 - $summary['count'];
                        $needed_for_10 = 10 - $summary['count'];
                        
                        if ($needed_for_5 > 0 && $needed_for_5 <= 2) {
                            $discount_5 = get_option('dl_bundle_5_discount', 10);
                            printf(__('Add %d more for %d%% bundle discount!', 'developer-lessons'), $needed_for_5, $discount_5);
                        }
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <button type="button" class="dl-btn dl-btn-primary dl-btn-large dl-add-all-btn">
                <?php echo esc_html($button_text); ?>
            </button>

            <p class="dl-buy-all-note">
                <?php _e('Instant access to all lessons after payment', 'developer-lessons'); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
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
