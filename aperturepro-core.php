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

// Initialize the Plugin
function aperture_init() {
    $cpt_manager = new Aperture_CPT_Manager();
    $cpt_manager->init();
    
    $automation = new Aperture_Automation();
    $automation->init();
}
add_action( 'plugins_loaded', 'aperture_init' );

// Activation Hook for Permalinks
register_activation_hook( __FILE__, 'aperture_activate' );
function aperture_activate() {
    aperture_init();
    flush_rewrite_rules();
}
