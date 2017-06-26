'use strict';

var amount = flw_payment_args.amount,
    cbUrl  = flw_payment_args.cb_url,
    country = flw_payment_args.country,
    curr   = flw_payment_args.currency,
    desc   = flw_payment_args.desc,
    email  = flw_payment_args.email,
    form   = jQuery( '#flw-pay-now-button' ),
    p_key  = flw_payment_args.p_key,
    title  = flw_payment_args.title,
    txref  = flw_payment_args.txnref,
    paymentMethod  = flw_payment_args.payment_method,
    redirect_url;

if ( form ) {

  form.on( 'click', function( evt ) {
    evt.preventDefault();
    processPayment();
  } );

}

var processPayment = function() {

  getpaidSetup({
    amount: amount,
    country: country,
    currency: curr,
    custom_description: desc,
    custom_title: title,
    customer_email: email,
    txref: txref,
    payment_method: paymentMethod,
    PBFPubKey: p_key,
    onclose: function(){
      if (redirect_url) {
        redirectTo( redirect_url );
      }
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
      setTimeout( redirectTo, 5000, redirect_url );
    } );
};

var redirectTo = function( url ) {
  location.href = url;
};
