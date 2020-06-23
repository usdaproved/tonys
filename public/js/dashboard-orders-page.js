// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, intToCurrency } from './utility.js';

"use strict";

const refundButtons = document.querySelectorAll('.refund-button');

refundButtons.forEach(button => button.addEventListener('click', (e) => {
    const container = e.target.closest('.payment-container');
    let refundValue = container.querySelector('input[type="number"]').value;
    if(!refundValue || parseFloat(refundValue) === 0) return;
    refundValue = parseInt(refundValue * 100);

    let paymentID = e.target.dataset.paymentId;

    const refundTotalElement = container.querySelector('.refund-total');
    let refundTotal = parseInt(refundTotalElement.innerText * 100);
    const paymentAmount = parseInt(container.querySelector('.payment-amount').innerText * 100);

    // TODO(Trystan): Display some error messages: can't refund more than payment amount.
    if((paymentAmount - refundTotal) < refundValue) return;

    let confirmed = confirm(`Initiate a refund of ${intToCurrency(refundValue.toString())}?`);
    if(confirmed){
        const url = "/Dashboard/orders/refund";
        let json = {
            "payment_id": paymentID,
            "amount": refundValue
        };
        postJSON(url, json).then(response => response.text()).then(result => {
            refundTotal = parseInt(refundTotalElement.innerText * 100) + refundValue
            refundTotalElement.innerText = intToCurrency(refundTotal.toString());

            if(refundTotal === paymentAmount){
                // remove refund input and button.
                container.querySelectorAll('input').forEach(input => input.remove());
            }
        });
    }
}));