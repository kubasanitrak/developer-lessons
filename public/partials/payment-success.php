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
    
    <div class="dl-order-details--header taL ">
        <h1 class="taL mar-B-05 maxi"><?php _e('Payment Successful!', 'developer-lessons'); ?></h1>
        <p class="plain"><?php _e('Thank you for your purchase. You now have access to your lessons.', 'developer-lessons'); ?></p>
    </div>
    
    <?php if ($order): ?>
        <div class="dl-order-details">
            <div class="dl-order-details--header mar-B-05">
                <p class="strong divider"><?php _e('Order Details', 'developer-lessons'); ?></p>
            </div>
            <p class="plain"><strong><?php _e('Order Number:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
            <p class="plain"><strong><?php _e('Total:', 'developer-lessons'); ?></strong> <?php echo DL_Payments::format_price($order->total); ?></p>
            
            <div class="dl-order-details--header mar-T mar-B-05">
                <p class=" divider strong"><?php _e('Purchased Lessons:', 'developer-lessons'); ?></p>
            </div>
            <ul class="dl-order-details--list list-none">
                <?php foreach ($order->items as $item): ?>
                    <li class="plain">
                        <a class="" href="<?php echo get_permalink($item->lesson_id); ?>">
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
