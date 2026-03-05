<?php
/**
 * Uninstall script
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if data should be deleted
if (!get_option('dl_uninstall_delete_data')) {
    return;
}

global $wpdb;

// Delete custom tables
$tables = array(
    $wpdb->prefix . 'dl_orders',
    $wpdb->prefix . 'dl_order_items',
    $wpdb->prefix . 'dl_purchases',
    $wpdb->prefix . 'dl_basket',
    $wpdb->prefix . 'dl_logs'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete options
$options = array(
    'dl_currency_code',
    'dl_currency_symbol',
    'dl_currency_position',
    'dl_terms_page',
    'dl_landing_page',
    'dl_bundle_5_discount',
    'dl_bundle_10_discount',
    'dl_comgate_enabled',
    'dl_comgate_merchant_id',
    'dl_comgate_secret_key',
    'dl_comgate_test_mode',
    'dl_bank_transfer_enabled',
    'dl_bank_account_name',
    'dl_bank_account_number',
    'dl_bank_code',
    'dl_bank_iban',
    'dl_bank_bic',
    'dl_email_sender_name',
    'dl_email_sender_email',
    'dl_email_template_type',
    'dl_admin_notification_enabled',
    'dl_admin_notification_email',
    'dl_order_expiry_time',
    'dl_order_expiry_notification',
    'dl_basket_cleanup_time',
    'dl_uninstall_delete_data',
    'dl_db_version',
    'dl_page_ids',
    'dl_plugin_activated'
);

foreach ($options as $option) {
    delete_option($option);
}

// Delete lesson post meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_dl_%'");

// Delete created pages
$page_ids = get_option('dl_page_ids', array());
foreach ($page_ids as $page_id) {
    wp_delete_post($page_id, true);
}

// Clear scheduled cron jobs
wp_clear_scheduled_hook('dl_hourly_cron');
wp_clear_scheduled_hook('dl_daily_cron');

// Flush rewrite rules
flush_rewrite_rules();
