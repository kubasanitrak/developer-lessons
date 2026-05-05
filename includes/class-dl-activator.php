<?php
/**
 * Plugin Activator
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Activator {

    public static function activate() {
        self::create_tables();
        self::run_migrations(); // Add this line
        self::create_pages();
        self::set_default_options();
        self::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('dl_plugin_activated', true);
    }
    
    /**
     * Run database migrations
     */
    public static function run_migrations() {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'dl_orders';
        
        // Check if table exists first
        if ($wpdb->get_var("SHOW TABLES LIKE '$orders_table'") !== $orders_table) {
            return;
        }
        
        // Check if invoice_data column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $orders_table LIKE 'invoice_data'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $orders_table ADD COLUMN invoice_data longtext DEFAULT NULL AFTER transaction_id");
        }
    }

    /**
     * Create custom database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Orders table
        $orders_table = $wpdb->prefix . 'dl_orders';
        // Orders table (update the CREATE TABLE statement)
        $orders_sql = "CREATE TABLE $orders_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            order_number varchar(32) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) NOT NULL,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            discount decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) NOT NULL DEFAULT 'CZK',
            transaction_id varchar(100) DEFAULT NULL,
            invoice_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_number (order_number),
            KEY status (status)
        ) $charset_collate;";

        
        // Order items table
        $order_items_table = $wpdb->prefix . 'dl_order_items';
        $order_items_sql = "CREATE TABLE $order_items_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY lesson_id (lesson_id)
        ) $charset_collate;";
        
        // Purchases table (completed purchases)
        $purchases_table = $wpdb->prefix . 'dl_purchases';
        $purchases_sql = "CREATE TABLE $purchases_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            order_id bigint(20) UNSIGNED NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            purchased_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_id (user_id),
            KEY lesson_id (lesson_id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        // Basket table
        $basket_table = $wpdb->prefix . 'dl_basket';
        $basket_sql = "CREATE TABLE $basket_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Statistics/Logs table
        $logs_table = $wpdb->prefix . 'dl_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            data longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($orders_sql);
        dbDelta($order_items_sql);
        dbDelta($purchases_sql);
        dbDelta($basket_sql);
        dbDelta($logs_sql);
        
        update_option('dl_db_version', DL_VERSION);
    }

    /**
     * Create required pages
     */
    private static function create_pages() {
        $pages = array(
            'checkout' => array(
                'title' => __('Checkout', 'developer-lessons'),
                'slug' => 'checkout',
                'content' => '[dl_checkout]'
            ),
            'dashboard' => array(
                'title' => __('My Lessons', 'developer-lessons'),
                'slug' => 'dashboard',
                'content' => '[dl_dashboard]'
            ),
            'payment_success' => array(
                'title' => __('Payment Successful', 'developer-lessons'),
                'slug' => 'payment-success',
                'content' => '[dl_payment_success]'
            ),
            'payment_failed' => array(
                'title' => __('Payment Failed', 'developer-lessons'),
                'slug' => 'payment-failed',
                'content' => '[dl_payment_failed]'
            )
        );
        
        $page_ids = array();
        
        foreach ($pages as $key => $page) {
            $existing = get_page_by_path($page['slug']);
            
            if (!$existing) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_name' => $page['slug'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                ));
                $page_ids[$key] = $page_id;
            } else {
                $page_ids[$key] = $existing->ID;
            }
        }
        
        update_option('dl_page_ids', $page_ids);
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            // General
            'dl_currency_code' => 'CZK',
            'dl_currency_symbol' => 'Kč',
            'dl_currency_position' => 'after',
            'dl_terms_page' => 0,
            'dl_landing_page' => 0,
            
            // Pricing
            'dl_bundle_5_discount' => 10,
            'dl_bundle_10_discount' => 20,
            
            // Comgate
            'dl_comgate_enabled' => 0,
            'dl_comgate_merchant_id' => '',
            'dl_comgate_secret_key' => '',
            'dl_comgate_test_mode' => 1,
            
            // Bank Transfer
            'dl_bank_transfer_enabled' => 1,
            'dl_bank_account_name' => '',
            'dl_bank_account_number' => '',
            'dl_bank_code' => '',
            'dl_bank_iban' => '',
            'dl_bank_bic' => '',
            
            // Emails
            'dl_email_sender_name' => get_bloginfo('name'),
            'dl_email_sender_email' => get_option('admin_email'),
            'dl_email_template_type' => 'html',
            'dl_admin_notification_enabled' => 1,
            'dl_admin_notification_email' => get_option('admin_email'),
            
            // Advanced
            'dl_order_expiry_time' => 24,
            'dl_order_expiry_notification' => 1,
            'dl_basket_cleanup_time' => 72,
            'dl_uninstall_delete_data' => 0
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs() {
        if (!wp_next_scheduled('dl_hourly_cron')) {
            wp_schedule_event(time(), 'hourly', 'dl_hourly_cron');
        }
        
        if (!wp_next_scheduled('dl_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'dl_daily_cron');
        }
    }
}
