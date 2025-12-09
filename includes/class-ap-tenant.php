<?php
namespace AperturePro;

class Tenant {
  public static function resolve_slug(): string {
    $hdr = isset($_SERVER['HTTP_X_TENANT']) ? sanitize_title($_SERVER['HTTP_X_TENANT']) : '';
    if ($hdr) return $hdr;
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $parts = explode('.', $host);
    if (count($parts) > 2) return sanitize_title($parts[0]);
    return get_option('ap_default_tenant_slug', 'default');
  }

  public static function current_id(): int {
    $slug = self::resolve_slug();
    $tenant = get_page_by_path($slug, OBJECT, 'ap_tenant');
    return $tenant ? (int) $tenant->ID : 0;
  }

  public static function tag_post(int $post_id) {
    $tenant_id = self::current_id();
    if ($tenant_id) {
      wp_set_object_terms($post_id, $tenant_id, 'ap_tenant_tag', false);
    }
  }

  public static function enforce_query(array $args): array {
    $tenant_id = self::current_id();
    $tax_query = [
      [
        'taxonomy' => 'ap_tenant_tag',
        'field' => 'term_id',
        'terms' => $tenant_id,
      ]
    ];
    $args['tax_query'] = isset($args['tax_query']) ? array_merge($args['tax_query'], $tax_query) : $tax_query;
    return $args;
  }
}
