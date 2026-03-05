<?php
/**
 * Payment success template
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_ids = get_option('dl_page_ids');
$dashboard_url = get_permalink($page_ids['dashboard']);
?>
<div class="dl-payment-result dl-payment-success">
    <div class="dl-result-icon">
        <span class="dashicons dashicons-yes-alt"></span>
    </div>
    
    <h2><?php _e('Payment Successful!', 'developer-lessons'); ?></h2>
    
    <p><?php _e('Thank you for your purchase. You now have access to your lessons.', 'developer-lessons'); ?></p>
    
    <?php if ($order): ?>
        <div class="dl-order-details">
            <h3><?php _e('Order Details', 'developer-lessons'); ?></h3>
            <p><strong><?php _e('Order Number:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
            <p><strong><?php _e('Total:', 'developer-lessons'); ?></strong> <?php echo DL_Payments::format_price($order->total); ?></p>
            
            <h4><?php _e('Purchased Lessons:', 'developer-lessons'); ?></h4>
            <ul>
                <?php foreach ($order->items as $item): ?>
                    <li>
                        <a href="<?php echo get_permalink($item->lesson_id); ?>">
                            <?php echo esc_html($item->lesson_title); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="dl-result-actions">
        <a href="<?php echo esc_url($dashboard_url); ?>" class="dl-btn dl-btn-primary">
            <?php _e('Go to My Lessons', 'developer-lessons'); ?>
        </a>
    </div>
</div>
