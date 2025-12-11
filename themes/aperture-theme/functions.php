<?php
// Enqueue styles for the signing and gallery pages
function aperture_theme_scripts() {
    wp_enqueue_style( 'aperture-style', get_stylesheet_uri() );
    
    if ( is_singular('ap_contract') ) {
        // Enqueue Signature Pad JS library
        wp_enqueue_script( 'signature-pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', array(), null, true );
        wp_enqueue_script( 'ap-contract-js', get_template_directory_uri() . '/js/contract.js', array('signature-pad'), '1.0', true );
    }
}
add_action( 'wp_enqueue_scripts', 'aperture_theme_scripts' );
