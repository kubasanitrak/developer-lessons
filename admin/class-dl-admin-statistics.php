<?php
/**
 * Admin Statistics
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Admin_Statistics {

    /**
     * Render statistics page
     */
    public function render() {
        global $wpdb;

        $purchases_table = $wpdb->prefix . 'dl_purchases';
        $orders_table = $wpdb->prefix . 'dl_orders';

        // Date range filter
        $range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '30days';
        $date_filter = $this->get_date_filter($range);

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
}
