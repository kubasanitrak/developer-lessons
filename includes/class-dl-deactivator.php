<?php
/**
 * Plugin Deactivator
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Deactivator {

    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('dl_hourly_cron');
        wp_clear_scheduled_hook('dl_daily_cron');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
