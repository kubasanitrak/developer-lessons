<?php
/**
 * Seller / company details (invoices, bank transfer, emails)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Seller {

    /**
     * Seller company + address for PDFs
     */
    public static function get_company() {
        return array(
            'name' => get_option('dl_seller_name', ''),
            'street' => get_option('dl_seller_street', ''),
            'city' => get_option('dl_seller_city', ''),
            'zip' => get_option('dl_seller_zip', ''),
            'country' => get_option('dl_seller_country', ''),
            'ic' => get_option('dl_seller_ic', ''),
            'dic' => get_option('dl_seller_dic', ''),
        );
    }

    /**
     * Bank details (shared with bank transfer UI)
     */
    public static function get_bank() {
        $account_name = get_option('dl_seller_name', '');
        if ($account_name === '') {
            $account_name = get_option('dl_bank_account_name', '');
        }

        return array(
            'account_name' => $account_name,
            'account_number' => self::get_option_with_legacy('dl_seller_account_number', 'dl_bank_account_number'),
            'bank_code' => self::get_option_with_legacy('dl_seller_bank_code', 'dl_bank_code'),
            'iban' => self::get_option_with_legacy('dl_seller_iban', 'dl_bank_iban'),
            'bic' => self::get_option_with_legacy('dl_seller_bic', 'dl_bank_bic'),
        );
    }

    /**
     * Formatted multi-line seller address for PDFs
     */
    public static function format_company_address() {
        $c = self::get_company();
        $lines = array();

        if ($c['name'] !== '') {
            $lines[] = $c['name'];
        }
        if ($c['street'] !== '') {
            $lines[] = $c['street'];
        }
        $city_line = trim($c['zip'] . ' ' . $c['city']);
        if ($city_line !== '') {
            $lines[] = $city_line;
        }
        if ($c['country'] !== '') {
            $lines[] = $c['country'];
        }
        if ($c['ic'] !== '') {
            $lines[] = sprintf(__('Company ID: %s', 'developer-lessons'), $c['ic']);
        }
        if ($c['dic'] !== '') {
            $lines[] = sprintf(__('VAT ID: %s', 'developer-lessons'), $c['dic']);
        }

        return implode("\n", $lines);
    }

    /**
     * Copy legacy bank options into seller options on upgrade
     */
    public static function maybe_migrate_legacy_bank_options() {
        if (get_option('dl_seller_migrated')) {
            return;
        }

        $map = array(
            'dl_bank_account_name' => 'dl_seller_name',
            'dl_bank_account_number' => 'dl_seller_account_number',
            'dl_bank_code' => 'dl_seller_bank_code',
            'dl_bank_iban' => 'dl_seller_iban',
            'dl_bank_bic' => 'dl_seller_bic',
        );

        foreach ($map as $legacy => $seller_key) {
            if (get_option($seller_key) === false && get_option($legacy)) {
                update_option($seller_key, get_option($legacy));
            }
        }

        update_option('dl_seller_migrated', 1);
    }

    private static function get_option_with_legacy($primary, $legacy) {
        $val = get_option($primary, '');
        if ($val === '' || $val === false) {
            $val = get_option($legacy, '');
        }
        return $val;
    }
}
