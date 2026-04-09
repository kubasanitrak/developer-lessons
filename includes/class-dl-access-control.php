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
        $currency_symbol = get_option('dl_currency_symbol', 'Kč');
        $currency_position = get_option('dl_currency_position', 'after');

        $POST_TITLE = esc_html( get_the_title() );

        if ($currency_position === 'before') {
            $formatted_price = $currency_symbol . ' ' . number_format((float)$price, 2);
        } else {
            $formatted_price = number_format((float)$price, 2) . ' ' . $currency_symbol;
        }

        ob_start();
        ?>
        <!-- <div class="dl-lesson-teaser"> -->
            <div class="lesson-title--container">
                <!-- <h1 class=""><?php #echo get_field('lesson_title_1st_line', $post->ID); ?> <br> <span class="color-green"><?php #echo get_field('lesson_title_2nd_line', $post->ID); ?></span> </h1> -->
                <h1 class=""><?php echo $POST_TITLE; ?> </h1>
            </div>
            <div class="dl-teaser-content--img lesson-img">
                <?php the_post_thumbnail( 'full', ['class' => 'grid-item--img', 'title' => $POST_TITLE, 'alt' => $POST_TITLE ] ); ?>
            </div>
            <div class="dl-teaser-content lesson-caption">
                <?php echo wpautop($teaser_content); ?>
            
                <div class="dl-lesson-cta">
                    <div class="dl-cta-overlay">
                        <div class="dl-cta-box">
                            <h3><?php _e('Get Full Access to This Lesson', 'developer-lessons'); ?></h3>
                            <p class="dl-price"><?php echo esc_html($formatted_price); ?></p>
                            <button type="button" 
                                    class="dl-add-to-basket-btn" 
                                    data-lesson-id="<?php echo esc_attr($post->ID); ?>">
                                <?php _e('Add to Basket', 'developer-lessons'); ?>
                            </button>
                            <p class="dl-cta-note">
                                <?php _e('Instant access after payment', 'developer-lessons'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <!-- </div> -->
         <h1 class=""><?php echo get_field('lesson_title_1st_line'); ?> <br> <span class="color-green"><?php echo get_field('lesson_title_2nd_line'); ?></span> </h1>
        <?php
        return ob_get_clean();
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

        return $wpdb->get_col($wpdb->prepare(
            "SELECT lesson_id FROM $purchases_table WHERE user_id = %d",
            $user_id
        ));
    }
}
