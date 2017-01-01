<?php

  if( ! defined( 'ABSPATH' ) ) { exit; }

  /**
   * Main Flutterwave Gateway Class
   */
  class FLW_WC_Payment_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {

      $this->id = 'flutterwave';
      $this->icon = null;
      $this->has_fields         = false;
      $this->method_title       = __( 'Flutterwave Pay', 'flw-payments' );
      $this->method_description = __( 'Flutterwave Payment Gateway', 'flw-payments' );
      $this->supports           = array(
        'products',
      );

      $this->init_form_fields();
      $this->init_settings();

      $this->title        = __( 'Debit Card / Credit Card / Bank Account', 'flw-payments' );
      $this->description  = __( 'Pay with your bank account or credit/debit card', 'flw-payments' );
      $this->enabled      = $this->get_option( 'enabled' );
      $this->public_key   = $this->get_option( 'public_key' );

      add_action( 'admin_notices', array( $this, 'admin_notices' ) );
      add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
      add_action( 'woocommerce_api_flw_wc_payment_gateway', array( $this, 'flw_verify_payment' ) );

      if ( is_admin() ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
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
          'label'       => __( 'Enable Flutterwave Express Checkout', 'flw-payments' ),
          'type'        => 'checkbox',
          'description' => __( 'Enable Flutterwave Express Checkout as a payment option on the checkout page', 'flw-payments' ),
          'default'     => 'no',
          'desc_tip'    => true
        ),
        'public_key' => array(
          'title'       => __( 'Integration Public Key', 'flw-payments' ),
          'type'        => 'text',
          'description' => __( 'Required! Enter your integration public key here', 'flw-payments' ),
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
      if ( ! ( $this->public_key ) ) {

        echo '<div class="error"><p>';
        echo sprintf(
          'Provide your Flutterwave integration public key <a href="%s">here</a> to be able to use the WooCommerce Flutterwave Express Checkout plugin.',
           admin_url( 'admin.php?page=wc-settings&tab=checkout&section=flutterwave' )
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

      echo '<p>'.__( 'Thank you for your order, please click the button below to pay with Flutterwave Express Checkout.', 'flw-payments' ).'</p>';
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

      wp_enqueue_script( 'flwpbf_inline_js', '//flw-pms-dev.eu-west-1.elasticbeanstalk.com/flwv3-pug/getpaidx/api/flwpbf-inline.js', array(), '1.0.0', true );
      wp_enqueue_script( 'flw_js', plugins_url( 'assets/js/flw.js', FLW_WC_PLUGIN_FILE ), array( 'flwpbf_inline_js' ), '1.0.0', true );

      $payment_args = array(
        'p_key' => $this->public_key
      );

      if ( get_query_var( 'order-pay' ) ) {

        $order_key = urldecode( $_REQUEST['key'] );
        $order_id  = absint( get_query_var( 'order-pay' ) );
        $order     = wc_get_order( $order_id );
        $txnref    = "WOOC_" . $order_id . '_' . time();
        $amount    = $order->order_total;
        $email     = $order->billing_email;

        if ( $order->order_key == $order_key ) {

          $payment_args['amount'] = $amount;
          $payment_args['cb_url'] = WC()->api_request_url( 'FLW_WC_Payment_Gateway' );
          $payment_args['desc']   = $this->get_option( 'modal_description' );
          $payment_args['email']  = $email;
          $payment_args['txnref'] = $txnref;
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
        $response_code = ( $_POST['paymentType'] === 'account' ) ? $_POST['acctvalrespcode'] : $_POST['vbvrespcode'];
          $txn_ref = $_POST['txRef'];
          $order_id = intval( explode( '_', $txn_ref )[1] );
          $order = wc_get_order( $order_id );
          $order_currency = $order->get_order_currency();

        if ( $response_code == '00' ) {

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

  }
?>
