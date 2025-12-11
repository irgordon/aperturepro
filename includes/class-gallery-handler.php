<?php
/**
 * Handles Gallery Selections and Client Interactions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Gallery_Handler {

    public function init() {
        add_action( 'admin_post_nopriv_ap_submit_gallery_selection', array( $this, 'handle_selection' ) );
        add_action( 'admin_post_ap_submit_gallery_selection', array( $this, 'handle_selection' ) );
    }

    public function handle_selection() {
        if ( ! isset( $_POST['ap_gallery_nonce'] ) || ! wp_verify_nonce( $_POST['ap_gallery_nonce'], 'ap_gallery_submit' ) ) {
            wp_die( 'Security check failed' );
        }

        $gallery_id = intval( $_POST['gallery_id'] );
        $selected_ids = isset( $_POST['selected_images'] ) ? array_map( 'intval', $_POST['selected_images'] ) : array();

        if ( empty( $selected_ids ) ) {
            wp_die( 'Please select at least one image.' );
        }

        // 1. Save Selection to Gallery Meta
        update_post_meta( $gallery_id, '_ap_client_selection', $selected_ids );
        update_post_meta( $gallery_id, '_ap_selection_date', current_time( 'mysql' ) );

        // 2. Find Linked Project
        // Assuming we linked them via meta when creating the gallery
        $project_id = get_post_meta( $gallery_id, '_ap_gallery_project_id', true );

        if ( $project_id ) {
            // 3. Create "Retouching" Task for Photographer
            $task_id = wp_insert_post(array(
                'post_type' => 'ap_task',
                'post_title' => 'Retouch ' . count($selected_ids) . ' Images (Client Selected)',
                'post_status' => 'publish',
                'meta_input' => array(
                    '_ap_task_project_id' => $project_id,
                    '_ap_task_priority' => 'high',
                    '_ap_task_due_date' => date('Y-m-d', strtotime('+7 days')) // Default 1 week turnaround
                )
            ));

            // 4. Update Project Stage
            update_post_meta( $project_id, '_ap_project_stage', 'editing' );
            
            // 5. Notify Admin (Optional hook)
            do_action( 'ap_gallery_selection_made', $project_id, $task_id );
        }

        // Redirect to a "Thank You" page or back to gallery with success msg
        $redirect_url = add_query_arg( 'selection_saved', '1', get_permalink( $gallery_id ) );
        wp_redirect( $redirect_url );
        exit;
    }
}
