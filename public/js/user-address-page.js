import { postJSON } from './utility.js';

"use strict";

const CSRFToken = document.querySelector('#CSRFToken').value;

let selectedAddressID = null;

const setDefaultButton = document.querySelector('#set-default-button');
const addressContainers = document.querySelector('.orders-container').querySelectorAll('.order-container');
const selectAddress = (e) => {
    let container = e.target.closest('.order-container');
    if(container.classList.contains('selected')){
        container.classList.remove('selected');
        selectedAddressID = null;
        setDefaultButton.hidden = true;
        return;
    }
    addressContainers.forEach(container => {
        container.classList.remove('selected');
    });
    container.classList.add('selected');
    selectedAddressID = container.id.split('-')[1];
    setDefaultButton.hidden = false;
};
addressContainers.forEach(container => {
    container.addEventListener('click', selectAddress);
});
setDefaultButton.addEventListener('click', (e) => {
    addressContainers.forEach(container => {
        container.classList.remove('selected');
    });
    if(!selectedAddressID){
        return;
    }

    let url = '/User/address/setDefault';
    let json = {'address_id':selectedAddressID};
    postJSON(url, json, CSRFToken).then(response => response.text()).then(result => {
        location.reload();
    });
});