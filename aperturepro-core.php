<?php
/**
 * Plugin Name: AperturePro Core
 * Description: A WordPress CRM for photography studios. Handles Customers, Projects, Invoices, and Galleries.
 * Version: 1.0.0
 * Author: AperturePro
 * Text Domain: aperturepro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'APERTURE_PATH', plugin_dir_path( __FILE__ ) );
define( 'APERTURE_URL', plugin_dir_url( __FILE__ ) );

// Autoload Classes
require_once APERTURE_PATH . 'includes/class-cpt-manager.php';
require_once APERTURE_PATH . 'includes/class-automation.php';
require_once APERTURE_PATH . 'includes/class-payment-gateway.php';
require_once APERTURE_PATH . 'includes/class-task-manager.php';
require_once APERTURE_PATH . 'includes/class-template-manager.php';
require_once APERTURE_PATH . 'includes/class-lead-capture.php';
require_once APERTURE_PATH . 'includes/class-contract-handler.php';
require_once APERTURE_PATH . 'admin/settings-page.php';
// Autoload Classes
require_once APERTURE_PATH . 'includes/class-cpt-manager.php';
require_once APERTURE_PATH . 'includes/class-automation.php';
require_once APERTURE_PATH . 'includes/class-gallery-handler.php';
require_once APERTURE_PATH . 'includes/class-automation-cron.php';

// NEW: Load the next step classes
require_once APERTURE_PATH . 'includes/class-payment-gateway.php';
require_once APERTURE_PATH . 'includes/class-gallery-proof.php';
require_once APERTURE_PATH . 'includes/class-calendar-sync.php';

// Initialize the Plugin
function aperture_init() {
    $cpt_manager = new Aperture_CPT_Manager();
    $cpt_manager->init();
    
    $automation = new Aperture_Automation();
    $automation->init();
    
    // NEW: Init new modules
    $payment = new Aperture_Payment_Gateway();
    $payment->init();
    
    $gallery = new Aperture_Gallery_Proof();
    $gallery->init();
    
    $calendar = new Aperture_Calendar_Sync();
    $calendar->init();
}

// Initialize the Plugin
function aperture_init() {
    $cpt_manager = new Aperture_CPT_Manager();
    $cpt_manager->init();
    
    $automation = new Aperture_Automation();
    $automation->init();
    $tasks = new Aperture_Task_Manager();
    $tasks->init();

    $templates = new Aperture_Template_Manager();
    $templates->init();
    $leads = new Aperture_Lead_Capture();
    $leads->init();
    
    $contracts = new Aperture_Contract_Handler();
    $contracts->init();
    $gallery_handler = new Aperture_Gallery_Handler();
    $gallery_handler->init();
    
    $cron = new Aperture_Automation_Cron();
    $cron->init();

    // Only load admin pages if in admin area
    if ( is_admin() ) {
        $settings = new Aperture_Settings_Page();
        $settings->init();
    }
}
add_action( 'plugins_loaded', 'aperture_init' );

// Activation Hook for Permalinks
register_activation_hook( __FILE__, 'aperture_activate' );
function aperture_activate() {
    aperture_init();
    flush_rewrite_rules();
}
add_action( 'admin_menu', 'aperture_register_admin_page' );

function aperture_register_admin_page() {
    add_menu_page(
        'AperturePro', 
        'AperturePro', 
        'manage_options', 
        'aperture-dashboard', 
        'aperture_render_dashboard', 
        'dashicons-camera', 
        2 
    );
}

function aperture_render_dashboard() {
    // Query metrics
    $lead_count = wp_count_posts('ap_project')->draft; // Assuming draft = lead
    $pending_invoices = 5; // Placeholder for actual DB query
    ?>
    <div class="wrap">
        <h1>AperturePro Command Center</h1>
        <div class="ap-dashboard-widgets">
            <div class="card">
                <h2><?php echo $lead_count; ?></h2>
                <p>New Leads</p>
            </div>
            <div class="card">
                <h2><?php echo $pending_invoices; ?></h2>
                <p>Unpaid Invoices</p>
            </div>
            <div class="card">
                <h2>Upcoming Shoots</h2>
                <ul>
                    <li>Wedding: Smith vs Jones (Oct 12)</li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}
