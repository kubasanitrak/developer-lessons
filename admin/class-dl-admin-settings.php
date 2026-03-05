<?php
/**
 * Admin Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Admin_Settings {

    private $tabs;
    private $current_tab;

    public function __construct() {
        $this->tabs = array(
            'general' => __('General', 'developer-lessons'),
            'pricing' => __('Pricing', 'developer-lessons'),
            'comgate' => __('Comgate', 'developer-lessons'),
            'bank_transfer' => __('Bank Transfer', 'developer-lessons'),
            'emails' => __('Emails', 'developer-lessons'),
            'advanced' => __('Advanced', 'developer-lessons')
        );

        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('dl_general_settings', 'dl_currency_code');
        register_setting('dl_general_settings', 'dl_currency_symbol');
        register_setting('dl_general_settings', 'dl_currency_position');
        register_setting('dl_general_settings', 'dl_terms_page');
        register_setting('dl_general_settings', 'dl_landing_page');

        // Pricing settings
        register_setting('dl_pricing_settings', 'dl_bundle_5_discount');
        register_setting('dl_pricing_settings', 'dl_bundle_10_discount');

        // Comgate settings
        register_setting('dl_comgate_settings', 'dl_comgate_enabled');
        register_setting('dl_comgate_settings', 'dl_comgate_merchant_id');
        register_setting('dl_comgate_settings', 'dl_comgate_secret_key');
        register_setting('dl_comgate_settings', 'dl_comgate_test_mode');

        // Bank Transfer settings
        register_setting('dl_bank_transfer_settings', 'dl_bank_transfer_enabled');
        register_setting('dl_bank_transfer_settings', 'dl_bank_account_name');
        register_setting('dl_bank_transfer_settings', 'dl_bank_account_number');
        register_setting('dl_bank_transfer_settings', 'dl_bank_code');
        register_setting('dl_bank_transfer_settings', 'dl_bank_iban');
        register_setting('dl_bank_transfer_settings', 'dl_bank_bic');

        // Email settings
        register_setting('dl_email_settings', 'dl_email_sender_name');
        register_setting('dl_email_settings', 'dl_email_sender_email');
        register_setting('dl_email_settings', 'dl_email_template_type');
        register_setting('dl_email_settings', 'dl_admin_notification_enabled');
        register_setting('dl_email_settings', 'dl_admin_notification_email');

        // Advanced settings
        register_setting('dl_advanced_settings', 'dl_order_expiry_time');
        register_setting('dl_advanced_settings', 'dl_order_expiry_notification');
        register_setting('dl_advanced_settings', 'dl_basket_cleanup_time');
        register_setting('dl_advanced_settings', 'dl_uninstall_delete_data');
    }

    /**
     * Render settings page
     */
    public function render() {
        $this->current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        include DL_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * Render tab content
     */
    public function render_tab_content() {
        switch ($this->current_tab) {
            case 'general':
                $this->render_general_tab();
                break;
            case 'pricing':
                $this->render_pricing_tab();
                break;
            case 'comgate':
                $this->render_comgate_tab();
                break;
            case 'bank_transfer':
                $this->render_bank_transfer_tab();
                break;
            case 'emails':
                $this->render_emails_tab();
                break;
            case 'advanced':
                $this->render_advanced_tab();
                break;
        }
    }

    /**
     * Render General tab
     */
    private function render_general_tab() {
        $pages = get_pages();
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dl_general_settings'); ?>
            
            <h2><?php _e('Currency Settings', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_currency_code"><?php _e('Currency Code', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_currency_code" id="dl_currency_code" 
                               value="<?php echo esc_attr(get_option('dl_currency_code', 'CZK')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('ISO 4217 currency code (e.g., CZK, EUR, USD)', 'developer-lessons'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_currency_symbol"><?php _e('Currency Symbol', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_currency_symbol" id="dl_currency_symbol" 
                               value="<?php echo esc_attr(get_option('dl_currency_symbol', 'Kč')); ?>" 
                               class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_currency_position"><?php _e('Symbol Position', 'developer-lessons'); ?></label></th>
                    <td>
                        <select name="dl_currency_position" id="dl_currency_position">
                            <option value="before" <?php selected(get_option('dl_currency_position'), 'before'); ?>>
                                <?php _e('Before amount ($ 100)', 'developer-lessons'); ?>
                            </option>
                            <option value="after" <?php selected(get_option('dl_currency_position'), 'after'); ?>>
                                <?php _e('After amount (100 Kč)', 'developer-lessons'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2><?php _e('Page Settings', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_terms_page"><?php _e('Terms & Conditions Page', 'developer-lessons'); ?></label></th>
                    <td>
                        <select name="dl_terms_page" id="dl_terms_page">
                            <option value="0"><?php _e('— Select —', 'developer-lessons'); ?></option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo $page->ID; ?>" <?php selected(get_option('dl_terms_page'), $page->ID); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_landing_page"><?php _e('Landing Page (for non-logged-in users)', 'developer-lessons'); ?></label></th>
                    <td>
                        <select name="dl_landing_page" id="dl_landing_page">
                            <option value="0"><?php _e('— Use WordPress Login —', 'developer-lessons'); ?></option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo $page->ID; ?>" <?php selected(get_option('dl_landing_page'), $page->ID); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render Pricing tab
     */
    private function render_pricing_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dl_pricing_settings'); ?>
            
            <h2><?php _e('Bundle Discounts', 'developer-lessons'); ?></h2>
            <p class="description"><?php _e('Set discounts for purchasing multiple lessons at once.', 'developer-lessons'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><label for="dl_bundle_5_discount"><?php _e('5+ Lessons Discount (%)', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="number" name="dl_bundle_5_discount" id="dl_bundle_5_discount" 
                               value="<?php echo esc_attr(get_option('dl_bundle_5_discount', 10)); ?>"
                               min="0" max="100" step="1" class="small-text">%
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_bundle_10_discount"><?php _e('10+ Lessons Discount (%)', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="number" name="dl_bundle_10_discount" id="dl_bundle_10_discount" 
                               value="<?php echo esc_attr(get_option('dl_bundle_10_discount', 20)); ?>"
                               min="0" max="100" step="1" class="small-text">%
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render Comgate tab
     */
    private function render_comgate_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dl_comgate_settings'); ?>
            
            <h2><?php _e('Comgate Payment Gateway', 'developer-lessons'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="dl_comgate_enabled"><?php _e('Enable Card Payments', 'developer-lessons'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="dl_comgate_enabled" id="dl_comgate_enabled" 
                                   value="1" <?php checked(get_option('dl_comgate_enabled'), 1); ?>>
                            <?php _e('Enable Comgate payment gateway', 'developer-lessons'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_comgate_merchant_id"><?php _e('Merchant ID', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_comgate_merchant_id" id="dl_comgate_merchant_id" 
                               value="<?php echo esc_attr(get_option('dl_comgate_merchant_id')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_comgate_secret_key"><?php _e('Secret Key', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="password" name="dl_comgate_secret_key" id="dl_comgate_secret_key" 
                               value="<?php echo esc_attr(get_option('dl_comgate_secret_key')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_comgate_test_mode"><?php _e('Test Mode', 'developer-lessons'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="dl_comgate_test_mode" id="dl_comgate_test_mode" 
                                   value="1" <?php checked(get_option('dl_comgate_test_mode', 1), 1); ?>>
                            <?php _e('Enable test mode (sandbox)', 'developer-lessons'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render Bank Transfer tab
     */
    private function render_bank_transfer_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dl_bank_transfer_settings'); ?>
            
            <h2><?php _e('Bank Transfer Settings', 'developer-lessons'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="dl_bank_transfer_enabled"><?php _e('Enable Bank Transfer', 'developer-lessons'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="dl_bank_transfer_enabled" id="dl_bank_transfer_enabled" 
                                   value="1" <?php checked(get_option('dl_bank_transfer_enabled'), 1); ?>>
                            <?php _e('Enable bank transfer payments', 'developer-lessons'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_bank_account_name"><?php _e('Account Name', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_bank_account_name" id="dl_bank_account_name" 
                               value="<?php echo esc_attr(get_option('dl_bank_account_name')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_bank_account_number"><?php _e('Account Number', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_bank_account_number" id="dl_bank_account_number" 
                               value="<?php echo esc_attr(get_option('dl_bank_account_number')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Without bank code', 'developer-lessons'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_bank_code"><?php _e('Bank Code', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_bank_code" id="dl_bank_code" 
                               value="<?php echo esc_attr(get_option('dl_bank_code')); ?>" 
                               class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_bank_iban"><?php _e('IBAN', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_bank_iban" id="dl_bank_iban" 
                               value="<?php echo esc_attr(get_option('dl_bank_iban')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_bank_bic"><?php _e('BIC/SWIFT', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_bank_bic" id="dl_bank_bic" 
                               value="<?php echo esc_attr(get_option('dl_bank_bic')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render Emails tab
     */
    private function render_emails_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dl_email_settings'); ?>
            
            <h2><?php _e('Sender Settings', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_email_sender_name"><?php _e('Sender Name', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="text" name="dl_email_sender_name" id="dl_email_sender_name" 
                               value="<?php echo esc_attr(get_option('dl_email_sender_name', get_bloginfo('name'))); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_email_sender_email"><?php _e('Sender Email', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="email" name="dl_email_sender_email" id="dl_email_sender_email" 
                               value="<?php echo esc_attr(get_option('dl_email_sender_email', get_option('admin_email'))); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>

            <h2><?php _e('Template Settings', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_email_template_type"><?php _e('Email Format', 'developer-lessons'); ?></label></th>
                    <td>
                        <select name="dl_email_template_type" id="dl_email_template_type">
                            <option value="html" <?php selected(get_option('dl_email_template_type'), 'html'); ?>>
                                <?php _e('HTML', 'developer-lessons'); ?>
                            </option>
                            <option value="plain" <?php selected(get_option('dl_email_template_type'), 'plain'); ?>>
                                <?php _e('Plain Text', 'developer-lessons'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2><?php _e('Admin Notifications', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_admin_notification_enabled"><?php _e('Enable Notifications', 'developer-lessons'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="dl_admin_notification_enabled" id="dl_admin_notification_enabled" 
                                   value="1" <?php checked(get_option('dl_admin_notification_enabled', 1), 1); ?>>
                            <?php _e('Send email notifications for new purchases', 'developer-lessons'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_admin_notification_email"><?php _e('Notification Email', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="email" name="dl_admin_notification_email" id="dl_admin_notification_email" 
                               value="<?php echo esc_attr(get_option('dl_admin_notification_email', get_option('admin_email'))); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>

            <h2><?php _e('Email Types', 'developer-lessons'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Email Type', 'developer-lessons'); ?></th>
                        <th><?php _e('Recipient', 'developer-lessons'); ?></th>
                        <th><?php _e('Trigger', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Purchase Confirmation', 'developer-lessons'); ?></td>
                        <td><?php _e('Customer', 'developer-lessons'); ?></td>
                        <td><?php _e('Payment Completed', 'developer-lessons'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Admin Notification', 'developer-lessons'); ?></td>
                        <td><?php _e('Admin', 'developer-lessons'); ?></td>
                        <td><?php _e('New Purchase', 'developer-lessons'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Order Expiry Warning', 'developer-lessons'); ?></td>
                        <td><?php _e('Customer', 'developer-lessons'); ?></td>
                        <td><?php _e('Order Expiring Soon', 'developer-lessons'); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render Advanced tab
     */
    private function render_advanced_tab() {
        $cron_status = DL_Cron::get_cron_status();
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dl_advanced_settings'); ?>
            
            <h2><?php _e('Order Settings', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_order_expiry_time"><?php _e('Order Expiry Time (hours)', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="number" name="dl_order_expiry_time" id="dl_order_expiry_time" 
                               value="<?php echo esc_attr(get_option('dl_order_expiry_time', 24)); ?>"
                               min="1" max="168" class="small-text">
                        <p class="description"><?php _e('Pending orders will expire after this time', 'developer-lessons'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="dl_order_expiry_notification"><?php _e('Expiry Notification', 'developer-lessons'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="dl_order_expiry_notification" id="dl_order_expiry_notification" 
                                   value="1" <?php checked(get_option('dl_order_expiry_notification', 1), 1); ?>>
                            <?php _e('Notify customers when their order is about to expire', 'developer-lessons'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h2><?php _e('Basket Settings', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_basket_cleanup_time"><?php _e('Basket Cleanup Time (hours)', 'developer-lessons'); ?></label></th>
                    <td>
                        <input type="number" name="dl_basket_cleanup_time" id="dl_basket_cleanup_time" 
                               value="<?php echo esc_attr(get_option('dl_basket_cleanup_time', 72)); ?>"
                               min="1" max="720" class="small-text">
                        <p class="description"><?php _e('Remove basket items older than this', 'developer-lessons'); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php _e('Data Management', 'developer-lessons'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="dl_uninstall_delete_data"><?php _e('Uninstall Behavior', 'developer-lessons'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="dl_uninstall_delete_data" id="dl_uninstall_delete_data" 
                                   value="1" <?php checked(get_option('dl_uninstall_delete_data'), 1); ?>>
                            <?php _e('Delete all plugin data when uninstalling (WARNING: This cannot be undone!)', 'developer-lessons'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <h2><?php _e('Cron Jobs Status', 'developer-lessons'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Job', 'developer-lessons'); ?></th>
                    <th><?php _e('Next Run', 'developer-lessons'); ?></th>
                    <th><?php _e('Status', 'developer-lessons'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('Hourly Tasks', 'developer-lessons'); ?></td>
                    <td><?php echo esc_html($cron_status['hourly']['next_run_formatted']); ?></td>
                    <td>
                        <?php if ($cron_status['hourly']['next_run']): ?>
                            <span class="dl-status-active"><?php _e('Scheduled', 'developer-lessons'); ?></span>
                        <?php else: ?>
                            <span class="dl-status-inactive"><?php _e('Not Scheduled', 'developer-lessons'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php _e('Daily Tasks', 'developer-lessons'); ?></td>
                    <td><?php echo esc_html($cron_status['daily']['next_run_formatted']); ?></td>
                    <td>
                        <?php if ($cron_status['daily']['next_run']): ?>
                            <span class="dl-status-active"><?php _e('Scheduled', 'developer-lessons'); ?></span>
                        <?php else: ?>
                            <span class="dl-status-inactive"><?php _e('Not Scheduled', 'developer-lessons'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php _e('System Info', 'developer-lessons'); ?></h2>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <th><?php _e('Plugin Version', 'developer-lessons'); ?></th>
                    <td><?php echo DL_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php _e('WordPress Version', 'developer-lessons'); ?></th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('PHP Version', 'developer-lessons'); ?></th>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Database Version', 'developer-lessons'); ?></th>
                    <td><?php echo get_option('dl_db_version', 'Unknown'); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}
