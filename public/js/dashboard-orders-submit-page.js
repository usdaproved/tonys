// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, initSearchUsersComponent } from './utility.js';

"use strict";

const submitButton = document.querySelector('#submit-order-button');
let selectedUserUUID = null;

initSearchUsersComponent((e) => {
    let container = e.target.closest('.order-container');
    if(container.classList.contains('selected')){
        container.classList.remove('selected');
        selectedUserUUID = null;
        submitButton.value = "Submit Order";
        return;
    }
    container.closest('.orders-container')
    .querySelectorAll('.order-containers')
    .forEach(container => {
        container.classList.remove('selected');
    });
    container.classList.add('selected');
    selectedUserUUID = container.id;
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

