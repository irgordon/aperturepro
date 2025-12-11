<?php
/**
 * Handles payment processing via Stripe.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Payment_Gateway {

    private $stripe_secret_key;
    private $stripe_publishable_key;

    public function init() {
        // In a real app, get these from your Settings page: get_option('ap_stripe_secret')
        $this->stripe_secret_key = 'sk_test_...'; 
        $this->stripe_publishable_key = 'pk_test_...';

        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );
        add_shortcode( 'ap_checkout_button', array( $this, 'render_checkout_button' ) );
    }

    /**
     * Generates a Stripe Payment Intent for a specific Invoice ID
     */
    public function create_payment_intent( $invoice_id ) {
        if ( ! class_exists( '\Stripe\Stripe' ) ) {
            return new WP_Error( 'stripe_missing', 'Stripe SDK not loaded' );
        }

        \Stripe\Stripe::setApiKey( $this->stripe_secret_key );

        $amount = get_post_meta( $invoice_id, '_ap_invoice_total', true ); // e.g., 500.00
        $amount_cents = intval( floatval( $amount ) * 100 );

        try {
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount_cents,
                'currency' => 'usd',
                'metadata' => ['invoice_id' => $invoice_id],
            ]);
            
            // Store the intent ID to track it later
            update_post_meta( $invoice_id, '_ap_stripe_intent_id', $intent->id );
            
            return $intent;
        } catch ( Exception $e ) {
            return new WP_Error( 'stripe_error', $e->getMessage() );
        }
    }

    /**
     * Webhook listener: Stripe calls this when payment is successful
     * URL: https://yoursite.com/wp-json/aperture/v1/stripe-webhook
     */
    public function register_webhook_route() {
        register_rest_route( 'aperture/v1', '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true', // Stripe is an external service
        ));
    }

    public function handle_webhook( $request ) {
        $payload = $request->get_body();
        $sig_header = $request->get_header( 'stripe-signature' );
        $endpoint_secret = 'whsec_...'; // Get from settings

        try {
            // Verify signature
            $event = \Stripe\Webhook::constructEvent( $payload, $sig_header, $endpoint_secret );
        } catch( \UnexpectedValueException $e ) {
            return new WP_Error( 'invalid_payload', 'Invalid Payload', array( 'status' => 400 ) );
        }

        // Handle the event
        if ( $event->type == 'payment_intent.succeeded' ) {
            $paymentIntent = $event->data->object;
            $invoice_id = $paymentIntent->metadata->invoice_id;
            
            // Mark Invoice as Paid
            update_post_meta( $invoice_id, '_ap_invoice_status', 'paid' );
            
            // Trigger Automation (Email, etc.)
            do_action( 'ap_invoice_paid', $invoice_id );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }
}
