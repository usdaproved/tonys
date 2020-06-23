// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, createOrderElement, STATUS_ARRAY, intToCurrency} from './utility.js';

"use strict";

const deliveryOrdersContainer = document.querySelector('#delivery-orders');
const pickupOrdersContainer = document.querySelector('#pickup-orders');
const restaurantOrdersContainer = document.querySelector('#in-restaurant-orders');
const ORDER_TYPE = ['delivery','pickup','in-restaurant'];

const orderTypeContainers = [deliveryOrdersContainer, pickupOrdersContainer, restaurantOrdersContainer];

let lastReceivedOrderDate = null;
let orderStorage = {};
let orderSelection = [];
let unpaidOrderUUIDs = [];

const addToSelection = (e) => {
    let container = e.target.closest('.order-container');
    let statusElement = container.querySelector('.order-status');
    let orderUUID = container.id;
    let statusIndex = parseInt(orderStorage[orderUUID].status);
    if(container.classList.contains('selected')){
        container.classList.remove('selected');
        const index = orderSelection.indexOf(orderUUID);
        orderSelection.splice(index, 1);
        statusElement.innerText = STATUS_ARRAY[statusIndex];
    } else {
        if(!container.classList.contains('delivery')){
            // If it's not a delivery, skip the 'delivering' status.
            // go straight to complete.
            if(statusIndex === 3) statusIndex = 4;
        }
        container.classList.add('selected');
        orderSelection.push(orderUUID);
        statusElement.innerText = STATUS_ARRAY[statusIndex + 1];
    }
}

const orderComplete = (orderUUID) => {
    let container = document.querySelector(`[id='${orderUUID}']`);
    container.classList.add('completed');
    container.removeEventListener('click', addToSelection);
    container.removeEventListener('click', clickCollect);
    let closeSVG = new Image(24, 24);
    closeSVG.classList.add('info-icon');
    closeSVG.src = '/svg/close-24px.svg';
    container.appendChild(closeSVG);
    container.addEventListener('click', (e) => {
        let container = e.target.closest('.order-container');
        container.remove();
    });
}

// TODO(Trystan): we copied a whole bunch of code from the other page,
// we could probably share it in the utility or something.

const endDialogMode = () => {
    let dialog = document.querySelector('#dialog-container');
    dialog.remove();

    document.body.style.overflow = 'auto';
};

const onCashInput = (e) => {
    // Not sure if we want to grab this every time the cash input is updated. Better to just store once.
    let total = parseFloat(document.querySelector('#total-cost-amount').innerText);
    let changeElement = document.querySelector('#change-due-amount');
    let cashGiven = parseFloat(e.target.value);

    if(cashGiven >= total){
        changeElement.innerText = (cashGiven - total).toFixed(2);
    } else {
        changeElement.innerText = '0.00';
    }
};

const clickCollect = (e) => {
    // TODO(Trystan): Come back to this when we get a stripe reader.
    // For now just collect 'cash' payment.
    let container = e.target.closest('.order-container');
    let orderUUID = container.id;

    let urlGetPaymentInfo = "/Dashboard/orders/active/getPaymentInfo";
    postJSON(urlGetPaymentInfo, {"uuid":orderUUID}).then(response => response.json()).then(info => {
        beginDialogMode(info, orderUUID);
    });
};

const submitCashPayment = (e) => {
    let total = parseFloat(document.querySelector('#total-cost-amount').innerText);
    let orderUUID  = document.querySelector('#dialog-container').dataset.orderUUID;
    let cashGiven = parseFloat(document.querySelector('#cash').value);

    if(cashGiven >= total){
        let url = '/Dashboard/orders/active/submitPayment';
        total = parseInt(total * 100);
        let json = {'uuid':orderUUID, 'amount':total, 'method':0} // 0 = cash, 1 = stripe
        postJSON(url, json).then(response => response.text()).then(result => {

        });

        const unpaidIndex = unpaidOrderUUIDs.indexOf(orderUUID);
        unpaidOrderUUIDs.splice(unpaidIndex, 1);
        let container = document.querySelector(`[id='${orderUUID}']`);
        container.classList.remove('unpaid');
        container.querySelector('.info-icon').remove();
        container.removeEventListener('click', clickCollect);
        orderComplete(orderUUID);
        delete orderStorage[orderUUID];
        updateOrderStatusText(orderUUID, STATUS_ARRAY[5]); // Complete
        endDialogMode();
    } else {
        // show error that cash does not meet requirements.
    }
};

const newDialog = (orderPaymentInfo, orderUUID) => {
    let dialogContainer = document.createElement('div');
    dialogContainer.id = 'dialog-container';
    dialogContainer.dataset.orderUUID = orderUUID;
    dialogContainer.classList.add('dialog-container');
    dialogContainer.addEventListener('click', (e) => {
        if(e.target.id === 'dialog-container'){
            e.preventDefault();
    
            endDialogMode();
        }
    });

    let dialog = document.createElement('div');
    dialog.setAttribute('role', 'dialog');
    dialog.classList.add('dialog');

    let dialogInfoContainer = document.createElement('div');
    dialogInfoContainer.classList.add('dialog-info-container');

    let cost = orderPaymentInfo.cost;
    let fee = parseInt(cost.fee);
    let tax = parseInt(cost.tax);
    let subtotal = parseInt(cost.subtotal);

    let subtotalElement = document.createElement('p');
    subtotalElement.innerText = 'Subtotal: ' + intToCurrency(subtotal);

    dialogInfoContainer.appendChild(subtotalElement);

    if(fee !== 0){
        let feeElement = document.createElement('p');
        feeElement.innerText = 'Fee: ' + intToCurrency(fee);

        dialogInfoContainer.appendChild(feeElement);
    }

    let taxElement = document.createElement('p');
    taxElement.innerText = 'Tax: ' + intToCurrency(tax);

    dialogInfoContainer.appendChild(taxElement);

    let totalCostElement = document.createElement('p');
    totalCostElement.innerText = 'Total: ';
    let totalCostAmount = document.createElement('span');
    totalCostAmount.id = 'total-cost-amount';
    totalCostAmount.innerText = intToCurrency(fee + tax + subtotal);
    totalCostElement.appendChild(totalCostAmount);

    dialogInfoContainer.appendChild(totalCostElement);

    let cashInputLabel = document.createElement('label');
    cashInputLabel.innerText = 'Cash: ';
    cashInputLabel.setAttribute('for', 'cash');

    dialogInfoContainer.appendChild(cashInputLabel);

    let cashInput = document.createElement('input');
    cashInput.id = `cash`;
    cashInput.type = 'number';
    cashInput.setAttribute('step', '0.01');
    cashInput.addEventListener('input', onCashInput);

    dialogInfoContainer.appendChild(cashInput);

    let changeDueElement = document.createElement('p');
    changeDueElement.innerText = 'Change Due: ';
    let changeDueAmount = document.createElement('span');
    changeDueAmount.id = 'change-due-amount';
    changeDueAmount.innerText = '0.00';

    changeDueElement.appendChild(changeDueAmount);

    dialogInfoContainer.appendChild(changeDueElement);

    let submitPaymentButton = document.createElement('button');
    submitPaymentButton.innerText = 'Submit Payment';
    submitPaymentButton.addEventListener('click', submitCashPayment);

    dialogInfoContainer.appendChild(submitPaymentButton);

    dialog.appendChild(dialogInfoContainer);

    dialogContainer.appendChild(dialog);

    return dialogContainer;
};

const beginDialogMode = (orderPaymentInfo, orderUUID) => {
    let dialog = newDialog(orderPaymentInfo, orderUUID);
    document.body.appendChild(dialog);

    document.body.style.overflow = 'hidden';
};

const collectPayment = (orderElement) => {
    orderElement.classList.add('unpaid');
    orderElement.removeEventListener('click', addToSelection);
    let moneySVG = new Image(24, 24);
    moneySVG.classList.add('info-icon');
    moneySVG.src = '/svg/money-24px.svg';
    orderElement.appendChild(moneySVG);
    orderElement.addEventListener('click', clickCollect);
}

const getOrderList = () => {
    const getOrdersURL = '/Dashboard/orders/active/getOrders';
    let getOrdersJson = {"last_received" : lastReceivedOrderDate};
    postJSON(getOrdersURL, getOrdersJson).then(response => response.json()).then(orders => {
        orders.forEach(order => {
            orderStorage[order.uuid] = order;
            const orderElement = createOrderElement(order);
            orderElement.classList.add(ORDER_TYPE[order.order_type]);
            
            // TODO(Trystan): Add any additional info we want to show with orders.
            // Names, addresses, etc.
            let orderType = parseInt(order.order_type);
            if(orderType === 0){
                let addressInfoElement = document.createElement('div');
                let addressLine = document.createElement('div');
                addressLine.innerText = order.address.line;

                addressInfoElement.appendChild(addressLine);
                orderElement.prepend(addressInfoElement);
            }
            
            let userInfo = order.user_info;
            if(userInfo){
                let nameElement = document.createElement('div');
                nameElement.classList.add('order-name');
                nameElement.innerText = userInfo.name_first + ' ' + userInfo.name_last;
                orderElement.prepend(nameElement);
            }

            let statusElement = document.createElement('div');
            statusElement.classList.add('order-status');
            statusElement.innerText = STATUS_ARRAY[parseInt(order.status)];
            orderElement.appendChild(statusElement);

            if(((order.status == 3 && orderType !== 0) 
                || (order.status == 4)) && !order.is_paid){
                collectPayment(orderElement);
            } else {
                orderElement.addEventListener('click', addToSelection);
            }
            
            // NOTE(Trystan): we could prepend here instead, put newest on top.
            orderTypeContainers[order.order_type].appendChild(orderElement);

            if(!order.is_paid){
                unpaidOrderUUIDs.push(order.uuid);
            }

            // The orders are given in ascending order, every new order we get
            // will be the newest by definition. No need to check.
            lastReceivedOrderDate = order.date;
        });
    });
};

getOrderList();

const fetchInterval = 10000;
setInterval(getOrderList, fetchInterval);

const toggleHidden = (element) => {
    element.hidden = !element.hidden;
}

const orderTypeFilters = document.querySelectorAll('#order-type-filters > input');
orderTypeFilters.forEach(filter => {
    filter.addEventListener('change', (e) => {
        switch (e.target.id){
            case 'view-delivery':
                toggleHidden(document.querySelector('#order-type-name-delivery'));
                toggleHidden(deliveryOrdersContainer);
                break;
            case 'view-pickup':
                toggleHidden(document.querySelector('#order-type-name-pickup'));
                toggleHidden(pickupOrdersContainer);
                break;
            case 'view-in-restaurant':
                toggleHidden(document.querySelector('#order-type-name-in-restaurant'));
                toggleHidden(restaurantOrdersContainer);
                break;
        }
    });
    
});

const updateOrderStatusText = (orderUUID, status) => {
    const orderElement = document.querySelector(`[id='${orderUUID}']`);
    const statusText = orderElement.querySelector('.order-status');
    statusText.innerText = status;
};

const getStatus = () => {
    const url = '/Dashboard/orders/active/getStatus';
    let orderUUIDs = [];
    for(var orderUUID in orderStorage){
         orderUUIDs.push(orderUUID);
    }
    let json = {"orderUUIDs" : orderUUIDs}
    postJSON(url, json).then(response => response.json()).then(orders => {
        orders.forEach(order => {
            if(orderStorage[order.uuid].status != order.status){
                orderStorage[order.uuid].status = order.status;
                const selectionIndex = orderSelection.indexOf(order.uuid);
                const container = document.querySelector(`[id='${order.uuid}']`);
                if(selectionIndex != -1){
                    container.classList.remove('selected');
                    orderSelection.splice(selectionIndex, 1);
                }

                container.classList.add('flash');
                setTimeout(() => {container.classList.remove('flash')}, 4000);
                updateOrderStatusText(order.uuid, STATUS_ARRAY[order.status]);
                // See if we need to trigger collectPayment, if it's the status right before
                // the final status then yes. Different for delivery vs pickup/restaurant.
                if(((order.status == 3 && parseInt(orderStorage[order.uuid].order_type) !== 0) 
                    || (order.status == 4)) && !orderStorage[order.uuid].is_paid){
                        let orderElement = document.querySelector(`[id='${order.uuid}']`);
                        collectPayment(orderElement);
                }
                // Check if the order is complete.
                if(order.status == 5){
                    const selectionIndex = orderSelection.indexOf(order.uuid);
                    const container = document.querySelector(`[id='${order.uuid}']`);
                    if(selectionIndex != -1){
                        container.classList.remove('selected');
                        orderSelection.splice(selectionIndex, 1);
                    }
    
                    container.classList.add('flash');
                    setTimeout(() => {container.classList.remove('flash')}, 4000);
                    updateOrderStatusText(order.uuid, 'complete');
    
                    orderComplete(order.uuid);
                    // We can remove it from local storage as we are not tracking info anymore.
                    delete orderStorage[order.uuid];
                }
            }
        });
    });
};

setInterval(getStatus, fetchInterval);

const updateStatusButton = document.querySelector('#update-status-button');
updateStatusButton.addEventListener('click', (e) => {
    const url = '/Dashboard/orders/active/updateStatus';
    let json = {'status' : orderSelection};
    postJSON(url, json).then(response => response.json()).then(orders => {
        for(var uuid in orders){
            orderStorage[uuid].status = orders[uuid];
            if(orders[uuid] == 5){
                orderComplete(uuid);
                delete orderStorage[uuid];
            } else if(((orderStorage[uuid].status == 3 && parseInt(orderStorage[uuid].order_type) !== 0) 
                        || (orderStorage[uuid].status == 4)) && !orderStorage[uuid].is_paid){
                let orderElement = document.querySelector(`[id='${uuid}']`);
                collectPayment(orderElement);
            } else {
                updateOrderStatusText(uuid, STATUS_ARRAY[orders[uuid]]);
            }
        }
    });
    const selectedElements = document.querySelectorAll('.order-container.selected');
    selectedElements.forEach(element => element.classList.remove('selected'));
    orderSelection = [];
});

// Not correct grammar, 'is' is tied with being boolean though.
// Plural to show we can get multiple orders at once.
const isOrdersPaid = () => {
    const url = '/Dashboard/orders/active/checkPayment';
    let json = {'uuid' : unpaidOrderUUIDs};
    postJSON(url, json).then(response => response.json()).then(orders => {
        for(var uuid in orders){
            if(orders[uuid]){
                const container = document.querySelector(`[id='${uuid}']`);
                container.classList.remove('unpaid');
                container.querySelector('.info-icon').remove();
                const unpaidIndex = unpaidOrderUUIDs.indexOf(uuid);
                unpaidOrderUUIDs.splice(unpaidIndex, 1);
            }
        }
    });
};

setInterval(isOrdersPaid, fetchInterval);