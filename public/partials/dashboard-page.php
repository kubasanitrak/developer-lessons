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
            <div class="dl-dashboard--section_header divider">
                <p class="dl-dashboard--section_headline strong"><?php _e('Pending Orders', 'developer-lessons'); ?></p>
            </div>
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
                            <td><?php echo date_i18n(get_option('date_format'), mysql2date('U', $order->created_at)); ?></td>
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
        <div class="dl-dashboard--section_header divider dl-my-lessons-header">
            <p class="dl-dashboard--section_headline strong"><?php _e('My Lessons', 'developer-lessons'); ?></p>
            <?php if (!empty($purchased_lessons)) : ?>
                <div class="dl-lessons-view-switch" role="group" aria-label="<?php esc_attr_e('Lessons overview layout', 'developer-lessons'); ?>">
                    <button type="button"
                            class="dl-lessons-view-btn<?php echo $lessons_view === 'grid' ? ' is-active' : ''; ?>"
                            data-view="grid"
                            aria-pressed="<?php echo $lessons_view === 'grid' ? 'true' : 'false'; ?>">
                        <span class="dashicons dashicons-grid-view" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php _e('Grid view', 'developer-lessons'); ?></span>
                    </button>
                    <button type="button"
                            class="dl-lessons-view-btn<?php echo $lessons_view === 'list' ? ' is-active' : ''; ?>"
                            data-view="list"
                            aria-pressed="<?php echo $lessons_view === 'list' ? 'true' : 'false'; ?>">
                        <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php _e('List view', 'developer-lessons'); ?></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($purchased_lessons)) : ?>
            <p class="dl-no-lessons"><?php _e('You have not purchased any lessons yet.', 'developer-lessons'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('lesson')); ?>" class="dl-btn">
                <?php _e('Browse Lessons', 'developer-lessons'); ?>
            </a>
        <?php else : ?>
            <div class="dl-my-lessons-overview customtable" data-view="<?php echo esc_attr($lessons_view); ?>">
                <?php
                $access_control = new DL_Access_Control();
                echo $access_control->render_dashboard_lessons_overview($purchased_lessons);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
