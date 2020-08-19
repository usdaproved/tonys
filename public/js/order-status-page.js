// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

import { STATUS_ARRAY, postJSON } from './utility.js';

"use strict";

const orderStatusContainer = document.querySelector('#order-status-container');
const svgCircleElement = orderStatusContainer.querySelector('#svg-active-circle');
const statusElement = orderStatusContainer.querySelector('#order-status-text');
const preparedElement = orderStatusContainer.querySelector('#order-prepared-notice');

const orderElement = document.querySelector('.order-info');
const orderUUID = orderElement.dataset.uuid;
let currentStatus = orderElement.dataset.status;

if(STATUS_ARRAY[currentStatus] !== 'complete'){
    const getStatus = () => {
        const getStatusURL = '/Order/getStatus';
        const getStatusJSON = {"order_uuid" : orderUUID};

        postJSON(getStatusURL, getStatusJSON).then(response => response.json()).then(status => {
            if(status === 'fail'){
                // Perhaps some type of error here.
                // Though if they are reaching it, it's because they are doing something weird.
                return;
            }

            if(currentStatus != status){
                currentStatus = status;

                statusElement.innerText = STATUS_ARRAY[currentStatus];

                if(STATUS_ARRAY[currentStatus] === 'prepared'){
                    preparedElement.removeAttribute('hidden');
                } else {
                    preparedElement.setAttribute('hidden', 'true');
                }

                if(STATUS_ARRAY[currentStatus] === 'complete'){
                    svgCircleElement.setAttribute('hidden', 'true');
                    orderStatusContainer.classList.remove('active');

                    clearInterval(statusInterval);
                }
            }
        });
    }

    const statusInterval = setInterval(getStatus, 10000);
}

