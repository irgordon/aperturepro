<?php
/**
 * Handles Image Watermarking and Gallery Protection.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Gallery_Proof {

    public function init() {
        // 1. Add rewrite rule to intercept image requests
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_image_request' ) );
    }

    /**
     * Redirects requests like /proofs/123/image.jpg to our script
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^proofs/([0-9]+)/(.+)\.jpg$',
            'index.php?ap_gallery_id=$matches[1]&ap_image_name=$matches[2]',
            'top'
        );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'ap_gallery_id';
        $vars[] = 'ap_image_name';
        return $vars;
    }

    /**
     * Checks permissions and serves the watermarked image
     */
    public function handle_image_request() {
        $gallery_id = get_query_var( 'ap_gallery_id' );
        $image_name = get_query_var( 'ap_image_name' );

        if ( ! $gallery_id || ! $image_name ) {
            return;
        }

        // Security Check: Is user allowed to view this gallery?
        // In reality, check if current_user_can() or if a valid token is in URL
        if ( ! is_user_logged_in() ) {
            status_header( 403 );
            die( 'Access Denied: Please log in to view proofs.' );
        }

        // Locate the source file (Original High-Res)
        // Store these outside the public /uploads/ folder for safety
        $upload_dir = wp_upload_dir();
        $source_path = $upload_dir['basedir'] . '/aperture-protected/' . $gallery_id . '/' . $image_name . '.jpg';

        if ( ! file_exists( $source_path ) ) {
            status_header( 404 );
            die( 'Image not found.' );
        }

        // Check if we serve Original or Watermarked
        $is_paid = get_post_meta( $gallery_id, '_ap_gallery_paid', true );
        
        if ( $is_paid ) {
            // Serve original high-res
            $this->serve_image( $source_path );
        } else {
            // Generate/Serve Watermarked Version
            $this->serve_watermarked_image( $source_path );
        }
        exit;
    }

    private function serve_watermarked_image( $path ) {
        // Load image, stamp watermark using GD Library, output header/content
        $image = imagecreatefromjpeg( $path );
        $color = imagecolorallocatealpha( $image, 255, 255, 255, 60 ); // White, semi-transparent
        $font_size = 50;
        
        // Add Text Watermark
        imagestring( $image, 5, 50, 50, "PROOF - DO NOT PRINT", $color );

        header( 'Content-Type: image/jpeg' );
        imagejpeg( $image );
        imagedestroy( $image );
    }

    private function serve_image( $path ) {
        header( 'Content-Type: image/jpeg' );
        readfile( $path );
    }
}
