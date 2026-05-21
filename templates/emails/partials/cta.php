<?php
/**
 * Shared CTA button.
 *
 * @var string $cta_url
 * @var string $cta_label
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($cta_url) || empty($cta_label)) {
    return;
}
?>
<tr>
    <td class="section" style="padding-top:24px;padding-bottom:38px;">
        <a class="button" href="<?php echo esc_url($cta_url); ?>" target="_blank"><?php echo esc_html($cta_label); ?></a>
    </td>
</tr>
