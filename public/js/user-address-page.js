// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, SingleContainerSelector } from './utility.js';

"use strict";

let selectedAddressUUID = null;

const setDefaultButton = document.querySelector('#set-default-button');
const deleteButton = document.querySelector('#delete-button');
const addressContainers = document.querySelector('.addresses-container').querySelectorAll('.selectable-address');

addressContainers.forEach(addressContainer => {
    addressContainer.addEventListener('click', (e) => {
        // If we are unselecting the current one.
        let deselection = addressContainer.classList.contains('active');
        addressContainers.forEach(container => {
            container.classList.remove('active');
        });
        selectedAddressUUID = null;

        if(deselection){
            deleteButton.classList.add('inactive');
            setDefaultButton.classList.add('inactive');
            deleteButton.disabled = true;
            setDefaultButton.disabled = true;
            return;
        }

        addressContainer.classList.add('active');
        selectedAddressUUID = addressContainer.id;
        deleteButton.classList.remove('inactive');
        setDefaultButton.classList.remove('inactive');
        deleteButton.disabled = false;
        setDefaultButton.disabled = false;
    });
});

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