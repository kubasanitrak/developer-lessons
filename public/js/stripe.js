/**
 * Stripe Payment Handler
 */

(function($) {
    'use strict';

    const DLStripe = {
        stripe: null,
        elements: null,
        cardElement: null,
        initialized: false,

        init: function() {
            if (typeof Stripe === 'undefined') {
                console.log('DL Stripe: Stripe.js not loaded');
                return;
            }
            
            if (typeof dl_stripe === 'undefined') {
                console.log('DL Stripe: dl_stripe config not found');
                return;
            }

            if (!dl_stripe.publishable_key) {
                console.log('DL Stripe: No publishable key');
                return;
            }

            this.stripe = Stripe(dl_stripe.publishable_key);
            this.bindEvents();
            
            // Initialize card element if Stripe payment method exists and is selected
            this.initOnLoad();
        },

        initOnLoad: function() {
            const self = this;
            
            // Wait for DOM to be ready and container to exist
            $(document).ready(function() {
                // Small delay to ensure all elements are rendered
                setTimeout(function() {
                    const $stripeMethod = $('input[name="payment_method"][value="stripe"]');
                    
                    if ($stripeMethod.length && $stripeMethod.is(':checked')) {
                        self.initCardElement();
                    } else if ($stripeMethod.length) {
                        // Pre-initialize even if not selected, just hide the container
                        self.initCardElement();
                        if (!$stripeMethod.is(':checked')) {
                            $('.dl-stripe-card-element').hide();
                        }
                    }
                }, 100);
            });
        },

        bindEvents: function() {
            const self = this;
            
            // Handle Stripe payment method selection
            $(document).on('change', 'input[name="payment_method"]', function(e) {
                self.handlePaymentMethodChange(e);
            });
        },

        handlePaymentMethodChange: function(e) {
            const method = $(e.currentTarget).val();
            
            if (method === 'stripe') {
                if (!this.cardElement) {
                    this.initCardElement();
                }
                $('.dl-stripe-card-element').slideDown(200);
            } else {
                $('.dl-stripe-card-element').slideUp(200);
            }
        },

        initCardElement: function() {
            const self = this;
            
            if (this.initialized) {
                console.log('DL Stripe: Already initialized');
                return true;
            }

            const $container = $('#dl-stripe-card-element');
            
            if ($container.length === 0) {
                console.log('DL Stripe: Card element container not found');
                return false;
            }

            if (!this.stripe) {
                console.log('DL Stripe: Stripe not loaded');
                return false;
            }

            try {
                // Create Elements instance
                this.elements = this.stripe.elements({
                    fonts: [
                        {
                            cssSrc: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap',
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

                this.cardElement.on('ready', function() {
                    console.log('DL Stripe: Card element ready');
                });

                this.initialized = true;
                console.log('DL Stripe: Card element initialized successfully');
                return true;

            } catch (error) {
                console.log('DL Stripe: Error initializing card element', error);
                return false;
            }
        },

        /**
         * Process Stripe payment
         */
        processPayment: function(orderId, callback) {
            const self = this;

            // Try to initialize if not done yet
            if (!this.cardElement) {
                if (!this.initCardElement()) {
                    callback({ error: 'Stripe card element not initialized. Please refresh the page.' });
                    return;
                }
            }

            if (!this.stripe) {
                callback({ error: 'Stripe not loaded' });
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
                    if (response.success && response.data.client_secret) {
                        self.confirmPayment(response.data.client_secret, callback);
                    } else {
                        callback({ error: response.data.message || 'Failed to create payment intent' });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('DL Stripe: AJAX error', error);
                    callback({ error: dl_stripe.strings.error || 'Payment request failed' });
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
                    console.log('DL Stripe: Payment error', result.error);
                    callback({ error: result.error.message });
                } else if (result.paymentIntent.status === 'succeeded') {
                    console.log('DL Stripe: Payment succeeded');
                    callback({ success: true });
                } else {
                    console.log('DL Stripe: Unexpected status', result.paymentIntent.status);
                    callback({ error: 'Payment not completed. Status: ' + result.paymentIntent.status });
                }
            }).catch(function(error) {
                console.log('DL Stripe: confirmCardPayment error', error);
                callback({ error: error.message || 'Payment confirmation failed' });
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

    // Expose globally
    window.DLStripe = DLStripe;

    // Initialize when document is ready
    $(document).ready(function() {
        DLStripe.init();
    });

})(jQuery);
