// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON } from './utility.js';

"use strict";

const deliveryStatusCheckbox = document.querySelector('#delivery-on');
deliveryStatusCheckbox.addEventListener('click', (e) => {
    let currentStatus = !e.target.checked;
    // We don't want the input to actually change until the user has confirmed it.
    e.target.checked = currentStatus;
    let message = (currentStatus) ? 'off' : 'on';
    let confirmed = confirm(`Do you wish to turn ${message} deliveries?`);
    if(confirmed){
        let status = !currentStatus;
        e.target.checked = status;
        let json = {'status' : status};
        const url = '/Dashboard/settings/updateDeliveryStatus';
        postJSON(url, json).then(response => response.text()).then(result => {
            
        });
    }
});

const pickupStatusCheckbox = document.querySelector('#pickup-on');
pickupStatusCheckbox.addEventListener('click', (e) => {
    let currentStatus = !e.target.checked;
    // We don't want the input to actually change until the user has confirmed it.
    e.target.checked = currentStatus;
    let message = (currentStatus) ? 'off' : 'on';
    let confirmed = confirm(`Do you wish to turn ${message} pickups?`);
    if(confirmed){
        let status = !currentStatus;
        e.target.checked = status;
        let json = {'status' : status};
        const url = '/Dashboard/settings/updatePickupStatus';
        postJSON(url, json).then(response => response.text()).then(result => {
            
        });
    }
});