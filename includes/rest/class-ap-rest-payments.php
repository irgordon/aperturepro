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

class Payments extends WP_REST_Controller {
  public function register_routes() {
    $ns='aperturepro/v1';
    register_rest_route($ns,'/invoices/(?P<id>\d+)',[
      'methods'=>'GET','permission_callback'=>'__return_true','callback'=>[$this,'show_invoice']
    ]);
    register_rest_route($ns,'/invoices/(?P<id>\d+)/pay/stripe',[
      'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'pay_stripe']
    ]);
    register_rest_route($ns,'/invoices/(?P<id>\d+)/pay/paypal',[
      'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'pay_paypal']
    ]);
    register_rest_route($ns,'/invoices/(?P<id>\d+)/pay/cashapp/init',[
      'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'cashapp_init']
    ]);
    register_rest_route($ns,'/webhooks/cashapp',[
      'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'cashapp_webhook']
    ]);
  }

  public function show_invoice($req){
    try {
      $id=(int)$req['id']; $p=get_post($id);
      if(!$p || $p->post_type!=='ap_invoice') return Errors::not_found('Invoice not found');
      $tenant_id = Tenant::current_id();
      $terms = wp_get_object_terms($id, 'ap_tenant_tag', ['fields'=>'ids']);
      if ($tenant_id && !in_array($tenant_id, $terms, true)) return Errors::forbidden('Cross-tenant access denied');
      return [
        'id'=>$id,'title'=>$p->post_title,
        'amount_cents'=> (int) get_post_meta($id,'_ap_amount_cents',true),
        'currency'=> get_post_meta($id,'_ap_currency',true) ?: 'USD',
        'due_date'=> get_post_meta($id,'_ap_due_date',true),
        'status'=> get_post_meta($id,'_ap_status',true) ?: 'outstanding'
      ];
    } catch (\Throwable $e) {
      return Errors::bad_request($e->getMessage());
    }
  }

  public function pay_stripe($req){
    try {
      if (!Rate_Limit::check('pay_stripe', 20, 60)) return Errors::forbidden('Rate limit exceeded');
      $id=(int)$req['id']; $p=get_post($id); if(!$p) return Errors::not_found('Invoice not found');
      $provider_txn_id = Validator::text($req['provider_txn_id'], 128);
      $method_type = Validator::enum($req['method'], ['card','apple_pay']);
      $amount_cents = (int)get_post_meta($id,'_ap_amount_cents',true);
      $currency = get_post_meta($id,'_ap_currency',true) ?: 'USD';
      $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $geo = Geo::snapshot($ip);
      $receipt = [
        'tenant_id'=> Tenant::current_id(), 'invoice_id'=>$id, 'method'=>$method_type,
        'provider_txn_id'=>$provider_txn_id, 'amount_cents'=>$amount_cents,
        'currency'=>$currency, 'paid_at'=>gmdate('c'), 'ip'=>$ip, 'geo'=>$geo
      ];
      $hash = Hash::sha256($receipt, get_option('ap_audit_salt','default'));
      add_post_meta($id, '_ap_payment', ['receipt'=>$receipt,'hash'=>$hash,'status'=>'succeeded']);
      update_post_meta($id, '_ap_status','paid');
      Audit::log('invoice',$id,'paid',['receipt_hash'=>$hash,'method'=>$method_type]);
      return ['ok'=>true,'receipt_hash'=>$hash];
    } catch (\Throwable $e) {
      return Errors::bad_request($e->getMessage());
    }
  }

  public function pay_paypal($req){
    try {
      if (!Rate_Limit::check('pay_paypal', 20, 60)) return Errors::forbidden('Rate limit exceeded');
      $id=(int)$req['id']; $order_id=sanitize_text_field($req['order_id']);
      $p=get_post($id); if(!$p || $p->post_type!=='ap_invoice') return Errors::not_found('Invoice not found');
      $amount_cents = (int)get_post_meta($id,'_ap_amount_cents',true);
      $currency = get_post_meta($id,'_ap_currency',true) ?: 'USD';
      $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $geo = Geo::snapshot($ip);
      $receipt = [
        'tenant_id'=>Tenant::current_id(),'invoice_id'=>$id,'method'=>'paypal',
        'provider_txn_id'=>$order_id,'amount_cents'=>$amount_cents,'currency'=>$currency,
        'paid_at'=>gmdate('c'),'ip'=>$ip,'geo'=>$geo
      ];
      $hash = Hash::sha256($receipt, get_option('ap_audit_salt','default'));
      add_post_meta($id,'_ap_payment',['receipt'=>$receipt,'hash'=>$hash,'status'=>'succeeded']);
      update_post_meta($id,'_ap_status','paid');
      Audit::log('invoice',$id,'paid',['receipt_hash'=>$hash,'method'=>'paypal']);
      return ['ok'=>true,'receipt_hash'=>$hash];
    } catch (\Throwable $e) {
      return Errors::bad_request($e->getMessage());
    }
  }

  public function cashapp_init($req){
    try {
      $id=(int)$req['id'];
      $cashTag = get_option('ap_cashapp_tag','$StudioTag');
      $invoice_number = get_post_meta($id,'_ap_invoice_number',true) ?: ('INV-'.$id);
      $amount_cents = (int)get_post_meta($id,'_ap_amount_cents',true);
      $deeplink = "https://cash.app/$cashTag?amount=" . ($amount_cents/100) . "&note=" . rawurlencode($invoice_number);
      return ['deeplink'=>$deeplink];
    } catch (\Throwable $e) {
      return Errors::bad_request($e->getMessage());
    }
  }

  public function cashapp_webhook($req){
    try {
      if (!Rate_Limit::check('cashapp_webhook', 100, 60)) return Errors::forbidden('Rate limit exceeded');
      $raw = $req->get_body();
      $sig = $_SERVER['HTTP_X_CASHAPP_SIGNATURE'] ?? '';
      if (!$this->verify_cashapp_hmac($raw, $sig)) return Errors::forbidden('Invalid webhook signature');

      $payload = json_decode($raw,true);
      $invoice_number = sanitize_text_field($payload['note'] ?? '');
      $amount_cents = (int) round((float)($payload['amount'] ?? 0) * 100);
      $txn_id = sanitize_text_field($payload['transaction_id'] ?? '');

      $replay_key = 'ap_cashapp_txn_' . md5($txn_id);
      if (get_transient($replay_key)) return ['ok'=>true]; // already processed
      set_transient($replay_key, 1, 60 * 60);

      $q = new \WP_Query(['post_type'=>'ap_invoice','meta_query'=>[
        ['key'=>'_ap_invoice_number','value'=>$invoice_number,'compare'=>'=']
      ], 'posts_per_page'=>1]);
      if(!$q->have_posts()) return Errors::not_found('Invoice not found');
      $post = $q->posts[0]; $id = $post->ID;

      $currency = get_post_meta($id,'_ap_currency',true) ?: 'USD';
      $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $geo = Geo::snapshot($ip);
      $receipt = [
        'tenant_id'=>Tenant::current_id(),'invoice_id'=>$id,'method'=>'cash_app',
        'provider_txn_id'=>$txn_id,'amount_cents'=>$amount_cents,'currency'=>$currency,
        'paid_at'=>gmdate('c'),'ip'=>$ip,'geo'=>$geo
      ];
      $hash = Hash::sha256($receipt, get_option('ap_audit_salt','default'));
      add_post_meta($id,'_ap_payment',['receipt'=>$receipt,'hash'=>$hash,'status'=>'succeeded']);
      update_post_meta($id,'_ap_status','paid');
      Audit::log('invoice',$id,'paid',['receipt_hash'=>$hash,'method'=>'cash_app','txn'=>$txn_id]);
      return ['ok'=>true];
    } catch (\Throwable $e) {
      return Errors::server_error($e->getMessage());
    }
  }

  private function verify_cashapp_hmac(string $raw, string $sig): bool {
    $secret = get_option('ap_cashapp_webhook_secret','');
    if (!$secret) return false;
    $calc = base64_encode(hash_hmac('sha256', $raw, $secret, true));
    return hash_equals($calc, $sig);
  }
}
