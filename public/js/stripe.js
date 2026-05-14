/**
 * Stripe Payment Handler
 */

(function($) {
    'use strict';

    const DLStripe = {
        stripe: null,
        elements: null,
        cardElement: null,

        init: function() {
            if (typeof Stripe === 'undefined' || typeof dl_stripe === 'undefined') {
                return;
            }

            this.stripe = Stripe(dl_stripe.publishable_key);
            this.bindEvents();
        },

        bindEvents: function() {
            // Handle Stripe payment method selection
            $(document).on('change', 'input[name="payment_method"]', this.handlePaymentMethodChange.bind(this));
            
            // Initialize if Stripe is already selected
            if ($('input[name="payment_method"][value="stripe"]:checked').length) {
                this.initCardElement();
            }
        },

        handlePaymentMethodChange: function(e) {
            const method = $(e.currentTarget).val();
            
            if (method === 'stripe') {
                this.initCardElement();
                $('.dl-stripe-card-element').slideDown(200);
            } else {
                $('.dl-stripe-card-element').slideUp(200);
            }
        },

        initCardElement: function() {
            if (this.cardElement) {
                return; // Already initialized
            }

            const $container = $('#dl-stripe-card-element');
            if ($container.length === 0) {
                return;
            }

            // Create Elements instance
            this.elements = this.stripe.elements({
                fonts: [
                    {
                        cssSrc: '[fonts.googleapis.com](https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap)',
                    }
                ]
            });

            // Style for the card element
            const style = {
                base: {
                    color: '#333',
                    fontFamily: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#dc3232',
                    iconColor: '#dc3232'
                }
            };

            // Create card element
            this.cardElement = this.elements.create('card', { style: style });
            this.cardElement.mount('#dl-stripe-card-element');

            // Handle card errors
            this.cardElement.on('change', function(event) {
                const $error = $('#dl-stripe-card-errors');
                if (event.error) {
                    $error.text(event.error.message).show();
                } else {
                    $error.text('').hide();
                }
            });
        },

        /**
         * Process Stripe payment (called from main checkout form)
         */
        processPayment: function(orderId, callback) {
            const self = this;

            if (!this.cardElement) {
                callback({ error: 'Stripe not initialized <= !this.cardElement' });
                return;
            }

            if (!this.stripe) {
                callback({ error: 'Stripe not initialized caused by !this.stripe' });
                return;
            }

            // Create Payment Intent
            $.ajax({
                url: dl_stripe.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_stripe_payment_intent',
                    order_id: orderId,
                    nonce: dl_stripe.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.confirmPayment(response.data.client_secret, callback);
                    } else {
                        callback({ error: response.data.message });
                    }
                },
                error: function() {
                    callback({ error: dl_stripe.strings.error });
                }
            });
        },

        /**
         * Confirm payment with Stripe
         */
        confirmPayment: function(clientSecret, callback) {
            const self = this;

            this.stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: this.cardElement,
                }
            }).then(function(result) {
                if (result.error) {
                    callback({ error: result.error.message });
                } else if (result.paymentIntent.status === 'succeeded') {
                    callback({ success: true });
                } else {
                    callback({ error: 'Payment not completed' });
                }
            });
        },

        /**
         * Redirect to Stripe Checkout (alternative method)
         */
        redirectToCheckout: function(orderId, callback) {
            const self = this;

            $.ajax({
                url: dl_stripe.ajax_url,
                type: 'POST',
                data: {
                    action: 'dl_create_stripe_session',
                    order_id: orderId,
                    nonce: dl_stripe.nonce
                },
                success: function(response) {
                    if (response.success && response.data.session_id) {
                        self.stripe.redirectToCheckout({
                            sessionId: response.data.session_id
                        }).then(function(result) {
                            if (result.error) {
                                callback({ error: result.error.message });
                            }
                        });
                    } else {
                        callback({ error: response.data.message || dl_stripe.strings.error });
                    }
                },
                error: function() {
                    callback({ error: dl_stripe.strings.error });
                }
            });
        }
    };

    // Expose globally for checkout form
    window.DLStripe = DLStripe;

    $(document).ready(function() {
        DLStripe.init();
    });

})(jQuery);
