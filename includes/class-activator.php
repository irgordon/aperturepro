<?php
/**
 * Fired during plugin activation.
 */
class Aperture_Activator {

    public static function activate() {
        // 1. Sanity Check: Composer Dependencies
        if ( ! file_exists( APERTURE_PATH . 'vendor/autoload.php' ) ) {
            // We can't stop activation easily, but we can log error or set a transient to show a notice
            set_transient( 'ap_composer_missing', true, 30 );
        }

        // 2. Create Required Directories
        $upload_dir = wp_upload_dir();
        $dirs = array(
            $upload_dir['basedir'] . '/ap-signatures',
            $upload_dir['basedir'] . '/ap-proofs-protected'
        );

        foreach ( $dirs as $dir ) {
            if ( ! file_exists( $dir ) ) {
                wp_mkdir_p( $dir );
            }
            // Add .htaccess to protect proofs folder from direct browser access
            if ( strpos( $dir, 'ap-proofs-protected' ) !== false ) {
                $htaccess = $dir . '/.htaccess';
                if ( ! file_exists( $htaccess ) ) {
                    file_put_contents( $htaccess, "Deny from all" );
                }
            }
        }

        // 3. Auto-Create "Client Portal" Page
        $page_slug = 'client-portal';
        $existing_page = get_page_by_path( $page_slug );

        if ( ! $existing_page ) {
            wp_insert_post( array(
                'post_title'     => 'Client Portal',
                'post_name'      => $page_slug,
                'post_content'   => '[ap_client_portal]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            ));
        }

        // 4. Flush Rewrites for CPTs
        flush_rewrite_rules();
    }
}
