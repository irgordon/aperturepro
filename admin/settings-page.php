<?php
/**
 * Main Settings Page for AperturePro.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Settings_Page {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_menu() {
        add_submenu_page(
            'aperture-dashboard', // Parent slug (defined in Step 4 of previous response)
            'Settings',
            'Settings',
            'manage_options',
            'aperture-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        // 1. General Branding
        register_setting( 'aperture_options', 'ap_brand_logo' );
        register_setting( 'aperture_options', 'ap_primary_color' );

        // 2. Stripe Settings
        register_setting( 'aperture_options', 'ap_stripe_publishable' );
        register_setting( 'aperture_options', 'ap_stripe_secret' );

        // 3. Google Calendar Settings
        register_setting( 'aperture_options', 'ap_google_client_id' );
        register_setting( 'aperture_options', 'ap_google_client_secret' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>AperturePro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'aperture_options' ); ?>
                <?php do_settings_sections( 'aperture_options' ); ?>

                <h2 class="title">Branding</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Logo URL</th>
                        <td><input type="text" name="ap_brand_logo" value="<?php echo esc_attr( get_option('ap_brand_logo') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Primary Brand Color</th>
                        <td><input type="color" name="ap_primary_color" value="<?php echo esc_attr( get_option('ap_primary_color', '#0073aa') ); ?>"></td>
                    </tr>
                </table>

                <h2 class="title">Payment Gateways (Stripe)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Publishable Key</th>
                        <td><input type="text" name="ap_stripe_publishable" value="<?php echo esc_attr( get_option('ap_stripe_publishable') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Secret Key</th>
                        <td><input type="password" name="ap_stripe_secret" value="<?php echo esc_attr( get_option('ap_stripe_secret') ); ?>" class="regular-text"></td>
                    </tr>
                </table>
                
                <h2 class="title">Integrations (Google Calendar)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Client ID</th>
                        <td><input type="text" name="ap_google_client_id" value="<?php echo esc_attr( get_option('ap_google_client_id') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret</th>
                        <td><input type="password" name="ap_google_client_secret" value="<?php echo esc_attr( get_option('ap_google_client_secret') ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
