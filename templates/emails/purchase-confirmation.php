<?php
/**
 * Purchase confirmation email template (HTML)
 */

if (!defined('ABSPATH')) {
    exit;
}

$partials_dir = DL_PLUGIN_DIR . 'templates/emails/partials/';
$email_phase = isset($email_phase) ? $email_phase : 'payment_confirmed';
$customer_name = $user->display_name ? $user->display_name : $user->user_login;

if ($email_phase === 'order_placed') {
    $email_title = __('Objednávka přijata', 'developer-lessons');
    $email_preview = __('Vaši objednávku evidujeme. Údaje k platbě a proforma fakturu najdete v e-mailu.', 'developer-lessons');
    $hero_title = __('Děkujeme za objednávku, počítáme s vámi.', 'developer-lessons');
    $intro_copy = __('Vaši objednávku evidujeme. Údaje k platbě najdete níže a proforma fakturu posíláme v příloze tohoto e-mailu.', 'developer-lessons');
} else {
    $email_title = __('Platba potvrzena', 'developer-lessons');
    $email_preview = __('Platba dorazila, děkujeme. Přístup k lekcím je aktivní.', 'developer-lessons');
    $hero_title = __('Platba dorazila, děkujeme.', 'developer-lessons');
    $intro_copy = __('Váš přístup k zakoupeným lekcím je aktivní. Proforma fakturu i daňový doklad posíláme v příloze tohoto e-mailu.', 'developer-lessons');
}

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
                        <td class="section" style="padding-top:0;padding-bottom:50px;">
                            <p class="brand-title"><?php echo esc_html($hero_title); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="section" style="padding-top:0;">
                            <p class="body-copy" style="padding-bottom:12px;"><?php printf(esc_html__('Dobrý den, %s,', 'developer-lessons'), esc_html($customer_name)); ?></p>
                            <p class="body-copy"><?php echo esc_html($intro_copy); ?></p>
                        </td>
                    </tr>
                    <?php include $partials_dir . 'order-summary.php'; ?>
                    <?php if ($email_phase === 'order_placed') : ?>
                        <?php include $partials_dir . 'payment-instructions.php'; ?>
                    <?php endif; ?>
                    <?php
                    if ($email_phase !== 'order_placed') {
                        $cta_url = $dashboard_url;
                        $cta_label = __('Otevřít moje lekce', 'developer-lessons');
                    } else {
                        $cta_url = $checkout_url;
                        $cta_label = __('Dokončit platbu', 'developer-lessons');
                    }
                    include $partials_dir . 'cta.php';
                    ?>
                    <?php include $partials_dir . 'footer.php'; ?>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
