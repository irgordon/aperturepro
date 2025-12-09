<?php
namespace AperturePro;

class ImportExport {
  public static function export() {
    $data = [];
    $types = ['ap_tenant','ap_session','ap_contract','ap_invoice','ap_gallery','ap_escalation'];
    foreach ($types as $t) {
      $posts = get_posts(['post_type'=>$t,'numberposts'=>-1,'post_status'=>'any']);
      foreach ($posts as $p) {
        $data[$t][] = [
          'post' => [
            'post_title'=>$p->post_title,
            'post_content'=>$p->post_content,
            'post_status'=>$p->post_status,
            'post_type'=>$p->post_type
          ],
          'meta' => get_post_meta($p->ID)
        ];
      }
    }
    $data['options'] = [
      'ap_geo_provider'=>get_option('ap_geo_provider'),
      'ap_audit_salt'=>get_option('ap_audit_salt'),
      'ap_cashapp_tag'=>get_option('ap_cashapp_tag'),
      'ap_default_tenant_slug'=>get_option('ap_default_tenant_slug'),
      'ap_stripe_pk'=>get_option('ap_stripe_pk'),
      'ap_stripe_sk'=>get_option('ap_stripe_sk'),
      'ap_paypal_client_id'=>get_option('ap_paypal_client_id'),
      'ap_paypal_secret'=>get_option('ap_paypal_secret'),
      'ap_cashapp_webhook_secret'=>get_option('ap_cashapp_webhook_secret')
    ];
    return wp_json_encode($data, JSON_PRETTY_PRINT);
  }

  public static function import($json) {
    $data = json_decode($json,true);
    if (!$data) return false;
    foreach ($data as $type=>$entries) {
      if ($type==='options') {
        foreach ($entries as $k=>$v) update_option($k,$v);
      } else {
        foreach ($entries as $entry) {
          $id = wp_insert_post($entry['post']);
          foreach ($entry['meta'] as $mk=>$mv) {
            if (is_array($mv)) {
              update_post_meta($id,$mk,$mv[0]);
            } else {
              update_post_meta($id,$mk,$mv);
            }
          }
        }
      }
    }
    return true;
  }
}
