<?php
/**
 * Admin notification email template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('New Purchase Notification', 'developer-lessons'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <?php $email_phase = isset($email_phase) ? $email_phase : 'payment_confirmed'; ?>
    <?php if ($email_phase === 'order_placed') : ?>
    <h2><?php _e('New order awaiting payment', 'developer-lessons'); ?></h2>
    <p><?php _e('A new order has been placed and is awaiting payment. A pro-forma invoice is attached.', 'developer-lessons'); ?></p>
    <?php else : ?>
    <h2><?php _e('New Purchase Received', 'developer-lessons'); ?></h2>
    <p><?php _e('A new purchase has been completed on your site. Pro-forma and tax invoice PDFs are attached.', 'developer-lessons'); ?></p>
    <?php endif; ?>
    
    <h3><?php _e('Order Details', 'developer-lessons'); ?></h3>
    <ul>
        <li><strong><?php _e('Order Number:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></li>
        <li><strong><?php _e('Customer:', 'developer-lessons'); ?></strong> <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)</li>
        <li><strong><?php _e('Total:', 'developer-lessons'); ?></strong> <?php echo DL_Payments::format_price($order->total); ?></li>
        <li><strong><?php _e('Payment Method:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->payment_method); ?></li>
    </ul>
    
    <?php if ($email_phase === 'order_placed' && !empty($payment_instructions)) : ?>
        <?php echo $payment_instructions; ?>
    <?php endif; ?>

    <h3><?php _e('Lessons Purchased', 'developer-lessons'); ?></h3>
    <ul>
        <?php foreach ($order->items as $item): ?>
            <li><?php echo esc_html($item->lesson_title); ?> - <?php echo DL_Payments::format_price($item->price); ?></li>
        <?php endforeach; ?>
    </ul>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=dl-orders'); ?>">
            <?php _e('View in Admin', 'developer-lessons'); ?>
        </a>
    </p>
</body>
</html>
