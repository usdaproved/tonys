"use strict";

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

let categoryElements = document.querySelectorAll('#menu > *');
[].forEach.call(categoryElements, addDragAndDropHandlers);

const resultElement = document.querySelector('#result');

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
    // Grab the new order.
    categoryElements = document.querySelectorAll('#menu > *');
    categoryElements.forEach(category => {
        let menuItems = category.querySelectorAll(".menu-item");
        let menuItemIDs = new Array();
        menuItems.forEach(item =>{
            let wholeID = item.id;
            let itemID = wholeID.split("-")[0];
            menuItemIDs.push(itemID);
        });
        let categoryID = category.id.split("-")[0];
        // This preserves our ordering
        result[categoryID + "-category"] = menuItemIDs;
    });

    return result;
};

const CSRFToken = document.querySelector('#CSRFToken').value;
const updateMenuButton = document.querySelector('#update-menu');
updateMenuButton.addEventListener('click', e => {
    e.preventDefault();

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