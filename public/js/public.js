/**
 * Developer Lessons Public JS
 */

(function($) {
    'use strict';

    const DeveloperLessons = {
        init: function() {
            this.bindEvents();
            this.initBasket();
        },

        bindEvents: function() {
            // Add to basket
            $(document).on('click', '.dl-add-to-basket-btn', this.addToBasket.bind(this));
            
            // Add ALL to basket
            $(document).on('click', '.dl-add-all-btn', this.addAllToBasket.bind(this));
            
            // Remove from basket (sidebar)
            $(document).on('click', '.dl-basket-remove', this.removeFromBasket.bind(this));
            
            // Remove from checkout
            $(document).on('click', '.dl-remove-item', this.removeFromCheckout.bind(this));
            
            // Basket toggle
            $(document).on('click', '#dl-basket-toggle', this.toggleBasket.bind(this));
            $(document).on('click', '.dl-basket-close, #dl-basket-overlay', this.closeBasket.bind(this));
            
            // View basket button
            $(document).on('click', '.dl-view-basket-btn', this.openBasket.bind(this));
            
            // Checkout form
            $(document).on('submit', '#dl-checkout-form', this.processCheckout.bind(this));
            
            // Invoice toggle
            $(document).on('change', '#dl_want_invoice', this.toggleInvoiceFields.bind(this));
            
            // Save invoice to profile toggle
            $(document).on('change', '#dl_save_invoice_to_profile', this.toggleSaveInvoice.bind(this));
        },

        initBasket: function() {
            this.updateBasketCount();
        },
        /*/
        addToBasket: function(e) {
            e.preventDefault();

            if (!dl_public.is_logged_in) {
                alert(dl_public.strings.please_login);
                return;
            }

            const $btn = $(e.currentTarget);
            const lessonId = $btn.data('lesson-id');
            const originalText = $btn.text();
            const isGridItem = $btn.hasClass('grid-item--btn');

            $btn.prop('disabled', true).text(dl_public.strings.processing);

            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_add_to_basket',
                    lesson_id: lessonId,
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        DeveloperLessons.updateBasketCount(response.data.count);
                        DeveloperLessons.showNotification(response.data.message, 'success');
                        DeveloperLessons.refreshBasketSidebar();
                        
                        // Update button state
                        if (isGridItem) {
                            // Grid item button - change to "In Basket"
                            $btn.text(dl_public.strings.in_basket || 'In Basket')
                                .removeClass('dl-add-to-basket-btn')
                                .addClass('dl-btn-secondary dl-view-basket-btn')
                                .prop('disabled', false);
                        } else {
                            // CTA button - change to "View Basket"
                            $btn.text(dl_public.strings.view_basket)
                                .removeClass('dl-add-to-basket-btn')
                                .addClass('dl-view-basket-btn')
                                .prop('disabled', false);
                        }
                    } else {
                        DeveloperLessons.showNotification(response.data.message, 'error');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    DeveloperLessons.showNotification(dl_public.strings.error, 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },
        /*/
        addToBasket: function(e) {
            e.preventDefault();
            if (!dl_public.is_logged_in) {
                alert(dl_public.strings.please_login);
                return;
            }
            const $btn = $(e.currentTarget);
            const lessonId = $btn.data('lesson-id');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text(dl_public.strings.processing);
            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_add_to_basket',
                    lesson_id: lessonId,
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        DeveloperLessons.updateBasketCount(response.data.count);
                        DeveloperLessons.showNotification(response.data.message, 'success');
                        DeveloperLessons.refreshBasketSidebar();
                        
                        // Update all buttons for this lesson
                        DeveloperLessons.updateGridItemButton(lessonId, true);
                    } else {
                        DeveloperLessons.showNotification(response.data.message, 'error');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    DeveloperLessons.showNotification(dl_public.strings.error, 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },
        //*/

        addAllToBasket: function(e) {
            e.preventDefault();

            if (!dl_public.is_logged_in) {
                alert(dl_public.strings.please_login);
                return;
            }

            const $btn = $(e.currentTarget);
            const originalText = $btn.text();

            $btn.prop('disabled', true).text(dl_public.strings.processing);

            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_add_all_to_basket',
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        DeveloperLessons.updateBasketCount(response.data.count);
                        DeveloperLessons.showNotification(response.data.message, 'success');
                        DeveloperLessons.refreshBasketSidebar();
                        
                        // Change button to go to checkout
                        $btn.text(dl_public.strings.go_to_checkout || 'Go to Checkout')
                            .removeClass('dl-add-all-btn')
                            .addClass('dl-go-to-checkout-btn')
                            .prop('disabled', false)
                            .off('click')
                            .on('click', function(e) {
                                e.preventDefault();
                                window.location.href = dl_public.checkout_url;
                            });
                    } else {
                        DeveloperLessons.showNotification(response.data.message, 'error');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    DeveloperLessons.showNotification(dl_public.strings.error, 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        removeFromBasket: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const lessonId = $btn.data('lesson-id');
            const $item = $btn.closest('.dl-basket-item');
            $item.css('opacity', '0.5');
            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_remove_from_basket',
                    lesson_id: lessonId,
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $item.slideUp(200, function() {
                            $(this).remove();
                            DeveloperLessons.updateBasketCount(response.data.count);
                            DeveloperLessons.refreshBasketSidebar();
                            
                            // Update any grid item buttons for this lesson
                            DeveloperLessons.updateGridItemButton(lessonId, false);
                        });
                    } else {
                        $item.css('opacity', '1');
                        DeveloperLessons.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $item.css('opacity', '1');
                    DeveloperLessons.showNotification(dl_public.strings.error, 'error');
                }
            });
        },

        removeFromCheckout: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const lessonId = $btn.data('lesson-id');
            const $row = $btn.closest('tr');
            $row.css('opacity', '0.5');
            $btn.prop('disabled', true);
            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_remove_from_basket',
                    lesson_id: lessonId,
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $row.slideUp(200, function() {
                            $(this).remove();
                            DeveloperLessons.updateBasketCount(response.data.count);
                            DeveloperLessons.refreshBasketSidebar();
                            DeveloperLessons.updateCheckoutTotals();
                            
                            // Update any grid item buttons for this lesson
                            DeveloperLessons.updateGridItemButton(lessonId, false);
                        });
                    } else {
                        $row.css('opacity', '1');
                        $btn.prop('disabled', false);
                        DeveloperLessons.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $row.css('opacity', '1');
                    $btn.prop('disabled', false);
                    DeveloperLessons.showNotification(dl_public.strings.error, 'error');
                }
            });
        },

        /**
         * Update grid item button state
         * @param {int} lessonId - The lesson ID
         * @param {boolean} inBasket - Whether the item is in the basket
         */
        updateGridItemButton: function(lessonId, inBasket) {
            // Find all buttons for this lesson in grids
            const $gridBtns = $('.grid-item .dl-add-to-basket-btn[data-lesson-id="' + lessonId + '"], ' +
                               '.grid-item .dl-view-basket-btn[data-lesson-id="' + lessonId + '"]');
            
            $gridBtns.each(function() {
                const $btn = $(this);
                
                if (inBasket) {
                    // Change to "In Basket" state
                    $btn.text(dl_public.strings.in_basket || 'In Basket')
                        .removeClass('dl-add-to-basket-btn')
                        .addClass('dl-btn-secondary dl-view-basket-btn')
                        .prop('disabled', false);
                } else {
                    // Change back to "Add to Basket" state
                    $btn.text(dl_public.strings.add_to_basket || 'Add to Basket')
                        .removeClass('dl-btn-secondary dl-view-basket-btn')
                        .addClass('dl-add-to-basket-btn')
                        .prop('disabled', false);
                }
            });
            
            // Also update CTA box buttons
            const $ctaBtns = $('.dl-cta-box .dl-add-to-basket-btn[data-lesson-id="' + lessonId + '"], ' +
                              '.dl-cta-box .dl-view-basket-btn[data-lesson-id="' + lessonId + '"]');
            
            $ctaBtns.each(function() {
                const $btn = $(this);
                
                if (inBasket) {
                    $btn.text(dl_public.strings.view_basket || 'View Basket')
                        .removeClass('dl-add-to-basket-btn')
                        .addClass('dl-btn-secondary dl-view-basket-btn')
                        .prop('disabled', false);
                } else {
                    $btn.text(dl_public.strings.add_to_basket || 'Add to Basket')
                        .removeClass('dl-btn-secondary dl-view-basket-btn')
                        .addClass('dl-add-to-basket-btn')
                        .prop('disabled', false);
                }
            });
        },

        updateCheckoutTotals: function() {
            // Check if we're on checkout page
            if ($('.dl-checkout-items').length === 0) {
                return;
            }

            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_get_basket',
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // If basket is empty, reload page to show empty state
                        if (data.count === 0) {
                            location.reload();
                            return;
                        }

                        // Update subtotal
                        $('.dl-subtotal-row .dl-price-col').text(DeveloperLessons.formatPrice(data.subtotal));
                        
                        // Update discount row
                        const $discountRow = $('.dl-discount-row');
                        if (data.discount && data.discount.amount > 0) {
                            if ($discountRow.length === 0) {
                                // Add discount row if it doesn't exist
                                const discountHtml = '<tr class="dl-discount-row">' +
                                    '<th>' + DeveloperLessons.getDiscountLabel(data.discount.percentage) + '</th>' +
                                    '<td colspan="2" class="dl-price-col dl-discount">-' + DeveloperLessons.formatPrice(data.discount.amount) + '</td>' +
                                    '</tr>';
                                $('.dl-subtotal-row').after(discountHtml);
                            } else {
                                // Update existing discount row
                                $discountRow.find('th').text(DeveloperLessons.getDiscountLabel(data.discount.percentage));
                                $discountRow.find('.dl-price-col').text('-' + DeveloperLessons.formatPrice(data.discount.amount));
                            }
                        } else {
                            // Remove discount row if no discount
                            $discountRow.remove();
                        }
                        
                        // Update total
                        $('.dl-total-row .dl-price-col').text(DeveloperLessons.formatPrice(data.total));
                        
                        // Update item count in header if exists
                        const $itemCount = $('.dl-checkout-item-count');
                        if ($itemCount.length) {
                            $itemCount.text(data.count);
                        }
                    }
                }
            });
        },

        getDiscountLabel: function(percentage) {
            // Use localized string if available, otherwise build it
            if (dl_public.strings.bundle_discount) {
                return dl_public.strings.bundle_discount.replace('%d', percentage);
            }
            return 'Bundle Discount (' + percentage + '%)';
        },

        toggleBasket: function(e) {
            e.preventDefault();
            
            if ($('#dl-basket-sidebar').hasClass('active')) {
                this.closeBasket();
            } else {
                this.openBasket();
            }
        },

        openBasket: function(e) {
            if (e) e.preventDefault();
            $('#dl-basket-sidebar').addClass('active');
            $('#dl-basket-overlay').addClass('active');
            $('body').css('overflow', 'hidden');
        },

        closeBasket: function() {
            $('#dl-basket-sidebar').removeClass('active');
            $('#dl-basket-overlay').removeClass('active');
            $('body').css('overflow', '');
        },

        updateBasketCount: function(count) {
            if (typeof count === 'undefined') {
                $.ajax({
                    url: dl_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dl_get_basket_count',
                        nonce: dl_public.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            DeveloperLessons.setBasketCount(response.data.count);
                        }
                    }
                });
            } else {
                this.setBasketCount(count);
            }
        },

        setBasketCount: function(count) {
            const $toggle = $('#dl-basket-toggle');
            let $count = $toggle.find('.dl-basket-count');

            if (count > 0) {
                if ($count.length === 0) {
                    $toggle.append('<span class="dl-basket-count">' + count + '</span>');
                } else {
                    $count.text(count);
                }
            } else {
                $count.remove();
            }
        },

        refreshBasketSidebar: function() {
            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_get_basket',
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const $content = $('.dl-basket-content');
                        
                        if (data.items.length === 0) {
                            $content.html('<p class="dl-basket-empty">' + (dl_public.strings.basket_empty || 'Your basket is empty.') + '</p>');
                        } else {
                            let html = '<ul class="dl-basket-items">';
                            
                            data.items.forEach(function(item) {
                                html += '<li class="dl-basket-item" data-lesson-id="' + item.lesson_id + '">';
                                html += '<div class="dl-basket-item-info">';
                                html += '<a href="' + item.permalink + '">' + item.lesson_title + '</a>';
                                html += '<span class="dl-basket-item-price">' + DeveloperLessons.formatPrice(item.price) + '</span>';
                                html += '</div>';
                                html += '<button type="button" class="dl-basket-remove" data-lesson-id="' + item.lesson_id + '">&times;</button>';
                                html += '</li>';
                            });
                            
                            html += '</ul>';
                            html += '<div class="dl-basket-total">';
                            html += '<span>' + (dl_public.strings.total || 'Total:') + '</span>';
                            html += '<span class="dl-basket-total-amount">' + DeveloperLessons.formatPrice(data.total) + '</span>';
                            html += '</div>';
                            
                            $content.html(html);
                        }
                    }
                }
            });
        },

        formatPrice: function(amount) {
            const symbol = dl_public.currency_symbol || 'Kč';
            const position = dl_public.currency_position || 'after';
            const formatted = parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ').replace('.', ',');
            
            if (position === 'before') {
                return symbol + ' ' + formatted;
            }
            return formatted + ' ' + symbol;
        },

        toggleInvoiceFields: function(e) {
            const $checkbox = $(e.currentTarget);
            const $fields = $('.dl-invoice-fields');
            
            if ($checkbox.is(':checked')) {
                $fields.slideDown(200);
            } else {
                $fields.slideUp(200);
            }
        },

        toggleSaveInvoice: function(e) {
            // Optional: add visual feedback when user toggles save to profile
        },

        processCheckout: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $('#dl-submit-checkout');
            const $messages = $('#dl-checkout-messages');
            const paymentMethod = $form.find('input[name="payment_method"]:checked').val();
            const agreeTerms = $form.find('#agree_terms').is(':checked');
            const wantInvoice = $form.find('#dl_want_invoice').is(':checked');

            if (!paymentMethod) {
                $messages.html('<div class="dl-error">' + (dl_public.strings.select_payment || 'Please select a payment method.') + '</div>');
                DeveloperLessons.scrollToElement($messages);
                return;
            }

            // Validate invoice fields if requested
            if (wantInvoice) {
                const companyName = $form.find('#dl_invoice_company_name').val().trim();
                const street = $form.find('#dl_invoice_street').val().trim();
                const city = $form.find('#dl_invoice_city').val().trim();
                const zip = $form.find('#dl_invoice_zip').val().trim();
                const ic = $form.find('#dl_invoice_ic').val().trim();

                if (!companyName || !street || !city || !zip || !ic) {
                    $messages.html('<div class="dl-error">' + (dl_public.strings.invoice_required || 'Please fill in all required invoice fields.') + '</div>');
                    DeveloperLessons.scrollToElement($messages);
                    return;
                }
            }

            $btn.prop('disabled', true).text(dl_public.strings.processing);
            $messages.empty();

            // Collect form data
            const formData = {
                action: 'dl_process_checkout',
                payment_method: paymentMethod,
                agree_terms: agreeTerms,
                want_invoice: wantInvoice,
                nonce: dl_public.nonce
            };

            // Add invoice data if requested
            if (wantInvoice) {
                formData.invoice_company_name = $form.find('#dl_invoice_company_name').val();
                formData.invoice_street = $form.find('#dl_invoice_street').val();
                formData.invoice_street_number = $form.find('#dl_invoice_street_number').val();
                formData.invoice_city = $form.find('#dl_invoice_city').val();
                formData.invoice_zip = $form.find('#dl_invoice_zip').val();
                formData.invoice_ic = $form.find('#dl_invoice_ic').val();
                formData.invoice_dic = $form.find('#dl_invoice_dic').val();
                formData.save_invoice_to_profile = $form.find('#dl_save_invoice_to_profile').is(':checked');
            }

            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Handle Stripe payment
                        if (response.data.payment_method === 'stripe' && response.data.process_payment) {
                            DeveloperLessons.processStripePayment(response.data.order_id, $btn, $messages);
                        } 
                        // Handle redirect (Comgate, Bank Transfer)
                        else if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        $messages.html('<div class="dl-error">' + (response.data.message || dl_public.strings.error) + '</div>');
                        $btn.prop('disabled', false).text(dl_public.strings.complete_purchase || 'Complete Purchase');
                        DeveloperLessons.scrollToElement($messages);
                    }
                },
                error: function() {
                    $messages.html('<div class="dl-error">' + dl_public.strings.error + '</div>');
                    $btn.prop('disabled', false).text(dl_public.strings.complete_purchase || 'Complete Purchase');
                    DeveloperLessons.scrollToElement($messages);
                }
            });
        },

        /**
         * Process Stripe payment after order is created
         */
        processStripePayment: function(orderId, $btn, $messages) {
            if (typeof window.DLStripe === 'undefined') {
                $messages.html('<div class="dl-error">Stripe not initialized in public.js</div>');
                $btn.prop('disabled', false).text(dl_public.strings.complete_purchase || 'Complete Purchase');
                return;
            }

            $btn.text(dl_public.strings.processing_payment || 'Processing payment...');

            window.DLStripe.processPayment(orderId, function(result) {
                if (result.success) {
                    // Payment succeeded, redirect to success page
                    window.location.href = dl_public.checkout_url.replace('/checkout/', '/payment-success/') + '?order=' + orderId;
                } else {
                    $messages.html('<div class="dl-error">' + (result.error || dl_public.strings.error) + '</div>');
                    $btn.prop('disabled', false).text(dl_public.strings.complete_purchase || 'Complete Purchase');
                    DeveloperLessons.scrollToElement($messages);
                }
            });
        },


        scrollToElement: function($element) {
            if ($element.length) {
                $('html, body').animate({
                    scrollTop: $element.offset().top - 100
                }, 300);
            }
        },

        showNotification: function(message, type) {
            // Remove existing notification
            $('.dl-notification').remove();

            const $notification = $('<div class="dl-notification dl-notification-' + type + '">' + message + '</div>');
            
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 25px',
                background: type === 'success' ? '#46b450' : '#dc3232',
                color: '#fff',
                borderRadius: '4px',
                zIndex: 10000,
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                maxWidth: '400px'
            });

            $('body').append($notification);

            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        DeveloperLessons.init();
    });

})(jQuery);
