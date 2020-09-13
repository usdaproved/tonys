// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, createOrderElement } from './utility.js';

"use strict";

const orderTableElement = document.querySelector('#order-table');
const searchButton = document.querySelector('#order-search-button');
const orderTypeCheckboxes = document.querySelector('#order-type-container').querySelectorAll('button');

let currentSearch = null;

orderTypeCheckboxes.forEach((button) => {
    button.addEventListener('click', (e) => {
        orderTypeCheckboxes.forEach(checkbox => {
            // If we are turning the same checkbox off.
            let doubleClick = false;
            if(!checkbox.classList.contains('inactive')){
                checkbox.classList.add('inactive');
                doubleClick = true;
            }
            if(e.target.closest('button') == checkbox){
                if(!doubleClick){
                    checkbox.classList.remove('inactive');
                }   
            }
        });
    });
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
        if(!checkbox.classList.contains('inactive')){
            if(checkbox.id === 'checkbox-delivery'){
                orderType = 0;
            } else if(checkbox.id === 'checkbox-pickup'){
                orderType = 1;
            } else {
                orderType = 2;
            }
        }
    });
    startAmount = (startAmount === "") ? null : startAmount * 100;
    endAmount = (endAmount === "") ? null : endAmount * 100;

    let url = '/Dashboard/searchOrders';
    currentSearch = {
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

    postJSON(url, currentSearch).then(response => response.json()).then(orders => {
        orders.forEach(order => {
            // Format these orders differently.
            // Probably just want to get basicOrderInfo
            let orderElement = document.createElement('button');
            orderElement.classList.add('search-result');
            orderElement.classList.add('svg-button');
            
            let userInfo = order.user_info;
            if(userInfo){
                let nameElement = document.createElement('div');
                nameElement.classList.add('search-result-name');
                nameElement.innerText = userInfo.name_first + ' ' + userInfo.name_last;
                orderElement.append(nameElement);
            }

            let dateElement = document.createElement('div');
            dateElement.classList.add('search-result-date');
            dateElement.innerText = order.date;
            
            orderElement.append(dateElement);

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