<?php
/**
 * REST API Routes for External Integrations (Headless Lead Capture).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_API_Routes {

    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        // Endpoint: /wp-json/aperture/v1/lead
        register_rest_route( 'aperture/v1', '/lead', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'create_lead' ),
            'permission_callback' => '__return_true', // Open endpoint for external tools (Zapier, etc.)
        ));
    }

    public function create_lead( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params['name'] ) || empty( $params['email'] ) ) {
            return new WP_Error( 'missing_params', 'Name and Email are required', array( 'status' => 400 ) );
        }

        // 1. Create Customer (Private)
        $customer_id = wp_insert_post(array(
            'post_type'   => 'ap_customer',
            'post_title'  => sanitize_text_field( $params['name'] ),
            'post_status' => 'private',
            'meta_input'  => array(
                '_ap_client_email' => sanitize_email( $params['email'] ),
                '_ap_client_phone' => sanitize_text_field( isset($params['phone']) ? $params['phone'] : '' ),
            )
        ));

        // 2. Create Project (Lead Stage)
        $source = isset($params['source']) ? sanitize_text_field($params['source']) : "Web Lead";
        $project_title = "Lead from " . $source;
        
        $project_id = wp_insert_post(array(
            'post_type'   => 'ap_project',
            'post_title'  => $project_title . ': ' . sanitize_text_field( $params['name'] ),
            'post_status' => 'publish',
            'meta_input'  => array(
                '_ap_project_customer' => $customer_id,
                '_ap_project_stage'    => 'lead',
                '_ap_client_notes'     => sanitize_textarea_field( isset($params['notes']) ? $params['notes'] : '' ),
            )
        ));

        // 3. Trigger New Lead Automation (Sends Welcome Email via Automation Class)
        do_action( 'ap_new_lead_captured', $project_id );

        return rest_ensure_response( array(
            'success' => true,
            'project_id' => $project_id,
            'message' => 'Lead created successfully'
        ));
    }
}
