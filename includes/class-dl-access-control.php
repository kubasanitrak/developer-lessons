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
        add_shortcode('dl_lessons_grid', array($this, 'render_lessons_grid_shortcode'));
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

        // Return teaser content with CTA and lessons grid
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
     * Get teaser content with CTA and other lessons
     */
    private function get_teaser_with_cta($post) {
        $teaser_content = get_post_meta($post->ID, '_dl_teaser_content', true);
        $price = get_post_meta($post->ID, '_dl_price', true);
        $formatted_price = DL_Payments::format_price($price);

        $POST_TITLE = esc_html( get_the_title() );

        ob_start();
        ?>

            <!-- LESSON TITLE -->
            <div class="lesson-title--container">
                <h1 class=""><?php echo $POST_TITLE; ?> </h1>
            </div>
            <!-- IMAGE -->
            <div class="dl-teaser-content--img lesson-img">
                <?php the_post_thumbnail( 'full', ['class' => 'grid-item--img', 'title' => $POST_TITLE, 'alt' => $POST_TITLE ] ); ?>
            </div>
            <!-- CAPTION = TEASER CONTENT -->
            <div class="dl-teaser-content lesson-caption">
                <?php if (!empty($teaser_content)): ?>
                    <div class="dl-teaser-content">
                        <?php echo wpautop($teaser_content); ?>
                    </div>
                <?php endif; ?>
                
                <?php echo $this->get_cta_box($post->ID, $formatted_price); ?>

            </div>

            <!-- GRID Other Available Lessons -->
            <?php echo $this->render_other_lessons_grid($post->ID); ?>
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
     * Render grid of other available lessons
     */
    public function render_other_lessons_grid($current_lesson_id = 0) {
        $user_id = get_current_user_id();
        
        // Get purchased lessons to exclude
        $purchased_lessons = self::get_user_purchased_lessons($user_id);
        
        // Build exclude array
        $exclude_ids = $purchased_lessons;
        if ($current_lesson_id) {
            $exclude_ids[] = $current_lesson_id;
        }

        // Query other paid lessons
        $args = array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'post__not_in' => $exclude_ids,
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
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $lessons = new WP_Query($args);

        if (!$lessons->have_posts()) {
            return '';
        }

        $basket = new DL_Basket();

        ob_start();
        ?>
        <!-- <div class="lesson-content--container mar-T"> -->
            <div class="section section-lesson_grid full-bleed scroll-trigger scroll-trigger--grid">
                <div class="section-lesson_grid--title">
                    <h1 class="dl-other-lessons-title"><?php _e('Other Available Lessons', 'developer-lessons'); ?></h1>
                </div>
                <div class="dl-other-lessons list-grid list-grid--lessons">
                        <?php while ($lessons->have_posts()): $lessons->the_post(); 
                            $post_id = get_the_ID();
                            $post_title = get_the_title();
                            $price = get_post_meta($post_id, '_dl_price', true);
                            $in_basket = $basket->is_in_basket($post_id);
                            
                            // Get image - try ACF first, then fallback to custom meta, then featured image
                            $img_id = $this->get_lesson_image_id($post_id);
                        ?>
                            <div class="grid-item">
                                <div class="grid-item--img_container">
                                    <?php if ($img_id): 
                                        $img_src = wp_get_attachment_image_url($img_id, 'medium');
                                        $img_srcset = wp_get_attachment_image_srcset($img_id, 'full');
                                    ?>
                                        <img class="grid-item--img lazyload" 
                                             data-srcset="<?php echo esc_attr($img_srcset); ?>" 
                                             data-src="<?php echo esc_url($img_src); ?>" 
                                             src="<?php echo esc_url($img_src); ?>"
                                             data-sizes="auto" 
                                             alt="<?php echo esc_attr($post_title); ?>" 
                                             title="<?php echo esc_attr($post_title); ?>">
                                    <?php else: ?>
                                        <div class="grid-item--img grid-item--img-placeholder">
                                            <span class="dashicons dashicons-welcome-learn-more"></span>
                                        </div>
                                    <?php endif; ?>

                                </div>
                                
                                <div class="grid-item--label">
                                    <h4 class="grid-item--title"><?php echo esc_html($post_title); ?></h4>
                                    <!-- <span class="grid-item--price"><?php #echo DL_Payments::format_price($price); ?></span> -->
                                </div>
                                
                                <a href="<?php the_permalink(); ?>" class="abs-link grid-item--title_link"></a>
                                    
                                <?php if ($in_basket): ?>
                                    <button type="button" 
                                            class="dl-btn dl-btn-secondary dl-view-basket-btn grid-item--btn">
                                        <?php _e('In Basket', 'developer-lessons'); ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button" 
                                            class="dl-add-to-basket-btn grid-item--btn" 
                                            data-lesson-id="<?php echo esc_attr($post_id); ?>">
                                        <?php _e('Add to Basket', 'developer-lessons'); ?>
                                    </button>
                                <?php endif; ?>
                                
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <!-- </div> -->
        <!-- </div> -->
        <?php
        wp_reset_postdata();
        
        return ob_get_clean();
    }

    /**
     * Get lesson image ID - tries ACF, then custom meta, then featured image
     */
    private function get_lesson_image_id($post_id) {
        $img_id = null;
        
        // Try ACF field first (if ACF is active)
        if (function_exists('get_field')) {
            $img_id = get_field('lesson_tile_img_id', $post_id);
        }
        
        // Fallback to custom meta field
        if (!$img_id) {
            $img_id = get_post_meta($post_id, '_dl_tile_image_id', true);
        }
        
        // Fallback to featured image
        if (!$img_id) {
            $img_id = get_post_thumbnail_id($post_id);
        }
        
        return $img_id ? intval($img_id) : null;
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
     * Shortcode: Lessons grid
     * Usage: [dl_lessons_grid] or [dl_lessons_grid exclude_current="true" count="8"]
     */
    public function render_lessons_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'exclude_current' => 'true',
            'count' => 12,
            'exclude_purchased' => 'true',
        ), $atts);

        $current_id = filter_var($atts['exclude_current'], FILTER_VALIDATE_BOOLEAN) ? get_the_ID() : 0;
        
        return $this->render_lessons_grid($current_id, intval($atts['count']), filter_var($atts['exclude_purchased'], FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * Render lessons grid (reusable)
     */
    public function render_lessons_grid($exclude_id = 0, $count = 12, $exclude_purchased = true) {
        $user_id = get_current_user_id();
        
        // Build exclude array
        $exclude_ids = array();
        
        if ($exclude_id) {
            $exclude_ids[] = $exclude_id;
        }
        
        if ($exclude_purchased && $user_id) {
            $purchased_lessons = self::get_user_purchased_lessons($user_id);
            $exclude_ids = array_merge($exclude_ids, $purchased_lessons);
        }

        // Query lessons
        $args = array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'posts_per_page' => $count,
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
            'orderby' => 'date',
            'order' => 'DESC'
        );

        if (!empty($exclude_ids)) {
            $args['post__not_in'] = $exclude_ids;
        }

        $lessons = new WP_Query($args);

        if (!$lessons->have_posts()) {
            return '<p class="dl-no-lessons">' . __('No lessons available.', 'developer-lessons') . '</p>';
        }

        $basket = new DL_Basket();

        ob_start();
        ?>
        <div class="list-grid list-grid--lessons">
            <?php while ($lessons->have_posts()): $lessons->the_post(); 
                $post_id = get_the_ID();
                $post_title = get_the_title();
                $price = get_post_meta($post_id, '_dl_price', true);
                $in_basket = is_user_logged_in() ? $basket->is_in_basket($post_id) : false;
                
                // Get image
                $img_id = $this->get_lesson_image_id($post_id);
            ?>
                <div class="grid-item">
                    <div class="grid-item--img_container">
                        <?php if ($img_id): 
                            $img_src = wp_get_attachment_image_url($img_id, 'medium');
                            $img_srcset = wp_get_attachment_image_srcset($img_id, 'full');
                        ?>
                            <img class="grid-item--img lazyload" 
                                 data-srcset="<?php echo esc_attr($img_srcset); ?>" 
                                 data-src="<?php echo esc_url($img_src); ?>" 
                                 src="<?php echo esc_url($img_src); ?>"
                                 data-sizes="auto" 
                                 alt="<?php echo esc_attr($post_title); ?>" 
                                 title="<?php echo esc_attr($post_title); ?>">
                        <?php else: ?>
                            <div class="grid-item--img grid-item--img-placeholder">
                                <span class="dashicons dashicons-welcome-learn-more"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid-item--label">
                        <h4 class="grid-item--title"><?php echo esc_html($post_title); ?></h4>
                        <span class="grid-item--price"><?php echo DL_Payments::format_price($price); ?></span>
                    </div>
                    
                    <a href="<?php the_permalink(); ?>" class="abs-link grid-item--title_link"></a>
                    
                    <?php if ($in_basket): ?>
                        <button type="button" 
                                class="dl-btn dl-btn-secondary dl-view-basket-btn grid-item--btn">
                            <?php _e('In Basket', 'developer-lessons'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" 
                                class="dl-add-to-basket-btn grid-item--btn" 
                                data-lesson-id="<?php echo esc_attr($post_id); ?>">
                            <?php _e('Add to Basket', 'developer-lessons'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        
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

        if (!$user_id) {
            return array();
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
