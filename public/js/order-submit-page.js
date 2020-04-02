import { postJSON } from './utility.js';

"use strict";

let CSRFToken = document.querySelector('#CSRFToken').value;

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
const orderID = submitButton.dataset.orderid;

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
        // Show a success message to your customer
        // There's a risk of the customer closing the window before callback
        // execution. Set up a webhook or plugin to listen for the
        // payment_intent.succeeded event that handles any business critical
        // post-payment actions.

        // TODO(Trystan): Show some success while this waits for confirmation.
        for(let attempts = 0; attempts < 3; attempts++){
          let url = '/Order/checkOrderConfirmation';
          let json = {'order_id' : orderID};
          postJSON(url, json, CSRFToken).then(response => response.text()).then(result => {
            if(result === 'confirmed'){
              window.location.replace(`/Order/confirmed?order=${orderID}`);
            }
          });
          
        }
        // Show some type of error message.
        // 'confirmation timed out.'
      }
    }
  });
});

// PAYPAL
paypal.Buttons({
  createOrder: function() {
    return fetch('/Order/paypalCreateOrder', {
      method: 'post',
      headers: {
        'content-type': 'application/json'
      }
    }).then(function(res) {
      return res.json();
    }).then(function(myServerResponse) {
      return myServerResponse.paypalID;
    });
  },
  onApprove: function(paypalData) {
    return fetch('/Order/paypalCaptureOrder', {
      method: 'post',
      headers: {
        'content-type': 'application/json'
      },
      body: JSON.stringify({
        paypalID: paypalData.orderID
      })
    }).then(function(res) {
      return res.json();
    }).then(function(details) {
      window.location.replace(`/Order/confirmed?order=${orderID}`);
    })
  }
}).render('#paypal-button-container');