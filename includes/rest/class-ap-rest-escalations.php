<?php
namespace AperturePro\Rest;
use WP_REST_Controller;
use AperturePro\Errors;
use AperturePro\Audit;

class Escalations extends WP_REST_Controller {
  public function register_routes() {
    $ns='aperturepro/v1';
    register_rest_route($ns,'/escalations',[
      'methods'=>'GET','permission_callback'=>'__return_true','callback'=>[$this,'list']
    ]);
  }

  public function list($req) {
    $q = new \WP_Query(['post_type'=>'ap_escalation','posts_per_page'=>-1]);
    $out = [];
    foreach ($q->posts as $p) {
      $out[] = [
        'id'=>$p->ID,'title'=>$p->post_title,
        'entity_type'=>get_post_meta($p->ID,'_ap_entity_type',true),
        'entity_id'=>get_post_meta($p->ID,'_ap_entity_id',true),
        'status'=>get_post_meta($p->ID,'_ap_status',true) ?: 'open'
      ];
    }
    return $out;
  }
}
