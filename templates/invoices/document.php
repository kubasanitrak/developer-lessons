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
        body {
            color: #000;
            font-family: inter, sans-serif;
            font-size: 10pt;
            line-height: 1.35;
        }
        p {
            margin: 0;
        }
        .eyebrow {
            font-size: 8pt;
            margin-bottom: 3mm;
        }
        .frame {
            border: 1px solid #000;
            padding: 9mm;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        td {
            vertical-align: top;
        }
        .section td {
            padding: 0 0 9mm;
        }
        .section-compact td {
            padding-bottom: 4mm;
        }
        .title {
            font-family: suisseworks, "Times New Roman", serif;
            font-size: 22pt;
            font-weight: normal;
            line-height: 1.05;
            margin: 0;
        }
        .doc-number {
            font-size: 10pt;
            text-align: left;
        }
        .label {
            font-weight: bold;
        }
        .muted {
            color: #333;
        }
        .items-header td {
            border-bottom: 1px solid #555;
            font-weight: bold;
            padding-bottom: 3mm;
        }
        .items-row td {
            padding: 3mm 0 3mm;
        }
        .price-cell {
            text-align: right;
            width: 34%;
        }
        .total-row {
            padding-bottom: 5mm;
        }
        .total-row td {
            border-top: 1px solid #555;
            padding-top: 3mm;
            padding-bottom: 3mm;
        }
        .total {
            font-size: 18pt;
            font-weight: bold;
            line-height: 1.15;
        }
        .notes {
            padding-top: 3mm;
        }
        .section.notes td,
        .issuer td {
            border-top: 1px solid #555;
            padding-top: 3mm;
        }
    </style>
</head>
<body>
    <?php
    $is_proforma = strpos((string) $doc_label, 'Pro-forma') !== false;
    $document_heading = $is_proforma ? __('PROFORMA FAKTURA', 'developer-lessons') : __('FAKTURA - DAŇOVÝ DOKLAD', 'developer-lessons');
    $issue_timestamp = mysql2date('U', $date_field);
    $issue_date = date_i18n('d. m. Y', $issue_timestamp);
    $due_date = date_i18n('d. m. Y', strtotime('+3 days', $issue_timestamp));
    $variable_symbol = preg_replace('/\D+/', '', (string) $doc_number);
    $seller_city_line = trim($seller_company['zip'] . ' ' . $seller_company['city']);
    ?>
    <p class="eyebrow"><?php echo esc_html($document_heading); ?></p>
    <div class="frame">
        <table class="section">
            <tr>
                <td style="width:50%;">
                    <h1 class="title">Lenka Krejčová<br>Barre Academy</h1>
                </td>
                <td style="width:50%;">
                    <p class="doc-number"><?php esc_html_e('Faktura číslo:', 'developer-lessons'); ?><?php echo esc_html($doc_number); ?></p>
                    <p class="muted"><?php esc_html_e('Objednávka:', 'developer-lessons'); ?> <?php echo esc_html($order->order_number); ?></p>
                </td>
            </tr>
        </table>

        <table class="section">
            <tr>
                <td style="width:50%;">
                    <p><span class="label"><?php esc_html_e('Dodavatel:', 'developer-lessons'); ?></span></p>
                    <?php if ($seller_company['name']) : ?>
                        <p><?php echo esc_html($seller_company['name']); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_company['street']) : ?>
                        <p><?php echo esc_html($seller_company['street']); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_city_line) : ?>
                        <p><?php echo esc_html($seller_city_line); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_company['country']) : ?>
                        <p><?php echo esc_html($seller_company['country']); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_company['ic']) : ?>
                        <p><?php echo esc_html(sprintf(__('IČ: %s', 'developer-lessons'), $seller_company['ic'])); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_company['dic']) : ?>
                        <p><?php echo esc_html(sprintf(__('DIČ: %s', 'developer-lessons'), $seller_company['dic'])); ?></p>
                    <?php endif; ?>
                </td>
                <td style="width:50%;">
                    <p><span class="label"><?php esc_html_e('Odběratel:', 'developer-lessons'); ?></span></p>
                    <p><?php echo esc_html($customer['name']); ?></p>
                    <?php if (!empty($customer['address'])) : ?>
                        <p><?php echo nl2br(esc_html($customer['address'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($customer['ic'])) : ?>
                        <p><?php echo esc_html(sprintf(__('IČ: %s', 'developer-lessons'), $customer['ic'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($customer['dic'])) : ?>
                        <p><?php echo esc_html(sprintf(__('DIČ: %s', 'developer-lessons'), $customer['dic'])); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <table class="section">
            <tr>
                <td style="width:50%;">
                    <?php if ($seller_bank['account_name']) : ?>
                        <p><span class="label"><?php esc_html_e('Majitel účtu:', 'developer-lessons'); ?></span> <?php echo esc_html($seller_bank['account_name']); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_bank['account_number'] && $seller_bank['bank_code']) : ?>
                        <p><span class="label"><?php esc_html_e('Číslo účtu:', 'developer-lessons'); ?></span> <?php echo esc_html($seller_bank['account_number'] . '/' . $seller_bank['bank_code']); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_bank['iban']) : ?>
                        <p><span class="label">IBAN:</span> <?php echo esc_html($seller_bank['iban']); ?></p>
                    <?php endif; ?>
                    <?php if ($seller_bank['bic']) : ?>
                        <p><span class="label">SWIFT (BIC):</span> <?php echo esc_html($seller_bank['bic']); ?></p>
                    <?php endif; ?>
                    <p><span class="label"><?php esc_html_e('Variabilní symbol:', 'developer-lessons'); ?></span> <?php echo esc_html($variable_symbol); ?></p>
                </td>
                <td style="width:50%;">
                    <p><span class="label"><?php esc_html_e('Forma úhrady:', 'developer-lessons'); ?></span> <?php esc_html_e('Převodem na účet', 'developer-lessons'); ?></p>
                    <p><?php esc_html_e('Nejsem plátce DPH', 'developer-lessons'); ?></p>
                </td>
            </tr>
        </table>

        <table class="section section-compact">
            <tr>
                <td style="width:50%;"><span class="label"><?php esc_html_e('Datum vystavení:', 'developer-lessons'); ?></span></td>
                <td style="width:50%;"><?php echo esc_html($issue_date); ?></td>
            </tr>
            <tr>
                <td style="width:50%;"><span class="label"><?php esc_html_e('Datum splatnosti:', 'developer-lessons'); ?></span></td>
                <td style="width:50%;"><?php echo esc_html($due_date); ?></td>
            </tr>
        </table>

        <table>
            <tr class="items-header">
                <td><?php esc_html_e('Popis:', 'developer-lessons'); ?></td>
                <td class="price-cell"><?php esc_html_e('Cena:', 'developer-lessons'); ?></td>
            </tr>
            <?php foreach ($order->items as $item) : ?>
                <tr class="items-row">
                    <td><?php echo esc_html($item->lesson_title); ?></td>
                    <td class="price-cell"><?php echo esc_html(DL_Payments::format_price($item->price, $order->currency)); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($order->discount > 0) : ?>
                <tr class="items-row">
                    <td><?php esc_html_e('Sleva', 'developer-lessons'); ?></td>
                    <td class="price-cell">-<?php echo esc_html(DL_Payments::format_price($order->discount, $order->currency)); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td><p class="total"><?php esc_html_e('Celkem k úhradě', 'developer-lessons'); ?></p></td>
                <td class="price-cell"><p class="total"><?php echo esc_html(DL_Payments::format_price($order->total, $order->currency)); ?></p></td>
            </tr>
        </table>

        <table class="section notes">
            <tr>
                <td>
                    <p><?php esc_html_e('Prodlení úhrady této faktury bude penalizováno úrokem z prodlení ve výši 0,1 % denně.', 'developer-lessons'); ?></p>
                    <p><?php esc_html_e('Případná reklamace této faktury musí být učiněna do data její splatnosti.', 'developer-lessons'); ?></p>
                </td>
            </tr>
        </table>

        <table class="issuer">
            <tr>
                <td>
                    <p><span class="label"><?php esc_html_e('Vystavil:', 'developer-lessons'); ?></span></p>
                    <p>Lenka Maternini</p>
                    <p>lenka@barreacademy.cz</p>
                    <p>+420 608 438 728</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
