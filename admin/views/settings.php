<div class="wrap ap-settings">
  <h1>AperturePro Settings</h1>
  <form method="post" action="options.php">
    <?php settings_fields('ap_settings'); ?>
    <table class="form-table">
      <tr><th>Geo Provider</th><td><input name="ap_geo_provider" value="<?php echo esc_attr(get_option('ap_geo_provider','none')); ?>"></td></tr>
      <tr><th>Audit Salt</th><td><input name="ap_audit_salt" value="<?php echo esc_attr(get_option('ap_audit_salt','')); ?>"></td></tr>
      <tr><th>Default Studio Slug</th><td><input name="ap_default_tenant_slug" value="<?php echo esc_attr(get_option('ap_default_tenant_slug','default')); ?>"></td></tr>
      <tr><th>Stripe Public Key</th><td><input name="ap_stripe_pk" value="<?php echo esc_attr(get_option('ap_stripe_pk','')); ?>"></td></tr>
      <tr><th>Stripe Secret Key</th><td><input name="ap_stripe_sk" value="<?php echo esc_attr(get_option('ap_stripe_sk','')); ?>"></td></tr>
      <tr><th>PayPal Client ID</th><td><input name="ap_paypal_client_id" value="<?php echo esc_attr(get_option('ap_paypal_client_id','')); ?>"></td></tr>
      <tr><th>PayPal Secret</th><td><input name="ap_paypal_secret" value="<?php echo esc_attr(get_option('ap_paypal_secret','')); ?>"></td></tr>
      <tr><th>CashApp Webhook Secret</th><td><input name="ap_cashapp_webhook_secret" value="<?php echo esc_attr(get_option('ap_cashapp_webhook_secret','')); ?>"></td></tr>
      <tr><th>CashApp Tag</th><td><input name="ap_cashapp_tag" value="<?php echo esc_attr(get_option('ap_cashapp_tag','$StudioTag')); ?>"></td></tr>
    </table>
    <?php submit_button(); ?>
  </form>

  <hr />
  <h2>Maintenance</h2>
  <?php if (get_option('ap_core_needs_clean')): ?>
    <p><strong>Version mismatch detected.</strong> Running a Clean Install will drop the audit table and delete AperturePro CPT data. Export before proceeding.</p>
    <form method="post">
      <?php wp_nonce_field('ap_clean_install'); ?>
      <button name="ap_clean_install" value="1" class="button button-danger">Clean Install</button>
    </form>
  <?php else: ?>
    <p>No maintenance actions required.</p>
  <?php endif; ?>

  <h2>Import/Export</h2>
  <form method="post">
    <?php wp_nonce_field('ap_export'); ?>
    <button name="ap_export" value="1" class="button">Export Data</button>
  </form>
  <form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('ap_import'); ?>
    <input type="file" name="ap_import_file" accept="application/json" />
    <button name="ap_import" value="1" class="button">Import Data</button>
  </form>
</div>
