<?php
/**
 * Manages CSS and JS assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Asset_Manager {

    public function init() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
    }

    public function enqueue_admin_assets( $hook ) {
        // Only load on Aperture pages
        if ( strpos( $hook, 'aperture' ) === false && get_post_type() !== 'ap_project' && get_post_type() !== 'ap_invoice' ) {
            return;
        }

        wp_enqueue_style( 'ap-admin-css', APERTURE_URL . 'assets/admin-style.css', array(), '1.0.0' );
        // wp_enqueue_script( 'ap-admin-js', APERTURE_URL . 'assets/admin-script.js', array('jquery'), '1.0.0', true );
    }

    public function enqueue_frontend_assets() {
        if ( is_singular( 'ap_invoice' ) || is_singular( 'ap_contract' ) || is_singular( 'ap_gallery' ) ) {
            wp_enqueue_style( 'ap-frontend-css', APERTURE_URL . 'assets/frontend-style.css', array(), '1.0.0' );
        }
    }
}
