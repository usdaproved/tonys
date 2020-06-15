import { postJSON } from './utility.js';

"use strict";

let CSRFToken = document.querySelector('#CSRFToken').value;
const addressContainer = document.querySelector('#address-select-container');
const changeAddressButton = document.querySelector('#change-address');

if(addressContainer){
  changeAddressButton.addEventListener('click', (e) => {
    addressContainer.hidden = !addressContainer.hidden;
    if(addressContainer.hidden){
      changeAddressButton.value = 'Change';
    } else {
      changeAddressButton.value = 'Cancel';
    }
  });

  addressContainer.querySelectorAll('.order-container').forEach(container => {
    container.addEventListener('click', (e) => {
      let container = e.target.closest('.order-container');
      let addressUUID = container.id;
    
      // Send post request to set delivery id to the selected one.
      // hide the address containers upon success and update the delivery address.
      const url = '/Order/submit/setDeliveryAddress';
      let json = {'address_uuid':addressUUID};
      postJSON(url, json, CSRFToken).then(response => response.text()).then(result => {
        if(result === 'success'){
          addressContainer.hidden = true;
          location.reload();
        }
      });
    });
  });
}

// This code must be structured in such a way
// that one of multiple payment processors will be used
// The customer will decide which one they want to use.
// All payment solutions are routed through the same submit button.
// Or perhaps we will have to wait and see how using paypal changes things.

// TODO: This is the test key. Gotta be sure to update with the actual information.
const STRIPE_PUBLIC_KEY = "pk_test_olXR5p3L8x6QCOlVwe4GthK6004qkU4Loa"; 

let stripe = Stripe(STRIPE_PUBLIC_KEY);
let stripeElements = stripe.elements();

let stripeStyling = {
  base: {
      color: "#32325d",
  }
};

let stripeCard = stripeElements.create("card", {style: stripeStyling});
stripeCard.mount("#stripe-card-element");


// Copy and pasted from the stripe website.
// Obviously modification will have to be done in order
// to allow for multiple processors and for our own info to go through as well.

stripeCard.addEventListener('change', ({error}) => {
  const displayError = document.getElementById('stripe-card-errors');
  if (error) {
    displayError.textContent = error.message;
  } else {
    displayError.textContent = '';
  }
});

  

const name_first = document.querySelector('#name_first').innerText;
const name_last = document.querySelector('#name_last').innerText;
  
const submitButton = document.querySelector('#stripe-payment-submit');
const clientSecret = submitButton.dataset.secret;
const orderUUID = submitButton.dataset.orderuuid;

let intervalID = null;

const checkOrderConfirmation = () => {
  let url = '/Order/submit/checkOrderConfirmation';
  let json = {'order_uuid' : orderUUID};
  postJSON(url, json, CSRFToken).then(response => response.text()).then(result => {
    if(result === 'confirmed'){
      clearInterval(intervalID);
      window.location.replace(`/Order/confirmed?order=${orderUUID}`);
    }
  });
};

submitButton.addEventListener('click', function(e) {
  e.preventDefault();

  stripe.confirmCardPayment(clientSecret, {
    payment_method: {
      card: stripeCard,
      billing_details: {
        // TODO(Trystan): We should probably put all relevant customer info in here.
        // That way we can collect all info from the webhook. I'm thinking that's how it works.
        name: name_first + name_last // TODO(trystan): Check if these inputs need sanitized.
      }
    }
  }).then(function(result) {
    if (result.error) {
      // Show error to your customer (e.g., insufficient funds)
      console.log(result.error.message);
    } else {
      // The payment has been processed!
      if (result.paymentIntent.status === 'succeeded') {
        // TODO(Trystan): Show some success while this waits for confirmation.
        // disable all inputs.
        intervalID = setInterval(checkOrderConfirmation, 2000);
      }
    }
  });
});

// COMMENTED OUT FOR VIEWING PURPOSES
// PAYPAL
// paypal.Buttons({
//   createOrder: function() {
//     return fetch('/Order/paypalCreateOrder', {
//       method: 'post',
//       headers: {
//         'content-type': 'application/json'
//       }
//     }).then(function(res) {
//       return res.json();
//     }).then(function(myServerResponse) {
//       return myServerResponse.paypalID;
//     });
//   },
//   onApprove: function(paypalData) {
//     return fetch('/Order/paypalCaptureOrder', {
//       method: 'post',
//       headers: {
//         'content-type': 'application/json'
//       },
//       body: JSON.stringify({
//         paypalID: paypalData.orderID
//       })
//     }).then(function(res) {
//       return res.json();
//     }).then(function(details) {
//       window.location.replace(`/Order/confirmed?order=${orderID}`);
//     })
//   }
// }).render('#paypal-button-container');