<?php
/**
 * User dashboard template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dl-dashboard">
    <?php if (!empty($pending_orders)): ?>
        <div class="dl-dashboard-section dl-pending-orders">
            <h2><?php _e('Pending Orders', 'developer-lessons'); ?></h2>
            <table class="dl-dashboard-table">
                <thead>
                    <tr>
                        <th><?php _e('Order', 'developer-lessons'); ?></th>
                        <th><?php _e('Amount', 'developer-lessons'); ?></th>
                        <th><?php _e('Status', 'developer-lessons'); ?></th>
                        <th><?php _e('Date', 'developer-lessons'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_orders as $order): ?>
                        <tr>
                            <td>#<?php echo esc_html($order->order_number); ?></td>
                            <td><?php echo DL_Payments::format_price($order->total); ?></td>
                            <td>
                                <span class="dl-order-status dl-status-<?php echo esc_attr($order->status); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $order->status))); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($order->created_at)); ?></td>
                            <td>
                                <?php if ($order->payment_method === 'bank_transfer' && $order->status === 'awaiting_payment'): ?>
                                    <?php 
                                    $page_ids = get_option('dl_page_ids');
                                    $payment_url = add_query_arg(array(
                                        'order' => $order->id,
                                        'method' => 'bank_transfer'
                                    ), get_permalink($page_ids['checkout']));
                                    ?>
                                    <a href="<?php echo esc_url($payment_url); ?>" class="dl-btn dl-btn-small">
                                        <?php _e('View Payment Info', 'developer-lessons'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="dl-dashboard-section dl-my-lessons">
        <h2><?php _e('My Lessons', 'developer-lessons'); ?></h2>
        
        <?php if (empty($purchased_lessons)): ?>
            <p class="dl-no-lessons"><?php _e('You have not purchased any lessons yet.', 'developer-lessons'); ?></p>
            <a href="<?php echo get_post_type_archive_link('lesson'); ?>" class="dl-btn">
                <?php _e('Browse Lessons', 'developer-lessons'); ?>
            </a>
        <?php else: ?>
            <div class="dl-lessons-grid">
                <?php foreach ($purchased_lessons as $purchase): ?>
                    <?php $lesson = get_post($purchase->lesson_id); ?>
                    <?php if ($lesson): ?>
                        <div class="dl-lesson-card">
                            <?php if (has_post_thumbnail($lesson)): ?>
                                <div class="dl-lesson-thumbnail">
                                    <a href="<?php echo get_permalink($lesson); ?>">
                                        <?php echo get_the_post_thumbnail($lesson, 'medium'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="dl-lesson-info">
                                <h3>
                                    <a href="<?php echo get_permalink($lesson); ?>">
                                        <?php echo esc_html($lesson->post_title); ?>
                                    </a>
                                </h3>
                                <p class="dl-lesson-date">
                                    <?php printf(
                                        __('Purchased on %s', 'developer-lessons'),
                                        date_i18n(get_option('date_format'), strtotime($purchase->purchased_at))
                                    ); ?>
                                </p>
                                <a href="<?php echo get_permalink($lesson); ?>" class="dl-btn dl-btn-small">
                                    <?php _e('View Lesson', 'developer-lessons'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
