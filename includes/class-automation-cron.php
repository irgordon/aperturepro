<?php
/**
 * Handles Scheduled Automations (Follow-ups, Stale States).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Automation_Cron {

    public function init() {
        // Register the cron interval if not exists (daily)
        add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
        
        if ( ! wp_next_scheduled( 'ap_daily_automation_check' ) ) {
            wp_schedule_event( time(), 'daily', 'ap_daily_automation_check' );
        }

        add_action( 'ap_daily_automation_check', array( $this, 'run_stale_checks' ) );
    }

    public function add_cron_interval( $schedules ) {
        $schedules['daily'] = array(
            'interval' => 86400,
            'display'  => esc_html__( 'Once Daily' ),
        );
        return $schedules;
    }

    /**
     * The Logic: Find "stuck" projects and nudge them.
     */
    public function run_stale_checks() {
        // Example Rule: "Proposal Sent" > 3 days ago w/o movement
        $args = array(
            'post_type'  => 'ap_project',
            'meta_query' => array(
                array(
                    'key'   => '_ap_project_stage',
                    'value' => 'proposal',
                ),
                // Ensure we haven't already nudged them
                array(
                    'key'     => '_ap_nudge_sent_proposal',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'date_query' => array(
                array(
                    'column' => 'post_modified_gmt',
                    'before' => '3 days ago'
                )
            ),
            'posts_per_page' => -1,
        );

        $stale_projects = get_posts( $args );

        foreach ( $stale_projects as $project ) {
            $this->send_nudge_email( $project->ID );
            
            // Mark as nudged so we don't spam them daily
            update_post_meta( $project->ID, '_ap_nudge_sent_proposal', current_time('mysql') );
        }
    }

    private function send_nudge_email( $project_id ) {
        // Fetch Client Email
        $customer_id = get_post_meta( $project_id, '_ap_project_customer', true );
        $email = get_post_meta( $customer_id, '_ap_client_email', true );
        
        if ( is_email( $email ) ) {
            $subject = "Just checking in on your proposal";
            $message = "Hi there, just wanted to see if you had any questions about the proposal I sent over? Link: ...";
            
            wp_mail( $email, $subject, $message );
            
            // Log this action in system notes (optional)
            // Aperture_Logger::log( $project_id, 'Sent automated follow-up' );
        }
    }
}
