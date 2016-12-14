'use strict';

var amount    = flw_payment_args.amount;
var email     = flw_payment_args.email;
var p_key     = flw_payment_args.p_key;
var payNowBtn = document.getElementById( 'flw-pay-now-button' );
var txref     = flw_payment_args.txnref;

payNowBtn.addEventListener( 'click', function( evt ) {
  evt.preventDefault();
  processCheckout();
} );

function processCheckout() {

  getpaidSetup({
    customer_email: email,
    amount: amount,
    txref: txref,
    PBFPubKey: p_key,
    onclose:function(){
      console.log(this);
    },
    callback:function(d){
      console.log(d);
    }
  });

};
