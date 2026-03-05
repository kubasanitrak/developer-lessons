<?php
/**
 * Payment failed template
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_ids = get_option('dl_page_ids');
$checkout_url = get_permalink($page_ids['checkout']);
?>
<div class="dl-payment-result dl-payment-failed">
    <div class="dl-result-icon">
        <span class="dashicons dashicons-dismiss"></span>
    </div>
    
    <h2><?php _e('Payment Failed', 'developer-lessons'); ?></h2>
    
    <p><?php _e('Unfortunately, your payment could not be processed. Please try again or choose a different payment method.', 'developer-lessons'); ?></p>
    
    <?php if ($order): ?>
        <p><strong><?php _e('Order Number:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
    <?php endif; ?>
    
    <div class="dl-result-actions">
        <a href="<?php echo esc_url($checkout_url); ?>" class="dl-btn dl-btn-primary">
            <?php _e('Try Again', 'developer-lessons'); ?>
        </a>
        <a href="<?php echo home_url(); ?>" class="dl-btn">
            <?php _e('Return to Home', 'developer-lessons'); ?>
        </a>
    </div>
</div>
