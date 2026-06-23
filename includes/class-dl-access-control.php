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
            return $content . $this->render_post_access_lesson_grids($post->ID);
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
                    <h2 class="dl-cta-box--headline"><?php _e('Get Full Access to This Lesson', 'developer-lessons'); ?></h2>
                    <h2 class="dl-price"><?php echo esc_html($formatted_price); ?></h2>
                    
                    <p class="dl-cta-note plain">
                        <?php _e('Instant access after payment', 'developer-lessons'); ?>
                    </p>
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
                    
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Grids below full lesson content (logged-in users with access).
     */
    public function render_post_access_lesson_grids($lesson_id) {
        $user_id = get_current_user_id();
        $output = '';

        $other_purchased_ids = $this->get_purchased_paid_lesson_ids($lesson_id, $user_id);
        if (!empty($other_purchased_ids)) {
            $output .= $this->render_lesson_grid_section(
                __('My other lessons', 'developer-lessons'),
                $this->get_paid_lessons_query_args(array(
                    'posts_per_page' => 12,
                    'post__in' => $other_purchased_ids,
                )),
                array('show_basket_buttons' => false)
            );
        }

        $output .= $this->render_lesson_grid_section(
            __('You might also like', 'developer-lessons'),
            $this->get_unpurchased_paid_lessons_query_args($lesson_id, 12, $user_id),
            array('show_basket_buttons' => true)
        );

        return $output;
    }

    /**
     * Render grid of other available lessons (teaser / no access view).
     */
    public function render_other_lessons_grid($current_lesson_id = 0) {
        return $this->render_lesson_grid_section(
            __('Other Available Lessons', 'developer-lessons'),
            $this->get_unpurchased_paid_lessons_query_args($current_lesson_id, 12, get_current_user_id()),
            array('show_basket_buttons' => true)
        );
    }

    /**
     * Base WP_Query args for published paid lessons.
     */
    private function get_paid_lessons_query_args($overrides = array()) {
        $args = array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_dl_access_type',
                    'value' => 'paid',
                    'compare' => '=',
                ),
                array(
                    'key' => '_dl_price',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC',
                ),
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        );

        return array_merge($args, $overrides);
    }

    /**
     * Query args for paid lessons the user has not purchased.
     */
    private function get_unpurchased_paid_lessons_query_args($exclude_lesson_id = 0, $count = 12, $user_id = null) {
        $exclude_ids = array();

        if ($exclude_lesson_id) {
            $exclude_ids[] = (int) $exclude_lesson_id;
        }

        if ($user_id) {
            $purchased = self::get_user_purchased_lessons($user_id);
            $exclude_ids = array_merge($exclude_ids, array_map('intval', $purchased));
        }

        $exclude_ids = array_values(array_unique(array_filter($exclude_ids)));

        $overrides = array('posts_per_page' => $count);

        if (!empty($exclude_ids)) {
            $overrides['post__not_in'] = $exclude_ids;
        }

        return $this->get_paid_lessons_query_args($overrides);
    }

    /**
     * Published paid lesson IDs the user has purchased, optionally excluding one lesson.
     */
    public function get_purchased_paid_lesson_ids($exclude_lesson_id = 0, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return array();
        }

        $purchased_ids = array_map('intval', self::get_user_purchased_lessons($user_id));

        if ($exclude_lesson_id) {
            $purchased_ids = array_values(array_diff($purchased_ids, array((int) $exclude_lesson_id)));
        }

        if (empty($purchased_ids)) {
            return array();
        }

        return get_posts($this->get_paid_lessons_query_args(array(
            'posts_per_page' => -1,
            'post__in' => $purchased_ids,
            'fields' => 'ids',
        )));
    }

    /**
     * Section wrapper + lesson grid (shared markup).
     */
    public function render_lesson_grid_section($title, $query_args, $options = array()) {
        $defaults = array(
            'show_basket_buttons' => true,
        );
        $options = wp_parse_args($options, $defaults);

        $lessons = new WP_Query($query_args);

        if (!$lessons->have_posts()) {
            wp_reset_postdata();
            return '';
        }

        $show_basket = (bool) $options['show_basket_buttons'];
        $basket = $show_basket ? new DL_Basket() : null;

        ob_start();
        ?>
        <div class="section section-lesson_grid full-bleed scroll-trigger scroll-trigger--grid">
            <div class="section-lesson_grid--title">
                <h1 class="dl-other-lessons-title"><?php echo esc_html($title); ?></h1>
            </div>
            <div class="dl-other-lessons list-grid list-grid--lessons">
                <?php while ($lessons->have_posts()) : $lessons->the_post();
                    $post_id = get_the_ID();
                    $post_title = get_the_title();
                    $in_basket = $show_basket && is_user_logged_in() ? $basket->is_in_basket($post_id) : false;
                    $img_id = $this->get_lesson_image_id($post_id);
                    $grid_item_class = 'grid-item' . (is_user_logged_in() ? '' : ' grid-item--login-required');
                    ?>
                    <div class="<?php echo esc_attr($grid_item_class); ?>">
                        <div class="grid-item--img_container">
                            <?php if ($img_id) :
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
                            <?php else : ?>
                                <div class="grid-item--img grid-item--img-placeholder" aria-hidden="true"></div>
                            <?php endif; ?>
                        </div>

                        <div class="grid-item--label">
                            <h4 class="grid-item--title"><?php echo esc_html($post_title); ?></h4>
                        </div>

                        <?php $this->render_grid_item_lesson_link($post_id); ?>

                        <?php if ($show_basket && is_user_logged_in()) : ?>
                            <?php if ($in_basket) : ?>
                                <button type="button"
                                        class="dl-btn dl-btn-secondary dl-view-basket-btn grid-item--btn">
                                    <?php _e('In Basket', 'developer-lessons'); ?>
                                </button>
                            <?php else : ?>
                                <button type="button"
                                        class="dl-add-to-basket-btn grid-item--btn"
                                        data-lesson-id="<?php echo esc_attr($post_id); ?>">
                                    <?php _e('Add to Basket', 'developer-lessons'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Dashboard "My Lessons" overview (single markup; grid/list via CSS + data-view).
     *
     * @param array $purchases Rows from DL_User::get_purchased_lessons().
     */
    public function render_dashboard_lessons_overview($purchases) {
        if (empty($purchases)) {
            return '';
        }

        ob_start();
        ?>
        <div class="dl-other-lessons list-grid list-grid--lessons dl-dashboard-lessons">
            <?php foreach ($purchases as $purchase) :
                $lesson = get_post((int) $purchase->lesson_id);
                if (!$lesson || $lesson->post_status !== 'publish') {
                    continue;
                }

                $post_id = $lesson->ID;
                $post_title = $lesson->post_title;
                $permalink = get_permalink($lesson);
                $img_id = $this->get_lesson_image_id($post_id);
                $purchased_label = sprintf(
                    __('Purchased on %s', 'developer-lessons'),
                    date_i18n(get_option('date_format'), mysql2date('U', $purchase->purchased_at))
                );
                ?>
                <div class="customtable-row grid-item dl-dashboard-lesson-item mar-T-0">
                    <div class="grid-item--img_container customtable-col customtable-col_THUMB">
                        <?php if ($img_id) :
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
                        <?php else : ?>
                            <div class="grid-item--img grid-item--img-placeholder" aria-hidden="true"></div>
                        <?php endif; ?>
                    </div>

                    <div class="customtable-col customtable-col_COURSE grid-item--label">
                        <h4 class="grid-item--title customtable-col--item"><?php echo esc_html($post_title); ?></h4>
                        <span class="customtable-col--item dl-lesson-purchased-date"><?php echo esc_html($purchased_label); ?></span>
                    </div>

                    <div class="customtable-col customtable-col_BOOK">
                        <a class="customtable-col--item_link caps" href="<?php echo esc_url($permalink); ?>">
                            <?php _e('View Lesson', 'developer-lessons'); ?>
                        </a>
                    </div>

                    <a href="<?php echo esc_url($permalink); ?>" class="abs-link grid-item--title_link" aria-label="<?php echo esc_attr($post_title); ?>"></a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Get lesson image ID - tries ACF, then custom meta, then featured image
     */
    public function get_lesson_image_id($post_id) {
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
     * Usage: [dl_lessons_grid]
     *        [dl_lessons_grid mode="purchased" title="My other lessons" show_basket="false"]
     *        [dl_lessons_grid mode="unpurchased" title="You might also like"]
     *        [dl_lessons_grid layout="section" title="Other Available Lessons"]
     */
    public function render_lessons_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'exclude_current' => 'true',
            'count' => 12,
            'exclude_purchased' => 'true',
            'mode' => 'unpurchased',
            'show_basket' => '',
            'title' => '',
            'layout' => '',
        ), $atts, 'dl_lessons_grid');

        $count = intval($atts['count']);
        $current_id = filter_var($atts['exclude_current'], FILTER_VALIDATE_BOOLEAN) ? get_the_ID() : 0;
        $user_id = get_current_user_id();
        $mode = in_array($atts['mode'], array('unpurchased', 'purchased'), true) ? $atts['mode'] : 'unpurchased';

        if ($mode === 'purchased') {
            $lesson_ids = $this->get_purchased_paid_lesson_ids($current_id, $user_id);
            if (empty($lesson_ids)) {
                return '';
            }
            $query_args = $this->get_paid_lessons_query_args(array(
                'posts_per_page' => $count,
                'post__in' => $lesson_ids,
            ));
            $default_show_basket = false;
            $default_title = __('My other lessons', 'developer-lessons');
        } else {
            $exclude_purchased = filter_var($atts['exclude_purchased'], FILTER_VALIDATE_BOOLEAN);
            if (!$exclude_purchased) {
                $overrides = array('posts_per_page' => $count);
                if ($current_id) {
                    $overrides['post__not_in'] = array((int) $current_id);
                }
                $query_args = $this->get_paid_lessons_query_args($overrides);
            } else {
                $query_args = $this->get_unpurchased_paid_lessons_query_args($current_id, $count, $user_id);
            }
            $default_show_basket = true;
            $default_title = __('Other Available Lessons', 'developer-lessons');
        }

        $show_basket = $atts['show_basket'] !== ''
            ? filter_var($atts['show_basket'], FILTER_VALIDATE_BOOLEAN)
            : $default_show_basket;

        $title = $atts['title'] !== '' ? $atts['title'] : '';
        $layout = $atts['layout'];

        if ($layout === 'section' || $layout === 'simple') {
            $use_section = ($layout === 'section');
        } else {
            $use_section = ($title !== '');
        }

        if ($use_section) {
            $section_title = $title !== '' ? $title : $default_title;
            return $this->render_lesson_grid_section(
                $section_title,
                $query_args,
                array('show_basket_buttons' => $show_basket)
            );
        }

        return $this->render_lessons_grid_from_query($query_args, $show_basket);
    }

    /**
     * Render lessons grid (simple list markup, backward compatible).
     */
    public function render_lessons_grid($exclude_id = 0, $count = 12, $exclude_purchased = true) {
        $user_id = get_current_user_id();

        if ($exclude_purchased) {
            $query_args = $this->get_unpurchased_paid_lessons_query_args($exclude_id, $count, $user_id);
        } else {
            $overrides = array('posts_per_page' => $count);
            if ($exclude_id) {
                $overrides['post__not_in'] = array((int) $exclude_id);
            }
            $query_args = $this->get_paid_lessons_query_args($overrides);
        }

        return $this->render_lessons_grid_from_query($query_args, true);
    }

    /**
     * Simple list-grid output (no section wrapper).
     */
    private function render_lessons_grid_from_query($query_args, $show_basket_buttons = true) {
        $lessons = new WP_Query($query_args);

        if (!$lessons->have_posts()) {
            wp_reset_postdata();
            return '<p class="dl-no-lessons">' . __('No lessons available.', 'developer-lessons') . '</p>';
        }

        $show_basket = (bool) $show_basket_buttons;
        $basket = $show_basket ? new DL_Basket() : null;

        ob_start();
        ?>
        <div class="list-grid list-grid--lessons">
            <?php while ($lessons->have_posts()): $lessons->the_post(); 
                $post_id = get_the_ID();
                $post_title = get_the_title();
                $price = get_post_meta($post_id, '_dl_price', true);
                $in_basket = $show_basket && is_user_logged_in() ? $basket->is_in_basket($post_id) : false;
                
                // Get image
                $img_id = $this->get_lesson_image_id($post_id);
                $grid_item_class = 'grid-item' . (is_user_logged_in() ? '' : ' grid-item--login-required');
            ?>
                <div class="<?php echo esc_attr($grid_item_class); ?>">
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
                            <div class="grid-item--img grid-item--img-placeholder" aria-hidden="true"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid-item--label">
                        <h4 class="grid-item--title"><?php echo esc_html($post_title); ?></h4>
                        <span class="grid-item--price"><?php echo DL_Payments::format_price($price); ?></span>
                    </div>
                    
                    <?php $this->render_grid_item_lesson_link($post_id); ?>
                    
                    <?php if ($show_basket && is_user_logged_in()) : ?>
                        <?php if ($in_basket) : ?>
                            <button type="button" 
                                    class="dl-btn dl-btn-secondary dl-view-basket-btn grid-item--btn">
                                <?php _e('In Basket', 'developer-lessons'); ?>
                            </button>
                        <?php else : ?>
                            <button type="button" 
                                    class="dl-add-to-basket-btn grid-item--btn" 
                                    data-lesson-id="<?php echo esc_attr($post_id); ?>">
                                <?php _e('Add to Basket', 'developer-lessons'); ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        
        return ob_get_clean();
    }

    /**
     * Grid item overlay link to the lesson (logged-in users only).
     *
     * @param int $post_id Lesson post ID.
     */
    private function render_grid_item_lesson_link($post_id) {
        if (!is_user_logged_in()) {
            return;
        }

        $permalink = get_permalink($post_id);
        ?>
        <a href="<?php echo esc_url($permalink); ?>"
           class="abs-link grid-item--title_link"
           aria-label="<?php echo esc_attr(get_the_title($post_id)); ?>"></a>
        <?php
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
