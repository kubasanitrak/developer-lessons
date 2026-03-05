<?php
/**
 * Order expiry notification email template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('Order Expiring Soon', 'developer-lessons'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2><?php _e('Your Order is Expiring Soon', 'developer-lessons'); ?></h2>
    
    <p><?php printf(__('Hello %s,', 'developer-lessons'), esc_html($user->display_name)); ?></p>
    
    <p><?php _e('Your pending order is about to expire. Please complete the payment to access your lessons.', 'developer-lessons'); ?></p>
    
    <div style="background: #f9f9f9; padding: 20px; margin: 20px 0;">
        <p><strong><?php _e('Order Number:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
        <p><strong><?php _e('Total:', 'developer-lessons'); ?></strong> <?php echo DL_Payments::format_price($order->total); ?></p>
        <p><strong><?php _e('Expires:', 'developer-lessons'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order->expires_at)); ?></p>
    </div>
    
    <p>
        <a href="<?php echo esc_url($checkout_url); ?>" style="display: inline-block; padding: 12px 30px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px;">
            <?php _e('Complete Payment', 'developer-lessons'); ?>
        </a>
    </p>
    
    <p style="color: #666; font-size: 13px;">
        <?php _e('If you no longer wish to complete this order, you can ignore this email.', 'developer-lessons'); ?>
    </p>
</body>
</html>
