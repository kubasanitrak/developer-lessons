<?php
/**
 * Admin dashboard page
 */

if (!defined('ABSPATH')) {
    exit;
}

$currency_symbol = get_option('dl_currency_symbol', 'Kč');
?>
<div class="wrap dl-admin-wrap">
    <h1><?php _e('Lessons Dashboard', 'developer-lessons'); ?></h1>

    <div class="dl-dashboard-stats">
        <div class="dl-stat-card">
            <h3><?php _e('Published Lessons', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo intval($total_lessons); ?></div>
            <a href="<?php echo admin_url('edit.php?post_type=lesson'); ?>"><?php _e('Manage Lessons', 'developer-lessons'); ?></a>
        </div>
        <div class="dl-stat-card">
            <h3><?php _e('Total Sales', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo intval($total_sales); ?></div>
        </div>
        <div class="dl-stat-card">
            <h3><?php _e('Total Revenue', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo number_format((float)$total_revenue, 2); ?> <?php echo esc_html($currency_symbol); ?></div>
        </div>
        <div class="dl-stat-card dl-stat-alert">
            <h3><?php _e('Pending Orders', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo intval($pending_orders); ?></div>
            <a href="<?php echo admin_url('edit.php?post_type=lesson&page=dl-orders&status=awaiting_payment'); ?>"><?php _e('View Orders', 'developer-lessons'); ?></a>
        </div>
    </div>

    <div class="dl-dashboard-section">
        <h2><?php _e('Recent Orders', 'developer-lessons'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Order', 'developer-lessons'); ?></th>
                    <th><?php _e('Customer', 'developer-lessons'); ?></th>
                    <th><?php _e('Total', 'developer-lessons'); ?></th>
                    <th><?php _e('Status', 'developer-lessons'); ?></th>
                    <th><?php _e('Date', 'developer-lessons'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="5"><?php _e('No orders yet.', 'developer-lessons'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($order->order_number); ?></strong></td>
                            <td><?php echo esc_html($order->display_name); ?></td>
                            <td><?php echo number_format((float)$order->total, 2); ?> <?php echo esc_html($currency_symbol); ?></td>
                            <td>
                                <span class="dl-order-status dl-status-<?php echo esc_attr($order->status); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $order->status))); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($order->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=lesson&page=dl-orders'); ?>" class="button">
                <?php _e('View All Orders', 'developer-lessons'); ?>
            </a>
        </p>
    </div>
</div>
