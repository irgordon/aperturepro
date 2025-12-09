<?php
namespace AperturePro;

class Cap {
  public static function is_admin(): bool {
    return current_user_can('ap_manage') || current_user_can('administrator');
  }
  public static function is_client(): bool {
    return current_user_can('ap_client') || is_user_logged_in();
  }
}
