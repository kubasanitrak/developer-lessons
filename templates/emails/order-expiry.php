<?php
/**
 * Order expiry notification email template
 */

if (!defined('ABSPATH')) {
    exit;
}

$partials_dir = DL_PLUGIN_DIR . 'templates/emails/partials/';
$customer_name = $user->display_name ? $user->display_name : $user->user_login;
$email_title = __('Objednávka brzy vyprší', 'developer-lessons');
$email_preview = __('Vaše objednávka brzy vyprší. Dokončete platbu, aby vám zůstal přístup k lekcím.', 'developer-lessons');

include $partials_dir . 'head.php';
?>
<body>
    <?php if ($email_preview) : ?>
        <div style="display:none;font-size:1px;color:#eee7d6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;"><?php echo esc_html($email_preview); ?></div>
    <?php endif; ?>
    <table class="email-shell" role="presentation" width="100%">
        <tr>
            <td>
                <table class="email-container" role="presentation" align="center" width="600">
                    <?php include $partials_dir . 'header.php'; ?>
                    <tr>
                        <td class="section" style="padding-top:0;padding-bottom:42px;">
                            <p class="brand-title"><?php esc_html_e('Objednávka brzy vyprší.', 'developer-lessons'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="section" style="padding-top:0;">
                            <p class="body-copy" style="padding-bottom:12px;"><?php printf(esc_html__('Dobrý den, %s,', 'developer-lessons'), esc_html($customer_name)); ?></p>
                            <p class="body-copy"><?php esc_html_e('Vaše objednávka čeká na dokončení platby a brzy vyprší. Pokud chcete zachovat přístup k vybraným lekcím, dokončete prosím platbu co nejdříve.', 'developer-lessons'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="section section-alt">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="padding-bottom:18px;">
                                        <p class="section-title"><?php esc_html_e('Detail objednávky', 'developer-lessons'); ?></p>
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
                                        <p class="label"><?php esc_html_e('Částka', 'developer-lessons'); ?></p>
                                        <p class="value"><?php echo esc_html(DL_Payments::format_price($order->total, $order->currency)); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="section-tight" style="padding-left:0;padding-right:0;">
                                        <p class="label"><?php esc_html_e('Platnost do', 'developer-lessons'); ?></p>
                                        <p class="value"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), mysql2date('U', $order->expires_at))); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php
                    $cta_url = $checkout_url;
                    $cta_label = __('Dokončit platbu', 'developer-lessons');
                    include $partials_dir . 'cta.php';
                    ?>
                    <tr>
                        <td class="section" style="padding-top:0;">
                            <p class="body-copy"><?php esc_html_e('Pokud už o objednávku nemáte zájem, tento e-mail můžete ignorovat.', 'developer-lessons'); ?></p>
                        </td>
                    </tr>
                    <?php include $partials_dir . 'footer.php'; ?>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
