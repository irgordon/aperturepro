<?php
/**
 * Plugin Name: AperturePro Core
 * Description: A comprehensive WordPress CRM for photography studios. Handles Customers, Projects, Invoices, Contracts, Galleries, Automation, Client Portals, and Dashboards.
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
// IMPORTANT: Run 'composer install' in the plugin directory for this to work.
if ( file_exists( APERTURE_PATH . 'vendor/autoload.php' ) ) {
    require_once APERTURE_PATH . 'vendor/autoload.php';
}

// 2. Autoload System Classes
require_once APERTURE_PATH . 'includes/class-cpt-manager.php';      // Entities (Projects, Invoices, etc.)
require_once APERTURE_PATH . 'includes/class-automation.php';       // Email Triggers
require_once APERTURE_PATH . 'includes/class-payment-gateway.php';  // Stripe Integration
require_once APERTURE_PATH . 'includes/class-gallery-proof.php';    // Image Protection & Watermarking
require_once APERTURE_PATH . 'includes/class-calendar-sync.php';    // Google Calendar Sync
require_once APERTURE_PATH . 'includes/class-task-manager.php';     // Task & Subtask Management
require_once APERTURE_PATH . 'includes/class-template-manager.php'; // Contract/Invoice Variable Templating
require_once APERTURE_PATH . 'includes/class-lead-capture.php';     // Frontend Lead Forms
require_once APERTURE_PATH . 'includes/class-contract-handler.php'; // Digital Signature Processing
require_once APERTURE_PATH . 'includes/class-gallery-handler.php';  // Client Selection Logic
require_once APERTURE_PATH . 'includes/class-automation-cron.php';  // Stale State & Nudge Bots
require_once APERTURE_PATH . 'includes/class-admin-ui.php';         // Admin List Views & 360 Customer Profile
require_once APERTURE_PATH . 'includes/class-api-routes.php';       // REST API for Headless Leads
require_once APERTURE_PATH . 'includes/class-client-portal.php';    // Frontend Client Dashboard

// NEW: Asset & Notification Managers
require_once APERTURE_PATH . 'includes/class-notification-manager.php'; // Email Template Settings
require_once APERTURE_PATH . 'includes/class-asset-manager.php';        // CSS/JS Enqueuing

// 3. Load Admin UI Pages (Only if in Admin Area)
if ( is_admin() ) {
    require_once APERTURE_PATH . 'admin/settings-page.php';
    require_once APERTURE_PATH . 'admin/dashboard-page.php'; // The Command Center
}

// 4. Initialize the Plugin Modules
function aperture_init() {
    // Core Entities
    $cpt_manager = new Aperture_CPT_Manager();
    $cpt_manager->init();
    
    // Automation & Logic
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

    // UI & API Enhancements
    $admin_ui = new Aperture_Admin_UI();
    $admin_ui->init();

    $api = new Aperture_API_Routes();
    $api->init();

    // Frontend Portal
    $portal = new Aperture_Client_Portal();
    $portal->init();

    // New Managers
    $notifications = new Aperture_Notification_Manager();
    $notifications->init();

    $assets = new Aperture_Asset_Manager();
    $assets->init();

    // Admin Pages
    if ( is_admin() ) {
        // Main Dashboard (Command Center)
        $dashboard = new Aperture_Dashboard_Page();
        $dashboard->init();

        // Settings Page
        $settings = new Aperture_Settings_Page();
        $settings->init();
    }
}
add_action( 'plugins_loaded', 'aperture_init' );

// 5. Activation Hook: Permalinks & Roles
register_activation_hook( __FILE__, 'aperture_activate' );

function aperture_activate() {
    // Trigger init to register CPTs so rewrite rules work immediately
    aperture_init();
    flush_rewrite_rules();

    // Add 'Photographer Assistant' Role
    // Allows team members to manage tasks without full admin access
    add_role( 'ap_assistant', 'Photographer Assistant', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'manage_ap_tasks' => true, 
        'view_ap_projects' => true,
    ));
}
