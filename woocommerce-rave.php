<?php
/*
Plugin Name: WooCommerce Rave Payment Gateway
Plugin URI: http://flutterwave.com/
Description: WooCommerce payment gateway for Rave.
Version: 0.0.1
Author: Bosun Olanrewaju
Author URI: http://twitter.com/bosunolanrewaju
  Copyright: © 2016 Bosun Olanrewaju.
  License: MIT License
*/


if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

define( 'FLW_WC_PLUGIN_FILE', __FILE__ );
define( 'FLW_WC_DIR_PATH', plugin_dir_path( FLW_WC_PLUGIN_FILE ) );

add_action('plugins_loaded', 'flw_woocommerce_rave_init', 0);

function flw_woocommerce_rave_init() {

  if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

  require_once( FLW_WC_DIR_PATH . 'includes/class.flw_wc_payment_gateway.php' );

  add_filter('woocommerce_payment_gateways', 'flw_woocommerce_add_rave_gateway' );

  /**
   * Add the Settings link to the plugin
   *
   * @param  Array $links Existing links on the plugin page
   *
   * @return Array          Existing links with our settings link added
   */
  function flw_plugin_action_links( $links ) {

    $rave_settings_url = esc_url( get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=rave' ) );
    array_unshift( $links, "<a title='Rave Settings Page' href='$rave_settings_url'>Settings</a>" );

    return $links;

  }

  add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'flw_plugin_action_links' );

  /**
   * Add the Gateway to WooCommerce
   *
   * @param  Array $methods Existing gateways in WooCommerce
   *
   * @return Array          Gateway list with our gateway added
   */
  function flw_woocommerce_add_rave_gateway($methods) {

    $methods[] = 'FLW_WC_Payment_Gateway';
    return $methods;

  }
}
