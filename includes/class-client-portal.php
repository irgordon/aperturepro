<?php
/**
 * Frontend Client Portal.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Client_Portal {

    public function init() {
        add_shortcode( 'ap_client_portal', array( $this, 'render_portal' ) );
    }

    public function render_portal() {
        if ( ! is_user_logged_in() ) {
            return '<p>Please <a href="' . wp_login_url( get_permalink() ) . '">log in</a> to view your client portal.</p>';
        }

        $user = wp_get_current_user();
        $email = $user->user_email;

        // Find Customer Entity linked to this User Email
        // Note: This assumes the 'ap_customer' post meta '_ap_client_email' matches the WP User email
        $args = array(
            'post_type' => 'ap_customer',
            'meta_key' => '_ap_client_email',
            'meta_value' => $email,
            'posts_per_page' => 1
        );
        $customers = get_posts( $args );

        if ( ! $customers ) {
            return '<div class="ap-notice">No client records found for this email address.</div>';
        }

        $customer_id = $customers[0]->ID;
        
        // Fetch Projects for this Customer
        $projects = get_posts(array(
            'post_type' => 'ap_project',
            'meta_key' => '_ap_project_customer',
            'meta_value' => $customer_id
        ));

        ob_start();
        ?>
        <div class="ap-portal-dashboard">
            <h2>Welcome, <?php echo esc_html( $user->display_name ); ?></h2>
            
            <?php if ( $projects ) : ?>
                <div class="ap-portal-projects">
                    <?php foreach ( $projects as $project ) : ?>
                        <div class="ap-portal-project-card">
                            <h3><?php echo esc_html( $project->post_title ); ?></h3>
                            <div class="ap-project-meta">
                                <span>Date: <?php echo esc_html( get_post_meta($project->ID, '_ap_project_date', true) ); ?></span>
                                <span class="ap-stage-badge"><?php echo esc_html( ucfirst( get_post_meta($project->ID, '_ap_project_stage', true) ) ); ?></span>
                            </div>

                            <h4>Invoices</h4>
                            <?php 
                            // In a real app, you would query invoices linked to this project ID
                            // For this demo, we are showing all invoices for the user
                            // You would need to implement 'project_id' meta on invoices to filter strictly
                            echo '<a href="#" class="button">View Invoices</a>'; 
                            ?>
                            
                            <h4>Galleries</h4>
                            <?php 
                            $galleries = get_posts(array(
                                'post_type' => 'ap_gallery',
                                'meta_key' => '_ap_gallery_project_id',
                                'meta_value' => $project->ID
                            ));
                            foreach($galleries as $g) {
                                echo '<a href="' . get_permalink($g->ID) . '" class="button">View Gallery: ' . get_the_title($g->ID) . '</a><br>';
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>You have no active projects with us.</p>
            <?php endif; ?>
        </div>
        
        <style>
            .ap-portal-project-card { border: 1px solid #eee; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #fff; }
            .ap-project-meta { margin-bottom: 15px; color: #666; }
            .ap-stage-badge { background: #eef; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; }
        </style>
        <?php
        return ob_get_clean();
    }
}
