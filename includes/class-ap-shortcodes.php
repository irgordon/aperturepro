<?php
namespace AperturePro;

if (!defined('ABSPATH')) exit;

class Shortcodes {
  public static function init() {
    add_shortcode('ap_dashboard_portal', [__CLASS__, 'dashboard']);
    add_shortcode('ap_contracts_portal', [__CLASS__, 'contracts']);
    add_shortcode('ap_payments_portal', [__CLASS__, 'payments']);
    add_shortcode('ap_galleries_portal', [__CLASS__, 'galleries']);
  }

  public static function dashboard() {
    ob_start();
    ?>
    <div class="ap-portal ap-dashboard">
      <h2><?php esc_html_e('Welcome to AperturePro Studio Portal','aperturepro-core'); ?></h2>
      <ul>
        <li><a href="<?php echo esc_url(site_url('/ap-contracts')); ?>"><?php esc_html_e('Contracts','aperturepro-core'); ?></a></li>
        <li><a href="<?php echo esc_url(site_url('/ap-invoices')); ?>"><?php esc_html_e('Invoices','aperturepro-core'); ?></a></li>
        <li><a href="<?php echo esc_url(site_url('/ap-galleries')); ?>"><?php esc_html_e('Galleries','aperturepro-core'); ?></a></li>
      </ul>
    </div>
    <?php
    return ob_get_clean();
  }

  public static function contracts() {
    $q = new \WP_Query(['post_type'=>'ap_contract','posts_per_page'=>-1]);
    ob_start();
    echo '<div class="ap-portal ap-contracts"><h2>Contracts</h2>';
    if ($q->have_posts()) {
      echo '<ul>';
      foreach ($q->posts as $p) {
        $status = get_post_meta($p->ID,'_ap_contract_status',true);
        echo '<li><strong>'.esc_html($p->post_title).'</strong> ('.esc_html($status).')</li>';
      }
      echo '</ul>';
    } else {
      echo '<p>No contracts found.</p>';
    }
    echo '</div>';
    return ob_get_clean();
  }

  public static function payments() {
    $q = new \WP_Query(['post_type'=>'ap_invoice','posts_per_page'=>-1]);
    ob_start();
    echo '<div class="ap-portal ap-invoices"><h2>Invoices</h2>';
    if ($q->have_posts()) {
      echo '<ul>';
      foreach ($q->posts as $p) {
        $amount = get_post_meta($p->ID,'_ap_amount_cents',true);
        $currency = get_post_meta($p->ID,'_ap_currency',true) ?: 'USD';
        $status = get_post_meta($p->ID,'_ap_status',true);
        echo '<li><strong>'.esc_html($p->post_title).'</strong> - '.esc_html($amount/100).' '.esc_html($currency).' ('.esc_html($status).')</li>';
      }
      echo '</ul>';
    } else {
      echo '<p>No invoices found.</p>';
    }
    echo '</div>';
    return ob_get_clean();
  }

  public static function galleries() {
    $q = new \WP_Query(['post_type'=>'ap_gallery','posts_per_page'=>-1]);
    ob_start();
    echo '<div class="ap-portal ap-galleries"><h2>Galleries</h2>';
    if ($q->have_posts()) {
      echo '<div class="ap-grid">';
      foreach ($q->posts as $p) {
        $images = get_post_meta($p->ID,'_ap_gallery_images',true) ?: [];
        echo '<div class="ap-gallery-card"><h3>'.esc_html($p->post_title).'</h3>';
        foreach ($images as $img) {
          echo '<img src="'.esc_url($img).'" alt="">';
        }
        echo '</div>';
      }
      echo '</div>';
    } else {
      echo '<p>No galleries found.</p>';
    }
    echo '</div>';
    return ob_get_clean();
  }
}
