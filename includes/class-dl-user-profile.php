<?php
/**
 * User Profile - Invoice Fields
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_User_Profile {

    public function __construct() {
        // Add fields to user profile (admin)
        add_action('show_user_profile', array($this, 'add_invoice_fields'));
        add_action('edit_user_profile', array($this, 'add_invoice_fields'));
        
        // Save fields
        add_action('personal_options_update', array($this, 'save_invoice_fields'));
        add_action('edit_user_profile_update', array($this, 'save_invoice_fields'));
        
        // Add fields to frontend profile (if using a frontend profile page)
        add_shortcode('dl_invoice_profile', array($this, 'render_invoice_form_shortcode'));
        
        // AJAX save
        add_action('wp_ajax_dl_save_invoice_profile', array($this, 'ajax_save_invoice_profile'));
    }

    /**
     * Add invoice fields to user profile in admin
     */
    public function add_invoice_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        ?>
        <h2><?php _e('Invoice Details', 'developer-lessons'); ?></h2>
        <p class="description"><?php _e('These details will be used for invoices when purchasing lessons.', 'developer-lessons'); ?></p>
        
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="dl_invoice_company_name"><?php _e('Company Name', 'developer-lessons'); ?></label></th>
                <td>
                    <input type="text" name="dl_invoice_company_name" id="dl_invoice_company_name" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'dl_invoice_company_name', true)); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="dl_invoice_street"><?php _e('Street', 'developer-lessons'); ?></label></th>
                <td>
                    <input type="text" name="dl_invoice_street" id="dl_invoice_street" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'dl_invoice_street', true)); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="dl_invoice_street_number"><?php _e('Street Number', 'developer-lessons'); ?></label></th>
                <td>
                    <input type="text" name="dl_invoice_street_number" id="dl_invoice_street_number" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'dl_invoice_street_number', true)); ?>" 
                           class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="dl_invoice_city"><?php _e('City', 'developer-lessons'); ?></label></th>
                <td>
                    <input type="text" name="dl_invoice_city" id="dl_invoice_city" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'dl_invoice_city', true)); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="dl_invoice_zip"><?php _e('ZIP Code', 'developer-lessons'); ?></label></th>
                <td>
                    <input type="text" name="dl_invoice_zip" id="dl_invoice_zip" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'dl_invoice_zip', true)); ?>" 
                           class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="dl_invoice_ic"><?php _e('Company ID (IČ)', 'developer-lessons'); ?></label></th>
                <td>
                    <input type="text" name="dl_invoice_ic" id="dl_invoice_ic" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'dl_invoice_ic', true)); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('8-digit company identification number', 'developer-lessons'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="dl_invoice_dic"><?php _e('VAT ID (DIČ)', 'developer-lessons'); ?></label></th>
                <td>
                    <input type="text" name="dl_invoice_dic" id="dl_invoice_dic" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'dl_invoice_dic', true)); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('VAT identification number (e.g., CZ12345678). Leave empty if not a VAT payer.', 'developer-lessons'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save invoice fields from admin profile
     */
    public function save_invoice_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        $fields = array(
            'dl_invoice_company_name',
            'dl_invoice_street',
            'dl_invoice_street_number',
            'dl_invoice_city',
            'dl_invoice_zip',
            'dl_invoice_ic',
            'dl_invoice_dic'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * Render invoice form shortcode for frontend
     * Usage: [dl_invoice_profile]
     */
    public function render_invoice_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to manage your invoice details.', 'developer-lessons') . '</p>';
        }

        $user_id = get_current_user_id();
        
        $invoice_data = array(
            'company_name' => get_user_meta($user_id, 'dl_invoice_company_name', true),
            'street' => get_user_meta($user_id, 'dl_invoice_street', true),
            'street_number' => get_user_meta($user_id, 'dl_invoice_street_number', true),
            'city' => get_user_meta($user_id, 'dl_invoice_city', true),
            'zip' => get_user_meta($user_id, 'dl_invoice_zip', true),
            'ic' => get_user_meta($user_id, 'dl_invoice_ic', true),
            'dic' => get_user_meta($user_id, 'dl_invoice_dic', true),
        );

        ob_start();
        ?>
        <div class="dl-invoice-profile-form">
            <h3><?php _e('Invoice Details', 'developer-lessons'); ?></h3>
            <p class="dl-form-description"><?php _e('These details will be used when you request an invoice during checkout.', 'developer-lessons'); ?></p>
            
            <form id="dl-invoice-profile-form" class="dl-form">
                <div class="dl-form-row">
                    <label for="dl_profile_company_name"><?php _e('Company Name', 'developer-lessons'); ?></label>
                    <input type="text" name="company_name" id="dl_profile_company_name" 
                           value="<?php echo esc_attr($invoice_data['company_name']); ?>">
                </div>

                <div class="dl-form-row dl-form-row-half">
                    <div class="dl-form-col">
                        <label for="dl_profile_street"><?php _e('Street', 'developer-lessons'); ?></label>
                        <input type="text" name="street" id="dl_profile_street" 
                               value="<?php echo esc_attr($invoice_data['street']); ?>">
                    </div>
                    <div class="dl-form-col dl-form-col-small">
                        <label for="dl_profile_street_number"><?php _e('Number', 'developer-lessons'); ?></label>
                        <input type="text" name="street_number" id="dl_profile_street_number" 
                               value="<?php echo esc_attr($invoice_data['street_number']); ?>">
                    </div>
                </div>

                <div class="dl-form-row dl-form-row-half">
                    <div class="dl-form-col dl-form-col-small">
                        <label for="dl_profile_zip"><?php _e('ZIP Code', 'developer-lessons'); ?></label>
                        <input type="text" name="zip" id="dl_profile_zip" 
                               value="<?php echo esc_attr($invoice_data['zip']); ?>">
                    </div>
                    <div class="dl-form-col">
                        <label for="dl_profile_city"><?php _e('City', 'developer-lessons'); ?></label>
                        <input type="text" name="city" id="dl_profile_city" 
                               value="<?php echo esc_attr($invoice_data['city']); ?>">
                    </div>
                </div>

                <div class="dl-form-row dl-form-row-half">
                    <div class="dl-form-col">
                        <label for="dl_profile_ic"><?php _e('Company ID (IČ)', 'developer-lessons'); ?></label>
                        <input type="text" name="ic" id="dl_profile_ic" 
                               value="<?php echo esc_attr($invoice_data['ic']); ?>"
                               placeholder="<?php _e('e.g., 12345678', 'developer-lessons'); ?>">
                    </div>
                    <div class="dl-form-col">
                        <label for="dl_profile_dic"><?php _e('VAT ID (DIČ)', 'developer-lessons'); ?></label>
                        <input type="text" name="dic" id="dl_profile_dic" 
                               value="<?php echo esc_attr($invoice_data['dic']); ?>"
                               placeholder="<?php _e('e.g., CZ12345678', 'developer-lessons'); ?>">
                        <p class="dl-field-hint"><?php _e('Leave empty if not a VAT payer', 'developer-lessons'); ?></p>
                    </div>
                </div>

                <div class="dl-form-actions">
                    <button type="submit" class="dl-btn dl-btn-primary" id="dl-save-invoice-profile">
                        <?php _e('Save Invoice Details', 'developer-lessons'); ?>
                    </button>
                    <span class="dl-form-message" id="dl-invoice-message"></span>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#dl-invoice-profile-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $btn = $('#dl-save-invoice-profile');
                var $message = $('#dl-invoice-message');
                var originalText = $btn.text();
                
                $btn.prop('disabled', true).text('<?php _e('Saving...', 'developer-lessons'); ?>');
                $message.removeClass('success error').text('');
                
                $.ajax({
                    url: dl_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dl_save_invoice_profile',
                        nonce: dl_public.nonce,
                        company_name: $form.find('[name="company_name"]').val(),
                        street: $form.find('[name="street"]').val(),
                        street_number: $form.find('[name="street_number"]').val(),
                        city: $form.find('[name="city"]').val(),
                        zip: $form.find('[name="zip"]').val(),
                        ic: $form.find('[name="ic"]').val(),
                        dic: $form.find('[name="dic"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.addClass('success').text(response.data.message);
                        } else {
                            $message.addClass('error').text(response.data.message);
                        }
                        $btn.prop('disabled', false).text(originalText);
                    },
                    error: function() {
                        $message.addClass('error').text('<?php _e('An error occurred. Please try again.', 'developer-lessons'); ?>');
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Save invoice profile
     */
    public function ajax_save_invoice_profile() {
        check_ajax_referer('dl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'developer-lessons')));
        }

        $user_id = get_current_user_id();

        $fields = array(
            'company_name' => 'dl_invoice_company_name',
            'street' => 'dl_invoice_street',
            'street_number' => 'dl_invoice_street_number',
            'city' => 'dl_invoice_city',
            'zip' => 'dl_invoice_zip',
            'ic' => 'dl_invoice_ic',
            'dic' => 'dl_invoice_dic'
        );

        foreach ($fields as $post_key => $meta_key) {
            $value = isset($_POST[$post_key]) ? sanitize_text_field($_POST[$post_key]) : '';
            update_user_meta($user_id, $meta_key, $value);
        }

        wp_send_json_success(array('message' => __('Invoice details saved successfully.', 'developer-lessons')));
    }

    /**
     * Get user's invoice data
     */
    public static function get_user_invoice_data($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return array(
            'company_name' => get_user_meta($user_id, 'dl_invoice_company_name', true),
            'street' => get_user_meta($user_id, 'dl_invoice_street', true),
            'street_number' => get_user_meta($user_id, 'dl_invoice_street_number', true),
            'city' => get_user_meta($user_id, 'dl_invoice_city', true),
            'zip' => get_user_meta($user_id, 'dl_invoice_zip', true),
            'ic' => get_user_meta($user_id, 'dl_invoice_ic', true),
            'dic' => get_user_meta($user_id, 'dl_invoice_dic', true),
        );
    }

    /**
     * Format invoice address
     */
    public static function format_invoice_address($invoice_data) {
        $parts = array();
        
        if (!empty($invoice_data['company_name'])) {
            $parts[] = $invoice_data['company_name'];
        }
        
        $street_line = trim($invoice_data['street'] . ' ' . $invoice_data['street_number']);
        if (!empty($street_line)) {
            $parts[] = $street_line;
        }
        
        $city_line = trim($invoice_data['zip'] . ' ' . $invoice_data['city']);
        if (!empty($city_line)) {
            $parts[] = $city_line;
        }
        
        if (!empty($invoice_data['ic'])) {
            $parts[] = 'IČ: ' . $invoice_data['ic'];
        }
        
        if (!empty($invoice_data['dic'])) {
            $parts[] = 'DIČ: ' . $invoice_data['dic'];
        }
        
        return implode("\n", $parts);
    }
}
