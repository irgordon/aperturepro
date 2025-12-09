<?php
namespace AperturePro;

class Validator {
  public static function id($v): int {
    $n = intval($v);
    if ($n <= 0) throw new \InvalidArgumentException('Invalid ID');
    return $n;
  }
  public static function text($v, $max = 2048): string {
    $s = sanitize_text_field($v);
    if (strlen($s) > $max) throw new \InvalidArgumentException('Text too long');
    return $s;
  }
  public static function email($v): string {
    $e = sanitize_email($v);
    if (!$e) throw new \InvalidArgumentException('Invalid email');
    return $e;
  }
  public static function url($v): string {
    $u = esc_url_raw($v);
    if (!$u) throw new \InvalidArgumentException('Invalid URL');
    return $u;
  }
  public static function enum($v, array $allowed): string {
    $s = sanitize_text_field($v);
    if (!in_array($s, $allowed, true)) throw new \InvalidArgumentException('Invalid enum');
    return $s;
  }
}
