<?php
/**
 * Public Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_basket_sidebar'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'dl-public-css',
            DL_PLUGIN_URL . 'public/css/public.css',
            array(),
            DL_VERSION
        );
        wp_enqueue_script(
            'dl-public-js',
            DL_PLUGIN_URL . 'public/js/public.js',
            array('jquery'),
            DL_VERSION,
            true
        );
        $page_ids = get_option('dl_page_ids', array());
        // wp_localize_script('dl-public-js', 'dl_public', array(
        //     'ajax_url' => admin_url('admin-ajax.php'),
        //     'nonce' => wp_create_nonce('dl_nonce'),
        //     'checkout_url' => isset($page_ids['checkout']) ? get_permalink($page_ids['checkout']) : '',
        //     'is_logged_in' => is_user_logged_in(),
        //     'currency_symbol' => get_option('dl_currency_symbol', 'Kč'),
        //     'currency_position' => get_option('dl_currency_position', 'after'),
        //     'strings' => array(
        //         'added_to_basket' => __('Added to basket!', 'developer-lessons'),
        //         'all_added' => __('All lessons added to basket!', 'developer-lessons'),
        //         'error' => __('An error occurred. Please try again.', 'developer-lessons'),
        //         'processing' => __('Processing...', 'developer-lessons'),
        //         'add_to_basket' => __('Add to Basket', 'developer-lessons'),
        //         'view_basket' => __('View Basket', 'developer-lessons'),
        //         'go_to_checkout' => __('Go to Checkout', 'developer-lessons'),
        //         'please_login' => __('Please log in to add items to basket.', 'developer-lessons'),
        //         'basket_empty' => __('Your basket is empty.', 'developer-lessons'),
        //         'total' => __('Total:', 'developer-lessons'),
        //         'select_payment' => __('Please select a payment method.', 'developer-lessons'),
        //         'complete_purchase' => __('Complete Purchase', 'developer-lessons')
        //     )
        // ));
        wp_localize_script('dl-public-js', 'dl_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dl_nonce'),
            'checkout_url' => isset($page_ids['checkout']) ? get_permalink($page_ids['checkout']) : '',
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
            )
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
        $page_ids = get_option('dl_page_ids', array());
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
            <span class="dashicons dashicons-cart"></span>
            <?php if ($count > 0): ?>
                <span class="dl-basket-count"><?php echo $count; ?></span>
            <?php endif; ?>
        </button>
        <?php
    }
}
