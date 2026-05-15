<?php
/**
 * Admin Statistics
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Admin_Statistics {

    /**
     * Allowed statistics date range keys.
     *
     * @var string[]
     */
    private static $allowed_ranges = array('7days', '30days', '90days', 'year', 'all');

    /**
     * Render statistics page
     */
    public function render() {
        global $wpdb;

        $purchases_table = $wpdb->prefix . 'dl_purchases';
        $orders_table = $wpdb->prefix . 'dl_orders';
        $order_items_table = $wpdb->prefix . 'dl_order_items';

        $range = isset($_GET['range']) ? sanitize_text_field(wp_unslash($_GET['range'])) : '30days';
        $range = $this->sanitize_range($range);
        $date_filter = $this->get_date_filter($range);

        // Lesson statistics
        $lesson_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT p.lesson_id, post.post_title as title,
                    COUNT(*) as sales, SUM(p.price) as revenue
             FROM $purchases_table p
             LEFT JOIN {$wpdb->posts} post ON p.lesson_id = post.ID
             WHERE p.purchased_at >= %s
             GROUP BY p.lesson_id
             ORDER BY sales DESC
             LIMIT 20",
            $date_filter
        ));

        // Transaction statistics by price
        $price_stats = $wpdb->get_results($wpdb->prepare(
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
             WHERE purchased_at >= %s
             GROUP BY price_range
             ORDER BY MIN(price)",
            $date_filter
        ));

        // Time-based statistics
        $time_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(purchased_at) as date, COUNT(*) as sales, SUM(price) as revenue
             FROM $purchases_table
             WHERE purchased_at >= %s
             GROUP BY DATE(purchased_at)
             ORDER BY date DESC",
            $date_filter
        ));

        // Bundle statistics
        $bundle_stats = $wpdb->get_results($wpdb->prepare(
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
                LEFT JOIN $order_items_table oi ON o.id = oi.order_id
                WHERE o.status = 'completed' AND o.paid_at >= %s
                GROUP BY o.id
             ) as order_counts
             GROUP BY bundle_type",
            $date_filter
        ));

        // Summary stats
        $summary = array(
            'total_sales' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $purchases_table WHERE purchased_at >= %s",
                $date_filter
            )),
            'total_revenue' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(price) FROM $purchases_table WHERE purchased_at >= %s",
                $date_filter
            )),
            'total_orders' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $orders_table WHERE status = 'completed' AND paid_at >= %s",
                $date_filter
            )),
            'avg_order_value' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(total) FROM $orders_table WHERE status = 'completed' AND paid_at >= %s",
                $date_filter
            )),
        );

        include DL_PLUGIN_DIR . 'admin/partials/statistics-page.php';
    }

    /**
     * Restrict range to known filter keys.
     *
     * @param string $range Raw range from request.
     * @return string Valid range key.
     */
    private function sanitize_range($range) {
        return in_array($range, self::$allowed_ranges, true) ? $range : '30days';
    }

    /**
     * Get date filter based on range
     *
     * @param string $range Whitelisted range key.
     * @return string Date string (Y-m-d) for SQL comparison.
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
}
