<?php
/**
 * Plugin Name: AperturePro Core
 * Description: Contracts, payments, galleries, proofing, deliveries, audit for photography studios.
 * Version: 1.0.0
 * Author: AperturePro
 */

if (!defined('ABSPATH')) exit;

define('AP_CORE_VERSION', '1.0.0');
define('AP_CORE_PATH', plugin_dir_path(__FILE__));
define('AP_CORE_URL', plugin_dir_url(__FILE__));

require_once AP_CORE_PATH . 'includes/class-ap-core-loader.php';
require_once AP_CORE_PATH . 'includes/installers/class-ap-install.php';
require_once AP_CORE_PATH . 'includes/class-ap-import-export.php';

register_activation_hook(__FILE__, ['AperturePro\Install', 'activate']);
register_deactivation_hook(__FILE__, ['AperturePro\Install', 'deactivate']);

AperturePro\Core_Loader::init();
