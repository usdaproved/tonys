import { postJSON } from './utility.js';

"use strict";

const STATUS_ARRAY = ['cart','submitted','preparing','prepared',
                      'delivering','pay','delivered','complete','paid'];
const CSRFToken = document.querySelector('#CSRFToken').value;
const orderTable = document.querySelector('#order-table');

const getOrderList = () => {
    // TODO(trystan): Might want to create some utility function for regular GET requests.
    const getOrdersURL = window.location.origin + '/Dashboard/orders/getOrders';
    fetch(getOrdersURL).then(response => response.json()).then(orders => {
        console.log(orders);
        for(var order in orders){
            if(orderTable.querySelector(`#order-${orders[order].id}`) === null){
                // New order. Create new entry.
                let orderContainer = document.createElement('div');
                orderContainer.classList.add('order-container');
                orderContainer.id = `order-${orders[order].id}`;

                const lineItems = orders[order].line_items;
                for(var lineItem in lineItems){
                    let lineItemContainer = document.createElement('div');
                    lineItemContainer.classList.add('line-item-container');

                    let lineItemName = document.createElement('h3');
                    lineItemName.innerText = lineItems[lineItem].name;

                    lineItemContainer.appendChild(lineItemName);

                    let choices = lineItems[lineItem].choices;
                    for(var choice in choices){
                        let choiceName = document.createElement('h5');
                        choiceName.innerText = choices[choice].name;

                        lineItemContainer.appendChild(choiceName);

                        let options = choices[choice].options;
                        for(var option in options){
                            let optionName = document.createElement('p');
                            optionName.innerText = options[option].name;

                            lineItemContainer.appendChild(optionName);
                        }
                    }

                    let additions = lineItems[lineItem].additions;
                    if(Object.entries(additions).length){
                        let additionHeader = document.createElement('h5');
                        additionHeader.innerText = 'Additions';

                        lineItemContainer.appendChild(additionHeader);
                    }
                    for(var addition in additions){
                        let additionName = document.createElement('p');
                        additionName.innerText = additions[addition].name;

                        lineItemContainer.appendChild(additionName);
                    }

                    orderContainer.appendChild(lineItemContainer);
                }

                orderTable.appendChild(orderContainer);
            } else {
                // remove if order status is a completed or picked up one.
                if(parseInt(orders[order].status) > 5){
                    let orderElement = document.querySelector(`#order-${orders[order].id}`);
                    orderElement.remove();
                    return;
                }
            }
        }
    });
};
getOrderList();

const fetchInterval = 10000;
setInterval(getOrderList, fetchInterval);

/*
var evtSource = new EventSource('/Dashboard/orders/stream');
evtSource.onopen = function() {
  
};
evtSource.onmessage = function(e) {
  
};
evtSource.onerror = function() {
  
};
*/