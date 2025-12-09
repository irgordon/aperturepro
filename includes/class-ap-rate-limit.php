<?php
namespace AperturePro;

class Rate_Limit {
  public static function check(string $key, int $limit, int $window_sec): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $k = 'ap_rl_' . md5($key . '|' . $ip);
    $data = get_transient($k);
    if (!$data) {
      set_transient($k, ['count' => 1, 'start' => time()], $window_sec);
      return true;
    }
    if (time() - $data['start'] > $window_sec) {
      set_transient($k, ['count' => 1, 'start' => time()], $window_sec);
      return true;
    }
    if ($data['count'] >= $limit) return false;
    $data['count']++;
    set_transient($k, $data, $window_sec);
    return true;
  }
}
