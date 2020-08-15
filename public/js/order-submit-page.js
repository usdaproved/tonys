// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

import { postJSON } from './utility.js';

"use strict";

let CSRFToken = document.querySelector('#CSRFToken').value;
const addressContainer = document.querySelector('#address-select-container');
const changeAddressButton = document.querySelector('#change-address-button');

if(addressContainer){
  changeAddressButton.addEventListener('click', (e) => {
    addressContainer.hidden = !addressContainer.hidden;
    if(addressContainer.hidden){
      changeAddressButton.classList.remove('cancel');
      changeAddressButton.value = 'Change';
    } else {
      changeAddressButton.classList.add('cancel');
      changeAddressButton.value = 'Cancel';
    }
  });

  const addressSelected = (e) => {
    if(e.type === 'keydown' && e.keyCode !== 13){
      return;
    }

    e.preventDefault();

    let container = e.target.closest('.order-container');
    if(!container){
      container = e.target.querySelector('.order-container');
    }
    let addressUUID = container.id;
  
    // Send post request to set delivery id to the selected one.
    // hide the address containers upon success and update the delivery address.
    const url = '/Order/submit/setDeliveryAddress';
    let json = {'address_uuid':addressUUID};
    postJSON(url, json, CSRFToken).then(response => response.text()).then(result => {
      if(result === 'success'){
        location.reload();
      }
    });
  };

  addressContainer.querySelectorAll('.event-wrapper').forEach(container => {
    container.addEventListener('click', addressSelected);
    container.addEventListener('keydown', addressSelected);
  });
}

// TODO: This is the test key. Gotta be sure to update with the actual information.
const STRIPE_PUBLIC_KEY = "pk_test_olXR5p3L8x6QCOlVwe4GthK6004qkU4Loa"; 

let stripe = Stripe(STRIPE_PUBLIC_KEY);
let stripeElements = stripe.elements();

let stripeStyling = {
  base: {
      color: "#32325d",
      fontWeight: 500,
      fontSize: "16px",
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

  submitButton.disabled = true;

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
      // Allow the customer to make necessary changes and resubmit.
      submitButton.disabled = false;
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