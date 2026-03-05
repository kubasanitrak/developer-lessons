<?php
/**
 * QR Code Generator for Bank Payments
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_QR_Generator {

    /**
     * Generate QR code for bank payment (SPAYD format)
     */
    public function generate_payment_qr($iban, $amount, $currency, $variable_symbol) {
        // Build SPAYD (Short Payment Descriptor) string
        $iban = preg_replace('/\s+/', '', $iban);
        
        $spayd_parts = array(
            'SPD*1.0',
            'ACC:' . $iban,
            'AM:' . number_format($amount, 2, '.', ''),
            'CC:' . $currency,
            'X-VS:' . preg_replace('/[^0-9]/', '', $variable_symbol)
        );

        $spayd = implode('*', $spayd_parts);

        // Generate QR code using Google Charts API (or use a library)
        $qr_url = 'https://chart.googleapis.com/chart?' . http_build_query(array(
            'cht' => 'qr',
            'chs' => '300x300',
            'chl' => $spayd,
            'choe' => 'UTF-8'
        ));

        return $qr_url;
    }

    /**
     * Generate QR code as SVG (using simple library)
     */
    public function generate_qr_svg($data) {
        // This is a placeholder - you could integrate a PHP QR code library
        // like 'chillerlan/php-qrcode' or 'endroid/qr-code'
        return null;
    }
}
