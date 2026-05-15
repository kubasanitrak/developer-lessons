<?php
/**
 * PDF document template (pro-forma / invoice)
 *
 * @var object $order
 * @var array  $customer
 * @var array  $seller_company
 * @var array  $seller_bank
 * @var string $doc_number
 * @var string $doc_label
 * @var string $date_field
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #222; }
        h1 { font-size: 16pt; margin: 0 0 8px; }
        .cols { width: 100%; margin-bottom: 24px; }
        .cols td { vertical-align: top; width: 50%; }
        .label { font-size: 9pt; color: #666; text-transform: uppercase; margin-bottom: 4px; }
        table.items { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.items th, table.items td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        table.items th { background: #f0f0f0; }
        .total { font-weight: bold; font-size: 12pt; text-align: right; margin-top: 12px; }
        .bank { margin-top: 24px; font-size: 10pt; }
    </style>
</head>
<body>
    <h1><?php echo esc_html($doc_label); ?></h1>
    <p><strong><?php esc_html_e('Document number:', 'developer-lessons'); ?></strong> <?php echo esc_html($doc_number); ?></p>
    <p><strong><?php esc_html_e('Order:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
    <p><strong><?php esc_html_e('Date:', 'developer-lessons'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($date_field))); ?></p>

    <table class="cols">
        <tr>
            <td>
                <div class="label"><?php esc_html_e('Supplier', 'developer-lessons'); ?></div>
                <div>
                    <?php if ($seller_company['name']) : ?>
                        <strong><?php echo esc_html($seller_company['name']); ?></strong><br>
                    <?php endif; ?>
                    <?php echo nl2br(esc_html(DL_Seller::format_company_address())); ?>
                </div>
            </td>
            <td>
                <div class="label"><?php esc_html_e('Customer', 'developer-lessons'); ?></div>
                <div>
                    <strong><?php echo esc_html($customer['name']); ?></strong><br>
                    <?php echo nl2br(esc_html($customer['address'])); ?>
                    <?php if (!empty($customer['ic'])) : ?>
                        <br><?php echo esc_html(sprintf(__('Company ID: %s', 'developer-lessons'), $customer['ic'])); ?>
                    <?php endif; ?>
                    <?php if (!empty($customer['dic'])) : ?>
                        <br><?php echo esc_html(sprintf(__('VAT ID: %s', 'developer-lessons'), $customer['dic'])); ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th><?php esc_html_e('Description', 'developer-lessons'); ?></th>
                <th style="width:100px;text-align:right;"><?php esc_html_e('Price', 'developer-lessons'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->items as $item) : ?>
                <tr>
                    <td><?php echo esc_html($item->lesson_title); ?></td>
                    <td style="text-align:right;"><?php echo esc_html(DL_Payments::format_price($item->price, $order->currency)); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($order->discount > 0) : ?>
                <tr>
                    <td><?php esc_html_e('Discount', 'developer-lessons'); ?></td>
                    <td style="text-align:right;">-<?php echo esc_html(DL_Payments::format_price($order->discount, $order->currency)); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p class="total"><?php esc_html_e('Total:', 'developer-lessons'); ?> <?php echo esc_html(DL_Payments::format_price($order->total, $order->currency)); ?></p>

    <?php if ($seller_bank['account_number'] || $seller_bank['iban']) : ?>
        <div class="bank">
            <strong><?php esc_html_e('Payment details', 'developer-lessons'); ?></strong><br>
            <?php if ($seller_bank['account_name']) : ?>
                <?php echo esc_html($seller_bank['account_name']); ?><br>
            <?php endif; ?>
            <?php if ($seller_bank['account_number'] && $seller_bank['bank_code']) : ?>
                <?php echo esc_html($seller_bank['account_number'] . '/' . $seller_bank['bank_code']); ?><br>
            <?php endif; ?>
            <?php if ($seller_bank['iban']) : ?>
                IBAN: <?php echo esc_html($seller_bank['iban']); ?><br>
            <?php endif; ?>
            <?php if ($seller_bank['bic']) : ?>
                BIC: <?php echo esc_html($seller_bank['bic']); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
