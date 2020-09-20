// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON } from './utility.js';

"use strict";

const deliveryScheduleElements = document.querySelector('#delivery-schedule').querySelectorAll('.day-container');
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
    
    const url = '/Dashboard/settings/sechedule/updateDelivery';
    postJSON(url, json).then(response => response.text()).then(result => {
            
    });
});

const pickupScheduleElements = document.querySelector('#pickup-schedule').querySelectorAll('.day-container');
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
    
    const url = '/Dashboard/settings/schedule/updatePickup';
    postJSON(url, json).then(response => response.text()).then(result => {
            
    });
});