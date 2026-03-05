<?php
/**
 * AJAX Handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Ajax {

    public function __construct() {
        // Basket count for header
        add_action('wp_ajax_dl_update_basket_icon', array($this, 'update_basket_icon'));
        add_action('wp_ajax_nopriv_dl_update_basket_icon', array($this, 'update_basket_icon'));
    }

    /**
     * Update basket icon count
     */
    public function update_basket_icon() {
        $count = 0;

        if (is_user_logged_in()) {
            $basket = new DL_Basket();
            $count = $basket->get_count();
        }

        wp_send_json_success(array(
            'count' => $count,
            'html' => $count > 0 ? '<span class="dl-basket-count">' . $count . '</span>' : ''
        ));
    }
}
