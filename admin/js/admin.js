/**
 * Developer Lessons Admin JS
 */

(function($) {
    'use strict';

    const DLAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Confirm payment button
            $(document).on('click', '.dl-confirm-payment', this.confirmPayment);
            
            // Cancel order button
            $(document).on('click', '.dl-cancel-order', this.cancelOrder);

            // Invoices: select all
            $(document).on('change', '#dl-invoices-select-all', this.toggleInvoiceSelectAll);
            $(document).on('change', '.dl-invoice-file-checkbox', this.syncInvoiceSelectAll);
            $(document).on('submit', '#dl-invoices-download-form', this.validateInvoiceDownload);
        },

        confirmPayment: function(e) {
            e.preventDefault();
            
            if (!confirm(dl_admin.strings.confirm_action)) {
                return;
            }

            const $btn = $(this);
            const orderId = $btn.data('order-id');

            $btn.prop('disabled', true).text(dl_admin.strings.processing);

            $.ajax({
                url: dl_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_confirm_bank_transfer',
                    order_id: orderId,
                    nonce: dl_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || dl_admin.strings.error);
                        $btn.prop('disabled', false).text('Confirm Payment');
                    }
                },
                error: function() {
                    alert(dl_admin.strings.error);
                    $btn.prop('disabled', false).text('Confirm Payment');
                }
            });
        },

        cancelOrder: function(e) {
            if (!confirm(dl_admin.strings.confirm_action)) {
                e.preventDefault();
            }
        },

        toggleInvoiceSelectAll: function() {
            const checked = $(this).prop('checked');
            $('.dl-invoice-file-checkbox').prop('checked', checked);
        },

        syncInvoiceSelectAll: function() {
            const $boxes = $('.dl-invoice-file-checkbox');
            const $all = $('#dl-invoices-select-all');
            if (!$all.length || !$boxes.length) {
                return;
            }
            const total = $boxes.length;
            const checked = $boxes.filter(':checked').length;
            $all.prop('checked', total > 0 && checked === total);
            $all.prop('indeterminate', checked > 0 && checked < total);
        },

        validateInvoiceDownload: function(e) {
            if ($('.dl-invoice-file-checkbox:checked').length === 0) {
                e.preventDefault();
                alert(dl_admin.strings.no_invoices_selected || 'Please select at least one invoice.');
            }
        }
    };

    $(document).ready(function() {
        DLAdmin.init();
    });

})(jQuery);
