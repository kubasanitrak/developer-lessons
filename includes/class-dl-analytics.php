<?php
/**
 * User activity analytics (registration, login, lesson views).
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Analytics {

    const DEDUPE_MINUTES = 30;

    public function __construct() {
        add_action('user_register', array($this, 'on_user_register'), 10, 1);
        add_action('wp_login', array($this, 'on_wp_login'), 10, 2);
        add_action('template_redirect', array($this, 'track_lesson_view'), 20);
    }

    /**
     * Events table name.
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'dl_events';
    }

    /**
     * Create or update the events table.
     */
    public static function create_table() {
        global $wpdb;

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            event_type varchar(50) NOT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id bigint(20) UNSIGNED DEFAULT NULL,
            meta longtext DEFAULT NULL,
            ip_hash char(64) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY object_lookup (object_type, object_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Log an analytics event.
     */
    public static function log_event($event_type, $args = array()) {
        global $wpdb;

        if (!self::table_exists()) {
            return false;
        }

        $event_type = sanitize_key($event_type);
        if ($event_type === '') {
            return false;
        }

        $user_id = isset($args['user_id']) ? (int) $args['user_id'] : get_current_user_id();
        if ($user_id && !self::should_track_user($user_id)) {
            return false;
        }

        if (self::is_bot_request()) {
            return false;
        }

        $object_type = isset($args['object_type']) ? sanitize_key($args['object_type']) : null;
        $object_id = isset($args['object_id']) ? (int) $args['object_id'] : null;
        $meta = isset($args['meta']) && is_array($args['meta']) ? $args['meta'] : array();

        if (!empty($args['dedupe']) && self::is_duplicate_event($event_type, $user_id, $object_type, $object_id, (int) $args['dedupe'])) {
            return false;
        }

        $ip_hash = self::hash_ip(self::get_client_ip());
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 255) : '';

        return $wpdb->insert(
            self::table_name(),
            array(
                'user_id' => $user_id ?: null,
                'event_type' => $event_type,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'meta' => !empty($meta) ? wp_json_encode($meta) : null,
                'ip_hash' => $ip_hash,
                'user_agent' => $user_agent,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        ) !== false;
    }

    /**
     * Handle new user registration.
     */
    public function on_user_register($user_id) {
        $user_id = (int) $user_id;
        if (!$user_id || !self::should_track_user($user_id)) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        update_user_meta($user_id, 'dl_registration_at', $user->user_registered);

        self::log_event('registration', array(
            'user_id' => $user_id,
            'meta' => array(
                'source' => 'user_register',
            ),
        ));
    }

    /**
     * Handle user login.
     */
    public function on_wp_login($user_login, $user) {
        $user_id = isset($user->ID) ? (int) $user->ID : 0;
        if (!$user_id || !self::should_track_user($user_id)) {
            return;
        }

        $now = current_time('mysql');
        $first_login = get_user_meta($user_id, 'dl_first_login_at', true);

        update_user_meta($user_id, 'dl_last_login_at', $now);
        update_user_meta($user_id, 'dl_login_count', (int) get_user_meta($user_id, 'dl_login_count', true) + 1);

        if (empty($first_login)) {
            update_user_meta($user_id, 'dl_first_login_at', $now);
            self::log_event('first_login', array(
                'user_id' => $user_id,
                'meta' => array(
                    'days_since_registration' => self::days_between(get_user_meta($user_id, 'dl_registration_at', true), $now),
                ),
            ));
        }

        self::log_event('login', array(
            'user_id' => $user_id,
        ));
    }

    /**
     * Track lesson page views for logged-in users.
     */
    public function track_lesson_view() {
        if (!is_singular('lesson') || !is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        if (!self::should_track_user($user_id)) {
            return;
        }

        $lesson_id = get_queried_object_id();
        if (!$lesson_id) {
            return;
        }

        $access_control = new DL_Access_Control();
        $has_access = $access_control->user_has_access($lesson_id, $user_id);

        self::log_event('lesson_view', array(
            'user_id' => $user_id,
            'object_type' => 'lesson',
            'object_id' => $lesson_id,
            'meta' => array(
                'access' => $has_access ? 'full' : 'teaser',
            ),
            'dedupe' => self::DEDUPE_MINUTES,
        ));
    }

    /**
     * Recent user registrations for the admin report.
     */
    public static function get_registration_report($limit = 50, $days = 90) {
        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime('-' . absint($days) . ' days'));
        $events_table = self::table_name();
        $purchases_table = $wpdb->prefix . 'dl_purchases';
        $has_events = self::table_exists();

        $lesson_views_sql = '0 AS lesson_views';
        if ($has_events) {
            $lesson_views_sql = "(SELECT COUNT(*) FROM $events_table e WHERE e.user_id = u.ID AND e.event_type = 'lesson_view') AS lesson_views";
        }

        $purchase_count_sql = '0 AS purchase_count';
        if ($wpdb->get_var("SHOW TABLES LIKE '$purchases_table'") === $purchases_table) {
            $purchase_count_sql = "(SELECT COUNT(*) FROM $purchases_table p WHERE p.user_id = u.ID) AS purchase_count";
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.user_login, u.user_email, u.user_registered,
                    m_first.meta_value AS first_login_at,
                    m_last.meta_value AS last_login_at,
                    m_count.meta_value AS login_count,
                    $lesson_views_sql,
                    $purchase_count_sql
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->usermeta} m_first ON u.ID = m_first.user_id AND m_first.meta_key = 'dl_first_login_at'
             LEFT JOIN {$wpdb->usermeta} m_last ON u.ID = m_last.user_id AND m_last.meta_key = 'dl_last_login_at'
             LEFT JOIN {$wpdb->usermeta} m_count ON u.ID = m_count.user_id AND m_count.meta_key = 'dl_login_count'
             WHERE u.user_registered >= %s
             ORDER BY u.user_registered DESC
             LIMIT %d",
            $since,
            absint($limit)
        ));
    }

    /**
     * Top lesson views for the admin report.
     */
    public static function get_lesson_view_stats($limit = 20, $days = 30) {
        global $wpdb;

        if (!self::table_exists()) {
            return array();
        }

        $since = date('Y-m-d H:i:s', strtotime('-' . absint($days) . ' days'));
        $events_table = self::table_name();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.object_id AS lesson_id, p.post_title AS title,
                    COUNT(*) AS views,
                    COUNT(DISTINCT e.user_id) AS unique_users
             FROM $events_table e
             LEFT JOIN {$wpdb->posts} p ON e.object_id = p.ID
             WHERE e.event_type = 'lesson_view'
               AND e.object_type = 'lesson'
               AND e.created_at >= %s
             GROUP BY e.object_id
             ORDER BY views DESC
             LIMIT %d",
            $since,
            absint($limit)
        ));
    }

    private static function table_exists() {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }

    private static function should_track_user($user_id) {
        return !user_can($user_id, 'manage_options');
    }

    private static function is_bot_request() {
        if (function_exists('wp_is_bot') && wp_is_bot()) {
            return true;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        return false;
    }

    private static function is_duplicate_event($event_type, $user_id, $object_type, $object_id, $minutes) {
        global $wpdb;

        if (!$user_id) {
            return false;
        }

        $since = date('Y-m-d H:i:s', strtotime('-' . absint($minutes) . ' minutes'));
        $table = self::table_name();

        $query = $wpdb->prepare(
            "SELECT id FROM $table
             WHERE event_type = %s
               AND user_id = %d
               AND created_at >= %s",
            $event_type,
            $user_id,
            $since
        );

        if ($object_type !== null && $object_type !== '') {
            $query .= $wpdb->prepare(' AND object_type = %s', $object_type);
        }

        if ($object_id) {
            $query .= $wpdb->prepare(' AND object_id = %d', $object_id);
        }

        $query .= ' LIMIT 1';

        return (bool) $wpdb->get_var($query);
    }

    private static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        return $ip;
    }

    private static function hash_ip($ip) {
        if ($ip === '') {
            return null;
        }

        $salt = defined('AUTH_KEY') ? AUTH_KEY : 'dl-analytics';
        return hash('sha256', $ip . $salt);
    }

    private static function days_between($start, $end) {
        if (empty($start) || empty($end)) {
            return null;
        }

        $start_ts = strtotime($start);
        $end_ts = strtotime($end);

        if (!$start_ts || !$end_ts) {
            return null;
        }

        return (int) floor(($end_ts - $start_ts) / DAY_IN_SECONDS);
    }
}
