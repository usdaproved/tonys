// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON } from './utility.js';

"use strict";

const toggleEditButton = document.querySelector('#toggle-edit');
const updateMenuButton = document.querySelector('#update-menu');
const menuElement =      document.querySelector('#menu');
let categoryElements =   document.querySelectorAll('.menu-category');

let allDraggableElements = document.querySelectorAll('.menu-category, .menu-item');
let previousState = menuElement.innerHTML;

let inEditMode = false;
var draggedElement;

const handleDragStart = (e) => {
    if(!inEditMode) return;
    e.dataTransfer.setData('text/html', null);

    let target = e.target.closest('.menu-item');
    if(!target) target = e.target.closest('.menu-category');

    draggedElement = target;
};

const handleDragEnter = (e) => {
    e.preventDefault();
    if(!inEditMode) return;

    if(!e.target.closest) return;

    let target = null;

    if(draggedElement.classList.contains('menu-item')){
        target = e.target.closest('.menu-item');
        // The dragged element is looking to join another menu item and hasn't found one.
        if(!target) return;
    }

    
    if(!target) target = e.target.closest('.menu-category');

    let currentDraggedOver = Array.from(allDraggableElements).filter((element) => {
        if(element.classList.contains('dragged-over')) return element;
    });

    currentDraggedOver.forEach(element => {
        element.classList.remove('dragged-over');
    });

    target.classList.add('dragged-over');
};

const handleDragOver = (e) => {
    e.preventDefault();
};

const handleDrop = (e) => {
    e.preventDefault();

    let currentDraggedOver = Array.from(allDraggableElements).filter((element) => {
        if(element.classList.contains('dragged-over')) return element;
    });

    currentDraggedOver.forEach(element => {
        element.classList.remove('dragged-over');
    });
    
    let dropTarget = e.target.closest('.' + draggedElement.classList[0]);

    // Element can only be dragged onto elements of same class.
    if(dropTarget !== null){
        dropTarget.insertAdjacentElement('beforebegin', draggedElement);
    }
};

const handleDragEnd = (e) => {
    e.preventDefault();

    let currentDraggedOver = Array.from(allDraggableElements).filter((element) => {
        if(element.classList.contains('dragged-over')) return element;
    });

    currentDraggedOver.forEach(element => {
        element.classList.remove('dragged-over');
    });
}

const addDragAndDropHandlers = (elem) => {
    elem.addEventListener('dragstart', handleDragStart);
    elem.addEventListener('dragenter', handleDragEnter);
    elem.addEventListener('dragover', handleDragOver);
    elem.addEventListener('drop', handleDrop);
    elem.addEventListener('dragend', handleDragEnd);
};

[].forEach.call(categoryElements, addDragAndDropHandlers);

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
    updateMenuButton.classList.remove('inactive');
    updateMenuButton.disabled = false;
    toggleEditButton.innerText = 'Cancel';
    toggleEditButton.classList.add('cancel');
    allDraggableElements.forEach(e => {
        e.setAttribute('draggable', 'true');
        e.classList.add('draggable');
    });
};

const endEditMode = (e) => {
    inEditMode = false;
    updateMenuButton.classList.add('inactive');
    updateMenuButton.disabled = true;
    toggleEditButton.innerText = 'Edit Order';
    toggleEditButton.classList.remove('cancel');
    // if end edit was called because it was cancelled.
    if(e && e.target.closest('button').id === 'toggle-edit'){
        menuElement.innerHTML = previousState;
        allDraggableElements = document.querySelectorAll('.menu-category, .menu-item');
        allDraggableElements.forEach(element => element.classList.remove('draggable'));
        categoryElements = document.querySelectorAll('.menu-category');
        categoryElements.forEach(element => element.classList.remove('draggable'));
        [].forEach.call(categoryElements, addDragAndDropHandlers);
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

    const url = '/Dashboard/menu/updateMenuSequence';
    let json = serializeMenu();

    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});