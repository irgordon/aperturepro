<?php
namespace AperturePro\Rest;
use WP_REST_Controller;
use AperturePro\Tenant;
use AperturePro\Validator;
use AperturePro\Errors;
use AperturePro\Hash;
use AperturePro\Audit;
use AperturePro\Geo;
use AperturePro\Rate_Limit;
use AperturePro\Cap;

class Contracts extends WP_REST_Controller {
  public function register_routes() {
    $ns = 'aperturepro/v1';
    register_rest_route($ns, '/contracts', [
      'methods' => 'POST',
      'permission_callback' => [Cap::class, 'is_admin'],
      'callback' => [$this, 'create']
    ]);
    register_rest_route($ns, '/contracts/(?P<id>\d+)', [
      'methods' => 'GET',
      'permission_callback' => '__return_true',
      'callback' => [$this, 'show']
    ]);
    register_rest_route($ns, '/contracts/(?P<id>\d+)/deadline', [
      'methods' => 'POST',
      'permission_callback' => [Cap::class, 'is_admin'],
      'callback' => [$this, 'deadline']
    ]);
    register_rest_route($ns, '/contracts/(?P<id>\d+)/sign', [
      'methods' => 'POST',
      'permission_callback' => [Cap::class, 'is_client'],
      'callback' => [$this, 'sign']
    ]);
  }

  public function create($req) {
    try {
      $title = Validator::text($req['title'], 256);
      $content = wp_kses_post($req['content']);
      $summary = is_array($req['summary']) ? $req['summary'] : [];

      $post_id = wp_insert_post([
        'post_type' => 'ap_contract',
        'post_title' => $title,
        'post_status' => 'publish',
        'post_content' => $content
      ]);
      Tenant::tag_post($post_id);

      $hash = Hash::sha256([
        'tenant' => Tenant::current_id(), 'contract_id' => $post_id,
        'content' => $content, 'summary' => $summary, 'version' => 1
      ], get_option('ap_audit_salt','default'));

      update_post_meta($post_id, '_ap_contract_summary', $summary);
      update_post_meta($post_id, '_ap_contract_version', 1);
      update_post_meta($post_id, '_ap_contract_hash', $hash);
      update_post_meta($post_id, '_ap_contract_status', 'pending');

      Audit::log('contract', $post_id, 'created', ['hash'=>$hash]);
      return new \WP_REST_Response(['id'=>$post_id,'hash'=>$hash], 201);
    } catch (\Throwable $e) {
      return Errors::bad_request($e->getMessage());
    }
  }

  public function show($req) {
    $id = (int)$req['id'];
    $post = get_post($id);
    if (!$post || $post->post_type !== 'ap_contract') return Errors::not_found('Contract not found');
    return [
      'id' => $id,
      'title' => $post->post_title,
      'content' => wpautop($post->post_content),
      'summary' => get_post_meta($id, '_ap_contract_summary', true),
      'status' => get_post_meta($id, '_ap_contract_status', true),
      'hash' => get_post_meta($id, '_ap_contract_hash', true),
    ];
  }

  public function deadline($req) {
    try {
      $id = Validator::id($req['id']);
      $deadline = Validator::text($req['deadline'], 32); // YYYY-MM-DD
      update_post_meta($id, '_ap_contract_deadline', $deadline);
      Audit::log('contract', $id, 'deadline_set', ['deadline'=>$deadline]);
      return ['ok'=>true];
    } catch (\Throwable $e) {
      return Errors::bad_request($e->getMessage());
    }
  }

  public function sign($req) {
    try {
      if (!Rate_Limit::check('contract_sign', 50, 60)) return Errors::forbidden('Rate limit exceeded');
      $id = Validator::id($req['id']);
      $name = Validator::text($req['name'], 128);
      $email = Validator::email($req['email']);
      $signature_url = Validator::url($req['signature_image_url']);
      $ip = $_SERVER['REMOTE_ADDR'] ?? '';
      $geo = Geo::snapshot($ip);

      $payload = [
        'contract_id'=>$id,'name'=>$name,'email'=>$email,
        'signature_image_url'=>$signature_url,'signed_at'=>gmdate('c'),
        'ip'=>$ip,'geo'=>$geo
      ];
      $hash = Hash::sha256($payload, get_option('ap_audit_salt','default'));
      add_post_meta($id, '_ap_signature', ['payload'=>$payload,'hash'=>$hash]);
      update_post_meta($id, '_ap_contract_status','signed');
      Audit::log('contract', $id, 'signed', ['signature_hash'=>$hash]);
      return ['ok'=>true,'signature_hash'=>$hash];
    } catch (\Throwable $e) {
      return Errors::bad_request($e->getMessage());
    }
  }
}
