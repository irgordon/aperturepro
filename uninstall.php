<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
// Preserve data for compliance; only delete options
delete_option('ap_geo_provider');
delete_option('ap_audit_salt');
delete_option('ap_cashapp_tag');
delete_option('ap_default_tenant_slug');
delete_option('ap_stripe_pk');
delete_option('ap_stripe_sk');
delete_option('ap_paypal_client_id');
delete_option('ap_paypal_secret');
delete_option('ap_cashapp_webhook_secret');
delete_option('ap_core_needs_clean');
delete_option('ap_core_version');
