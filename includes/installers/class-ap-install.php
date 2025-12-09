<?php
namespace AperturePro;

if (!defined('ABSPATH')) exit;

class Install {
  public static function activate() {
    $current = get_option('ap_core_version');
    if ($current !== AP_CORE_VERSION) {
      update_option('ap_core_version', AP_CORE_VERSION);
      update_option('ap_core_needs_clean', true);
    }
    self::create_tables();
    self::register_roles();
    self::schedule_cron();
  }

  public static function deactivate() {
    // Preserve data; unschedule cron
    wp_clear_scheduled_hook('ap_hourly_event');
  }

  private static function create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $audit = $wpdb->prefix . 'ap_audit_logs';

    $sql = "CREATE TABLE IF NOT EXISTS $audit (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      tenant_id BIGINT UNSIGNED NOT NULL,
      actor_type VARCHAR(16) NOT NULL,
      actor_id BIGINT UNSIGNED NULL,
      entity_type VARCHAR(32) NOT NULL,
      entity_id VARCHAR(64) NOT NULL,
      action VARCHAR(32) NOT NULL,
      ts DATETIME NOT NULL,
      ip VARCHAR(64) NULL,
      geo JSON NULL,
      channel VARCHAR(16) NULL,
      hash CHAR(64) NOT NULL,
      metadata JSON NULL,
      PRIMARY KEY (id),
      KEY tenant_idx (tenant_id),
      KEY entity_idx (entity_type, entity_id)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
  }

  private static function register_roles() {
    add_role('ap_client', 'AperturePro Client', ['read' => true]);
    $role = get_role('administrator');
    if ($role) {
      $role->add_cap('ap_manage');
      $role->add_cap('ap_view_audit');
    }
  }

  private static function schedule_cron() {
    if (!wp_next_scheduled('ap_hourly_event')) {
      wp_schedule_event(time(), 'hourly', 'ap_hourly_event');
    }
  }

  public static function clean_install() {
    global $wpdb;
    // Drop custom tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ap_audit_logs");

    // Delete CPT data
    $types = ['ap_tenant','ap_session','ap_contract','ap_invoice','ap_gallery','ap_escalation'];
    foreach ($types as $t) {
      $posts = get_posts(['post_type'=>$t,'numberposts'=>-1,'post_status'=>'any']);
      foreach ($posts as $p) wp_delete_post($p->ID,true);
    }

    // Reset flags
    delete_option('ap_core_needs_clean');
    update_option('ap_core_version', AP_CORE_VERSION);

    // Recreate tables/roles/cron
    self::create_tables();
    self::register_roles();
    self::schedule_cron();
  }
}
