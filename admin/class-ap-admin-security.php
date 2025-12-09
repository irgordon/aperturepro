<?php
namespace AperturePro;

class Admin_Security {
  public static function require_cap() {
    if (!Cap::is_admin()) {
      wp_die(__('Insufficient permissions', 'ap-core'));
    }
  }

  public static function nonce_field(string $action) {
    wp_nonce_field($action, '_ap_nonce');
  }

  public static function verify_nonce(string $action) {
    $nonce = $_POST['_ap_nonce'] ?? '';
    if (!wp_verify_nonce($nonce, $action)) {
      wp_die(__('Security check failed', 'ap-core'));
    }
  }
}
