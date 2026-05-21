<?php
/**
 * Invoices overview admin page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap dl-admin-wrap">
    <h1><?php _e('Invoices', 'developer-lessons'); ?></h1>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'no_files'): ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php _e('No invoice files were selected for download.', 'developer-lessons'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$dir_exists): ?>
        <div class="notice notice-warning">
            <p><?php _e('Invoices directory not found. PDF files will appear here after orders generate pro-formas or invoices.', 'developer-lessons'); ?></p>
        </div>
    <?php endif; ?>

    <div class="dl-invoices-filter">
        <form method="get">
            <input type="hidden" name="page" value="dl-invoices">

            <label for="dl-invoices-date-from"><?php _e('Date from', 'developer-lessons'); ?></label>
            <input type="date" name="date_from" id="dl-invoices-date-from" value="<?php echo esc_attr($date_from); ?>">

            <label for="dl-invoices-date-to"><?php _e('Date to', 'developer-lessons'); ?></label>
            <input type="date" name="date_to" id="dl-invoices-date-to" value="<?php echo esc_attr($date_to); ?>">

            <label for="dl-invoices-sort"><?php _e('Sort by date', 'developer-lessons'); ?></label>
            <select name="sort" id="dl-invoices-sort">
                <option value="desc" <?php selected($sort, 'desc'); ?>><?php _e('Newest first', 'developer-lessons'); ?></option>
                <option value="asc" <?php selected($sort, 'asc'); ?>><?php _e('Oldest first', 'developer-lessons'); ?></option>
            </select>

            <button type="submit" class="button"><?php _e('Filter', 'developer-lessons'); ?></button>
            <?php if ($date_from !== '' || $date_to !== ''): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=dl-invoices')); ?>" class="button"><?php _e('Clear', 'developer-lessons'); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=dl-invoices')); ?>" id="dl-invoices-download-form">
        <?php wp_nonce_field(DL_Admin_Invoices::NONCE_ACTION); ?>
        <input type="hidden" name="dl_invoice_action" value="download_zip">

        <table class="wp-list-table widefat fixed striped dl-invoices-table">
            <thead>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" id="dl-invoices-select-all" <?php disabled(empty($files)); ?>>
                    </td>
                    <th><?php _e('Date created', 'developer-lessons'); ?></th>
                    <th><?php _e('Filename', 'developer-lessons'); ?></th>
                    <th><?php _e('File size', 'developer-lessons'); ?></th>
                    <th><?php _e('View', 'developer-lessons'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="5"><?php _e('No invoice PDF files found.', 'developer-lessons'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox"
                                       class="dl-invoice-file-checkbox"
                                       name="invoice_files[]"
                                       value="<?php echo esc_attr($file['filename']); ?>">
                            </th>
                            <td><?php echo esc_html($file['date']); ?></td>
                            <td><code><?php echo esc_html($file['filename']); ?></code></td>
                            <td><?php echo esc_html(size_format($file['size'])); ?></td>
                            <td>
                                <a href="<?php echo esc_url(DL_Admin_Invoices::view_url($file['filename'])); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <?php _e('Open PDF', 'developer-lessons'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p class="dl-invoices-download-actions">
            <?php if (!$zip_available): ?>
                <span class="description"><?php _e('ZIP download requires the PHP Zip extension (ZipArchive).', 'developer-lessons'); ?></span>
            <?php else: ?>
                <button type="submit" class="button button-primary" id="dl-invoices-download-zip" <?php disabled(empty($files)); ?>>
                    <?php _e('Download selected as ZIP', 'developer-lessons'); ?>
                </button>
            <?php endif; ?>
        </p>
    </form>
</div>
