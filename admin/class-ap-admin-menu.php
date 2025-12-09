<?php
namespace AperturePro;

class Admin_Menu {
  public static function init() {
    add_action('admin_menu', function(){
      add_menu_page('AperturePro', 'AperturePro', 'manage_options', 'aperturepro', [__CLASS__,'render_settings'],'dashicons-camera', 3);
      add_submenu_page('aperturepro','Studios','Studios','manage_options','aperturepro-studios',[__CLASS__,'render_tenants']);
    });
    add_action('admin_init', [__CLASS__, 'register_settings']);
  }

  public static function register_settings() {
    register_setting('ap_settings','ap_geo_provider');
    register_setting('ap_settings','ap_audit_salt');
    register_setting('ap_settings','ap_cashapp_tag');
    register_setting('ap_settings','ap_default_tenant_slug');
    register_setting('ap_settings','ap_stripe_pk');
    register_setting('ap_settings','ap_stripe_sk');
    register_setting('ap_settings','ap_paypal_client_id');
    register_setting('ap_settings','ap_paypal_secret');
    register_setting('ap_settings','ap_cashapp_webhook_secret');
  }

  public static function render_settings() {
    if (isset($_POST['ap_clean_install']) && check_admin_referer('ap_clean_install')) {
      Install::clean_install();
      echo '<div class="updated"><p>Clean install completed.</p></div>';
    }
    if (isset($_POST['ap_export']) && check_admin_referer('ap_export')) {
      $json = ImportExport::export();
      header('Content-Type: application/json');
      header('Content-Disposition: attachment; filename="aperturepro-export.json"');
      echo $json; exit;
    }
    if (isset($_POST['ap_import']) && check_admin_referer('ap_import')) {
      $file = $_FILES['ap_import_file']['tmp_name'] ?? '';
      if (!$file) {
        echo '<div class="error"><p>No file uploaded.</p></div>';
      } else {
        $json = file_get_contents($file);
        if (ImportExport::import($json)) {
          echo '<div class="updated"><p>Import successful.</p></div>';
        } else {
          echo '<div class="error"><p>Import failed.</p></div>';
        }
      }
    }
    include AP_CORE_PATH.'admin/views/settings.php';
  }

  public static function render_tenants() {
    include AP_CORE_PATH.'admin/views/tenants.php';
  }
}
