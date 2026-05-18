<?php
/**
 * Branded email footer.
 */

if (!defined('ABSPATH')) {
    exit;
}

$footer_logo_url = DL_PLUGIN_URL . 'assets/email/LenkaKrejcovaBarreAcademy-logo.png';
$facebook_icon_url = DL_PLUGIN_URL . 'assets/email/icon-fb.png';
$instagram_icon_url = DL_PLUGIN_URL . 'assets/email/icon-ig.png';
?>
<tr>
    <td class="section" style="padding-top:34px;padding-bottom:20px;">
        <a href="https://barreacademy.cz/" target="_blank">
            <img src="<?php echo esc_url($footer_logo_url); ?>" width="146" alt="Lenka Krejčová Barre Academy">
        </a>
    </td>
</tr>
<tr>
    <td class="section" style="padding-top:0;padding-bottom:20px;">
        <table role="presentation" width="100%">
            <tr>
                <td style="width:50%;padding-right:12px;vertical-align:top;">
                    <p class="body-copy">Jankovcova 1639/16b</p>
                    <p class="body-copy">170 00 Praha 7</p>
                    <p class="body-copy">Česká republika</p>
                </td>
                <td style="width:50%;vertical-align:top;">
                    <p class="body-copy"><a class="footer-link" href="mailto:lenka@barreacademy.cz">lenka@barreacademy.cz</a></p>
                    <p class="body-copy"><a class="footer-link" href="tel:+420608438728">+420 608 438 728</a></p>
                    <p class="body-copy"><a class="footer-link" href="https://barreacademy.cz/" target="_blank">barreacademy.cz</a></p>
                </td>
            </tr>
        </table>
    </td>
</tr>
<tr>
    <td class="section" style="padding-top:20px;padding-bottom:60px;">
        <table role="presentation" width="100%">
            <tr>
                <td style="vertical-align:middle;width:150px;">
                    <p class="section-title"><?php esc_html_e('Sledujte nás', 'developer-lessons'); ?></p>
                </td>
                <td style="vertical-align:middle;">
                    <table role="presentation" align="left">
                        <tr>
                            <td style="padding-right:14px;">
                                <a href="https://www.facebook.com/lenkakrejcovabarreacademy/" target="_blank">
                                    <img src="<?php echo esc_url($facebook_icon_url); ?>" width="30" height="30" alt="Facebook">
                                </a>
                            </td>
                            <td>
                                <a href="https://www.instagram.com/lenka_krejcova_barre_academy/" target="_blank">
                                    <img src="<?php echo esc_url($instagram_icon_url); ?>" width="30" height="30" alt="Instagram">
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>
