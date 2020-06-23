// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON } from './utility.js';

"use strict";

const deliveryStatusCheckbox = document.querySelector('#delivery-on');
deliveryStatusCheckbox.addEventListener('click', (e) => {
    let currentStatus = !e.target.checked;
    // We don't want the input to actually change until the user has confirmed it.
    e.target.checked = currentStatus;
    let message = (currentStatus) ? 'off' : 'on';
    let confirmed = confirm(`Do you wish to turn ${message} deliveries?`);
    if(confirmed){
        let status = !currentStatus;
        e.target.checked = status;
        let json = {'status' : status};
        const url = '/Dashboard/settings/updateDeliveryStatus';
        postJSON(url, json).then(response => response.text()).then(result => {
            
        });
    }
});

const pickupStatusCheckbox = document.querySelector('#pickup-on');
pickupStatusCheckbox.addEventListener('click', (e) => {
    let currentStatus = !e.target.checked;
    // We don't want the input to actually change until the user has confirmed it.
    e.target.checked = currentStatus;
    let message = (currentStatus) ? 'off' : 'on';
    let confirmed = confirm(`Do you wish to turn ${message} pickups?`);
    if(confirmed){
        let status = !currentStatus;
        e.target.checked = status;
        let json = {'status' : status};
        const url = '/Dashboard/settings/updatePickupStatus';
        postJSON(url, json).then(response => response.text()).then(result => {
            
        });
    }
});

const deliveryScheduleElements = document.querySelector('#delivery-schedule').querySelectorAll('.order-container');
const updateDeliveryScheduleButton = document.querySelector('#delivery-submit');
updateDeliveryScheduleButton.addEventListener('click', (e) => {
    let json = {'days' : []};
    deliveryScheduleElements.forEach(element => {
        let day = element.id.split('-')[0];
        let startTime = element.querySelector(`[id='${day}-start-time']`).value;
        let endTime = element.querySelector(`[id='${day}-end-time']`).value;
        let dayData = {'day' : day, 'start_time' : startTime, 'end_time' : endTime};
        json.days.push(dayData);
    });
    
    const url = '/Dashboard/settings/updateDeliverySchedule';
    postJSON(url, json).then(response => response.text()).then(result => {
            
    });
});

const pickupScheduleElements = document.querySelector('#pickup-schedule').querySelectorAll('.order-container');
const updatePickupScheduleButton = document.querySelector('#pickup-submit');
updatePickupScheduleButton.addEventListener('click', (e) => {
    let json = {'days' : []};
    pickupScheduleElements.forEach(element => {
        let day = element.id.split('-')[0];
        let startTime = element.querySelector(`[id='${day}-start-time']`).value;
        let endTime = element.querySelector(`[id='${day}-end-time']`).value;
        let dayData = {'day' : day, 'start_time' : startTime, 'end_time' : endTime};
        json.days.push(dayData);
    });
    
    const url = '/Dashboard/settings/updatePickupSchedule';
    postJSON(url, json).then(response => response.text()).then(result => {
            
    });
});