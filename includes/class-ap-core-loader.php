<?php
namespace AperturePro;

if (!defined('ABSPATH')) exit;

class Core_Loader {
  public static function init() {
    // Load core utility classes
    $classes = [
      'class-ap-cap.php',
      'class-ap-errors.php',
      'class-ap-validator.php',
      'class-ap-rate-limit.php',
      'class-ap-tenant.php',
      'class-ap-hash.php',
      'class-ap-geo.php',
      'class-ap-audit.php'
    ];
    foreach ($classes as $c) {
      require_once AP_CORE_PATH . 'includes/' . $c;
    }

    // Admin classes
    require_once AP_CORE_PATH . 'admin/class-ap-admin-menu.php';
    require_once AP_CORE_PATH . 'admin/class-ap-admin-security.php';

    // Shortcodes
    require_once AP_CORE_PATH . 'includes/class-ap-shortcodes.php';

    // Initialize features
    add_action('init', function() {
      \AperturePro\Shortcodes::init();
    });
    \AperturePro\Admin_Menu::init();

    // REST API
    add_action('rest_api_init', [__CLASS__, 'register_rest']);

    // CPTs & taxonomies
    add_action('init', [__CLASS__, 'register_cpts']);
    add_action('init', [__CLASS__, 'register_taxonomies']);

    // Assets
    add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'portal_assets']);

    // Cron
    add_action('ap_hourly_event', [__CLASS__, 'run_hourly_tasks']);
  }

  public static function register_rest() {
    require_once AP_CORE_PATH . 'includes/rest/class-ap-rest-contracts.php';
    require_once AP_CORE_PATH . 'includes/rest/class-ap-rest-payments.php';
    require_once AP_CORE_PATH . 'includes/rest/class-ap-rest-galleries.php';
    require_once AP_CORE_PATH . 'includes/rest/class-ap-rest-proofing.php';
    require_once AP_CORE_PATH . 'includes/rest/class-ap-rest-escalations.php';

    (new \AperturePro\Rest\Contracts())->register_routes();
    (new \AperturePro\Rest\Payments())->register_routes();
    (new \AperturePro\Rest\Galleries())->register_routes();
    (new \AperturePro\Rest\Proofing())->register_routes();
    (new \AperturePro\Rest\Escalations())->register_routes();
  }

  public static function register_cpts() {
    register_post_type('ap_tenant', [
      'label'=>'Studios','public'=>false,'show_ui'=>true,
      'supports'=>['title'],'menu_icon'=>'dashicons-store'
    ]);
    register_post_type('ap_session', [
      'label'=>'Sessions','public'=>false,'show_ui'=>true,
      'supports'=>['title','custom-fields'],'menu_icon'=>'dashicons-calendar-alt'
    ]);
    register_post_type('ap_contract', [
      'label'=>'Contracts','public'=>false,'show_ui'=>true,
      'supports'=>['title','editor','custom-fields'],'menu_icon'=>'dashicons-media-text'
    ]);
    register_post_type('ap_invoice', [
      'label'=>'Invoices','public'=>false,'show_ui'=>true,
      'supports'=>['title','custom-fields'],'menu_icon'=>'dashicons-clipboard'
    ]);
    register_post_type('ap_gallery', [
      'label'=>'Galleries','public'=>false,'show_ui'=>true,
      'supports'=>['title','custom-fields'],'menu_icon'=>'dashicons-format-gallery'
    ]);
    register_post_type('ap_escalation', [
      'label'=>'Escalations','public'=>false,'show_ui'=>true,
      'supports'=>['title','custom-fields'],'menu_icon'=>'dashicons-warning'
    ]);
  }

  public static function register_taxonomies() {
    register_taxonomy('ap_tenant_tag',
      ['ap_contract','ap_invoice','ap_gallery','ap_session'],
      ['label'=>'Studio','public'=>false,'show_ui'=>true,'hierarchical'=>false]
    );
  }

  public static function admin_assets($hook) {
    wp_enqueue_style('ap-admin', AP_CORE_URL.'assets/css/admin.css', [], AP_CORE_VERSION);
    wp_enqueue_script('ap-admin', AP_CORE_URL.'assets/js/admin-settings.js', ['wp-i18n','wp-element'], AP_CORE_VERSION, true);
  }

  public static function portal_assets() {
    wp_enqueue_style('ap-portal', AP_CORE_URL.'assets/css/portal.css', [], AP_CORE_VERSION);
    wp_enqueue_script('ap-signature', AP_CORE_URL.'assets/js/signature-canvas.js', [], AP_CORE_VERSION, true);
  }

  public static function run_hourly_tasks() {
    // Overdue invoices
    $q = new \WP_Query([
      'post_type'=>'ap_invoice',
      'meta_query'=>[['key'=>'_ap_status','value'=>'outstanding','compare'=>'=']],
      'posts_per_page'=>-1
    ]);
    foreach ($q->posts as $p) {
      $due = get_post_meta($p->ID,'_ap_due_date',true);
      if ($due && strtotime($due) < time()) {
        update_post_meta($p->ID,'_ap_status','overdue');
        \AperturePro\Audit::log('invoice',$p->ID,'overdue',[]);
      }
    }

    // Expired galleries
    $g = new \WP_Query(['post_type'=>'ap_gallery','posts_per_page'=>-1]);
    foreach ($g->posts as $pg) {
      $exp = get_post_meta($pg->ID,'_ap_expiration_date',true);
      if ($exp && strtotime($exp) < time()) {
        update_post_meta($pg->ID,'_ap_status','expired');
        \AperturePro\Audit::log('gallery',$pg->ID,'expired',[]);
      }
    }

    // Unsigned contracts past deadline → escalation
    $c = new \WP_Query([
      'post_type'=>'ap_contract',
      'meta_query'=>[['key'=>'_ap_contract_status','value'=>'pending','compare'=>'=']],
      'posts_per_page'=>-1
    ]);
    foreach ($c->posts as $pc) {
      $dl = get_post_meta($pc->ID,'_ap_contract_deadline',true);
      if ($dl && strtotime($dl) < time()) {
        $escalation_id = wp_insert_post([
          'post_type'=>'ap_escalation',
          'post_title'=>'Unsigned contract past deadline',
          'post_status'=>'publish'
        ]);
        \AperturePro\Tenant::tag_post($escalation_id);
        update_post_meta($escalation_id,'_ap_entity_type','contract');
        update_post_meta($escalation_id,'_ap_entity_id',$pc->ID);
        update_post_meta($escalation_id,'_ap_status','open');
        \AperturePro\Audit::log('escalation',$escalation_id,'created',['reason'=>'contract_deadline_missed']);
      }
    }
  }
