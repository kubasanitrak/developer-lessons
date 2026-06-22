<?php
/**
 * Admin Statistics
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Admin_Statistics {

    /**
     * @var DL_Admin_Statistics|null
     */
    private static $instance = null;

    public function __construct() {
        self::$instance = $this;
        add_filter('set_screen_option_dl_stats_users_per_page', array($this, 'save_screen_option'), 10, 3);
        add_action('admin_init', array($this, 'handle_backfill_action'));
        add_action('admin_notices', array($this, 'render_backfill_notice'));
    }

    /**
     * Shared statistics admin instance.
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register screen options for the users report.
     */
    public static function register_screen_options() {
        add_screen_option('per_page', array(
            'label' => __('Users per page', 'developer-lessons'),
            'default' => 20,
            'option' => 'dl_stats_users_per_page',
        ));
    }

    /**
     * Persist custom per-page screen option.
     */
    public function save_screen_option($status, $option, $value) {
        return max(1, min(999, (int) $value));
    }

    /**
     * Users per page for the statistics users tab.
     */
    private function get_users_per_page() {
        if (isset($_GET['users_per_page'])) {
            $per_page = max(1, min(999, (int) $_GET['users_per_page']));
            update_user_option(get_current_user_id(), 'dl_stats_users_per_page', $per_page);

            return $per_page;
        }

        $per_page = (int) get_user_option('dl_stats_users_per_page');

        if ($per_page < 1) {
            $per_page = 20;
        }

        return min($per_page, 999);
    }

    /**
     * Handle manual analytics backfill requests.
     */
    public function handle_backfill_action() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'dl-statistics') {
            return;
        }

        if (!isset($_GET['dl_analytics_backfill'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('dl_analytics_backfill');

        $overwrite = !empty($_GET['overwrite']);
        $result = DL_Analytics::backfill_user_meta(array(
            'force' => true,
            'overwrite' => $overwrite,
        ));

        $user_id = get_current_user_id();
        set_transient('dl_analytics_backfill_result_' . $user_id, array_merge($result, array(
            'overwrite' => $overwrite,
        )), 60);

        $redirect_args = array(
            'page' => 'dl-statistics',
            'tab' => 'users',
            'range' => isset($_GET['range']) ? sanitize_text_field(wp_unslash($_GET['range'])) : '30days',
            'dl_backfill' => 'done',
        );

        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    /**
     * Show backfill result notice on the statistics page.
     */
    public function render_backfill_notice() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'dl-statistics') {
            return;
        }

        if (!isset($_GET['dl_backfill']) || $_GET['dl_backfill'] !== 'done') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $result = get_transient('dl_analytics_backfill_result_' . get_current_user_id());
        if (!$result) {
            return;
        }

        delete_transient('dl_analytics_backfill_result_' . get_current_user_id());

        if (!empty($result['skipped'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            esc_html_e('Analytics backfill was skipped because it already ran.', 'developer-lessons');
            echo '</p></div>';
            return;
        }

        $mode = !empty($result['overwrite'])
            ? __('historical overwrite', 'developer-lessons')
            : __('missing values only', 'developer-lessons');

        echo '<div class="notice notice-success is-dismissible"><p>';
        printf(
            esc_html__('Analytics backfill complete (%1$s). Processed %2$d users. Updated registration: %3$d, first login: %4$d, last login: %5$d, login count: %6$d.', 'developer-lessons'),
            esc_html($mode),
            (int) $result['processed'],
            (int) $result['updated_registration'],
            (int) $result['updated_first_login'],
            (int) $result['updated_last_login'],
            (int) $result['updated_login_count']
        );
        echo '</p></div>';
    }

    /**
     * Backfill action URL for the statistics page.
     */
    public static function get_backfill_url($range, $overwrite = false) {
        $args = array(
            'page' => 'dl-statistics',
            'tab' => 'users',
            'range' => $range,
            'dl_analytics_backfill' => '1',
        );

        if ($overwrite) {
            $args['overwrite'] = '1';
        }

        return wp_nonce_url(add_query_arg($args, admin_url('admin.php')), 'dl_analytics_backfill');
    }

    /**
     * Render statistics page
     */
    public function render() {
        global $wpdb;

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'sales';
        if (!in_array($tab, array('sales', 'users', 'lessons', 'funnel'), true)) {
            $tab = 'sales';
        }

        $range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '30days';
        $date_filter = $this->get_date_filter($range);

        if ($tab === 'users') {
            $per_page = $this->get_users_per_page();
            $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
            $days = $this->get_range_days($range);
            $total_users = DL_Analytics::count_registration_report($days);
            $total_pages = max(1, (int) ceil($total_users / $per_page));

            if ($current_page > $total_pages) {
                $current_page = $total_pages;
            }

            $user_stats = DL_Analytics::get_registration_report(array(
                'limit' => $per_page,
                'offset' => ($current_page - 1) * $per_page,
                'days' => $days,
            ));

            include DL_PLUGIN_DIR . 'admin/partials/statistics-page.php';
            return;
        }

        if ($tab === 'lessons') {
            $lesson_view_stats = DL_Analytics::get_lesson_view_stats(50, $this->get_range_days($range));
            include DL_PLUGIN_DIR . 'admin/partials/statistics-page.php';
            return;
        }

        if ($tab === 'funnel') {
            $days = $this->get_range_days($range);
            $funnel_summary = DL_Analytics::get_funnel_summary($days);
            $daily_activity = DL_Analytics::get_daily_activity($days);
            include DL_PLUGIN_DIR . 'admin/partials/statistics-page.php';
            return;
        }

        $purchases_table = $wpdb->prefix . 'dl_purchases';
        $orders_table = $wpdb->prefix . 'dl_orders';

        // Lesson statistics
        $lesson_stats = $wpdb->get_results(
            "SELECT p.lesson_id, post.post_title as title, 
                    COUNT(*) as sales, SUM(p.price) as revenue
             FROM $purchases_table p
             LEFT JOIN {$wpdb->posts} post ON p.lesson_id = post.ID
             WHERE p.purchased_at >= '$date_filter'
             GROUP BY p.lesson_id
             ORDER BY sales DESC
             LIMIT 20"
        );

        // Transaction statistics by price
        $price_stats = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN price < 100 THEN 'Under 100'
                    WHEN price >= 100 AND price < 500 THEN '100-499'
                    WHEN price >= 500 AND price < 1000 THEN '500-999'
                    ELSE '1000+'
                END as price_range,
                COUNT(*) as count,
                SUM(price) as total
             FROM $purchases_table
             WHERE purchased_at >= '$date_filter'
             GROUP BY price_range
             ORDER BY MIN(price)"
        );

        // Time-based statistics
        $time_stats = $wpdb->get_results(
            "SELECT DATE(purchased_at) as date, COUNT(*) as sales, SUM(price) as revenue
             FROM $purchases_table
             WHERE purchased_at >= '$date_filter'
             GROUP BY DATE(purchased_at)
             ORDER BY date DESC"
        );

        // Bundle statistics
        $bundle_stats = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN item_count = 1 THEN 'Single'
                    WHEN item_count >= 2 AND item_count < 5 THEN '2-4 items'
                    WHEN item_count >= 5 AND item_count < 10 THEN '5-9 items'
                    ELSE '10+ items'
                END as bundle_type,
                COUNT(*) as orders,
                SUM(total) as revenue
             FROM (
                SELECT o.id, o.total, COUNT(oi.id) as item_count
                FROM $orders_table o
                LEFT JOIN {$wpdb->prefix}dl_order_items oi ON o.id = oi.order_id
                WHERE o.status = 'completed' AND o.paid_at >= '$date_filter'
                GROUP BY o.id
             ) as order_counts
             GROUP BY bundle_type"
        );

        // Summary stats
        $summary = array(
            'total_sales' => $wpdb->get_var("SELECT COUNT(*) FROM $purchases_table WHERE purchased_at >= '$date_filter'"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(price) FROM $purchases_table WHERE purchased_at >= '$date_filter'"),
            'total_orders' => $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status = 'completed' AND paid_at >= '$date_filter'"),
            'avg_order_value' => $wpdb->get_var("SELECT AVG(total) FROM $orders_table WHERE status = 'completed' AND paid_at >= '$date_filter'")
        );

        include DL_PLUGIN_DIR . 'admin/partials/statistics-page.php';
    }

    /**
     * Get date filter based on range
     */
    private function get_date_filter($range) {
        switch ($range) {
            case '7days':
                return date('Y-m-d', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d', strtotime('-30 days'));
            case '90days':
                return date('Y-m-d', strtotime('-90 days'));
            case 'year':
                return date('Y-m-d', strtotime('-1 year'));
            case 'all':
                return '1970-01-01';
            default:
                return date('Y-m-d', strtotime('-30 days'));
        }
    }

    /**
     * Convert range slug to number of days for user/lesson activity reports.
     */
    private function get_range_days($range) {
        switch ($range) {
            case '7days':
                return 7;
            case '30days':
                return 30;
            case '90days':
                return 90;
            case 'year':
                return 365;
            case 'all':
                return 3650;
            default:
                return 30;
        }
    }
}
