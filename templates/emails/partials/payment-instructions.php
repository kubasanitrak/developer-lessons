<?php
/**
 * Shared payment instructions block.
 *
 * @var object $order
 */

if (!defined('ABSPATH')) {
    exit;
}

$bank = DL_Seller::get_bank();
$variable_symbol = preg_replace('/\D+/', '', (string) $order->order_number);
if (!empty($order->proforma_number)) {
    $variable_symbol = preg_replace('/\D+/', '', (string) $order->proforma_number);
}
?>
<tr>
    <td class="section section-alt">
        <table role="presentation" width="100%">
            <tr>
                <td style="padding-bottom:18px;">
                    <p class="section-title"><?php esc_html_e('Údaje k platbě', 'developer-lessons'); ?></p>
                </td>
            </tr>
            <tr>
                <td class="section-tight" style="padding-left:0;padding-right:0;">
                    <p class="label"><?php esc_html_e('Částka', 'developer-lessons'); ?></p>
                    <p class="value"><?php echo esc_html(DL_Payments::format_price($order->total, $order->currency)); ?></p>
                </td>
            </tr>
            <?php if ($bank['account_number'] && $bank['bank_code']) : ?>
                <tr>
                    <td class="section-tight" style="padding-left:0;padding-right:0;">
                        <p class="label"><?php esc_html_e('Číslo účtu', 'developer-lessons'); ?></p>
                        <p class="value"><?php echo esc_html($bank['account_number'] . '/' . $bank['bank_code']); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($bank['iban']) : ?>
                <tr>
                    <td class="section-tight" style="padding-left:0;padding-right:0;">
                        <p class="label">IBAN</p>
                        <p class="value"><?php echo esc_html($bank['iban']); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($bank['bic']) : ?>
                <tr>
                    <td class="section-tight" style="padding-left:0;padding-right:0;">
                        <p class="label">BIC/SWIFT</p>
                        <p class="value"><?php echo esc_html($bank['bic']); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="section-tight" style="padding-left:0;padding-right:0;">
                    <p class="label"><?php esc_html_e('Variabilní symbol', 'developer-lessons'); ?></p>
                    <p class="value"><?php echo esc_html($variable_symbol); ?></p>
                </td>
            </tr>
        </table>
    </td>
</tr>
