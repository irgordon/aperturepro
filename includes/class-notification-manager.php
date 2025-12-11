<?php
/**
 * Manages Email Templates and Sending Logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Notification_Manager {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_email_menu' ) );
        add_action( 'admin_init', array( $this, 'register_email_settings' ) );
    }

    public function add_email_menu() {
        add_submenu_page(
            'aperture-dashboard',
            'Email Templates',
            'Email Templates',
            'manage_options',
            'aperture-emails',
            array( $this, 'render_email_settings' )
        );
    }

    public function register_email_settings() {
        // Register settings for each email type
        $emails = array( 'welcome', 'proposal', 'nudge', 'payment', 'contract' );
        
        foreach ( $emails as $type ) {
            register_setting( 'ap_email_group', "ap_email_{$type}_subject" );
            register_setting( 'ap_email_group', "ap_email_{$type}_body" );
        }
    }

    public function render_email_settings() {
        ?>
        <div class="wrap">
            <h1>Email Templates</h1>
            <p>Customize the automated emails sent by AperturePro. Use <code>{{client_name}}</code>, <code>{{project_name}}</code>, and <code>{{link}}</code> as variables.</p>
            
            <form method="post" action="options.php" class="ap-email-form">
                <?php settings_fields( 'ap_email_group' ); ?>
                <?php do_settings_sections( 'ap_email_group' ); ?>
                
                <div class="ap-card">
                    <h2>Welcome / New Lead</h2>
                    <p><em>Sent when a lead form is submitted.</em></p>
                    <input type="text" name="ap_email_welcome_subject" value="<?php echo esc_attr( get_option('ap_email_welcome_subject', 'Welcome to AperturePro') ); ?>" class="widefat" placeholder="Subject Line">
                    <textarea name="ap_email_welcome_body" class="widefat" rows="5" placeholder="Body Content"><?php echo esc_textarea( get_option('ap_email_welcome_body', "Hi {{client_name}},\n\nThanks for your inquiry regarding {{project_name}}. We will be in touch shortly!") ); ?></textarea>
                </div>

                <div class="ap-card">
                    <h2>Proposal Sent</h2>
                    <p><em>Sent when a project moves to 'Proposal' stage.</em></p>
                    <input type="text" name="ap_email_proposal_subject" value="<?php echo esc_attr( get_option('ap_email_proposal_subject', 'Your Proposal is Ready') ); ?>" class="widefat">
                    <textarea name="ap_email_proposal_body" class="widefat" rows="5"><?php echo esc_textarea( get_option('ap_email_proposal_body', "Hi {{client_name}},\n\nPlease review your proposal here: {{link}}") ); ?></textarea>
                </div>

                <div class="ap-card">
                    <h2>Stale Nudge</h2>
                    <p><em>Sent automatically if no activity for 3 days.</em></p>
                    <input type="text" name="ap_email_nudge_subject" value="<?php echo esc_attr( get_option('ap_email_nudge_subject', 'Just checking in...') ); ?>" class="widefat">
                    <textarea name="ap_email_nudge_body" class="widefat" rows="5"><?php echo esc_textarea( get_option('ap_email_nudge_body', "Hi {{client_name}},\n\nI wanted to see if you had questions about the proposal?") ); ?></textarea>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <style>
            .ap-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px; max-width: 800px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .ap-card h2 { margin-top: 0; }
            .ap-email-form input, .ap-email-form textarea { margin-bottom: 10px; }
        </style>
        <?php
    }

    /**
     * Public helper to send emails using these templates
     */
    public function send_templated_email( $type, $to_email, $vars = array() ) {
        $subject_tmpl = get_option( "ap_email_{$type}_subject" );
        $body_tmpl    = get_option( "ap_email_{$type}_body" );

        if ( ! $subject_tmpl || ! $body_tmpl ) {
            return false; // Template not configured
        }

        // Replace variables
        $subject = $this->replace_vars( $subject_tmpl, $vars );
        $body    = $this->replace_vars( $body_tmpl, $vars );

        // Send
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $to_email, $subject, nl2br( $body ), $headers );
    }

    private function replace_vars( $content, $vars ) {
        foreach ( $vars as $key => $val ) {
            $content = str_replace( "{{" . $key . "}}", $val, $content );
        }
        return $content;
    }
}
