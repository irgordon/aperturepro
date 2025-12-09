<?php
namespace AperturePro\Rest;
use WP_REST_Controller;
use AperturePro\Tenant;
use AperturePro\Validator;
use AperturePro\Errors;
use AperturePro\Audit;

class Galleries extends WP_REST_Controller {
  public function register_routes() {
    $ns='aperturepro/v1';
    register_rest_route($ns,'/galleries/(?P<id>\d+)',[
      'methods'=>'GET','permission_callback'=>'__return_true','callback'=>[$this,'show']
    ]);
    register_rest_route($ns,'/galleries/(?P<id>\d+)/proofing/select',[
      'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'select']
    ]);
    register_rest_route($ns,'/galleries/(?P<id>\d+)/proofing/finalize',[
      'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'finalize']
    ]);
  }

  public function show($req) {
    $id = (int)$req['id']; $p=get_post($id);
    if(!$p || $p->post_type!=='ap_gallery') return Errors::not_found('Gallery not found');
    $images = get_post_meta($id, '_ap_gallery_images', true) ?: [];
    return ['id'=>$id,'title'=>$p->post_title,'images'=>$images];
  }

  public function select($req) {
    try {
      $id = Validator::id($req['id']);
      $image_id = Validator::text($req['image_id'], 512);
      $selected = !empty($req['selected']);
      $favorited = !empty($req['favorited']);
      $sel = get_post_meta($id, '_ap_proofing_selections', true) ?: [];
      $sel[$image_id] = ['selected'=>$selected,'favorited'=>$favorited];
      update_post_meta($id, '_ap_proofing_selections', $sel);
      Audit::log('gallery',$id,'proofing_select',['image_id'=>$image_id,'selected'=>$selected,'favorited'=>$favorited]);
      return ['ok'=>true];
    } catch (\Throwable $e) { return Errors::bad_request($e->getMessage()); }
  }

  public function finalize($req) {
    $id = (int)$req['id'];
    $sel = get_post_meta($id, '_ap_proofing_selections', true) ?: [];
    $hash = \AperturePro\Hash::sha256(['gallery_id'=>$id,'selections'=>$sel], get_option('ap_audit_salt','default'));
    update_post_meta($id, '_ap_proofing_final_hash', $hash);
    Audit::log('gallery',$id,'proofing_finalized',['selection_hash'=>$hash]);
    return ['ok'=>true,'selection_hash'=>$hash];
  }
}
