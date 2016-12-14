<?php
/*
Plugin Name: Flutterwave WooCommerce Gateway
Plugin URI: http://flutterwave.com/
Description: Flutterwave WooCommerce payment gateway.
Version: 0.0.1
Author: Bosun Olanrewaju
Author URI: http://twitter.com/bosunolanrewaju
  Copyright: © 2016 WooThemes.
  License: MIT License
*/
add_action('plugins_loaded', 'flw_woocommerce_flutterwave_init', 0);
function flw_woocommerce_flutterwave_init() {

  if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

  /**
   * Gateway class
   */
  class FLW_WC_Payment_Gateway extends WC_Payment_Gateway {

    // Go wild in here
  }

  /**
  * Add the Gateway to WooCommerce
  **/
  function flw_woocommerce_add_flutterwave_gateway($methods) {
    $methods[] = 'FLW_WC_Payment_Gateway';
    return $methods;
  }

  add_filter('woocommerce_payment_gateways', 'flw_woocommerce_add_flutterwave_gateway' );
}
