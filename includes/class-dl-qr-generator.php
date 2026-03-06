<?php
/**
 * QR Code Generator for Czech Bank Payments
 * Uses Paylibo API for generating SPAYD QR codes
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_QR_Generator {

    /**
     * Paylibo API endpoint
     */
    private $api_url = 'https://api.paylibo.com/paylibo/generator/czech/image';

    /**
     * Generate QR code for bank payment using Paylibo API
     *
     * @param float  $amount          Payment amount
     * @param string $currency        Currency code (default: CZK)
     * @param string $variable_symbol Variable symbol (order number)
     * @param string $message         Optional payment message
     * @param int    $size            QR code size in pixels (default: 200)
     * @return string|null            QR code image URL or null on failure
     */
    public function generate_payment_qr($amount, $currency = 'CZK', $variable_symbol = '', $message = '', $size = 200) {
        // Get bank account details from settings
        $account_number = get_option('dl_bank_account_number', '');
        $bank_code = get_option('dl_bank_code', '');

        // Validate required fields
        if (empty($account_number) || empty($bank_code)) {
            $this->log_error('Missing bank account details for QR generation');
            return null;
        }

        // Clean variable symbol - remove non-numeric characters, max 10 digits
        $vs = preg_replace('/[^0-9]/', '', $variable_symbol);
        $vs = substr($vs, 0, 10);

        // Build API parameters
        $params = array(
            'accountNumber' => $this->sanitize_account_number($account_number),
            'bankCode' => $this->sanitize_bank_code($bank_code),
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency' => strtoupper($currency),
            'size' => intval($size),
        );

        // Add variable symbol if provided
        if (!empty($vs)) {
            $params['vs'] = $vs;
        }

        // Add message if provided (max 60 characters)
        if (!empty($message)) {
            $params['message'] = $this->sanitize_message($message);
        }

        // Build QR code URL
        $qr_url = add_query_arg($params, $this->api_url);

        // Verify the URL is accessible (optional - can be removed for performance)
        if ($this->verify_qr_url($qr_url)) {
            return $qr_url;
        }

        // Return URL anyway - let the browser handle any errors
        return $qr_url;
    }

    /**
     * Generate QR code with IBAN (alternative method)
     *
     * @param string $iban            IBAN account number
     * @param float  $amount          Payment amount
     * @param string $currency        Currency code
     * @param string $variable_symbol Variable symbol
     * @param int    $size            QR code size
     * @return string|null            QR code image URL
     */
    public function generate_payment_qr_iban($iban, $amount, $currency = 'CZK', $variable_symbol = '', $size = 200) {
        if (empty($iban)) {
            return null;
        }

        // Extract account number and bank code from Czech IBAN
        // Czech IBAN format: CZ00 BBBB PPPP PPPP PPPP PPPP
        // BBBB = bank code, rest = account number
        $iban = preg_replace('/\s+/', '', $iban);
        
        if (strlen($iban) >= 24 && substr($iban, 0, 2) === 'CZ') {
            $bank_code = substr($iban, 4, 4);
            $account_prefix = ltrim(substr($iban, 8, 6), '0');
            $account_base = ltrim(substr($iban, 14, 10), '0');
            
            if (!empty($account_prefix)) {
                $account_number = $account_prefix . '-' . $account_base;
            } else {
                $account_number = $account_base;
            }

            // Temporarily override settings for this call
            $orig_account = get_option('dl_bank_account_number');
            $orig_bank = get_option('dl_bank_code');
            
            update_option('dl_bank_account_number', $account_number);
            update_option('dl_bank_code', $bank_code);
            
            $result = $this->generate_payment_qr($amount, $currency, $variable_symbol, '', $size);
            
            // Restore original settings
            update_option('dl_bank_account_number', $orig_account);
            update_option('dl_bank_code', $orig_bank);
            
            return $result;
        }

        return null;
    }

    /**
     * Generate QR code for order
     *
     * @param object $order Order object
     * @param int    $size  QR code size
     * @return string|null  QR code URL
     */
    public function generate_order_qr($order, $size = 250) {
        if (!$order) {
            return null;
        }

        $message = sprintf(
            __('Order %s', 'developer-lessons'),
            $order->order_number
        );

        return $this->generate_payment_qr(
            $order->total,
            $order->currency ?: 'CZK',
            $order->order_number,
            $message,
            $size
        );
    }

    /**
     * Sanitize account number
     * Handles formats: 123456789, 123-456789, 000123-0456789
     */
    private function sanitize_account_number($account_number) {
        // Remove spaces
        $account_number = preg_replace('/\s+/', '', $account_number);
        
        // If contains dash, it has prefix
        if (strpos($account_number, '-') !== false) {
            list($prefix, $base) = explode('-', $account_number, 2);
            $prefix = ltrim($prefix, '0');
            $base = ltrim($base, '0');
            
            if (!empty($prefix)) {
                return $prefix . '-' . $base;
            }
            return $base;
        }

        // Just base account number
        return ltrim($account_number, '0');
    }

    /**
     * Sanitize bank code
     */
    private function sanitize_bank_code($bank_code) {
        // Remove spaces and non-numeric characters
        $bank_code = preg_replace('/[^0-9]/', '', $bank_code);
        
        // Ensure 4 digits, pad with zeros if needed
        return str_pad($bank_code, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sanitize message for QR code
     * Max 60 characters, ASCII only
     */
    private function sanitize_message($message) {
        // Convert to ASCII
        $message = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $message);
        
        // Remove special characters
        $message = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $message);
        
        // Trim and limit length
        $message = trim($message);
        $message = substr($message, 0, 60);
        
        return $message;
    }

    /**
     * Verify QR code URL is accessible
     */
    private function verify_qr_url($url) {
        $response = wp_remote_head($url, array(
            'timeout' => 5,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            $this->log_error('QR URL verification failed: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    }

    /**
     * Log error message
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DL_QR_Generator: ' . $message);
        }
    }

    /**
     * Get QR code as base64 encoded image (for embedding)
     *
     * @param float  $amount          Payment amount
     * @param string $variable_symbol Variable symbol
     * @return string|null            Base64 encoded image data
     */
    public function get_qr_base64($amount, $variable_symbol = '') {
        $url = $this->generate_payment_qr($amount, 'CZK', $variable_symbol);
        
        if (!$url) {
            return null;
        }

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if (empty($body)) {
            return null;
        }

        return 'data:' . $content_type . ';base64,' . base64_encode($body);
    }

    /**
     * Render QR code HTML
     *
     * @param float  $amount          Payment amount
     * @param string $variable_symbol Variable symbol
     * @param int    $size            QR code size
     * @return string                 HTML markup
     */
    public function render_qr_html($amount, $variable_symbol = '', $size = 200) {
        $url = $this->generate_payment_qr($amount, 'CZK', $variable_symbol, '', $size);
        
        if (!$url) {
            return '<p class="dl-qr-error">' . __('QR code could not be generated. Please use the bank details above.', 'developer-lessons') . '</p>';
        }

        $alt = __('Payment QR Code', 'developer-lessons');
        
        return sprintf(
            '<div class="dl-qr-code"><img src="%s" alt="%s" width="%d" height="%d" loading="lazy"></div>',
            esc_url($url),
            esc_attr($alt),
            intval($size),
            intval($size)
        );
    }
}
