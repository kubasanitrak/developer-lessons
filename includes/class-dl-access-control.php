<?php
/**
 * Access Control
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Access_Control {

    public function __construct() {
        add_action('template_redirect', array($this, 'check_lesson_access'));
        add_filter('the_content', array($this, 'filter_lesson_content'), 20);
        
        // Add shortcodes
        add_shortcode('dl_buy_button', array($this, 'render_buy_button_shortcode'));
        add_shortcode('dl_lesson_price', array($this, 'render_price_shortcode'));
    }

    /**
     * Check access to lesson pages
     */
    public function check_lesson_access() {
        if (!is_singular('lesson')) {
            return;
        }

        // Redirect non-logged-in users to landing page
        if (!is_user_logged_in()) {
            $landing_page = get_option('dl_landing_page');
            
            if ($landing_page) {
                $redirect_url = get_permalink($landing_page);
            } else {
                $redirect_url = wp_login_url(get_permalink());
            }
            
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Filter lesson content based on access
     */
    public function filter_lesson_content($content) {
        if (!is_singular('lesson') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        global $post;

        // Check if user has access
        if ($this->user_has_access($post->ID)) {
            return $content;
        }

        // Return teaser content with CTA
        return $this->get_teaser_with_cta($post);
    }

    /**
     * Check if current user has access to lesson
     */
    public function user_has_access($lesson_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Admins have full access
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check if lesson is free
        $access_type = get_post_meta($lesson_id, '_dl_access_type', true);
        if ($access_type === 'free') {
            return true;
        }

        // Check if user has purchased
        return $this->user_has_purchased($lesson_id, $user_id);
    }

    /**
     * Check if user has purchased lesson
     */
    public function user_has_purchased($lesson_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $purchases_table = $wpdb->prefix . 'dl_purchases';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$purchases_table'") !== $purchases_table) {
            return false;
        }

        $purchased = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $purchases_table WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        ));

        return !empty($purchased);
    }

    /**
     * Get teaser content with CTA
     */
    private function get_teaser_with_cta($post) {
        $teaser_content = get_post_meta($post->ID, '_dl_teaser_content', true);
        $price = get_post_meta($post->ID, '_dl_price', true);
        $formatted_price = DL_Payments::format_price($price);

        ob_start();
        ?>
        <div class="dl-lesson-teaser">
            <?php if (!empty($teaser_content)): ?>
                <div class="dl-teaser-content">
                    <?php echo wpautop($teaser_content); ?>
                </div>
            <?php endif; ?>
            
            <?php echo $this->get_cta_box($post->ID, $formatted_price); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get CTA box HTML
     */
    public function get_cta_box($lesson_id, $formatted_price = null) {
        if ($formatted_price === null) {
            $price = get_post_meta($lesson_id, '_dl_price', true);
            $formatted_price = DL_Payments::format_price($price);
        }

        $basket = new DL_Basket();
        $in_basket = $basket->is_in_basket($lesson_id);
        $purchased = $this->user_has_purchased($lesson_id);

        ob_start();
        ?>
        <div class="dl-lesson-cta">
            <div class="dl-cta-box">
                <?php if ($purchased): ?>
                    <p class="dl-purchased-notice">✓ <?php _e('You own this lesson', 'developer-lessons'); ?></p>
                <?php else: ?>
                    <h3><?php _e('Get Full Access to This Lesson', 'developer-lessons'); ?></h3>
                    <p class="dl-price"><?php echo esc_html($formatted_price); ?></p>
                    
                    <?php if ($in_basket): ?>
                        <button type="button" class="dl-btn dl-btn-secondary dl-view-basket-btn">
                            <?php _e('View Basket', 'developer-lessons'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" 
                                class="dl-add-to-basket-btn" 
                                data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                            <?php _e('Add to Basket', 'developer-lessons'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <p class="dl-cta-note">
                        <?php _e('Instant access after payment', 'developer-lessons'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Buy button
     * Usage: [dl_buy_button] or [dl_buy_button id="123"]
     */
    public function render_buy_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
        ), $atts);

        $lesson_id = intval($atts['id']);
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        // Check if free
        $access_type = get_post_meta($lesson_id, '_dl_access_type', true);
        if ($access_type === 'free') {
            return '<p class="dl-free-access">' . __('This lesson is free for registered users.', 'developer-lessons') . '</p>';
        }

        return $this->get_cta_box($lesson_id);
    }

    /**
     * Shortcode: Lesson price
     * Usage: [dl_lesson_price] or [dl_lesson_price id="123"]
     */
    public function render_price_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
        ), $atts);

        $lesson_id = intval($atts['id']);
        
        if (!$lesson_id) {
            return '';
        }

        $access_type = get_post_meta($lesson_id, '_dl_access_type', true);
        
        if ($access_type === 'free') {
            return '<span class="dl-price dl-price-free">' . __('Free', 'developer-lessons') . '</span>';
        }

        $price = get_post_meta($lesson_id, '_dl_price', true);
        return '<span class="dl-price">' . DL_Payments::format_price($price) . '</span>';
    }

    /**
     * Get user's purchased lesson IDs
     */
    public static function get_user_purchased_lessons($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $purchases_table = $wpdb->prefix . 'dl_purchases';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$purchases_table'") !== $purchases_table) {
            return array();
        }

        return $wpdb->get_col($wpdb->prepare(
            "SELECT lesson_id FROM $purchases_table WHERE user_id = %d",
            $user_id
        ));
    }
}
