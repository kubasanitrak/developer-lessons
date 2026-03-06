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
            
            // Remove from basket
            $(document).on('click', '.dl-basket-remove, .dl-remove-item', this.removeFromBasket.bind(this));
            
            // Basket toggle
            $(document).on('click', '#dl-basket-toggle', this.toggleBasket.bind(this));
            $(document).on('click', '.dl-basket-close, #dl-basket-overlay', this.closeBasket.bind(this));
            
            // View basket button
            $(document).on('click', '.dl-view-basket-btn', this.openBasket.bind(this));
            
            // Checkout form
            $(document).on('submit', '#dl-checkout-form', this.processCheckout.bind(this));
        },

        initBasket: function() {
            this.updateBasketCount();
        },

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
                        
                        // Change button to view basket
                        $btn.text(dl_public.strings.view_basket)
                            .removeClass('dl-add-to-basket-btn')
                            .addClass('dl-view-basket-btn')
                            .prop('disabled', false);
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
            const $item = $btn.closest('.dl-basket-item, tr');

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
                            
                            // Reload checkout page if empty
                            if ($('.dl-checkout-items tbody tr').length === 0) {
                                location.reload();
                            }
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
            // Get from localized settings or use default
            const symbol = dl_public.currency_symbol || 'Kč';
            const position = dl_public.currency_position || 'after';
            const formatted = parseFloat(amount).toFixed(2).replace('.', ',');
            
            if (position === 'before') {
                return symbol + ' ' + formatted;
            }
            return formatted + ' ' + symbol;
        },

        processCheckout: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $('#dl-submit-checkout');
            const $messages = $('#dl-checkout-messages');
            const paymentMethod = $form.find('input[name="payment_method"]:checked').val();
            const agreeTerms = $form.find('#agree_terms').is(':checked');

            if (!paymentMethod) {
                $messages.html('<div class="dl-error">' + (dl_public.strings.select_payment || 'Please select a payment method.') + '</div>');
                return;
            }

            $btn.prop('disabled', true).text(dl_public.strings.processing);
            $messages.empty();

            $.ajax({
                url: dl_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_process_checkout',
                    payment_method: paymentMethod,
                    agree_terms: agreeTerms,
                    nonce: dl_public.nonce
                },
                success: function(response) {
                    if (response.success && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        $messages.html('<div class="dl-error">' + (response.data.message || dl_public.strings.error) + '</div>');
                        $btn.prop('disabled', false).text(dl_public.strings.complete_purchase || 'Complete Purchase');
                    }
                },
                error: function() {
                    $messages.html('<div class="dl-error">' + dl_public.strings.error + '</div>');
                    $btn.prop('disabled', false).text(dl_public.strings.complete_purchase || 'Complete Purchase');
                }
            });
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
