<?php
/**
 * Plugin Loader
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Loader {

    public function __construct() {
        $this->load_dependencies();
    }

    /**
     * Load required files
     */
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core includes
        // Core includes
        require_once DL_PLUGIN_DIR . 'includes/class-dl-activator.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-post-types.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-access-control.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-basket.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-checkout.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-payments.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-stripe.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-comgate.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-bank-transfer.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-qr-generator.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-seller.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-invoices.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-emails.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-user.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-user-profile.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-cron.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-ajax.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-analytics.php';
        require_once DL_PLUGIN_DIR . 'includes/class-dl-spam-scoring.php';

        // Admin
        if (is_admin()) {
            require_once DL_PLUGIN_DIR . 'admin/class-dl-admin.php';
            require_once DL_PLUGIN_DIR . 'admin/class-dl-admin-settings.php';
            require_once DL_PLUGIN_DIR . 'admin/class-dl-admin-statistics.php';
            require_once DL_PLUGIN_DIR . 'admin/class-dl-admin-invoices.php';
        }

        // Public
        require_once DL_PLUGIN_DIR . 'public/class-dl-public.php';
    }

    /**
     * Run the plugin
     */
    public function run() {
        DL_Activator::run_migrations();

        // Initialize components
        new DL_Post_Types();
        new DL_Access_Control();
        new DL_Basket();
        new DL_Checkout();
        new DL_Payments();
        new DL_Stripe();
        new DL_Comgate();
        new DL_Bank_Transfer();
        new DL_Emails();
        new DL_User();
        new DL_User_Profile(); // Add this line
        new DL_Cron();
        new DL_Ajax();
        new DL_Analytics();

        // Initialize admin
        if (is_admin()) {
            new DL_Admin();
            new DL_Admin_Settings();
            DL_Admin_Statistics::instance();
            new DL_Admin_Invoices();
        }

        // Initialize public
        new DL_Public();
    }
}