<?php
namespace AperturePro;

class Errors {
  public static function bad_request(string $msg) {
    return new \WP_Error('bad_request', $msg, ['status' => 400]);
  }
  public static function forbidden(string $msg) {
    return new \WP_Error('forbidden', $msg, ['status' => 403]);
  }
  public static function not_found(string $msg) {
    return new \WP_Error('not_found', $msg, ['status' => 404]);
  }
  public static function server_error(string $msg) {
    return new \WP_Error('server_error', $msg, ['status' => 500]);
  }
}
