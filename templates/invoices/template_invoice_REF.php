<?php
/**
 * PDF document REFERRENCE (pro-forma / invoice)
 *
 * @var object $order
 * @var array  $customer
 * @var array  $seller_company
 * @var array  $seller_bank
 * @var string $doc_number
 * @var string $doc_label
 * @var string $date_field
 */

if (!defined('ABSPATH')) {
    exit;
}

    function invoice_markup($TYPE, $INVOICING, $INVOICENUM, $NAME, $CC_MAIL, $PHONE, $DATE, $REQUIRE_INVOICE=false, $INV_NAME=null, $INV_ADDRESS=null, $INV_VAT=null, $INV_DIC=null, $VOUCHER_VALUE=null, $VOUCHER_ENTRIES=null, $VOUCHER_TOTAL=null, $WORKSHOP_NAME=null, $WORKSHOP_BYID=null, $WORKSHOP_DATE=null, $WORKSHOP_PLACE=null) {
    $MARKUP_DATA = '';

        if($TYPE == 'voucher') {
            $ORDERED_ITEM_NAME = "Dárkový poukaz";
        }
        if($TYPE == 'ttraining') {
            $ORDERED_ITEM_NAME = 'Teacher Training';
        }
        if($TYPE == 'ttraining2') {
            $ORDERED_ITEM_NAME = 'Teacher Training Level 2';
        }
        if($TYPE == 'studio') {
            $ORDERED_ITEM_NAME = 'Kurz: Jak si otevřít pilates studio';
        }
        if($TYPE == 'bstraining') {
            $ORDERED_ITEM_NAME = 'Barre & Strength Training Workshop';
        }
        if($TYPE == 'choreoinsp') {
            $ORDERED_ITEM_NAME = 'Choreo Inspiration Workshop';
        }

        $MARKUP_DATA = '';
        $MARKUP_DATA .= '<p class="plain" style="font-size: 8pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">FAKTURA - DAŇOVÝ DOKLAD</p> <div class="frame" style="border: 1px solid #000; padding: 1rem;"> <div class="row section"> <table style="width: 100%;" width="100%"> <tbody> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top">';
        $MARKUP_DATA .= '<h1 class="title" style="font-weight: bold; margin-top: 0; margin-bottom: 0.5em; font-size: 21pt; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Lenka&nbsp;Krejčová Barre&nbsp;Academy</h1>';
        $MARKUP_DATA .= '</td> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"><p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Faktura číslo:';
        $MARKUP_DATA .= $INVOICENUM;
        $MARKUP_DATA .= '</p></td> </tr> </tbody> </table> </div> <div class="row section"> <table style="width: 100%;" width="100%"> <tbody> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Dodavatel:</span></p>';

    // - / - / - / - / - / - / - / - / - / 
    // INVOICE SUBJ VARIANTS
    // - / - / - / - / - / - / - / - / - / 

        if($INVOICING != 'LKBARRE') {
        // LENKA MATERNINI
            $MARKUP_DATA .= '<p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Lenka Maternini</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Řehořova 986/16</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">130 00 Praha 3</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Česká republika</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">IČ:</span> 05378125</p>';
        } else {
        // LK BARRE PRAGUE S.R.O.
            $MARKUP_DATA .= '<p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">LK Barre Prague s.r.o.</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Kaprova 40/12</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">110 00 Praha Staré Město</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Česká republika</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">IČ:</span> 22246339</p>';
        }
    // - / - / - / - / - / - / - / - / - / 
    // END INVOICE SUBJ VARIANTS
    // - / - / - / - / - / - / - / - / - / 
        
        $MARKUP_DATA .= '</td> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Odběratel:</span></p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">';
        if($INV_NAME) {
            $MARKUP_DATA .= $INV_NAME;
        } else {
            $MARKUP_DATA .= $NAME;
        }
        $MARKUP_DATA .= '</p>';
        if($INV_ADDRESS) {
            $MARKUP_DATA .= '<p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">';
            $MARKUP_DATA .= $INV_ADDRESS;
            $MARKUP_DATA .= '</p>';
        }
        if($INV_VAT) {
            $MARKUP_DATA .= '<p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">IČ: ';
            $MARKUP_DATA .= $INV_VAT;
            $MARKUP_DATA .= '</p>';
        }
        if($INV_DIC) {
            $MARKUP_DATA .= '<p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">DIČ: ';
            $MARKUP_DATA .= $INV_DIC;
            $MARKUP_DATA .= '</p>';
        }
        if(!$REQUIRE_INVOICE) {
            $MARKUP_DATA .= '<p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">';
            $MARKUP_DATA .= $CC_MAIL;
            $MARKUP_DATA .= '</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">';
            $MARKUP_DATA .= $PHONE;
            $MARKUP_DATA .= '</p>';
        }
        $MARKUP_DATA .= '</td> </tr> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Banka:</span> ';
        if($INVOICING != 'LKBARRE') {
            $MARKUP_DATA .= 'Air Bank a.s.</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Číslo účtu:</span> 1517860032/3030</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">IBAN:</span> CZ56 3030 0000 0015 1786 0032</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">SWIFT (BIC):</span> AIRACZPP</p>';
        } else {
            $MARKUP_DATA .= 'Raiffeisenbank a.s.</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Číslo účtu:</span> 222463393/5500</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">IBAN:</span> CZ17 5500 0000 0002 2246 3393</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">SWIFT (BIC):</span> RZBCCZPP</p>';
        }
        $MARKUP_DATA .= '<p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Variabilní symbol:</span> ';
        $MARKUP_DATA .= str_replace('-', '', $INVOICENUM);
        $MARKUP_DATA .= '</p> </td> </tr> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Forma úhrady:</span> Převodem na účet</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Nejsem plátce DPH</p> </td> </tr> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 0; width: 50%;" width="50%" valign="top"> <div class="column" style="margin: 0;"><span class="label strong" style="font-weight: 700;">Datum vystavení:</span></div> </td> <td style="vertical-align: top; padding: 1em 0 0; width: 50%;" width="50%" valign="top"> <div class="column" style="margin: 0;">';
        $MARKUP_DATA .= date("d. m. Y", $DATE);
        $MARKUP_DATA .= '</div> </td> </tr> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"><div class="column" style="margin: 0;"><span class="label strong" style="font-weight: 700;">Datum splatnosti:</span></div></td> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"><div class="column" style="margin: 0;">';
        $MARKUP_DATA .= date('d. m. Y', time() + 3*24*60*60);
        $MARKUP_DATA .= '</div></td> </tr> </tbody> </table> </div> <div class="section"> <table style="width: 100%;" width="100%"> <tbody> <tr style="padding-bottom: 1rem;"> <td class="pad-0" style="vertical-align: top; width: 50%; padding: 0;" width="50%" valign="top"><div class="column" style="margin: 0;"><span class="label strong" style="font-weight: 700;">Popis:</span></div></td> <td class="pad-0" style="vertical-align: top; width: 50%; padding: 0;" width="50%" valign="top"><div class="column" style="margin: 0;"><span class="label strong" style="font-weight: 700;">Cena:</span></div></td> </tr> </tbody> </table> <table style="width: 100%;" width="100%"> <tbody> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <div class="column" style="margin: 0;">';

        $MARKUP_DATA .= $ORDERED_ITEM_NAME; 
    // - / - / - / - / - / - / - / - / - / 
    // ORDERED PRODUCT VARIANTS
    // - / - / - / - / - / - / - / - / - / 
        if($TYPE == 'voucher') {
            $MARKUP_DATA .= ', ';
            $MARKUP_DATA .= $VOUCHER_VALUE;
            $MARKUP_DATA .= ' Kč/lekce, ';
            $MARKUP_DATA .= $VOUCHER_ENTRIES;
            if($VOUCHER_ENTRIES > 4) {
                $MARKUP_DATA .= ' lekcí';
            } else {
                $MARKUP_DATA .= ' lekce';
            }
        }
        if($TYPE == 'ttraining' || $TYPE == 'ttraining2' || $TYPE == 'choreoinsp') {
            $MARKUP_DATA .= ' ';
            $MARKUP_DATA .= $WORKSHOP_BYID['lecture'];
            $MARKUP_DATA .= '<br/>';
            $MARKUP_DATA .= $WORKSHOP_DATE;
            $MARKUP_DATA .= ', ';
            $MARKUP_DATA .= $WORKSHOP_PLACE;
        }
    // - / - / - / - / - / - / - / - / - / 
    // END ORDERED PRODUCT VARIANTS
    // - / - / - / - / - / - / - / - / - / 
        $MARKUP_DATA .= '</div> </td> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <div class="column" style="margin: 0;">';
        $MARKUP_DATA .= pretty_price($VOUCHER_TOTAL);
        $MARKUP_DATA .= '</div> </td></tr></tbody></table></div> <div class="section" style="border-top: 1px solid #555;"> <table style="width: 100%;" width="100%"> <tbody> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <h2 class="price" style="font-weight: bold; margin-top: 0; margin-bottom: 0.5em; font-size: 18pt; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Celkem k úhradě</h2> </td> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"> <h2 class="price" style="font-weight: bold; margin-top: 0; margin-bottom: 0.5em; font-size: 18pt; font-family: \'Inter\', Helvetica, Arial, sans-serif;">';
        $MARKUP_DATA .= pretty_price($VOUCHER_TOTAL);
        $MARKUP_DATA .= '</h2> </td> </tr>';
        $MARKUP_DATA .= '<tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top" colspan="2"> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Prodlení úhrady této faktury bude penalizováno úrokem z prodlení ve výši 0,1 % denně.</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;" >Případná reklamace této faktury musí být učiněna do data její splatnosti.</p> </td> </tr> </tbody> </table> </div> <div class="section" style="border-top: 1px solid #555;"> <table style="width: 100%;" width="100%"> <tbody> <tr style="padding-bottom: 1rem;"> <td style="vertical-align: top; padding: 1em 0 2.5em; width: 50%;" width="50%" valign="top"><p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;"><span class="label strong" style="font-weight: 700;">Vystavil:</span></p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">Lenka Maternini</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">lenka@barreacademy.cz</p> <p class="plain" style="font-size: 10pt; font-weight: normal; margin: 0; font-family: \'Inter\', Helvetica, Arial, sans-serif;">+420 608 438 728</p></td> </tr> </tbody> </table> </div> </div>';

        return $MARKUP_DATA;

    }

    function mpdf_custom_font() {
            require_once '../vendor/autoload.php';
            $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            /* INVOICE */
            $invoiceMPDF = new \Mpdf\Mpdf([
                'fontDir' => array_merge($fontDirs, [
                    __DIR__ . 'public/fonts',
                ]),
                'fontdata' => $fontData + [ // lowercase letters only in font key
                    'inter' => [
                        'R' => 'Inter-roman.ttf',
                    ],
                ],
                'default_font' => 'inter',
                'mode'          => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
            ]);

            $INVOICE_CSS = '<style> body { font-family: inter, sans-serif; } </style>';
            $INVOICE_MARKUP = invoice_markup('ttraining2', 'LENKAK', $INVOICENUM, $NAME, $CC_MAIL, $PHONE, $DATE, $INV_TRUE, $INV_NAME, $INV_ADDRESS, $INV_VAT, $INV_DIC, NULL, NULL, $PRICE, $WORKSHOP_NAME, $WORKSHOP_BYID, $WORKSHOP_DATE, $WORKSHOP_PLACE);
            
            $invoiceMPDF->WriteHTML($INVOICE_CSS, \Mpdf\HTMLParserMode::HEADER_CSS);
            $invoiceMPDF->WriteHTML($INVOICE_MARKUP, \Mpdf\HTMLParserMode::HTML_BODY);
            $INVOICE_NAME = 'LKBA_faktura_' . $INVOICENUM . '-' . strval($WORKSHOP_NAME) . '.pdf';
            $INVOICE_SAFE_NAME = safename('LKBA_faktura_' . $INVOICENUM . '-' . strval($WORKSHOP_NAME)) . '.pdf';
            $INVOICE_PDF_FULLPATH = "../invoices/{$INVOICE_SAFE_NAME}";
            $invoiceMPDF->Output($INVOICE_PDF_FULLPATH, 'F');
    }
?>