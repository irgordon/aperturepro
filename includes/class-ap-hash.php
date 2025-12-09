<?php
namespace AperturePro;
class Hash {
  public static function sha256(array $data, string $salt): string {
    ksort($data);
    $json = wp_json_encode(self::stable($data), JSON_UNESCAPED_SLASHES);
    return hash('sha256', $salt . '|' . $json);
  }
  private static function stable(array $a): array {
    foreach ($a as $k => $v) if (is_array($v)) $a[$k] = self::stable($v);
    ksort($a);
    return $a;
  }
}
