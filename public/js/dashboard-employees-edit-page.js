// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, initSearchUsersComponent } from './utility.js';

"use strict";

const currentEmployeesContainers = document.querySelector('#current-employees').querySelectorAll('.current-employee');
const toggleAdminButton = document.querySelector('#toggle-admin');
const deleteButton = document.querySelector('#delete-employee');
let selectedEmployeeUUID = null;

currentEmployeesContainers.forEach(button => button.addEventListener('click', (e) => {
    let container = e.target.closest('button');
    if(container.classList.contains('selected')){
        container.classList.remove('selected');
        selectedEmployeeUUID = null;
        toggleAdminButton.classList.add('inactive');
        deleteButton.classList.add('inactive');
        toggleAdminButton.disabled = true;
        deleteButton.disabled = true;
        return;
    }

    currentEmployeesContainers.forEach(container => container.classList.remove('selected'));
    container.classList.add('selected');
    selectedEmployeeUUID = container.id;
    toggleAdminButton.classList.remove('inactive');
    deleteButton.classList.remove('inactive');
    toggleAdminButton.disabled = false;
    deleteButton.disabled = false;
}));

deleteButton.addEventListener('click', (e) => {
    let url = '/Dashboard/employees/delete';
    let json = {'user_uuid': selectedEmployeeUUID};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});

toggleAdminButton.addEventListener('click', (e) => {
    let url = '/Dashboard/employees/toggleAdmin';
    let json = {'user_uuid': selectedEmployeeUUID};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});

const addEmployeeButton = document.querySelector('#add-employee');
let selectedUserUUID = null;

initSearchUsersComponent((e) => {
    let container = e.target.closest('.search-result');
    if(container.classList.contains('selected')){
        container.classList.remove('selected');
        selectedUserUUID = null;
        addEmployeeButton.classList.add('inactive');
        addEmployeeButton.disabled = true;
        return;
    }
    container.closest('.search-result-container')
    .querySelectorAll('.search-result')
    .forEach(container => {
        container.classList.remove('selected');
    });
    container.classList.add('selected');
    selectedUserUUID = container.id;
    addEmployeeButton.classList.remove('inactive');
    addEmployeeButton.disabled = false;
});



document.querySelector('#user-search-button').addEventListener('click', (e) => {
    selectedUserUUID = null;
    if(!addEmployeeButton.classList.contains('inactive')){
        addEmployeeButton.classList.add('inactive');
        addEmployeeButton.disabled = true;
    }
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