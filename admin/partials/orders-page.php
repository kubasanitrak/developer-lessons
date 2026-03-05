<?php
/**
 * Orders admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

$currency_symbol = get_option('dl_currency_symbol', 'Kč');
$statuses = array(
    '' => __('All', 'developer-lessons'),
    'pending' => __('Pending', 'developer-lessons'),
    'awaiting_payment' => __('Awaiting Payment', 'developer-lessons'),
    'processing' => __('Processing', 'developer-lessons'),
    'completed' => __('Completed', 'developer-lessons'),
    'failed' => __('Failed', 'developer-lessons'),
    'expired' => __('Expired', 'developer-lessons'),
    'cancelled' => __('Cancelled', 'developer-lessons')
);
?>
<div class="wrap dl-admin-wrap">
    <h1><?php _e('Orders', 'developer-lessons'); ?></h1>

    <?php if (isset($_GET['message'])): ?>
        <?php if ($_GET['message'] === 'payment_confirmed'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Payment confirmed successfully.', 'developer-lessons'); ?></p>
            </div>
        <?php elseif ($_GET['message'] === 'order_cancelled'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Order cancelled.', 'developer-lessons'); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="dl-orders-filter" style="margin-bottom: 15px;">
        <form method="get">
            <input type="hidden" name="page" value="dl-orders">
            <select name="status" onchange="this.form.submit()">
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($status_filter, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Order', 'developer-lessons'); ?></th>
                <th><?php _e('Customer', 'developer-lessons'); ?></th>
                <th><?php _e('Payment', 'developer-lessons'); ?></th>
                <th><?php _e('Total', 'developer-lessons'); ?></th>
                <th><?php _e('Status', 'developer-lessons'); ?></th>
                <th><?php _e('Date', 'developer-lessons'); ?></th>
                <th><?php _e('Actions', 'developer-lessons'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7"><?php _e('No orders found.', 'developer-lessons'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?php echo esc_html($order->order_number); ?></strong></td>
                        <td>
                            <?php echo esc_html($order->display_name ?: 'Guest'); ?><br>
                            <small><?php echo esc_html($order->user_email); ?></small>
                        </td>
                        <td>
                            <?php echo $order->payment_method === 'comgate' ? __('Card', 'developer-lessons') : __('Bank Transfer', 'developer-lessons'); ?>
                        </td>
                        <td>
                            <?php echo number_format((float)$order->total, 2); ?> <?php echo esc_html($currency_symbol); ?>
                        </td>
                        <td>
                            <span class="dl-order-status dl-status-<?php echo esc_attr($order->status); ?>">
                                <?php echo isset($statuses[$order->status]) ? esc_html($statuses[$order->status]) : esc_html($order->status); ?>
                            </span>
                        </td>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order->created_at)); ?></td>
                        <td>
                            <?php if (in_array($order->status, array('pending', 'awaiting_payment'))): ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=dl-orders&dl_action=confirm_payment&order_id=' . $order->id), 'dl_admin_action'); ?>" 
                                   class="button button-small button-primary" 
                                   onclick="return confirm('<?php _e('Confirm payment?', 'developer-lessons'); ?>')">
                                    <?php _e('Confirm', 'developer-lessons'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=dl-orders&dl_action=cancel_order&order_id=' . $order->id), 'dl_admin_action'); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('<?php _e('Cancel this order?', 'developer-lessons'); ?>')">
                                    <?php _e('Cancel', 'developer-lessons'); ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
