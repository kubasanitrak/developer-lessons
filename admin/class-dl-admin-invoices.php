<?php
/**
 * Admin invoices overview (PDF files in uploads)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Admin_Invoices {

    const PAGE_SLUG = 'dl-invoices';
    const NONCE_ACTION = 'dl_invoices_action';

    public function __construct() {
        add_action('admin_init', array($this, 'handle_actions'), 1);
    }

    /**
     * Handle PDF view and ZIP download before page output
     */
    public function handle_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = '';
        if (isset($_GET['dl_invoice_action'])) {
            $action = sanitize_text_field(wp_unslash($_GET['dl_invoice_action']));
        } elseif (isset($_POST['dl_invoice_action'])) {
            $action = sanitize_text_field(wp_unslash($_POST['dl_invoice_action']));
        }

        if ($action === '') {
            return;
        }

        if ($action === 'view') {
            $this->handle_view();
            return;
        }

        if ($action === 'download_zip') {
            $this->handle_download_zip();
        }
    }

    /**
     * Render invoices overview page
     */
    public static function render_page() {
        $dir = DL_Invoices::get_invoices_dir();
        $dir_exists = is_dir($dir);

        $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
        $sort = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'asc' : 'desc';

        $files = $dir_exists ? self::list_files($date_from, $date_to, $sort) : array();
        $zip_available = class_exists('ZipArchive');

        include DL_PLUGIN_DIR . 'admin/partials/invoices-page.php';
    }

    /**
     * List PDF files in the invoices directory
     *
     * @return array<int, array{filename: string, size: int, mtime: int, date: string}>
     */
    private static function list_files($date_from, $date_to, $sort) {
        $dir = DL_Invoices::get_invoices_dir();
        if (!is_dir($dir)) {
            return array();
        }

        $from_ts = self::parse_filter_date($date_from, true);
        $to_ts = self::parse_filter_date($date_to, false);

        $files = array();
        $entries = scandir($dir);
        if ($entries === false) {
            return array();
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;
            if (!is_file($path) || strtolower(substr($entry, -4)) !== '.pdf') {
                continue;
            }

            $mtime = (int) filemtime($path);
            if ($from_ts !== null && $mtime < $from_ts) {
                continue;
            }
            if ($to_ts !== null && $mtime > $to_ts) {
                continue;
            }

            $files[] = array(
                'filename' => $entry,
                'size' => (int) filesize($path),
                'mtime' => $mtime,
                'date' => wp_date(get_option('date_format') . ' ' . get_option('time_format'), $mtime),
            );
        }

        usort($files, function ($a, $b) use ($sort) {
            if ($sort === 'asc') {
                return $a['mtime'] <=> $b['mtime'];
            }
            return $b['mtime'] <=> $a['mtime'];
        });

        return $files;
    }

    /**
     * @param string $date Y-m-d
     * @param bool   $start_of_day
     * @return int|null
     */
    private static function parse_filter_date($date, $start_of_day) {
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        $time = $start_of_day ? '00:00:00' : '23:59:59';
        $datetime = $date . ' ' . $time;
        $ts = strtotime($datetime);

        return $ts !== false ? (int) $ts : null;
    }

    private function handle_view() {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), self::NONCE_ACTION)) {
            wp_die(esc_html__('Invalid security token.', 'developer-lessons'));
        }

        $filename = isset($_GET['file']) ? sanitize_file_name(wp_unslash($_GET['file'])) : '';
        $path = self::resolve_safe_path($filename);

        if (!$path) {
            wp_die(esc_html__('File not found.', 'developer-lessons'));
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . rawurlencode(basename($path)) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($path);
        exit;
    }

    private function handle_download_zip() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::NONCE_ACTION)) {
            wp_die(esc_html__('Invalid security token.', 'developer-lessons'));
        }

        if (!class_exists('ZipArchive')) {
            wp_die(esc_html__('ZIP extension is not available on this server.', 'developer-lessons'));
        }

        $selected = isset($_POST['invoice_files']) ? (array) $_POST['invoice_files'] : array();
        if (empty($selected)) {
            wp_safe_redirect(add_query_arg('message', 'no_files', admin_url('admin.php?page=' . self::PAGE_SLUG)));
            exit;
        }

        $paths = array();
        foreach ($selected as $name) {
            $path = self::resolve_safe_path(sanitize_file_name(wp_unslash($name)));
            if ($path) {
                $paths[] = $path;
            }
        }

        if (empty($paths)) {
            wp_safe_redirect(add_query_arg('message', 'no_files', admin_url('admin.php?page=' . self::PAGE_SLUG)));
            exit;
        }

        $zip = new ZipArchive();
        $tmp = wp_tempnam('dl-invoices-');
        if ($tmp === false || $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            wp_die(esc_html__('Could not create ZIP archive.', 'developer-lessons'));
        }

        foreach ($paths as $path) {
            $zip->addFile($path, basename($path));
        }
        $zip->close();

        $download_name = 'invoices-' . gmdate('Y-m-d-His') . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $download_name . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    /**
     * Resolve filename to absolute path within invoices directory
     *
     * @param string $filename
     * @return string|false
     */
    private static function resolve_safe_path($filename) {
        if ($filename === '' || strtolower(substr($filename, -4)) !== '.pdf') {
            return false;
        }

        $dir = realpath(DL_Invoices::get_invoices_dir());
        if ($dir === false) {
            return false;
        }

        $path = realpath($dir . '/' . $filename);
        if ($path === false || strpos($path, $dir . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }

        return is_file($path) ? $path : false;
    }

    /**
     * Admin URL to view a PDF in a new tab
     */
    public static function view_url($filename) {
        return wp_nonce_url(
            add_query_arg(
                array(
                    'page' => self::PAGE_SLUG,
                    'dl_invoice_action' => 'view',
                    'file' => $filename,
                ),
                admin_url('admin.php')
            ),
            self::NONCE_ACTION
        );
    }
}
