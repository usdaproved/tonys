// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
"use strict";

const CSRFToken =        document.querySelector('#CSRFToken').value;
const resultElement =    document.querySelector('#result');
const toggleEditButton = document.querySelector('#toggle-edit');
const updateMenuButton = document.querySelector('#update-menu');
const menuElement =      document.querySelector('#menu');
let categoryElements =   document.querySelectorAll('.menu-category');

let allDraggableElements = document.querySelectorAll('.menu-category, .menu-item');
let previousState = menuElement.innerHTML;

let inEditMode = false;
var draggedElement;

const handleDragStart = (e) => {
    event.dataTransfer.setData('text/html', null);

    draggedElement = e.target;
};

const handleDragOver = (e) => {
    e.preventDefault();
};

const handleDrop = (e) => {
    e.preventDefault();
    
    let dropTarget = e.target.closest('.' + draggedElement.classList[0]);

    // Element can only be dragged onto elements of same class.
    if(dropTarget !== null){
        dropTarget.insertAdjacentElement('beforebegin', draggedElement);
    }
};

const addDragAndDropHandlers = (elem) => {
    elem.addEventListener('dragstart', handleDragStart);
    elem.addEventListener('dragover', handleDragOver);
    elem.addEventListener('drop', handleDrop);
};

[].forEach.call(categoryElements, addDragAndDropHandlers);

const displayResult = (result) => {
    if(result === 'success'){
        resultElement.textContent = "Menu order has been updated.";
    }
    if(result === 'fail'){
        resultElement.textContent = "Menu order has failed to update.";
    }
};

const serializeMenu = () => {
    let result = {};
    // We have to grab the elements again to grab the new ordering.
    categoryElements = document.querySelectorAll('.menu-category');
    categoryElements.forEach(category => {
        let menuItems = category.querySelectorAll(".menu-item");
        let menuItemIDs = new Array();
        menuItems.forEach(item =>{
            let wholeID = item.id;
            let itemID = wholeID.split("-")[0];
            menuItemIDs.push(itemID);
        });
        let categoryID = category.id.split("-")[0];
        // This preserves our category information.
        result[categoryID + "-category"] = menuItemIDs;
    });

    return result;
};

const beginEditMode = () => {
    inEditMode = true;
    updateMenuButton.removeAttribute('hidden');
    toggleEditButton.setAttribute('value', 'Cancel Edit');
    allDraggableElements.forEach(e => {
        e.setAttribute('draggable', 'true');
    });
};

const endEditMode = (e) => {
    inEditMode = false;
    updateMenuButton.setAttribute('hidden', '');
    toggleEditButton.setAttribute('value', 'Edit menu order');
    // if end edit was called because it was cancelled.
    if(e && e.target.id === 'toggle-edit'){
        menuElement.innerHTML = previousState;
        allDraggableElements = document.querySelectorAll('.menu-category, .menu-item');
        categoryElements = document.querySelectorAll('.menu-category');
        [].forEach.call(categoryElements, addDragAndDropHandlers);
    } else if(e && e.target.id === 'update-menu'){
        previousState = menuElement.innerHTML;
    }
};

toggleEditButton.addEventListener('click', e => {
    e.preventDefault();

    if(inEditMode){
        endEditMode(e);
    } else {
        beginEditMode();
    }
});

updateMenuButton.addEventListener('click', e => {
    e.preventDefault();

    endEditMode(e);

    let serializedMenu = serializeMenu();
    // We always have to add in that CSRFToken.
    serializedMenu["CSRFToken"] = CSRFToken;

    const url = window.location.origin + '/Dashboard/menu/updateMenuSequence';

    let data = JSON.stringify(serializedMenu);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: data
    }).then(response => response.text()).then(result => displayResult(result));
});