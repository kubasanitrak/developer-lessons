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

        add_action('wp_ajax_dl_track_video_event', array($this, 'track_video_event'));
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

    /**
     * Log Vimeo video engagement from the lesson page.
     */
    public function track_video_event() {
        check_ajax_referer('dl_video_analytics', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'developer-lessons')));
        }

        $lesson_id = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
        $vimeo_id = isset($_POST['vimeo_id']) ? sanitize_text_field(wp_unslash($_POST['vimeo_id'])) : '';
        $event_type = isset($_POST['event_type']) ? sanitize_key(wp_unslash($_POST['event_type'])) : '';

        if (!$lesson_id || $vimeo_id === '' || $event_type === '') {
            wp_send_json_error(array('message' => __('Invalid video event.', 'developer-lessons')));
        }

        if (get_post_type($lesson_id) !== 'lesson') {
            wp_send_json_error(array('message' => __('Invalid lesson.', 'developer-lessons')));
        }

        $access_control = new DL_Access_Control();
        if (!$access_control->user_has_access($lesson_id, get_current_user_id())) {
            wp_send_json_error(array('message' => __('Access denied.', 'developer-lessons')));
        }

        $extra = array();
        if (!empty($_POST['percent'])) {
            $extra['percent'] = (float) $_POST['percent'];
        }

        $logged = DL_Analytics::track_video_event($event_type, array(
            'user_id' => get_current_user_id(),
            'lesson_id' => $lesson_id,
            'vimeo_id' => $vimeo_id,
            'meta' => $extra,
        ));

        if (!$logged) {
            wp_send_json_success(array('logged' => false));
        }

        wp_send_json_success(array('logged' => true));
    }
}
