// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, createOrderElement } from './utility.js';

"use strict";

const orderTableElement = document.querySelector('#order-table');
const searchButton = document.querySelector('#order-search-button');
const orderTypeCheckboxes = document.querySelector('#order-type-container').querySelectorAll('input');

orderTypeCheckboxes.forEach((input) => {
    input.addEventListener('input', (e) => {
        orderTypeCheckboxes.forEach(checkbox => {
            if(e.target != checkbox) checkbox.checked = false;
        })
    })
});

searchButton.addEventListener('click', (e) => {
    while (orderTableElement.firstChild) {
        orderTableElement.removeChild(orderTableElement.firstChild);
    }

    let startDate = document.querySelector('#start_date').value;
    let endDate = document.querySelector('#end_date').value;
    let startAmount = document.querySelector('#start_amount').value;
    let endAmount = document.querySelector('#end_amount').value;
    let firstName = document.querySelector('#name_first').value;
    let lastName = document.querySelector('#name_last').value;
    let email = document.querySelector('#email').value;
    let phoneNumber = document.querySelector('#phone_number').value;
    let orderType = null;
    orderTypeCheckboxes.forEach(checkbox => {
        if(checkbox.checked){
            orderType = checkbox.value;
        }
    })
    startAmount = (startAmount === "") ? null : startAmount * 100;
    endAmount = (endAmount === "") ? null : endAmount * 100;

    let url = '/Dashboard/searchOrders';
    let json = {
        'start_date' : startDate,
        'end_date' : endDate,
        'start_amount' : startAmount,
        'end_amount' : endAmount,
        'first_name' : firstName,
        'last_name' : lastName,
        'email' : email,
        'phone_number' : phoneNumber,
        'order_type' : orderType
    };

    postJSON(url, json).then(response => response.json()).then(orders => {
        orders.forEach(order => {
            const orderElement = createOrderElement(order);
            
            let userInfo = order.user_info;
            if(userInfo){
                let nameElement = document.createElement('div');
                nameElement.classList.add('order-name');
                nameElement.innerText = userInfo.name_first + ' ' + userInfo.name_last;
                orderElement.prepend(nameElement);
            }

            let dateElement = document.createElement('div');
            dateElement.innerText = order.date;
            
            orderElement.prepend(dateElement);

            orderElement.addEventListener('click', (e) => {
                window.open(`/Dashboard/orders?uuid=${order.uuid}`);
            });

            orderTableElement.appendChild(orderElement);
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