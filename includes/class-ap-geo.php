<?php
namespace AperturePro;

class Geo {
  public static function snapshot(?string $ip): array {
    $provider = get_option('ap_geo_provider','none');
    return ['provider'=>$provider,'ip'=>$ip,'country'=>null,'region'=>null,'city'=>null,'lat'=>null,'lon'=>null];
  }
}
