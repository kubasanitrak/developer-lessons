(function($) {
    'use strict';

    const DLStripe = {
        stripe: null,
        elements: null,
        cardElement: null,
        initialized: false,

        init: function() {
            if (typeof Stripe === 'undefined') {
                console.log('DL Stripe: Stripe.js not available');
                return;
            }
            
            if (typeof dl_stripe === 'undefined' || !dl_stripe.publishable_key) {
                console.log('DL Stripe: Config not found');
                return;
            }

            this.stripe = Stripe(dl_stripe.publishable_key);
            console.log('DL Stripe: Initialized');
            
            this.bindEvents();
            this.initOnLoad();
        },

        initOnLoad: function() {
            const self = this;
            const $stripeMethod = $('input[name="payment_method"][value="stripe"]');
            
            if ($stripeMethod.length) {
                self.initCardElement();
                if (!$stripeMethod.is(':checked')) {
                    $('.dl-stripe-card-element').hide();
                }
            }
        },

        bindEvents: function() {
            const self = this;
            $(document).on('change', 'input[name="payment_method"]', function(e) {
                const method = $(this).val();
                if (method === 'stripe') {
                    if (!self.cardElement) self.initCardElement();
                    $('.dl-stripe-card-element').slideDown(200);
                } else {
                    $('.dl-stripe-card-element').slideUp(200);
                }
            });
        },

        initCardElement: function() {
            if (this.initialized || !this.stripe) return false;
            
            const $container = $('#dl-stripe-card-element');
            if (!$container.length) return false;

            this.elements = this.stripe.elements();
            this.cardElement = this.elements.create('card', {
                style: {
                    base: {
                        color: '#333',
                        fontSize: '16px',
                        '::placeholder': { color: '#aab7c4' }
                    },
                    invalid: { color: '#dc3232' }
                }
            });
            this.cardElement.mount('#dl-stripe-card-element');
            
            this.cardElement.on('change', function(event) {
                const $error = $('#dl-stripe-card-errors');
                event.error ? $error.text(event.error.message).show() : $error.hide();
            });

            this.initialized = true;
            console.log('DL Stripe: Card element mounted');
            return true;
        },

        processPayment: function(orderId, callback) {
            const self = this;
            
            if (!this.stripe || !this.cardElement) {
                callback({ error: 'Stripe not ready. Please refresh and try again.' });
                return;
            }

            $.post(dl_stripe.ajax_url, {
                action: 'dl_stripe_payment_intent',
                order_id: orderId,
                nonce: dl_stripe.nonce
            }, function(response) {
                if (response.success && response.data.client_secret) {
                    self.stripe.confirmCardPayment(response.data.client_secret, {
                        payment_method: { card: self.cardElement }
                    }).then(function(result) {
                        if (result.error) {
                            callback({ error: result.error.message });
                        } else if (result.paymentIntent.status === 'succeeded') {
                            callback({ success: true });
                        } else {
                            callback({ error: 'Payment incomplete' });
                        }
                    });
                } else {
                    callback({ error: response.data?.message || 'Payment failed' });
                }
            }).fail(function() {
                callback({ error: dl_stripe.strings.error });
            });
        }
    };

    window.DLStripe = DLStripe;
    $(function() { DLStripe.init(); });

})(jQuery);
