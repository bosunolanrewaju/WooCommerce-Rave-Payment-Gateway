<?php
/*
Plugin Name: WooCommerce Flutterwave Express Checkout Gateway
Plugin URI: http://flutterwave.com/
Description: WooCommerce payment gateway for Flutterwave Express Checkout.
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

add_action('plugins_loaded', 'flw_woocommerce_flutterwave_init', 0);

function flw_woocommerce_flutterwave_init() {

  if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

  require_once( FLW_WC_DIR_PATH . 'includes/class.flw_wc_payment_gateway.php' );

  /**
   * Add the Gateway to WooCommerce
   *
   * @param  Array $methods Existing gateways in WooCommerce
   *
   * @return Array          Gateway list with our gateway added
   */
  function flw_woocommerce_add_flutterwave_gateway($methods) {

    $methods[] = 'FLW_WC_Payment_Gateway';
    return $methods;

  }

  add_filter('woocommerce_payment_gateways', 'flw_woocommerce_add_flutterwave_gateway' );
}
