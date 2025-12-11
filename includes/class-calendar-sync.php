<?php
/**
 * Integrates with Google Calendar API to sync events.
 */

class Aperture_Calendar_Sync {
    
    // You must set these in your plugin settings
    private $client_id = 'YOUR_GOOGLE_CLIENT_ID';
    private $client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
    private $redirect_uri;

    public function init() {
        $this->redirect_uri = admin_url( 'admin.php?page=aperture-settings' );
        add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
        
        // Schedule hourly sync
        if ( ! wp_next_scheduled( 'ap_hourly_calendar_sync' ) ) {
            wp_schedule_event( time(), 'hourly', 'ap_hourly_calendar_sync' );
        }
        add_action( 'ap_hourly_calendar_sync', array( $this, 'sync_events' ) );
    }

    /**
     * 1. Get Authorization URL
     */
    public function get_auth_url() {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'https://www.googleapis.com/auth/calendar.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent'
        );
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query( $params );
    }

    /**
     * 2. Handle Callback & Save Token
     */
    public function handle_oauth_callback() {
        if ( isset( $_GET['code'] ) && isset( $_GET['page'] ) && $_GET['page'] === 'aperture-settings' ) {
            $code = sanitize_text_field( $_GET['code'] );
            $tokens = $this->exchange_code_for_token( $code );
            
            if ( ! empty( $tokens['access_token'] ) ) {
                update_option( 'ap_google_access_token', $tokens['access_token'] );
                update_option( 'ap_google_refresh_token', $tokens['refresh_token'] );
            }
        }
    }

    private function exchange_code_for_token( $code ) {
        // Make POST request to https://oauth2.googleapis.com/token
        // Returns JSON with access_token
        return array(); // Placeholder for actual curl/wp_remote_post logic
    }

    /**
     * 3. Sync Logic: Fetch Calendar -> Create Leads
     */
    public function sync_events() {
        $token = get_option( 'ap_google_access_token' );
        if ( ! $token ) return;

        // Fetch events from Google Calendar API
        // Loop through events
        // If event title contains "Consultation", create a Lead in WP
        
        $events = array(); // Placeholder for API response
        
        foreach ( $events as $event ) {
            $exists = $this->project_exists_by_google_id( $event['id'] );
            if ( ! $exists ) {
                $project_id = wp_insert_post( array(
                    'post_type' => 'ap_project',
                    'post_title' => $event['summary'], // e.g., "Wedding Consult: Smith"
                    'post_status' => 'publish',
                    'meta_input' => array(
                        '_ap_project_stage' => 'lead',
                        '_ap_google_event_id' => $event['id']
                    )
                ));
            }
        }
    }
    
    private function project_exists_by_google_id( $google_id ) {
        // Meta query to check existence
        return false; 
    }
}
