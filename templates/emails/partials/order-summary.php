<?php
/**
 * Shared order summary table.
 *
 * @var object $order
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<tr>
    <td class="section section-alt">
        <table role="presentation" width="100%">
            <tr>
                <td style="padding-bottom:18px;">
                    <p class="section-title"><?php esc_html_e('Rekapitulace objednávky', 'developer-lessons'); ?></p>
                </td>
            </tr>
            <tr>
                <td class="section-tight" style="padding-left:0;padding-right:0;">
                    <p class="label"><?php esc_html_e('Číslo objednávky', 'developer-lessons'); ?></p>
                    <p class="value"><?php echo esc_html($order->order_number); ?></p>
                </td>
            </tr>
            <tr>
                <td class="section-tight" style="padding-left:0;padding-right:0;">
                    <?php $order_date = !empty($order->paid_at) ? $order->paid_at : $order->created_at; ?>
                    <p class="label"><?php esc_html_e('Datum', 'developer-lessons'); ?></p>
                    <p class="value"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order_date))); ?></p>
                </td>
            </tr>
            <tr>
                <td style="padding-top:8px;">
                    <table class="summary-table" role="presentation">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Položka', 'developer-lessons'); ?></th>
                                <th class="price"><?php esc_html_e('Cena', 'developer-lessons'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order->items as $item) : ?>
                                <tr>
                                    <td><?php echo esc_html($item->lesson_title); ?></td>
                                    <td class="price"><?php echo esc_html(DL_Payments::format_price($item->price, $order->currency)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($order->discount > 0) : ?>
                                <tr>
                                    <td><?php esc_html_e('Sleva', 'developer-lessons'); ?></td>
                                    <td class="price">-<?php echo esc_html(DL_Payments::format_price($order->discount, $order->currency)); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="total">
                                <td><?php esc_html_e('Celkem', 'developer-lessons'); ?></td>
                                <td class="price"><?php echo esc_html(DL_Payments::format_price($order->total, $order->currency)); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>
