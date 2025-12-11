<?php
/**
 * Manages CSS and JS assets and injects Dynamic Branding.
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
        // 1. Only load on Aperture pages or CPTs to avoid conflicting with other plugins
        $screen = get_current_screen();
        $is_aperture_page = strpos( $hook, 'aperture' ) !== false;
        $is_aperture_cpt  = in_array( $screen->post_type, array('ap_project', 'ap_invoice', 'ap_contract', 'ap_customer') );

        if ( ! $is_aperture_page && ! $is_aperture_cpt ) {
            return;
        }

        // 2. Enqueue the base CSS file
        wp_enqueue_style( 'ap-admin-css', APERTURE_URL . 'assets/admin-style.css', array(), '1.0.0' );

        // 3. DYNAMIC BRANDING: Inject Database Settings into CSS Variables
        $brand_color = get_option( 'ap_primary_color', '#007698' ); // Default Teal
        
        // Simple opacity fallback for "light" variant (appending 'aa' to hex)
        $brand_light = $brand_color . 'aa'; 

        $custom_css = "
            :root {
                --ap-teal-dark: {$brand_color} !important;
                --ap-teal-light: {$brand_light} !important;
            }
            /* Override the first metric card border to match brand */
            .ap-metric-card:nth-child(1) { border-left-color: {$brand_color} !important; }
            
            /* Override buttons in the dashboard */
            ul.ap-actions-list .button { color: {$brand_color}; border-color: {$brand_color}; }
            ul.ap-actions-list .button:hover { background: {$brand_color}; color: #fff; }
        ";
        
        wp_add_inline_style( 'ap-admin-css', $custom_css );
    }

    public function enqueue_frontend_assets() {
        // Only load on singular Invoice/Contract/Gallery pages
        if ( is_singular( 'ap_invoice' ) || is_singular( 'ap_contract' ) || is_singular( 'ap_gallery' ) ) {
            
            wp_enqueue_style( 'ap-frontend-css', APERTURE_URL . 'assets/frontend-style.css', array(), '1.0.0' );
            
            // Inject Branding on Frontend
            $brand_color = get_option( 'ap_primary_color', '#007698' );
            
            $custom_css = "
                :root { --ap-brand: {$brand_color}; }
                
                /* Apply brand color to primary buttons and accents */
                .button-primary, 
                .ap-payment-zone button,
                .ap-gallery-footer button { 
                    background-color: {$brand_color} !important; 
                    border-color: {$brand_color} !important; 
                    color: #fff !important;
                }
                
                .ap-inv-header { border-bottom-color: {$brand_color} !important; }
            ";
            
            wp_add_inline_style( 'ap-frontend-css', $custom_css );
        }
    }
}
