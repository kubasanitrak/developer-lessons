<?php
/**
 * Checkout page template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if bank transfer info should be shown
if (isset($_GET['order']) && isset($_GET['method']) && $_GET['method'] === 'bank_transfer') {
    echo DL_Bank_Transfer::render_transfer_info(intval($_GET['order']));
    return;
}

// Get user's saved invoice data
$user_id = get_current_user_id();
$saved_invoice = array(
    'company_name' => get_user_meta($user_id, 'dl_invoice_company_name', true),
    'street' => get_user_meta($user_id, 'dl_invoice_street', true),
    'street_number' => get_user_meta($user_id, 'dl_invoice_street_number', true),
    'city' => get_user_meta($user_id, 'dl_invoice_city', true),
    'zip' => get_user_meta($user_id, 'dl_invoice_zip', true),
    'ic' => get_user_meta($user_id, 'dl_invoice_ic', true),
    'dic' => get_user_meta($user_id, 'dl_invoice_dic', true),
);
$has_saved_invoice = !empty($saved_invoice['company_name']) && !empty($saved_invoice['ic']);
?>
<div class="dl-checkout">
    <div class="dl-checkout-content">
        <h2><?php _e('Your Order', 'developer-lessons'); ?> <span class="dl-checkout-item-count">(<?php echo count($items); ?>)</span></h2>
        
        <table class="dl-checkout-items">
            <thead>
                <tr>
                    <th><?php _e('Lesson', 'developer-lessons'); ?></th>
                    <th class="dl-price-col"><?php _e('Price', 'developer-lessons'); ?></th>
                    <th class="dl-action-col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr data-lesson-id="<?php echo esc_attr($item->lesson_id); ?>">
                        <td>
                            <a href="<?php echo esc_url($item->permalink); ?>">
                                <?php echo esc_html($item->lesson_title); ?>
                            </a>
                        </td>
                        <td class="dl-price-col">
                            <?php echo DL_Payments::format_price($item->price); ?>
                        </td>
                        <td class="dl-action-col">
                            <button type="button" class="dl-remove-item" data-lesson-id="<?php echo esc_attr($item->lesson_id); ?>" title="<?php _e('Remove', 'developer-lessons'); ?>">
                                &times;
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="dl-subtotal-row">
                    <th><?php _e('Subtotal', 'developer-lessons'); ?></th>
                    <td colspan="2" class="dl-price-col">
                        <?php echo DL_Payments::format_price($subtotal); ?>
                    </td>
                </tr>
                <?php if ($discount['amount'] > 0): ?>
                    <tr class="dl-discount-row">
                        <th><?php printf(__('Bundle Discount (%d%%)', 'developer-lessons'), $discount['percentage']); ?></th>
                        <td colspan="2" class="dl-price-col dl-discount">
                            -<?php echo DL_Payments::format_price($discount['amount']); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr class="dl-total-row">
                    <th><?php _e('Total', 'developer-lessons'); ?></th>
                    <td colspan="2" class="dl-price-col dl-total">
                        <?php echo DL_Payments::format_price($total); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <form id="dl-checkout-form" class="dl-checkout-form">
            
            <!-- Invoice Section -->
            <div class="dl-checkout-section dl-invoice-section">
                <h3><?php _e('Invoice Details', 'developer-lessons'); ?></h3>
                
                <div class="dl-invoice-toggle">
                    <label class="dl-checkbox-label">
                        <input type="checkbox" name="want_invoice" id="dl_want_invoice" value="1" <?php checked($has_saved_invoice); ?>>
                        <span><?php _e('I want an invoice for my purchase', 'developer-lessons'); ?></span>
                    </label>
                </div>

                <div class="dl-invoice-fields" style="<?php echo $has_saved_invoice ? '' : 'display: none;'; ?>">
                    
                    <?php if ($has_saved_invoice): ?>
                        <div class="dl-saved-invoice-notice">
                            <p><?php _e('Using your saved invoice details. You can update them below.', 'developer-lessons'); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="dl-form-row">
                        <label for="dl_invoice_company_name"><?php _e('Company Name', 'developer-lessons'); ?> <span class="required">*</span></label>
                        <input type="text" name="invoice_company_name" id="dl_invoice_company_name" 
                               value="<?php echo esc_attr($saved_invoice['company_name']); ?>" 
                               placeholder="<?php _e('Enter company name', 'developer-lessons'); ?>">
                    </div>

                    <div class="dl-form-row dl-form-row-half">
                        <div class="dl-form-col">
                            <label for="dl_invoice_street"><?php _e('Street', 'developer-lessons'); ?> <span class="required">*</span></label>
                            <input type="text" name="invoice_street" id="dl_invoice_street" 
                                   value="<?php echo esc_attr($saved_invoice['street']); ?>" 
                                   placeholder="<?php _e('Street name', 'developer-lessons'); ?>">
                        </div>
                        <div class="dl-form-col dl-form-col-small">
                            <label for="dl_invoice_street_number"><?php _e('Number', 'developer-lessons'); ?></label>
                            <input type="text" name="invoice_street_number" id="dl_invoice_street_number" 
                                   value="<?php echo esc_attr($saved_invoice['street_number']); ?>" 
                                   placeholder="<?php _e('No.', 'developer-lessons'); ?>">
                        </div>
                    </div>

                    <div class="dl-form-row dl-form-row-half">
                        <div class="dl-form-col dl-form-col-small">
                            <label for="dl_invoice_zip"><?php _e('ZIP Code', 'developer-lessons'); ?> <span class="required">*</span></label>
                            <input type="text" name="invoice_zip" id="dl_invoice_zip" 
                                   value="<?php echo esc_attr($saved_invoice['zip']); ?>" 
                                   placeholder="<?php _e('ZIP', 'developer-lessons'); ?>">
                        </div>
                        <div class="dl-form-col">
                            <label for="dl_invoice_city"><?php _e('City', 'developer-lessons'); ?> <span class="required">*</span></label>
                            <input type="text" name="invoice_city" id="dl_invoice_city" 
                                   value="<?php echo esc_attr($saved_invoice['city']); ?>" 
                                   placeholder="<?php _e('City', 'developer-lessons'); ?>">
                        </div>
                    </div>

                    <div class="dl-form-row dl-form-row-half">
                        <div class="dl-form-col">
                            <label for="dl_invoice_ic"><?php _e('Company ID (IČ)', 'developer-lessons'); ?> <span class="required">*</span></label>
                            <input type="text" name="invoice_ic" id="dl_invoice_ic" 
                                   value="<?php echo esc_attr($saved_invoice['ic']); ?>" 
                                   placeholder="<?php _e('e.g., 12345678', 'developer-lessons'); ?>">
                        </div>
                        <div class="dl-form-col">
                            <label for="dl_invoice_dic"><?php _e('VAT ID (DIČ)', 'developer-lessons'); ?></label>
                            <input type="text" name="invoice_dic" id="dl_invoice_dic" 
                                   value="<?php echo esc_attr($saved_invoice['dic']); ?>" 
                                   placeholder="<?php _e('e.g., CZ12345678', 'developer-lessons'); ?>">
                            <p class="dl-field-hint"><?php _e('Optional - fill in if you are a VAT payer', 'developer-lessons'); ?></p>
                        </div>
                    </div>

                    <div class="dl-form-row">
                        <label class="dl-checkbox-label">
                            <input type="checkbox" name="save_invoice_to_profile" id="dl_save_invoice_to_profile" value="1" checked>
                            <span><?php _e('Save these details to my profile for future purchases', 'developer-lessons'); ?></span>
                        </label>
                    </div>

                </div>
            </div>

            <!-- Payment Method Section -->
            <div class="dl-checkout-section">
                <h3><?php _e('Payment Method', 'developer-lessons'); ?></h3>
                
                <div class="dl-payment-methods">
                    <?php if ($comgate_enabled): ?>
                        <label class="dl-payment-method">
                            <input type="radio" name="payment_method" value="comgate" checked>
                            <span class="dl-payment-method-label">
                                <strong><?php _e('Pay by Card', 'developer-lessons'); ?></strong>
                                <span><?php _e('Secure payment via Comgate', 'developer-lessons'); ?></span>
                            </span>
                        </label>
                    <?php endif; ?>

                    <?php if ($bank_transfer_enabled): ?>
                        <label class="dl-payment-method">
                            <input type="radio" name="payment_method" value="bank_transfer" <?php echo !$comgate_enabled ? 'checked' : ''; ?>>
                            <span class="dl-payment-method-label">
                                <strong><?php _e('Bank Transfer', 'developer-lessons'); ?></strong>
                                <span><?php _e('Pay via bank transfer with QR code', 'developer-lessons'); ?></span>
                            </span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($terms_page): ?>
                <div class="dl-terms-agreement">
                    <label class="dl-checkbox-label">
                        <input type="checkbox" name="agree_terms" id="agree_terms" required>
                        <span><?php printf(
                            __('I agree to the <a href="%s" target="_blank">Terms and Conditions</a>', 'developer-lessons'),
                            get_permalink($terms_page)
                        ); ?></span>
                    </label>
                </div>
            <?php endif; ?>

            <div class="dl-checkout-actions">
                <button type="submit" class="dl-btn dl-btn-primary dl-btn-large" id="dl-submit-checkout">
                    <?php _e('Complete Purchase', 'developer-lessons'); ?>
                </button>
            </div>

            <div id="dl-checkout-messages" class="dl-checkout-messages"></div>
        </form>
    </div>
</div>
