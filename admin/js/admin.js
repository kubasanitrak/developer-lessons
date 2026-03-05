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
        }
    };

    $(document).ready(function() {
        DLAdmin.init();
    });

})(jQuery);
