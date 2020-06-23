// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, initSearchUsersComponent, SingleContainerSelector } from './utility.js';

"use strict";

const currentEmployeesContainers = document.querySelector('#current-employees').querySelectorAll('.order-container');
const toggleAdminButton = document.querySelector('#toggle-admin');
const deleteButton = document.querySelector('#delete-employee');

let employeeSelector = new SingleContainerSelector(currentEmployeesContainers, [toggleAdminButton, deleteButton]);
deleteButton.addEventListener('click', (e) => {
    let employeeUUID = employeeSelector.selectedUUID;

    let url = '/Dashboard/employees/delete';
    let json = {'user_uuid': employeeUUID};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});

toggleAdminButton.addEventListener('click', (e) => {
    let employeeUUID = employeeSelector.selectedUUID;

    let url = '/Dashboard/employees/toggleAdmin';
    let json = {'user_uuid': employeeUUID};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});

const addEmployeeButton = document.querySelector('#add-employee');
let selectedUserUUID = null;

initSearchUsersComponent((e) => {
    let container = e.target.closest('.order-container');
    if(container.classList.contains('selected')){
        container.classList.remove('selected');
        selectedUserUUID = null;
        addEmployeeButton.hidden = true;
        return;
    }
    container.closest('.orders-container')
    .querySelectorAll('.order-containers')
    .forEach(container => {
        container.classList.remove('selected');
    });
    container.classList.add('selected');
    selectedUserUUID = container.id;
    addEmployeeButton.hidden = false;
});

document.querySelector('#user-search-button').addEventListener('click', (e) => {
    selectedUserUUID = null;
    addEmployeeButton.hidden = true;
});

addEmployeeButton.addEventListener('click', (e) => {
    let userContainer = document.querySelector(`[id='${selectedUserUUID}']`);
    if(!userContainer) return;
    let url = '/Dashboard/employees/add';
    let json = {'user_uuid': selectedUserUUID};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});