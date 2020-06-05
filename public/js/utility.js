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

    let additions = lineItem.additions;
    if(additions.length != 0){
        let additionsText = document.createTextNode('Additions');

        lineItemContainer.appendChild(additionsText);

        let additionsContainer = document.createElement('ul');
        additionsContainer.classList.add('additions-container');

        for(var addition in additions){
            let additionElement = document.createElement('li');
            additionElement.classList.add('line-item-addition');
            additionElement.innerText = `${additions[addition].name}`;

            additionsContainer.appendChild(additionElement);
        }
        lineItemContainer.appendChild(additionsContainer);
    }

    return lineItemContainer;
};

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

export { postJSON, createLineItemElement, createOrderElement, 
         STATUS_ARRAY, intToCurrency, initSearchUsersComponent,
         SingleContainerSelector }