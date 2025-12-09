<?php
namespace AperturePro;

class Audit {
  public static function log(string $entity_type, $entity_id, string $action, array $metadata = [], ?string $channel = 'portal') {
    global $wpdb;
    $tenant_id = Tenant::current_id();
    $actor_id = is_user_logged_in() ? get_current_user_id() : null;
    $actor_type = Cap::is_admin() ? 'admin' : (Cap::is_client() ? 'client' : 'system');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $geo = Geo::snapshot($ip);
    $payload = [
      'tenant_id' => $tenant_id,
      'actor_type' => $actor_type,
      'actor_id' => $actor_id,
      'entity_type' => $entity_type,
      'entity_id' => (string) $entity_id,
      'action' => $action,
      'ts' => gmdate('Y-m-d H:i:s'),
      'ip' => $ip,
      'geo' => $geo,
      'channel' => $channel,
      'metadata' => $metadata
    ];
    $hash = Hash::sha256($payload, get_option('ap_audit_salt', 'default_salt'));
    $table = $wpdb->prefix . 'ap_audit_logs';
    $wpdb->insert($table, [
      'tenant_id' => $tenant_id,
      'actor_type' => $actor_type,
      'actor_id' => $actor_id,
      'entity_type' => $entity_type,
      'entity_id' => (string) $entity_id,
      'action' => $action,
      'ts' => current_time('mysql', true),
      'ip' => $ip,
      'geo' => wp_json_encode($geo),
      'channel' => $channel,
      'hash' => $hash,
      'metadata' => wp_json_encode($metadata)
    ], ['%d','%s','%d','%s','%s','%s','%s','%s','%s','%s','%s']);
  }
}
