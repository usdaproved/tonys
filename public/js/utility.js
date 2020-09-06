// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
"use strict";

const CSRFToken = document.querySelector('#CSRFToken').value;

const postJSON = (url, json) => {
    // TODO(trystan): Handle the repsonse better here.
    // in case something goes wrong. Then return the results of
    // the response.
    url = window.location.origin + url;
    json["CSRFToken"] = CSRFToken;
    const data = JSON.stringify(json);
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: data
    });
};

const createLineItemElement = (lineItem) => {
    let lineItemContainer = document.createElement('li');
    lineItemContainer.classList.add('line-item');
    lineItemContainer.id = `${lineItem.uuid}`;

    let itemQuantitySpan = document.createElement('span');
    itemQuantitySpan.classList.add('line-item-quantity');
    itemQuantitySpan.innerText = lineItem.quantity;

    lineItemContainer.appendChild(itemQuantitySpan);

    let lineItemName = document.createTextNode(` ${lineItem.name}`);

    lineItemContainer.appendChild(lineItemName);

    let choices = lineItem.choices;
    for(var choice in choices){
        let choiceContainer = document.createElement('div');
        choiceContainer.classList.add('line-item-choice');

        let choiceName = document.createTextNode(`${choices[choice].name}`);
        
        choiceContainer.appendChild(choiceName);

        let optionsContainer = document.createElement('ul');
        optionsContainer.classList.add('options-container');

        let options = choices[choice].options;
        for(var option in options){
            let optionElement = document.createElement('li');
            optionElement.classList.add('line-item-option');
            optionElement.innerText = options[option].name;

            optionsContainer.appendChild(optionElement);
        }
        choiceContainer.appendChild(optionsContainer);
        lineItemContainer.appendChild(choiceContainer);
    }

    return lineItemContainer;
};

const createLineItemForCart = (lineItem) => {
    let lineItemContainer = document.createElement('li');
    lineItemContainer.classList.add('line-item');
    lineItemContainer.id = `${lineItem.uuid}`;

    let lineItemInfo = document.createElement('div');
    lineItemInfo.classList.add('line-item-info');

    let itemQuantitySpan = document.createElement('span');
    itemQuantitySpan.classList.add('line-item-quantity');
    itemQuantitySpan.innerText = lineItem.quantity;

    lineItemInfo.appendChild(itemQuantitySpan);

    let lineItemName = document.createElement('span');
    lineItemName.classList.add('line-item-name');
    lineItemName.innerText = lineItem.name;

    lineItemInfo.appendChild(lineItemName);

    let lineItemPrice = document.createElement('span');
    lineItemPrice.classList.add('line-item-price');
    lineItemPrice.innerText = '$';

    let lineItemPriceValue = document.createElement('span');
    lineItemPriceValue.classList.add('line-item-price-value');
    lineItemPriceValue.innerText = intToCurrency(lineItem.price);

    lineItemPrice.appendChild(lineItemPriceValue);

    lineItemInfo.appendChild(lineItemPrice);

    lineItemContainer.appendChild(lineItemInfo);

    let lineItemChoicesContainer = document.createElement('div');
    lineItemChoicesContainer.classList.add('line-item-choices-container');

    let choices = lineItem.choices;
    for(var choice in choices){
        let choiceContainer = document.createElement('div');
        choiceContainer.classList.add('line-item-choice-container');

        let choiceName = document.createElement('span');
        choiceName.classList.add('line-item-choice-name');
        choiceName.innerText = choices[choice].name;
        
        choiceContainer.appendChild(choiceName);

        let optionsContainer = document.createElement('ul');
        optionsContainer.classList.add('line-item-options-container');

        let options = choices[choice].options;
        for(var option in options){
            let optionElement = document.createElement('li');
            optionElement.classList.add('line-item-option');
            optionElement.innerText = options[option].name;

            optionsContainer.appendChild(optionElement);
        }
        choiceContainer.appendChild(optionsContainer);
        lineItemChoicesContainer.appendChild(choiceContainer);
    }

    lineItemContainer.appendChild(lineItemChoicesContainer);

    let lineItemComment = document.createElement('div');
    lineItemComment.classList.add('line-item-comment');
    lineItemComment.innerText = lineItem.comment;

    lineItemContainer.appendChild(lineItemComment);

    return lineItemContainer;
}

const createOrderElement = (order) => {
    let orderContainer = document.createElement('div');
    orderContainer.classList.add('order-container');
    orderContainer.id = `${order.uuid}`;

    const lineItems = order.line_items;
    for(var lineItem in lineItems){
        let lineItemElement = createLineItemElement(lineItems[lineItem]);
        orderContainer.appendChild(lineItemElement);
    }

    return orderContainer;
};

const createDetailedOrderElement = (order) => {
    let orderContainer = document.createElement('div');
    orderContainer.classList.add('order-container');
    orderContainer.id = `${order.uuid}`;

    const lineItems = order.line_items;
    for(var lineItem in lineItems){
        let lineItemElement = createLineItemForCart(lineItems[lineItem]);
        orderContainer.appendChild(lineItemElement);
    }

    return orderContainer;
}

const initSearchUsersComponent = (callback) => {
    const button = document.querySelector('#user-search-button');
    const table = document.querySelector('#user-table');
    
    button.addEventListener('click', (e) => {
        while (table.firstChild) {
            table.removeChild(table.firstChild);
        }
    
        let firstName = document.querySelector('#name_first').value;
        let lastName = document.querySelector('#name_last').value;
        let email = document.querySelector('#email').value;
        let phoneNumber = document.querySelector('#phone_number').value;
        let registeredOnly = document.querySelector('#registered-only').checked;
    
        let url = '/Dashboard/searchUsers';
        let json = {
            'first_name' : firstName,
            'last_name' : lastName,
            'email' : email,
            'phone_number' : phoneNumber,
            'registered' : registeredOnly
        };
    
        postJSON(url, json).then(response => response.json()).then(users => {
            if(!users) return;
            users.forEach(user => {
                let userContainer = document.createElement('div');
                userContainer.id = user.uuid;
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
    
                userContainer.addEventListener('click', callback);
    
                table.appendChild(userContainer);
            });
        });
    });

    const searchFilterInputs = document.querySelector('#search-filters').querySelectorAll('input');

    searchFilterInputs.forEach(input => {
        input.addEventListener('keyup', (e) => {
            if(e.keyCode === 13){
                button.click();
            }
        });
    });
};

class SingleContainerSelector {
    constructor(containerList, buttonList){
        this.selectedContainerUUID = null;
        this.containerList = containerList;
        this.buttonList = buttonList;
        this.containerList.forEach(container => {
            container.addEventListener('click', (e) => {
                let container = e.target.closest('.order-container');
                if(container.classList.contains('selected')){
                    container.classList.remove('selected');
                    this.selectedContainerUUID = null;
                    this.buttonList.forEach(button => button.hidden = true);
                    return;
                }
                this.containerList.forEach(container => container.classList.remove('selected'));
                container.classList.add('selected');
                this.selectedContainerUUID = container.id;
                this.buttonList.forEach(button => button.hidden = false);
            })
        });
    }

    get selectedUUID() {
        // Whenever we grab the value, we want to clear it.
        this.containerList.forEach(container => container.classList.remove('selected'));
        this.buttonList.forEach(button => button.hidden = true);
        let result = this.selectedContainerUUID;
        this.selectedContainerUUID = null;
        return result;
    }
}

const STATUS_ARRAY = ['cart','submitted','preparing','prepared','delivering','complete'];

const intToCurrency = (price) => {
    if(typeof price === "number"){
        price = price.toString();
    }
    let leadingZero = '';
    if(price < 100) leadingZero = '0';
    return leadingZero + price.slice(0, -2) + '.' + price.slice(-2);
};

export { postJSON, createLineItemElement, createLineItemForCart,
         createOrderElement, createDetailedOrderElement, STATUS_ARRAY, intToCurrency, 
         initSearchUsersComponent, SingleContainerSelector }