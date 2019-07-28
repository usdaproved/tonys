"use strict";
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
    let statusText = document.createTextNode(`${order['status']}`);
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
    ordersJSON.forEach(function(element) {
        if(orderTable.querySelector(`#order-${element['id']}`) === null){
            let tableAddition = createTableElement(element);
            orderTable.appendChild(tableAddition);
        }
    });
};

const fetchOrderList = () => {
    fetch(getOrdersURL).then(response => response.json()).then(result => fillOrderTable(result));
};

fetchOrderList();

setInterval(fetchOrderList, fetchInterval);

const formStatusUpdate = document.querySelector('#form-status-update');
formStatusUpdate.addEventListener('submit', event => {
    event.preventDefault();

    let formData = new FormData(formStatusUpdate);
    const url = window.location.origin + '/Dashboard/updateOrderStatus';

    fetch(url, {
	body: formData,
	method: 'post'
    }).then(response => response.text()).then(result => console.log(result));
});
