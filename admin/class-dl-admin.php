<?php
/**
 * Admin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    /**
     * Add admin menu items under Lessons CPT
     */
    public function add_admin_menu() {
        // Add submenus under the Lessons CPT menu
        // The parent slug for a CPT is 'edit.php?post_type={post_type}'
        $parent_slug = 'edit.php?post_type=lesson';

        // Dashboard/Overview
        add_submenu_page(
            $parent_slug,
            __('Dashboard', 'developer-lessons'),
            __('Dashboard', 'developer-lessons'),
            'manage_options',
            'dl-dashboard',
            array($this, 'render_dashboard')
        );

        // Orders
        add_submenu_page(
            $parent_slug,
            __('Orders', 'developer-lessons'),
            __('Orders', 'developer-lessons'),
            'manage_options',
            'dl-orders',
            array($this, 'render_orders')
        );

        // Statistics
        add_submenu_page(
            $parent_slug,
            __('Statistics', 'developer-lessons'),
            __('Statistics', 'developer-lessons'),
            'manage_options',
            'dl-statistics',
            array($this, 'render_statistics')
        );

        // Settings
        add_submenu_page(
            $parent_slug,
            __('Settings', 'developer-lessons'),
            __('Settings', 'developer-lessons'),
            'manage_options',
            'dl-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Check if we're on our plugin pages or lesson edit screen
        $is_plugin_page = strpos($hook, 'dl-') !== false;
        $is_lesson_page = (get_post_type() === 'lesson') || (isset($_GET['post_type']) && $_GET['post_type'] === 'lesson');
        
        if (!$is_plugin_page && !$is_lesson_page) {
            return;
        }

        wp_enqueue_style(
            'dl-admin-css',
            DL_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            DL_VERSION
        );

        wp_enqueue_script(
            'dl-admin-js',
            DL_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            DL_VERSION,
            true
        );

        wp_localize_script('dl-admin-js', 'dl_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dl_admin_nonce'),
            'strings' => array(
                'confirm_action' => __('Are you sure?', 'developer-lessons'),
                'processing' => __('Processing...', 'developer-lessons'),
                'success' => __('Success!', 'developer-lessons'),
                'error' => __('An error occurred.', 'developer-lessons')
            )
        ));
    }

    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!isset($_GET['dl_action']) || !isset($_GET['_wpnonce'])) {
            return;
        }

        if (!wp_verify_nonce($_GET['_wpnonce'], 'dl_admin_action')) {
            return;
        }

        $action = sanitize_text_field($_GET['dl_action']);
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        switch ($action) {
            case 'confirm_payment':
                if ($order_id && current_user_can('manage_options')) {
                    DL_Payments::complete_payment($order_id, 'manual_confirmation');
                    wp_redirect(add_query_arg('message', 'payment_confirmed', admin_url('edit.php?post_type=lesson&page=dl-orders')));
                    exit;
                }
                break;

            case 'cancel_order':
                if ($order_id && current_user_can('manage_options')) {
                    DL_Checkout::update_order_status($order_id, 'cancelled');
                    wp_redirect(add_query_arg('message', 'order_cancelled', admin_url('edit.php?post_type=lesson&page=dl-orders')));
                    exit;
                }
                break;
        }
    }

    /**
     * Render admin dashboard
     */
    public function render_dashboard() {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';
        $purchases_table = $wpdb->prefix . 'dl_purchases';

        // Check if tables exist
        $orders_exists = $wpdb->get_var("SHOW TABLES LIKE '$orders_table'") === $orders_table;
        $purchases_exists = $wpdb->get_var("SHOW TABLES LIKE '$purchases_table'") === $purchases_table;

        // Quick stats
        $total_lessons = wp_count_posts('lesson')->publish;
        
        if ($purchases_exists) {
            $total_sales = $wpdb->get_var("SELECT COUNT(*) FROM $purchases_table");
            $total_revenue = $wpdb->get_var("SELECT COALESCE(SUM(price), 0) FROM $purchases_table");
        } else {
            $total_sales = 0;
            $total_revenue = 0;
        }
        
        if ($orders_exists) {
            $pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status IN ('pending', 'awaiting_payment')");
            
            // Recent orders
            $recent_orders = $wpdb->get_results(
                "SELECT o.*, u.display_name, u.user_email 
                 FROM $orders_table o 
                 LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
                 ORDER BY o.created_at DESC 
                 LIMIT 10"
            );
        } else {
            $pending_orders = 0;
            $recent_orders = array();
        }

        include DL_PLUGIN_DIR . 'admin/partials/dashboard-page.php';
    }

    /**
     * Render orders page
     */
    public function render_orders() {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'dl_orders';
        $order_items_table = $wpdb->prefix . 'dl_order_items';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$orders_table'") !== $orders_table) {
            echo '<div class="wrap"><h1>' . __('Orders', 'developer-lessons') . '</h1>';
            echo '<p>' . __('No orders table found. Please deactivate and reactivate the plugin.', 'developer-lessons') . '</p></div>';
            return;
        }

        // Filter
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        $where = '';
        if ($status_filter) {
            $where = $wpdb->prepare(" WHERE o.status = %s", $status_filter);
        }

        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table o $where");
        $total_pages = ceil($total_orders / $per_page);

        $orders = $wpdb->get_results(
            "SELECT o.*, u.display_name, u.user_email 
             FROM $orders_table o 
             LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
             $where
             ORDER BY o.created_at DESC 
             LIMIT $offset, $per_page"
        );

        include DL_PLUGIN_DIR . 'admin/partials/orders-page.php';
    }

    /**
     * Render statistics page
     */
    public function render_statistics() {
        $statistics = new DL_Admin_Statistics();
        $statistics->render();
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        $settings = new DL_Admin_Settings();
        $settings->render();
    }
}
