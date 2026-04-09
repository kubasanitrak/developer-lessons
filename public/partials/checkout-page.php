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
?>
<div class="dl-checkout padded-content">
    <div class="dl-checkout-content">
        <h2><?php _e('Your Order', 'developer-lessons'); ?></h2>
        
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
                            <button type="button" class="dl-remove-item" data-lesson-id="<?php echo esc_attr($item->lesson_id); ?>">
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

            <?php if ($terms_page): ?>
                <div class="dl-terms-agreement">
                    <label>
                        <input type="checkbox" name="agree_terms" id="agree_terms" required>
                        <?php printf(
                            __('I agree to the <a href="%s" target="_blank">Terms and Conditions</a>', 'developer-lessons'),
                            get_permalink($terms_page)
                        ); ?>
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
