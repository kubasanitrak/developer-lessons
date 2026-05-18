<?php
/**
 * Branded email header.
 */

if (!defined('ABSPATH')) {
    exit;
}

$logo_url = DL_PLUGIN_URL . 'assets/email/LKBA-logo.png';
?>
<tr>
    <td class="section" style="padding-top:30px;padding-bottom:30px;">
        <a href="https://barreacademy.cz/" target="_blank">
            <img src="<?php echo esc_url($logo_url); ?>" width="80" alt="Lenka Krejčová Barre Academy">
        </a>
    </td>
</tr>
