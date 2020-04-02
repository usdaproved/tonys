import { postJSON } from './utility.js';

"use strict";

let CSRFToken = document.querySelector('#CSRFToken').value;

const userTableElement = document.querySelector('#user-table');

const insertUsers = (users) => {
    for(var user in users){
        let userContainer = document.createElement('div');
        
        let userSelect = document.createElement('input');
        userSelect.type = 'radio';
        userSelect.name = 'user_id';
        userSelect.value = users[user].email;

        userContainer.appendChild(userSelect);

        let userInfo = document.createElement('span')
        userInfo.innerText = users[user].name_first + ' ' + users[user].name_last;

        userContainer.appendChild(userInfo);

        userTableElement.appendChild(userContainer);
    }
};

const searchButton = document.querySelector('#customer-search-button');

searchButton.addEventListener('click', (e) => {
    while (userTableElement.firstChild) {
        userTableElement.removeChild(userTableElement.firstChild);
    }

    let firstName = document.querySelector('#name_first').value;
    let lastName = document.querySelector('#name_last').value;
    let email = document.querySelector('#email').value;
    let phoneNumber = document.querySelector('#phone_number').value;

    let url = '/Dashboard/searchUsers';
    let json = {
        'first_name' : firstName,
        'last_name' : lastName,
        'email' : email,
        'phone_number' : phoneNumber
    };

    postJSON(url, json, CSRFToken).then(response => response.json()).then(users => {
        insertUsers(users);
    });
});

const searchFilterInputs = document.querySelector('#search-filters').querySelectorAll('input');

searchFilterInputs.forEach(input => {
    input.addEventListener('keyup', (e) => {
        if(e.keyCode === 13){
            searchButton.click();
        }
    });
});

const submitButton = document.querySelector('#submit-order-button');

submitButton.addEventListener('click', (e) => {
    // 'null' is the default value.
    let customerEmail;
    let form = document.querySelector('#user-ids');
    form.querySelectorAll('input').forEach(radio => {
        if(radio.checked){
            customerEmail = radio.value;
        }
    });

    if(customerEmail === 'null') customerEmail = null;
    let url = '/Dashboard/orders/submit';
    let json = {'customer_email' : customerEmail};

    postJSON(url, json, CSRFToken).then(response => response.text()).then(result => {
        if(result === 'submitted'){
            // TODO(Trystan): Decide where we want to go after submission.
            // at the moment we just push a message saying success.
            window.location.replace(`/Dashboard`);
        }
    });
});

