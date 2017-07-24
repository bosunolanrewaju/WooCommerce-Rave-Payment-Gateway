<?php

  if( ! defined( 'ABSPATH' ) ) { exit; }

  /**
   * Main Rave Gateway Class
   */
  class FLW_WC_Payment_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {

      $this->base_url = 'http://flw-pms-dev.eu-west-1.elasticbeanstalk.com';
      $this->id = 'rave';
      $this->icon = null;
      $this->has_fields         = false;
      $this->method_title       = __( 'Rave', 'flw-payments' );
      $this->method_description = __( 'Rave Payment Gateway', 'flw-payments' );
      $this->supports = array(
        'products',
      );

      $this->init_form_fields();
      $this->init_settings();

      $this->title        = __( 'Debit Card / Credit Card / Bank Account', 'flw-payments' );
      $this->description  = __( 'Pay with your bank account or credit/debit card', 'flw-payments' );
      $this->enabled      = $this->get_option( 'enabled' );
      $this->public_key   = $this->get_option( 'public_key' );
      $this->secret_key   = $this->get_option( 'secret_key' );
      $this->go_live      = $this->get_option( 'go_live' );
      $this->payment_method = $this->get_option( 'payment_method' );
      $this->country = $this->get_option( 'country' );

      add_action( 'admin_notices', array( $this, 'admin_notices' ) );
      add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
      add_action( 'woocommerce_api_flw_wc_payment_gateway', array( $this, 'flw_verify_payment' ) );

      if ( is_admin() ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      }

      if ( 'yes' === $this->go_live ) {
        $this->base_url = 'https://api.ravepay.co';
      }

      $this->load_scripts();

    }

    /**
     * Initial gateway settings form fields
     *
     * @return void
     */
    public function init_form_fields() {

      $this->form_fields = array(

        'enabled' => array(
          'title'       => __( 'Enable/Disable', 'flw-payments' ),
          'label'       => __( 'Enable Rave Payment Gateway', 'flw-payments' ),
          'type'        => 'checkbox',
          'description' => __( 'Enable Rave Payment Gateway as a payment option on the checkout page', 'flw-payments' ),
          'default'     => 'no',
          'desc_tip'    => true
        ),
        'go_live' => array(
          'title'       => __( 'Go Live', 'flw-payments' ),
          'label'       => __( 'Switch to live account', 'flw-payments' ),
          'type'        => 'checkbox',
          'description' => __( 'Ensure that you are using a public key and secret key generated from the live account.', 'flw-payments' ),
          'default'     => 'no',
          'desc_tip'    => true
        ),
        'public_key' => array(
          'title'       => __( 'Rave Checkout Public Key', 'flw-payments' ),
          'type'        => 'text',
          'description' => __( 'Required! Enter your Rave Checkout public key here', 'flw-payments' ),
          'default'     => ''
        ),
        'secret_key' => array(
          'title'       => __( 'Rave Checkout Secret Key', 'flw-payments' ),
          'type'        => 'text',
          'description' => __( 'Required! Enter your Rave Checkout secret key here', 'flw-payments' ),
          'default'     => ''
        ),
        'payment_method' => array(
          'title'       => __( 'Payment Method', 'flw-payments' ),
          'type'        => 'select',
          'description' => __( 'Optional - Choice of payment method to use. Card, Account or Both. (Default: both)', 'flw-payments' ),
          'options'     => array(
            'both' => esc_html_x( 'Card and Account', 'payment_method', 'flw-payments' ),
            'card'  => esc_html_x( 'Card Only',  'payment_method', 'flw-payments' ),
            'account'  => esc_html_x( 'Account Only',  'payment_method', 'flw-payments' ),
          ),
          'default'     => ''
        ),
        'country' => array(
          'title'       => __( 'Charge Country', 'flw-payments' ),
          'type'        => 'select',
          'description' => __( 'Optional - Charge country. (Default: NG)', 'flw-payments' ),
          'options'     => array(
            'NG' => esc_html_x( 'NG', 'country', 'flw-payments' ),
            'GH' => esc_html_x( 'GH', 'country', 'flw-payments' ),
            'KE' => esc_html_x( 'KE', 'country', 'flw-payments' ),
          ),
          'default'     => ''
        ),
        'modal_title' => array(
          'title'       => __( 'Modal Title', 'flw-payments' ),
          'type'        => 'text',
          'description' => __( 'Optional - The title of the payment modal (default: FLW PAY)', 'flw-payments' ),
          'default'     => ''
        ),
        'modal_description' => array(
          'title'       => __( 'Modal Description', 'flw-payments' ),
          'type'        => 'text',
          'description' => __( 'Optional - The description of the payment modal (default: FLW PAY MODAL)', 'flw-payments' ),
          'default'     => ''
        ),

      );

    }

    /**
     * Process payment at checkout
     *
     * @return int $order_id
     */
    public function process_payment( $order_id ) {

      $order = wc_get_order( $order_id );

      return array(
        'result'   => 'success',
        'redirect' => $order->get_checkout_payment_url( true )
      );

    }

    /**
     * Handles admin notices
     *
     * @return void
     */
    public function admin_notices() {

      if ( 'no' == $this->enabled ) {
        return;
      }

      /**
       * Check if public key is provided
       */
      if ( ! $this->public_key || ! $this->secret_key ) {

        echo '<div class="error"><p>';
        echo sprintf(
          'Provide your Rave "Pay Button" public key and secret key <a href="%s">here</a> to be able to use the WooCommerce Rave Payment Gateway plugin.',
           admin_url( 'admin.php?page=wc-settings&tab=checkout&section=rave' )
         );
        echo '</p></div>';
        return;
      }

    }

    /**
     * Checkout receipt page
     *
     * @return void
     */
    public function receipt_page( $order ) {

      $order = wc_get_order( $order );

      echo '<p>'.__( 'Thank you for your order, please click the button below to pay with Rave.', 'flw-payments' ).'</p>';
      echo '<button class="button alt" id="flw-pay-now-button">Pay Now</button> ';
      echo '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">';
      echo __( 'Cancel order &amp; restore cart', 'flw-payments' ) . '</a>';

    }

    /**
     * Loads (enqueue) static files (js & css) for the checkout page
     *
     * @return void
     */
    public function load_scripts() {

      if ( ! is_checkout_pay_page() ) return;

      wp_enqueue_script( 'flwpbf_inline_js', $this->base_url . '/flwv3-pug/getpaidx/api/flwpbf-inline.js', array(), '1.0.0', true );
      wp_enqueue_script( 'flw_js', plugins_url( 'assets/js/flw.js', FLW_WC_PLUGIN_FILE ), array( 'jquery', 'flwpbf_inline_js' ), '1.0.0', true );

      $p_key = $this->public_key;
      $payment_method = $this->payment_method;

      if ( get_query_var( 'order-pay' ) ) {

        $order_key = urldecode( $_REQUEST['key'] );
        $order_id  = absint( get_query_var( 'order-pay' ) );
        $order     = wc_get_order( $order_id );
        $txnref    = "WOOC_" . $order_id . '_' . time();
        $amount    = $order->order_total;
        $email     = $order->billing_email;
        $currency  = get_option('woocommerce_currency');
        $country  = $this->country;

        if ( $order->order_key == $order_key ) {

          $payment_args = compact( 'amount', 'email', 'txnref', 'p_key', 'currency', 'country', 'payment_method' );
          $payment_args['cb_url'] = WC()->api_request_url( 'FLW_WC_Payment_Gateway' );
          $payment_args['desc']   = $this->get_option( 'modal_description' );
          $payment_args['title']  = $this->get_option( 'modal_title' );
        }

        update_post_meta( $order_id, '_flw_payment_txn_ref', $txnref );

      }

      wp_localize_script( 'flw_js', 'flw_payment_args', $payment_args );

    }

    /**
     * Verify payment made on the checkout page
     *
     * @return void
     */
    public function flw_verify_payment() {

      if ( isset( $_POST['txRef'] ) ) {

        $txn_ref = $_POST['txRef'];
        $o = explode( '_', $txn_ref );
        $order_id = intval( $o[1] );
        $order = wc_get_order( $order_id );
        $order_currency = $order->get_order_currency();
        $txn = json_decode( $this->_fetchTransaction( $_POST['flwRef'] ) );

        if ( ! empty($txn->data) && $this->_is_successful( $txn->data ) ) {

          $order_amount = $order->get_total();
          $charged_amount  = $_POST['amount'];

          if ( $charged_amount != $order_amount ) {

            $order->update_status( 'on-hold' );
            $customer_note  = 'Thank you for your order.<br>';
            $customer_note .= 'Your payment successfully went through, but we have to put your order <strong>on-hold</strong> ';
            $customer_note .= 'because the amount received is different from the total amount of your order. Please, contact us for information regarding this order.';
            $admin_note     = 'Attention: New order has been placed on hold because of incorrect payment amount. Please, look into it. <br>';
            $admin_note    .= "Amount paid: $order_currency $charged_amount <br> Order amount: $order_currency $order_amount <br> Reference: $txn_ref";

            $order->add_order_note( $customer_note, 1 );
            $order->add_order_note( $admin_note );

            wc_add_notice( $customer_note, 'notice' );

          } else {

            $order->payment_complete( $order_id );
            $order->add_order_note( "Payment processed and approved successfully with reference: $txn_ref" );

          }

          WC()->cart->empty_cart();

        } else {

          $order->update_status( 'failed', 'Payment not successful' );

        }

        $redirect_url = $this->get_return_url( $order );
        echo json_encode( array( 'redirect_url' => $redirect_url ) );

      }

      die();

    }

   /**
     * Fetches transaction from rave enpoint
     *
     * @param $tx_ref string the transaction to fetch
     *
     * @return string
     */
    private function _fetchTransaction( $flw_ref ) {

      $url = $this->base_url . '/flwv3-pug/getpaidx/api/verify';
      $args = array(
        'body' => array(
          'flw_ref' => $flw_ref,
          'SECKEY' => $this->secret_key ),
        'sslverify' => false
      );

      $response = wp_remote_post( $url, $args );
      $result = wp_remote_retrieve_response_code( $response );

      if( $result === 200 ){
        return wp_remote_retrieve_body( $response );
      }

      return $result;

    }

    /**
     * Checks if payment is successful
     *
     * @param $data object the transaction object to do the check on
     *
     * @return boolean
     */
    private function _is_successful($data) {
      return $data->flwMeta->chargeResponse === '00' || $data->flwMeta->chargeResponse === '0';
    }

  }
?>
