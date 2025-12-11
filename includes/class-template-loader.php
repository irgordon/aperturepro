<?php
class Aperture_Template_Loader {

    public function init() {
        add_filter( 'template_include', array( $this, 'load_custom_templates' ) );
    }

    public function load_custom_templates( $template ) {
        // Check if we are viewing our CPTs
        if ( is_singular( 'ap_invoice' ) ) {
            return $this->get_template_path( 'single-ap_invoice.php' );
        }
        if ( is_singular( 'ap_contract' ) ) {
            return $this->get_template_path( 'single-ap_contract.php' );
        }
        if ( is_singular( 'ap_gallery' ) ) {
            return $this->get_template_path( 'single-ap_gallery.php' );
        }

        return $template;
    }

    private function get_template_path( $filename ) {
        // Look in active theme first (allow overrides), then fallback to plugin
        $theme_file = locate_template( array( 'aperturepro/' . $filename ) );
        
        if ( $theme_file ) {
            return $theme_file;
        } else {
            return APERTURE_PATH . 'templates/' . $filename; 
            // NOTE: You must move the /themes/aperture-theme/ files into a /templates/ folder in the plugin
        }
    }
}
