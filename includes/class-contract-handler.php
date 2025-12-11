<?php
/**
 * Handles incoming contract signatures.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Contract_Handler {

    public function init() {
        add_action( 'admin_post_nopriv_ap_sign_contract', array( $this, 'process_signature' ) );
        add_action( 'admin_post_ap_sign_contract', array( $this, 'process_signature' ) );
    }

    public function process_signature() {
        // Validation would go here (Nonce check, etc)
        $contract_id = intval( $_POST['contract_id'] );
        $sig_data    = $_POST['signature_data']; // Base64 PNG string from JS Canvas

        if ( ! $contract_id || empty( $sig_data ) ) {
            wp_die( 'Invalid signature data.' );
        }

        // 1. Save Base64 Image to Server
        $upload_dir = wp_upload_dir();
        $sig_folder = $upload_dir['basedir'] . '/ap-signatures/';
        if ( ! file_exists( $sig_folder ) ) mkdir( $sig_folder, 0755, true );
        
        $filename = 'sig_' . $contract_id . '_' . time() . '.png';
        $file_path = $sig_folder . $filename;
        $file_url  = $upload_dir['baseurl'] . '/ap-signatures/' . $filename;

        // Strip "data:image/png;base64," header
        $sig_data = str_replace('data:image/png;base64,', '', $sig_data);
        $sig_data = str_replace(' ', '+', $sig_data);
        $data = base64_decode($sig_data);
        
        file_put_contents( $file_path, $data );

        // 2. Lock Contract
        update_post_meta( $contract_id, '_ap_contract_signed', true );
        update_post_meta( $contract_id, '_ap_signed_date', current_time( 'mysql' ) );
        update_post_meta( $contract_id, '_ap_signature_image', $file_url );
        update_post_meta( $contract_id, '_ap_signer_ip', $_SERVER['REMOTE_ADDR'] );

        // 3. Notify Admin & Client
        do_action( 'ap_contract_signed', $contract_id );

        // Redirect back to contract to show "Success" view
        wp_redirect( get_permalink( $contract_id ) );
        exit;
    }
}
