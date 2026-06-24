<?php
/**
 * Public Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Public {

    /**
     * Shortcodes that require plugin CSS on the current page.
     *
     * @var string[]
     */
    private static $content_shortcodes = array(
        'dl_lessons_grid',
        'dl_buy_button',
        'dl_lesson_price',
        'dl_buy_all_lessons',
        'dl_checkout',
        'dl_dashboard',
        'dl_payment_success',
        'dl_payment_failed',
        'dl_invoice_profile',
    );

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_video_analytics'), 20);
        add_action('wp_footer', array($this, 'render_basket_sidebar'));
    }

    /**
     * Whether plugin frontend styles should load.
     */
    public function should_enqueue_styles() {
        if (is_admin()) {
            return false;
        }

        if (is_singular('lesson') || is_post_type_archive('lesson')) {
            return true;
        }

        if ($this->is_plugin_page()) {
            return true;
        }

        if ($this->current_post_has_plugin_shortcode()) {
            return true;
        }

        if (is_user_logged_in()) {
            return true;
        }

        return false;
    }

    /**
     * Whether interactive plugin scripts (jQuery + public.js) should load.
     */
    public function should_enqueue_scripts() {
        if (!$this->should_enqueue_styles()) {
            return false;
        }

        if (is_user_logged_in()) {
            return true;
        }

        if ($this->is_plugin_page()) {
            return true;
        }

        if ($this->current_post_has_plugin_shortcode()) {
            return true;
        }

        return false;
    }

    /**
     * Dashicons are only needed on a few commerce UI surfaces.
     */
    private function should_enqueue_dashicons() {
        if (!$this->should_enqueue_styles()) {
            return false;
        }

        $page_ids = $this->get_plugin_page_ids();
        $current_id = get_queried_object_id();

        if (!$current_id) {
            return false;
        }

        foreach (array('dashboard', 'payment_success', 'payment_failed') as $key) {
            if (!empty($page_ids[$key]) && (int) $page_ids[$key] === (int) $current_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Plugin page IDs stored in options.
     */
    private function get_plugin_page_ids() {
        $page_ids = get_option('dl_page_ids', array());
        return is_array($page_ids) ? $page_ids : array();
    }

    /**
     * True when the current request is a configured plugin page.
     */
    private function is_plugin_page() {
        if (!is_page()) {
            return false;
        }

        $current_id = get_queried_object_id();
        if (!$current_id) {
            return false;
        }

        foreach ($this->get_plugin_page_ids() as $page_id) {
            if ((int) $page_id === (int) $current_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect developer-lessons shortcodes in the current singular post content.
     */
    private function current_post_has_plugin_shortcode($post_id = null) {
        if (!$post_id) {
            if (!is_singular()) {
                return false;
            }
            $post_id = get_queried_object_id();
        }

        if (!$post_id) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post || empty($post->post_content)) {
            return false;
        }

        foreach (self::$content_shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!$this->should_enqueue_styles()) {
            return;
        }

        wp_enqueue_style(
            'dl-public-css',
            DL_PLUGIN_URL . 'public/css/public.css',
            array(),
            DL_VERSION
        );

        if ($this->should_enqueue_dashicons()) {
            wp_enqueue_style('dashicons');
        }

        if (!$this->should_enqueue_scripts()) {
            return;
        }

        wp_enqueue_script(
            'dl-public-js',
            DL_PLUGIN_URL . 'public/js/public.js',
            array('jquery'),
            DL_VERSION,
            true
        );

        $page_ids = $this->get_plugin_page_ids();
        wp_localize_script('dl-public-js', 'dl_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dl_nonce'),
            'checkout_url' => isset($page_ids['checkout']) ? get_permalink($page_ids['checkout']) : '',
            'success_url' => isset($page_ids['payment_success']) ? get_permalink($page_ids['payment_success']) : '',
            'is_logged_in' => is_user_logged_in(),
            'currency_symbol' => get_option('dl_currency_symbol', 'Kč'),
            'currency_position' => get_option('dl_currency_position', 'after'),
            'strings' => array(
                'added_to_basket' => __('Added to basket!', 'developer-lessons'),
                'all_added' => __('All lessons added to basket!', 'developer-lessons'),
                'error' => __('An error occurred. Please try again.', 'developer-lessons'),
                'processing' => __('Processing...', 'developer-lessons'),
                'add_to_basket' => __('Add to Basket', 'developer-lessons'),
                'view_basket' => __('View Basket', 'developer-lessons'),
                'go_to_checkout' => __('Go to Checkout', 'developer-lessons'),
                'please_login' => __('Please log in to add items to basket.', 'developer-lessons'),
                'basket_empty' => __('Your basket is empty.', 'developer-lessons'),
                'total' => __('Total:', 'developer-lessons'),
                'select_payment' => __('Please select a payment method.', 'developer-lessons'),
                'complete_purchase' => __('Complete Purchase', 'developer-lessons'),
                'bundle_discount' => __('Bundle Discount (%d%%)', 'developer-lessons'),
                'invoice_required' => __('Please fill in all required invoice fields.', 'developer-lessons'),
                'saving' => __('Saving...', 'developer-lessons'),
                'in_basket' => __('In Basket', 'developer-lessons'),
                'processing_payment' => __('Processing payment...', 'developer-lessons'),
            )
        ));
    }

    /**
     * Enqueue Vimeo analytics on lesson pages with full access.
     */
    public function enqueue_video_analytics() {
        if (!is_singular('lesson') || !is_user_logged_in()) {
            return;
        }

        $lesson_id = get_queried_object_id();
        $access_control = new DL_Access_Control();

        if (!$access_control->user_has_access($lesson_id)) {
            return;
        }

        wp_enqueue_script(
            'dl-video-analytics',
            DL_PLUGIN_URL . 'public/js/video-analytics.js',
            array('jquery', 'dl-public-js'),
            DL_VERSION,
            true
        );

        wp_localize_script('dl-video-analytics', 'dl_video_analytics', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dl_video_analytics'),
            'lesson_id' => $lesson_id,
        ));
    }

    /**
     * Render basket sidebar
     */
    public function render_basket_sidebar() {
        if (!is_user_logged_in()) {
            return;
        }

        $basket = new DL_Basket();
        $items = $basket->get_items();
        $count = count($items);
        $total = $basket->get_final_total();
        $page_ids = $this->get_plugin_page_ids();
        $checkout_url = isset($page_ids['checkout']) ? get_permalink($page_ids['checkout']) : '#';
        ?>
        <div id="dl-basket-sidebar" class="dl-basket-sidebar">
            <div class="dl-basket-header">
                <h3><?php _e('Your Basket', 'developer-lessons'); ?></h3>
                <button type="button" class="dl-basket-close">&times;</button>
            </div>
            <div class="dl-basket-content">
                <?php if (empty($items)): ?>
                    <p class="dl-basket-empty"><?php _e('Your basket is empty.', 'developer-lessons'); ?></p>
                <?php else: ?>
                    <ul class="dl-basket-items">
                        <?php foreach ($items as $item): ?>
                            <li class="dl-basket-item" data-lesson-id="<?php echo esc_attr($item->lesson_id); ?>">
                                <div class="dl-basket-item-info">
                                    <a href="<?php echo esc_url($item->permalink); ?>">
                                        <?php echo esc_html($item->lesson_title); ?>
                                    </a>
                                    <span class="dl-basket-item-price">
                                        <?php echo DL_Payments::format_price($item->price); ?>
                                    </span>
                                </div>
                                <button type="button" class="dl-basket-remove" data-lesson-id="<?php echo esc_attr($item->lesson_id); ?>">
                                    &times;
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="dl-basket-total">
                        <span><?php _e('Total:', 'developer-lessons'); ?></span>
                        <span class="dl-basket-total-amount"><?php echo DL_Payments::format_price($total); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="dl-basket-footer">
                <a href="<?php echo esc_url($checkout_url); ?>" class="dl-btn dl-btn-primary dl-btn-block">
                    <?php _e('Go to Checkout', 'developer-lessons'); ?>
                </a>
            </div>
        </div>
        <div id="dl-basket-overlay" class="dl-basket-overlay"></div>

        <button type="button" id="dl-basket-toggle" class="dl-basket-toggle">
            <span class="dl-basket-toggle-icon" aria-hidden="true">
                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" focusable="false">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0020 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg> -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" focusable="false" viewBox="0 0 20 20">
                  <path d="M0 2.006h4.286V3.72H0z"/>
                  <path d="M4.343 2 6.38 7.26l-1.6.62-2.036-5.261zM15.714 5.434H20V7.15h-4.286z"/>
                  <path d="M3.833 5.434h16.025l-3.39 7.429H6.572zM9.429 16.72a1.857 1.857 0 1 1-3.715 0 1.857 1.857 0 0 1 3.715 0M17.143 16.72a1.857 1.857 0 1 1-3.715 0 1.857 1.857 0 0 1 3.715 0"/>
                </svg>
            </span>
            <?php if ($count > 0): ?>
                <span class="dl-basket-count"><?php echo $count; ?></span>
            <?php endif; ?>
        </button>
        <?php
    }
}
