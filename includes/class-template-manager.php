<?php
/**
 * Handles Template Management and Variable Merging.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Template_Manager {

    public function init() {
        add_action( 'init', array( $this, 'register_template_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_template_help' ) );
    }

    public function register_template_cpt() {
        register_post_type( 'ap_template', array(
            'labels' => array( 'name' => 'Templates', 'singular_name' => 'Template' ),
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-layout',
            'supports' => array( 'title', 'editor' ),
        ));
    }

    /**
     * Shows the "Cheat Sheet" of variables in the sidebar when editing a template
     */
    public function add_template_help() {
        add_meta_box( 'ap_template_vars', 'Available Variables', array( $this, 'render_var_list' ), 'ap_template', 'side', 'high' );
    }

    public function render_var_list() {
        ?>
        <div class="ap-var-list">
            <p><strong>Client Info:</strong></p>
            <code>{{client_name}}</code><br>
            <code>{{client_email}}</code><br>
            <code>{{client_phone}}</code>
            
            <p><strong>Project Info:</strong></p>
            <code>{{project_name}}</code><br>
            <code>{{shoot_date}}</code><br>
            <code>{{location}}</code>
            
            <p><strong>Financial:</strong></p>
            <code>{{total_amount}}</code><br>
            <code>{{deposit_due}}</code>
        </div>
        <script>
            // Simple click-to-copy script
            jQuery('.ap-var-list code').click(function(){
                var text = jQuery(this).text();
                navigator.clipboard.writeText(text);
                alert('Copied ' + text + ' to clipboard!');
            });
        </script>
        <?php
    }

    /**
     * The Engine: Takes a template ID and a Project ID, and returns the final HTML
     */
    public function render_merged_content( $template_id, $project_id ) {
        $template = get_post( $template_id );
        $content = $template->post_content;

        // Fetch Project & Client Data
        $client_name = get_post_meta( $project_id, '_ap_client_name', true ); // Simplified for example
        $shoot_date  = get_post_meta( $project_id, '_ap_project_date', true );
        $total       = get_post_meta( $project_id, '_ap_project_price', true );

        // Define Replacements
        $vars = array(
            '{{client_name}}' => $client_name,
            '{{shoot_date}}'  => $shoot_date,
            '{{total_amount}}'=> '$' . number_format((float)$total, 2),
            '{{project_name}}'=> get_the_title( $project_id ),
        );

        // Perform Swap
        foreach ( $vars as $tag => $value ) {
            $content = str_replace( $tag, $value, $content );
        }

        // Apply standard WP content filters (for paragraphs, etc)
        return apply_filters( 'the_content', $content );
    }
}
