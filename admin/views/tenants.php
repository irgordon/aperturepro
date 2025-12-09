<div class="wrap">
  <h1>Studios</h1>
  <p>Manage AperturePro tenants. Use the Studios CPT (ap_tenant) to add or edit tenants. Ensure each has a unique slug for subdomain routing.</p>
  <?php
    $q = new WP_Query(['post_type'=>'ap_tenant','posts_per_page'=>-1]);
    if ($q->have_posts()) {
      echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Title</th><th>Slug</th></tr></thead><tbody>';
      foreach ($q->posts as $p) {
        echo '<tr><td>'.esc_html($p->ID).'</td><td>'.esc_html($p->post_title).'</td><td>'.esc_html($p->post_name).'</td></tr>';
      }
      echo '</tbody></table>';
    } else {
      echo '<p>No studios found.</p>';
    }
  ?>
</div>
