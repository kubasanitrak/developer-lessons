<?php
/**
 * Admin notification email template
 */

if (!defined('ABSPATH')) {
    exit;
}

$partials_dir = DL_PLUGIN_DIR . 'templates/emails/partials/';
$email_phase = isset($email_phase) ? $email_phase : 'payment_confirmed';
$customer_name = $user->display_name ? $user->display_name : $user->user_login;
$email_title = $email_phase === 'order_placed'
    ? __('Nová objednávka čeká na platbu', 'developer-lessons')
    : __('Nová zaplacená objednávka', 'developer-lessons');
$email_preview = $email_title . ' - ' . $order->order_number;

include $partials_dir . 'head.php';
?>
<body>
    <table class="email-shell" role="presentation" width="100%">
        <tr>
            <td>
                <table class="email-container" role="presentation" align="center" width="600">
                    <?php include $partials_dir . 'header.php'; ?>
                    <tr>
                        <td class="section" style="padding-top:0;padding-bottom:34px;">
                            <p class="section-title"><?php echo esc_html($email_title); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="section" style="padding-top:0;">
                            <?php if ($email_phase === 'order_placed') : ?>
                                <p class="body-copy"><?php esc_html_e('Byla vytvořena nová objednávka a čeká na platbu. Proforma faktura je přiložená.', 'developer-lessons'); ?></p>
                            <?php else : ?>
                                <p class="body-copy"><?php esc_html_e('Objednávka byla zaplacena. Proforma faktura i daňový doklad jsou přiložené.', 'developer-lessons'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="section section-alt">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="padding-bottom:18px;">
                                        <p class="section-title"><?php esc_html_e('Zákazník', 'developer-lessons'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="section-tight" style="padding-left:0;padding-right:0;">
                                        <p class="label"><?php esc_html_e('Jméno', 'developer-lessons'); ?></p>
                                        <p class="value"><?php echo esc_html($customer_name); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="section-tight" style="padding-left:0;padding-right:0;">
                                        <p class="label"><?php esc_html_e('E-mail', 'developer-lessons'); ?></p>
                                        <p class="value"><a class="footer-link" href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="section-tight" style="padding-left:0;padding-right:0;">
                                        <p class="label"><?php esc_html_e('Platební metoda', 'developer-lessons'); ?></p>
                                        <p class="value"><?php echo esc_html($order->payment_method); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php include $partials_dir . 'order-summary.php'; ?>
                    <?php
                    $cta_url = admin_url('admin.php?page=dl-orders');
                    $cta_label = __('Otevřít objednávky v administraci', 'developer-lessons');
                    include $partials_dir . 'cta.php';
                    ?>
                    <?php include $partials_dir . 'footer.php'; ?>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
