<?php
/**
 * Handles Frontend Lead Capture Forms.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Lead_Capture {

    public function init() {
        add_shortcode( 'aperture_lead_form', array( $this, 'render_form' ) );
        add_action( 'admin_post_nopriv_ap_submit_lead', array( $this, 'handle_submission' ) );
        add_action( 'admin_post_ap_submit_lead', array( $this, 'handle_submission' ) );
    }

    public function render_form() {
        ob_start();
        ?>
        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="ap-lead-form">
            <input type="hidden" name="action" value="ap_submit_lead">
            <?php wp_nonce_field( 'ap_lead_submit', 'ap_lead_nonce' ); ?>
            
            <p>
                <label>Name</label>
                <input type="text" name="client_name" required>
            </p>
            <p>
                <label>Email</label>
                <input type="email" name="client_email" required>
            </p>
            <p>
                <label>Shoot Type</label>
                <select name="shoot_type">
                    <option value="wedding">Wedding</option>
                    <option value="portrait">Portrait</option>
                    <option value="commercial">Commercial</option>
                </select>
            </p>
            <p>
                <label>Requested Date</label>
                <input type="date" name="shoot_date">
            </p>
            <p>
                <label>Notes</label>
                <textarea name="client_notes"></textarea>
            </p>
            <button type="submit">Send Inquiry</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public function handle_submission() {
        // Verify Nonce
        if ( ! isset( $_POST['ap_lead_nonce'] ) || ! wp_verify_nonce( $_POST['ap_lead_nonce'], 'ap_lead_submit' ) ) {
            wp_die( 'Security check failed' );
        }

        $name  = sanitize_text_field( $_POST['client_name'] );
        $email = sanitize_email( $_POST['client_email'] );
        $type  = sanitize_text_field( $_POST['shoot_type'] );
        $date  = sanitize_text_field( $_POST['shoot_date'] );
        $notes = sanitize_textarea_field( $_POST['client_notes'] );

        // 1. Create/Find Customer (Simplified logic: just create new for now)
        $customer_id = wp_insert_post(array(
            'post_type' => 'ap_customer',
            'post_title' => $name,
            'post_status' => 'private',
            'meta_input' => array(
                '_ap_client_email' => $email,
            )
        ));

        // 2. Create Project as "Lead"
        $project_id = wp_insert_post(array(
            'post_type' => 'ap_project',
            'post_title' => "$type Inquiry: $name",
            'post_status' => 'publish',
            'meta_input' => array(
                '_ap_project_stage' => 'lead', // Key for your pipeline view
                '_ap_project_customer' => $customer_id,
                '_ap_project_date' => $date,
                '_ap_client_notes' => $notes
            )
        ));

        // 3. Trigger Automation (Welcome Email)
        do_action( 'ap_new_lead_captured', $project_id );

        // Redirect back with success message
        wp_redirect( home_url( '/thank-you/' ) );
        exit;
    }
}
