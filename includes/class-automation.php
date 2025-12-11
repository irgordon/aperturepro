<?php
class Aperture_Automation {

    public function init() {
        add_action( 'ap_project_stage_change', array( $this, 'handle_stage_change' ), 10, 3 );
    }

    public function handle_stage_change( $project_id, $new_stage, $old_stage ) {
        $client_email = $this->get_client_email_by_project( $project_id );

        switch ( $new_stage ) {
            case 'proposal':
                // Logic: Send Proposal Email
                $this->send_notification( $client_email, "Your Proposal is Ready", "Click here to view..." );
                break;
            
            case 'delivered':
                // Logic: Unlock Gallery Downloads & Ask for Review
                $this->unlock_gallery_downloads( $project_id );
                $this->send_notification( $client_email, "Your Photos are Ready!", "View your gallery..." );
                break;
        }
    }

    private function send_notification( $to, $subject, $message ) {
        wp_mail( $to, "[AperturePro] " . $subject, $message );
    }

    private function get_client_email_by_project( $project_id ) {
        // Implementation to fetch linked customer email
        return 'client@example.com'; // Placeholder
    }
    
    private function unlock_gallery_downloads( $project_id ) {
        // Logic to remove watermarks/enable zip download
        update_post_meta( $project_id, '_ap_gallery_download_enabled', true );
    }
}
