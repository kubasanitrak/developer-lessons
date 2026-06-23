<?php
/**
 * Spam scoring and account review flags.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Spam_Scoring {

    const REVIEW_THRESHOLD = 50;
    const CLEAR_REVIEW_THRESHOLD = 40;
    const META_SCORE = 'dl_spam_score';
    const META_FLAG = 'dl_account_flag';
    const META_SIGNALS = 'dl_spam_signals';
    const META_MANUAL_LOCK = 'dl_spam_manual_lock';

    /**
     * Disposable email domains used for lightweight heuristics.
     */
    private static function disposable_domains() {
        return array(
            'mailinator.com',
            'guerrillamail.com',
            'tempmail.com',
            'yopmail.com',
            'trashmail.com',
            '10minutemail.com',
            'discard.email',
            'getnada.com',
            'temp-mail.org',
            'sharklasers.com',
        );
    }

    /**
     * Recalculate scores for all non-admin users.
     */
    public static function recalculate_all() {
        $user_ids = get_users(array(
            'fields' => 'ID',
            'number' => -1,
        ));

        $processed = 0;

        foreach ($user_ids as $user_id) {
            if (user_can($user_id, 'manage_options')) {
                continue;
            }

            self::score_user($user_id);
            $processed++;
        }

        return $processed;
    }

    /**
     * Calculate and store spam score for one user.
     */
    public static function score_user($user_id) {
        $user_id = (int) $user_id;
        if (!$user_id || user_can($user_id, 'manage_options')) {
            return false;
        }

        $result = self::calculate_score($user_id);
        $score = (int) $result['score'];
        $flag = self::get_account_flag($user_id);
        $manual_lock = (bool) get_user_meta($user_id, self::META_MANUAL_LOCK, true);

        update_user_meta($user_id, self::META_SCORE, $score);
        update_user_meta($user_id, self::META_SIGNALS, wp_json_encode($result['signals']));

        if ($flag === 'spam') {
            return $result;
        }

        if ($manual_lock) {
            return $result;
        }

        if ($score >= self::REVIEW_THRESHOLD && $flag !== 'review') {
            update_user_meta($user_id, self::META_FLAG, 'review');
        } elseif ($flag === 'review' && $score < self::CLEAR_REVIEW_THRESHOLD) {
            update_user_meta($user_id, self::META_FLAG, 'normal');
        }

        return $result;
    }

    /**
     * Compute score and human-readable signals.
     */
    public static function calculate_score($user_id) {
        global $wpdb;

        $user = get_userdata($user_id);
        if (!$user) {
            return array('score' => 0, 'signals' => array());
        }

        $score = 0;
        $signals = array();
        $now = time();
        $registered_ts = strtotime($user->user_registered);
        $days_since_registration = $registered_ts ? (int) floor(($now - $registered_ts) / DAY_IN_SECONDS) : 0;

        $first_login = get_user_meta($user_id, 'dl_first_login_at', true);
        $has_logged_in = !empty($first_login);

        if (!$has_logged_in && $days_since_registration >= 7) {
            $score += 30;
            $signals[] = __('Registered 7+ days ago but never logged in', 'developer-lessons');
        } elseif (!$has_logged_in && $days_since_registration >= 3) {
            $score += 15;
            $signals[] = __('Registered 3+ days ago but never logged in', 'developer-lessons');
        }

        if ($has_logged_in && $days_since_registration >= 14) {
            $days_to_first = (int) floor((strtotime($first_login) - $registered_ts) / DAY_IN_SECONDS);
            if ($days_to_first > 14) {
                $score += 10;
                $signals[] = __('First login took more than 14 days', 'developer-lessons');
            }
        }

        $events_table = DL_Analytics::table_name();
        $lesson_views = 0;
        $video_plays = 0;
        $basket_adds = 0;

        if (DL_Analytics::table_exists()) {
            $lesson_views = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $events_table WHERE user_id = %d AND event_type = 'lesson_view'",
                $user_id
            ));
            $video_plays = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $events_table WHERE user_id = %d AND event_type = 'video_play_start'",
                $user_id
            ));
            $basket_adds = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $events_table WHERE user_id = %d AND event_type = 'basket_add'",
                $user_id
            ));
        }

        $purchases_table = $wpdb->prefix . 'dl_purchases';
        $purchase_count = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$purchases_table'") === $purchases_table) {
            $purchase_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $purchases_table WHERE user_id = %d",
                $user_id
            ));
        }

        if ($has_logged_in && $lesson_views === 0 && $video_plays === 0 && $days_since_registration >= 14) {
            $score += 20;
            $signals[] = __('Logged in but no lesson or video engagement after 14 days', 'developer-lessons');
        }

        if ($basket_adds > 0 && $purchase_count === 0) {
            $score += 15;
            $signals[] = __('Added lessons to basket but never purchased', 'developer-lessons');
        }

        if (self::has_shared_registration_ip($user_id)) {
            $score += 25;
            $signals[] = __('Shares registration IP with multiple accounts', 'developer-lessons');
        }

        if (self::is_disposable_email($user->user_email)) {
            $score += 20;
            $signals[] = __('Disposable email domain', 'developer-lessons');
        }

        return array(
            'score' => min(100, $score),
            'signals' => $signals,
        );
    }

    /**
     * Users for the admin review queue.
     */
    public static function get_review_queue($args = array()) {
        global $wpdb;

        $args = wp_parse_args($args, array(
            'filter' => 'review',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'score',
            'order' => 'desc',
        ));

        $events_table = DL_Analytics::table_name();
        $purchases_table = $wpdb->prefix . 'dl_purchases';
        $has_events = DL_Analytics::table_exists();

        $lesson_views_sql = '0 AS lesson_views';
        $video_plays_sql = '0 AS video_plays';
        $basket_adds_sql = '0 AS basket_adds';

        if ($has_events) {
            $lesson_views_sql = "(SELECT COUNT(*) FROM $events_table e WHERE e.user_id = u.ID AND e.event_type = 'lesson_view') AS lesson_views";
            $video_plays_sql = "(SELECT COUNT(*) FROM $events_table e WHERE e.user_id = u.ID AND e.event_type = 'video_play_start') AS video_plays";
            $basket_adds_sql = "(SELECT COUNT(*) FROM $events_table e WHERE e.user_id = u.ID AND e.event_type = 'basket_add') AS basket_adds";
        }

        $purchase_count_sql = '0 AS purchase_count';
        if ($wpdb->get_var("SHOW TABLES LIKE '$purchases_table'") === $purchases_table) {
            $purchase_count_sql = "(SELECT COUNT(*) FROM $purchases_table p WHERE p.user_id = u.ID) AS purchase_count";
        }

        $where = "WHERE role_meta.meta_value NOT LIKE '%administrator%'";
        $params = array();

        switch ($args['filter']) {
            case 'spam':
                $where .= " AND flag_meta.meta_value = 'spam'";
                break;
            case 'high':
                $where .= " AND CAST(IFNULL(score_meta.meta_value, 0) AS UNSIGNED) >= %d";
                $params[] = 70;
                break;
            case 'all':
                $where .= " AND (flag_meta.meta_value IN ('review', 'spam') OR CAST(IFNULL(score_meta.meta_value, 0) AS UNSIGNED) >= %d)";
                $params[] = self::REVIEW_THRESHOLD;
                break;
            case 'review':
            default:
                $where .= " AND flag_meta.meta_value = 'review'";
                break;
        }

        $order_sql = self::get_review_order_by($args['orderby'], $args['order']);
        $params[] = absint($args['limit']);
        $params[] = absint($args['offset']);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.user_login, u.user_email, u.user_registered,
                    score_meta.meta_value AS spam_score,
                    flag_meta.meta_value AS account_flag,
                    signals_meta.meta_value AS spam_signals,
                    m_first.meta_value AS first_login_at,
                    $lesson_views_sql,
                    $video_plays_sql,
                    $basket_adds_sql,
                    $purchase_count_sql
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} role_meta ON u.ID = role_meta.user_id AND role_meta.meta_key = '{$wpdb->prefix}capabilities'
             LEFT JOIN {$wpdb->usermeta} score_meta ON u.ID = score_meta.user_id AND score_meta.meta_key = '" . self::META_SCORE . "'
             LEFT JOIN {$wpdb->usermeta} flag_meta ON u.ID = flag_meta.user_id AND flag_meta.meta_key = '" . self::META_FLAG . "'
             LEFT JOIN {$wpdb->usermeta} signals_meta ON u.ID = signals_meta.user_id AND signals_meta.meta_key = '" . self::META_SIGNALS . "'
             LEFT JOIN {$wpdb->usermeta} m_first ON u.ID = m_first.user_id AND m_first.meta_key = 'dl_first_login_at'
             $where
             ORDER BY $order_sql
             LIMIT %d OFFSET %d",
            $params
        ));
    }

    /**
     * Count users in the review queue.
     */
    public static function count_review_queue($filter = 'review') {
        global $wpdb;

        $where = "WHERE role_meta.meta_value NOT LIKE '%administrator%'";
        $params = array();

        switch ($filter) {
            case 'spam':
                $where .= " AND flag_meta.meta_value = 'spam'";
                break;
            case 'high':
                $where .= " AND CAST(IFNULL(score_meta.meta_value, 0) AS UNSIGNED) >= %d";
                $params[] = 70;
                break;
            case 'all':
                $where .= " AND (flag_meta.meta_value IN ('review', 'spam') OR CAST(IFNULL(score_meta.meta_value, 0) AS UNSIGNED) >= %d)";
                $params[] = self::REVIEW_THRESHOLD;
                break;
            case 'review':
            default:
                $where .= " AND flag_meta.meta_value = 'review'";
                break;
        }

        $sql = "SELECT COUNT(*)
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} role_meta ON u.ID = role_meta.user_id AND role_meta.meta_key = '{$wpdb->prefix}capabilities'
                LEFT JOIN {$wpdb->usermeta} score_meta ON u.ID = score_meta.user_id AND score_meta.meta_key = '" . self::META_SCORE . "'
                LEFT JOIN {$wpdb->usermeta} flag_meta ON u.ID = flag_meta.user_id AND flag_meta.meta_key = '" . self::META_FLAG . "'
                $where";

        if (!empty($params)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Mark account as normal and prevent automatic re-queueing.
     */
    public static function mark_normal($user_id) {
        update_user_meta($user_id, self::META_FLAG, 'normal');
        update_user_meta($user_id, self::META_MANUAL_LOCK, 1);
    }

    /**
     * Mark account for manual review.
     */
    public static function mark_review($user_id) {
        update_user_meta($user_id, self::META_FLAG, 'review');
        delete_user_meta($user_id, self::META_MANUAL_LOCK);
    }

    /**
     * Mark account as spam.
     */
    public static function mark_spam($user_id) {
        update_user_meta($user_id, self::META_FLAG, 'spam');
        delete_user_meta($user_id, self::META_MANUAL_LOCK);
    }

    /**
     * Get account flag with default.
     */
    public static function get_account_flag($user_id) {
        $flag = get_user_meta($user_id, self::META_FLAG, true);

        if (!in_array($flag, array('normal', 'review', 'spam'), true)) {
            return 'normal';
        }

        return $flag;
    }

    /**
     * Decode stored signal list for display.
     */
    public static function get_signal_labels($signals_json) {
        if (empty($signals_json)) {
            return array();
        }

        $signals = json_decode($signals_json, true);

        return is_array($signals) ? $signals : array();
    }

    private static function get_review_order_by($orderby, $order) {
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
        $map = array(
            'score' => 'CAST(IFNULL(score_meta.meta_value, 0) AS UNSIGNED)',
            'user_login' => 'u.user_login',
            'user_registered' => 'u.user_registered',
            'purchase_count' => 'purchase_count',
            'lesson_views' => 'lesson_views',
        );

        $column = isset($map[$orderby]) ? $map[$orderby] : $map['score'];

        return $column . ' ' . $order;
    }

    private static function has_shared_registration_ip($user_id) {
        global $wpdb;

        if (!DL_Analytics::table_exists()) {
            return false;
        }

        $events_table = DL_Analytics::table_name();
        $ip_hash = $wpdb->get_var($wpdb->prepare(
            "SELECT ip_hash FROM $events_table
             WHERE user_id = %d AND event_type = 'registration' AND ip_hash IS NOT NULL AND ip_hash != ''
             ORDER BY id ASC LIMIT 1",
            $user_id
        ));

        if (empty($ip_hash)) {
            return false;
        }

        $shared_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $events_table
             WHERE event_type = 'registration' AND ip_hash = %s",
            $ip_hash
        ));

        return $shared_count >= 3;
    }

    private static function is_disposable_email($email) {
        $parts = explode('@', strtolower((string) $email));
        if (count($parts) !== 2) {
            return false;
        }

        return in_array($parts[1], self::disposable_domains(), true);
    }
}
