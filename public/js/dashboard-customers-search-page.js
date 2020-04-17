import { postJSON, createOrderElement } from './utility.js';

"use strict";

let CSRFToken = document.querySelector('#CSRFToken').value;
const userTableElement = document.querySelector('#user-table');
const searchButton = document.querySelector('#user-search-button');

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
        users.forEach(user => {
            let userContainer = document.createElement('div');
            userContainer.classList.add('order-container');

            let userName = document.createElement('div')
            userName.innerText = user.name_first + ' ' + user.name_last;

            userContainer.appendChild(userName);

            let userEmail = document.createElement('div');
            userEmail.innerText = user.email;

            userContainer.appendChild(userEmail);

            let userNumber = document.createElement('div');
            userNumber.innerText = user.phone_number;

            userContainer.appendChild(userNumber);

            userContainer.addEventListener('click', (e) => {
                window.open(`/Dashboard/customers?id=${user.id}`);
            });

            userTableElement.appendChild(userContainer);
        });
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