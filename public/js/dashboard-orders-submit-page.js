// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, initSearchUsersComponent } from './utility.js';

"use strict";

const defaultCustomerText = "No customer selected.";

let delivery

const submitButton = document.querySelector('#submit-order-button');
const customerTextElement = document.querySelector('#customer-name-text');
let selectedUserUUID = null;

initSearchUsersComponent((e) => {
    let container = e.target.closest('.search-result');
    if(container.classList.contains('selected')){
        container.classList.remove('selected');
        selectedUserUUID = null;
        customerTextElement.innerText = defaultCustomerText;
        return;
    }
    container.closest('.search-result-container')
    .querySelectorAll('.search-result')
    .forEach(container => {
        container.classList.remove('selected');
    });
    container.classList.add('selected');
    selectedUserUUID = container.id;
    let name = container.querySelector('.search-result-name').innerText;
    customerTextElement.innerText = name;
});

document.querySelector('#user-search-button').addEventListener('click', (e) => {
    selectedUserUUID = null;
});

submitButton.addEventListener('click', (e) => {
    // 'null' is the default value.
    let url = '/Dashboard/orders/submit';
    let json = {'user_uuid' : selectedUserUUID};

    postJSON(url, json).then(response => response.text()).then(result => {
        if(result === 'success'){
            window.location.replace(`/Order`);
        }
    });
});

