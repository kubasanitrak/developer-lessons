<?php
/**
 * Shared branded email head.
 *
 * @var string $email_title
 * @var string $email_preview
 */

if (!defined('ABSPATH')) {
    exit;
}

$email_title = isset($email_title) ? $email_title : get_bloginfo('name');
$email_preview = isset($email_preview) ? $email_preview : '';
?>
<!DOCTYPE html>
<html lang="cs" dir="auto" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc_html($email_title); ?></title>
    <style>
        body, table, td, p, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            border: 0;
            display: block;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        body {
            background: #eee7d6;
            color: #000;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }
        .email-shell {
            background: #eee7d6;
            width: 100%;
        }
        .email-container {
            background: #eee7d6;
            margin: 0 auto;
            max-width: 600px;
            width: 100%;
        }
        .section {
            padding: 24px 20px;
        }
        .section-tight {
            padding: 14px 20px;
        }
        .section-alt {
            background: #f1ebdd;
        }
        .brand-title {
            color: #000;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 37px;
            font-weight: 400;
            letter-spacing: -1.11px;
            line-height: 1.11;
            margin: 0;
        }
        .section-title {
            color: #000;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 26px;
            font-weight: 400;
            letter-spacing: -0.52px;
            line-height: 1.12;
            margin: 0;
        }
        .body-copy {
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 15px;
            line-height: 1.47;
            margin: 0;
        }
        .label {
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 13px;
            letter-spacing: 0.2px;
            line-height: 1.36;
            margin: 0 0 6px;
            text-transform: uppercase;
        }
        .value {
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.47;
            margin: 0;
        }
        .button {
            background: #000;
            border-radius: 999px;
            color: #fff !important;
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            padding: 15px 22px;
            text-decoration: none;
        }
        .summary-table {
            width: 100%;
        }
        .summary-table th,
        .summary-table td {
            border-bottom: 1px solid #d8cfbc;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.45;
            padding: 10px 0;
            text-align: left;
        }
        .summary-table th {
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }
        .summary-table .price {
            text-align: right;
            white-space: nowrap;
        }
        .summary-table .total td {
            border-bottom: 0;
            font-size: 17px;
            font-weight: 700;
        }
        .footer-link {
            color: #000 !important;
            text-decoration: underline;
        }
        @media only screen and (max-width: 599px) {
            .section {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
            .brand-title {
                font-size: 30px !important;
            }
            .section-title {
                font-size: 23px !important;
            }
        }
    </style>
</head>
