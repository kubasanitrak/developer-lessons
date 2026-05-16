<?php
/**
 * Pro-forma and invoice PDF generation (mPDF)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Invoices {

    const SUBDIR = 'developer-lessons/invoices';

    /**
     * Ensure upload invoices directory exists and is protected
     */
    public static function ensure_storage_dir() {
        $upload = wp_upload_dir();
        if (!empty($upload['error'])) {
            return false;
        }

        $dir = trailingslashit($upload['basedir']) . self::SUBDIR;
        if (!wp_mkdir_p($dir)) {
            return false;
        }

        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }

        $index = $dir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }

        return $dir;
    }

    /**
     * Absolute path to invoices directory
     */
    public static function get_invoices_dir() {
        $upload = wp_upload_dir();
        return trailingslashit($upload['basedir']) . self::SUBDIR;
    }

    /**
     * Generate or return existing pro-forma PDF path
     */
    public static function ensure_proforma($order_id) {
        $order = DL_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }

        if (!empty($order->proforma_pdf) && file_exists(self::absolute_path($order->proforma_pdf))) {
            return self::absolute_path($order->proforma_pdf);
        }

        if (empty($order->proforma_number)) {
            self::assign_proforma_number($order_id);
            $order = DL_Checkout::get_order($order_id);
        }

        $relative = 'proforma-' . sanitize_file_name($order->order_number) . '.pdf';
        $path = self::generate_pdf($order, 'proforma', $relative);

        if ($path) {
            self::update_order_invoice_fields($order_id, array(
                'proforma_pdf' => $relative,
            ));
        }

        return $path;
    }

    /**
     * Generate or return existing tax invoice PDF (completed orders only)
     */
    public static function ensure_invoice($order_id) {
        $order = DL_Checkout::get_order($order_id);
        if (!$order || $order->status !== 'completed') {
            return false;
        }

        if (!empty($order->invoice_pdf) && file_exists(self::absolute_path($order->invoice_pdf))) {
            return self::absolute_path($order->invoice_pdf);
        }

        self::ensure_proforma($order_id);
        $order = DL_Checkout::get_order($order_id);

        if (empty($order->invoice_number)) {
            self::assign_invoice_number($order_id);
            $order = DL_Checkout::get_order($order_id);
        }

        $relative = 'invoice-' . sanitize_file_name($order->order_number) . '.pdf';
        $path = self::generate_pdf($order, 'invoice', $relative);

        if ($path) {
            self::update_order_invoice_fields($order_id, array(
                'invoice_pdf' => $relative,
            ));
        }

        return $path;
    }

    /**
     * Customer block for PDFs
     */
    public static function get_customer_data($order) {
        $user = get_user_by('id', $order->user_id);
        $invoice_data = null;

        if (!empty($order->invoice_data)) {
            $invoice_data = json_decode($order->invoice_data, true);
        }

        if (is_array($invoice_data) && !empty($invoice_data['company_name'])) {
            return array(
                'name' => $invoice_data['company_name'],
                'address' => DL_User_Profile::format_invoice_address($invoice_data),
                'ic' => isset($invoice_data['ic']) ? $invoice_data['ic'] : '',
                'dic' => isset($invoice_data['dic']) ? $invoice_data['dic'] : '',
                'is_company' => true,
            );
        }

        $name = $user ? $user->display_name : '';
        if ($name === '' && $user) {
            $name = $user->user_login;
        }

        return array(
            'name' => $name,
            'address' => $user ? $user->user_email : '',
            'ic' => '',
            'dic' => '',
            'is_company' => false,
        );
    }

    /**
     * Next document number: YY-##### (sequence from admin "starts with")
     */
    public static function next_document_number() {
        $start = max(1, (int) get_option('dl_invoice_sequence_start', 1));
        $current = get_option('dl_invoice_sequence_current', false);
        if ($current === false) {
            $current = $start;
        } else {
            $current = max((int) $current, $start);
        }

        $year_suffix = gmdate('y');
        $number = $year_suffix . '-' . str_pad((string) $current, 5, '0', STR_PAD_LEFT);

        update_option('dl_invoice_sequence_current', $current + 1);

        return $number;
    }

    private static function assign_proforma_number($order_id) {
        $order = DL_Checkout::get_order($order_id);
        if (!$order || !empty($order->proforma_number)) {
            return;
        }
        $number = self::next_document_number();
        self::update_order_invoice_fields($order_id, array('proforma_number' => $number));
    }

    private static function assign_invoice_number($order_id) {
        $order = DL_Checkout::get_order($order_id);
        if (!$order || !empty($order->invoice_number)) {
            return;
        }
        $number = self::next_document_number();
        self::update_order_invoice_fields($order_id, array('invoice_number' => $number));
    }

    private static function generate_pdf($order, $type, $relative_filename) {
        if (!self::load_mpdf()) {
            error_log('DL Invoices: mPDF not available');
            return false;
        }

        $dir = self::ensure_storage_dir();
        if (!$dir) {
            return false;
        }

        $absolute = trailingslashit($dir) . $relative_filename;
        $html = self::render_template($type, $order);

        try {
            $mpdf = new \Mpdf\Mpdf(array(
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'fontDir' => array(DL_PLUGIN_DIR . 'vendor/mpdf/mpdf/ttfonts'),
                'fontdata' => array(
                    'dejavusans' => array(
                        'R' => 'DejaVuSans.ttf',
                        'B' => 'DejaVuSans-Bold.ttf',
                    ),
                ),
                'default_font' => 'dejavusans',
                'backupSubsFont' => array(),
                'backupSIPFont' => '',
            ));
            $mpdf->WriteHTML($html);
            $mpdf->Output($absolute, \Mpdf\Output\Destination::FILE);
        } catch (\Exception $e) {
            error_log('DL Invoices PDF error: ' . $e->getMessage());
            return false;
        }

        return file_exists($absolute) ? $absolute : false;
    }

    private static function render_template($type, $order) {
        $customer = self::get_customer_data($order);
        $seller_company = DL_Seller::get_company();
        $seller_bank = DL_Seller::get_bank();
        $doc_number = $type === 'proforma' ? $order->proforma_number : $order->invoice_number;
        $doc_label = $type === 'proforma'
            ? __('Pro-forma invoice', 'developer-lessons')
            : __('Invoice', 'developer-lessons');

        $date_field = ($type === 'invoice' && !empty($order->paid_at))
            ? $order->paid_at
            : $order->created_at;

        ob_start();
        include DL_PLUGIN_DIR . 'templates/invoices/document.php';
        return ob_get_clean();
    }

    private static function load_mpdf() {
        $autoload = DL_PLUGIN_DIR . 'vendor/autoload.php';
        if (!file_exists($autoload)) {
            return false;
        }
        require_once $autoload;
        return class_exists('\Mpdf\Mpdf');
    }

    public static function absolute_path($relative) {
        return trailingslashit(self::get_invoices_dir()) . ltrim($relative, '/');
    }

    private static function update_order_invoice_fields($order_id, $fields) {
        global $wpdb;
        $orders_table = $wpdb->prefix . 'dl_orders';
        $wpdb->update($orders_table, $fields, array('id' => $order_id));
    }
}
