<?php
/**
 * Plugin Name: AperturePro Core
 * Description: A comprehensive WordPress CRM for photography studios. Handles Customers, Projects, Invoices, Contracts, and Galleries.
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

// 1. Load Composer Dependencies (Stripe/Google SDKs)
// Run 'composer install' in the plugin directory for this to work.
if ( file_exists( APERTURE_PATH . 'vendor/autoload.php' ) ) {
    require_once APERTURE_PATH . 'vendor/autoload.php';
}

// 2. Autoload System Classes
require_once APERTURE_PATH . 'includes/class-cpt-manager.php';      // Entities
require_once APERTURE_PATH . 'includes/class-automation.php';       // Email Triggers
require_once APERTURE_PATH . 'includes/class-payment-gateway.php';  // Stripe
require_once APERTURE_PATH . 'includes/class-gallery-proof.php';    // Image Protection
require_once APERTURE_PATH . 'includes/class-calendar-sync.php';    // Google Calendar
require_once APERTURE_PATH . 'includes/class-task-manager.php';     // Subtasks
require_once APERTURE_PATH . 'includes/class-template-manager.php'; // Contracts/Invoice Templating
require_once APERTURE_PATH . 'includes/class-lead-capture.php';     // Frontend Forms
require_once APERTURE_PATH . 'includes/class-contract-handler.php'; // Digital Signatures
require_once APERTURE_PATH . 'includes/class-gallery-handler.php';  // Client Selections
require_once APERTURE_PATH . 'includes/class-automation-cron.php';  // Stale State Bots

// 3. Load Admin UI (Only if in Admin)
if ( is_admin() ) {
    require_once APERTURE_PATH . 'admin/settings-page.php';
}

// 4. Initialize the Plugin Modules
function aperture_init() {
    $cpt_manager = new Aperture_CPT_Manager();
    $cpt_manager->init();
    
    $automation = new Aperture_Automation();
    $automation->init();
    
    $payment = new Aperture_Payment_Gateway();
    $payment->init();
    
    $gallery = new Aperture_Gallery_Proof();
    $gallery->init();
    
    $calendar = new Aperture_Calendar_Sync();
    $calendar->init();

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

    if ( is_admin() ) {
        $settings = new Aperture_Settings_Page();
        $settings->init();
    }
}
add_action( 'plugins_loaded', 'aperture_init' );

// 5. Activation Hook: Permalinks & Roles
register_activation_hook( __FILE__, 'aperture_activate' );

function aperture_activate() {
    // Trigger init to register CPTs so rewrite rules work
    aperture_init();
    flush_rewrite_rules();

    // Add 'Photographer Assistant' Role
    add_role( 'ap_assistant', 'Photographer Assistant', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'manage_ap_tasks' => true, 
        'view_ap_projects' => true,
    ));
}
