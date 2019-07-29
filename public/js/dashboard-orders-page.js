"use strict";
const STATUS_ARRAY = ['cart','submitted','preparing','prepared','delivering','delivered','complete'];
const getOrdersURL = window.location.origin + '/Dashboard/getOrders';
const fetchInterval = 10000;
const orderTable = document.querySelector('#order-table');

const createTableElement = (order) => {
    let tableElement = document.createElement('tr');
    tableElement.setAttribute('id', `order-${order['id']}`);
    let lineItemTD = document.createElement('td');
    let ul = document.createElement('ul');

    order['order_line_items'].forEach((lineItem) => {
        let li = document.createElement('li');
        let liInnerText = document.createTextNode(`${lineItem['quantity']} ${lineItem['name']}`);
        li.appendChild(liInnerText);
        ul.appendChild(li);
    });

    lineItemTD.appendChild(ul);
    tableElement.appendChild(lineItemTD);

    let statusTextTD = document.createElement('td');
    statusTextTD.setAttribute('id', `order-status-${order['id']}`);
    let statusText = document.createTextNode(`${STATUS_ARRAY[order['status']]}`);
    statusTextTD.appendChild(statusText);
    tableElement.appendChild(statusTextTD);

    let statusCheckboxTD = document.createElement('td');
    let statusCheckbox = document.createElement('input');
    statusCheckbox.setAttribute('type', 'checkbox');
    statusCheckbox.setAttribute('name', 'status[]');
    statusCheckbox.setAttribute('value', `${order['id']}`);
    statusCheckboxTD.appendChild(statusCheckbox);

    tableElement.appendChild(statusCheckboxTD);

    return tableElement;
};

const fillOrderTable = (ordersJSON) => {
    ordersJSON.forEach(order => {
        if(orderTable.querySelector(`#order-${order['id']}`) === null){
            let tableAddition = createTableElement(order);
            orderTable.appendChild(tableAddition);
        }
    });
};

const fetchOrderList = () => {
    fetch(getOrdersURL).then(response => response.json()).then(result => fillOrderTable(result));
};

fetchOrderList();

setInterval(fetchOrderList, fetchInterval);

const updateStatusText = (ordersJSON) => {
    Object.keys(ordersJSON).forEach((orderID) => {
        if(ordersJSON[orderID] === 5 || ordersJSON[orderID] === 6){
            let orderElement = document.querySelector(`#order-${orderID}`);
            orderElement.remove();
            return;
        }
        let orderStatusText = document.querySelector(`#order-status-${orderID}`);
        orderStatusText.innerHTML = `${STATUS_ARRAY[ordersJSON[orderID]]}`;
    });
};

const formStatusUpdate = document.querySelector('#form-status-update');
formStatusUpdate.addEventListener('submit', event => {
    event.preventDefault();
    

    let formData = new FormData(formStatusUpdate);
    const url = window.location.origin + '/Dashboard/updateOrderStatus';

    formStatusUpdate.reset();

    fetch(url, {
	body: formData,
	method: 'post'
    }).then(response => response.json()).then(result => updateStatusText(result));
});
