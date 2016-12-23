'use strict';

var amount  = flw_payment_args.amount;
var cbUrl   = flw_payment_args.cb_url;
var email   = flw_payment_args.email;
var form    = jQuery( '#flw-pay-now-button' );
var p_key   = flw_payment_args.p_key;
var txref   = flw_payment_args.txnref;
var redirect_url;

if ( form ) {

  form.on( 'click', function( evt ) {
    evt.preventDefault();
    processPayment();
  } );

}

var processPayment = function() {

  getpaidSetup({
    customer_email: email,
    amount: amount,
    txref: txref,
    PBFPubKey: p_key,
    onclose: function(){
      location.href = redirect_url;
    },
    callback: function(d){
      sendPaymentRequestResponse( d );
    }
  });

};

var sendPaymentRequestResponse = function( res ) {
  jQuery
    .post( cbUrl, res.tx )
    .success( function(data) {
      var response = JSON.parse( data );
      redirect_url = response.redirect_url;
    } );
};
