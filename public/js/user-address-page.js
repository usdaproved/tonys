import { postJSON, SingleContainerSelector } from './utility.js';

"use strict";

let selectedAddressUUID = null;

const setDefaultButton = document.querySelector('#set-default-button');
const deleteButton = document.querySelector('#delete-button');
const addressContainers = document.querySelector('.orders-container').querySelectorAll('.order-container');

let addressSelector = new SingleContainerSelector(addressContainers, [setDefaultButton, deleteButton]);
setDefaultButton.addEventListener('click', (e) => {
    let addressUUID = addressSelector.selectedUUID;

    let url = '/User/address/setDefault';
    let json = {'address_uuid':addressUUID};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});

deleteButton.addEventListener('click', (e) => {
    let addressUUID = addressSelector.selectedUUID;

    let url = '/User/address/delete';
    let json = {'address_uuid':addressUUID};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});