"use strict";

const postJSON = (url, json, token) => {
    // TODO(trystan): Handle the repsonse better here.
    // in case something goes wrong. Then return the results of
    // the response.
    url = window.location.origin + url;
    json["CSRFToken"] = token;
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
    lineItemContainer.id = `${lineItem.id}-line-item`;

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
    orderContainer.id = `order-${order.id}`;

    const lineItems = order.line_items;
    for(var lineItem in lineItems){
        let lineItemElement = createLineItemElement(lineItems[lineItem]);
        orderContainer.appendChild(lineItemElement);
    }

    return orderContainer;
};

const STATUS_ARRAY = ['cart','submitted','preparing','prepared','delivering','complete'];

const intToCurrency = (price) => {
    let leadingZero = '';
    if(price < 100) leadingZero = '0';
    return leadingZero + price.slice(0, -2) + '.' + price.slice(-2);
};

export { postJSON, createLineItemElement, createOrderElement, STATUS_ARRAY, intToCurrency }