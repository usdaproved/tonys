// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, intToCurrency } from './utility.js';

"use strict";

const submitChoicesButton = document.querySelector('#update-choices');
const addGroupButton = document.querySelector('#add-group-button');
let choiceToggleEditButton = document.querySelector('#choice-toggle-order-edit');
let additionToggleEditButton = document.querySelector('#addition-toggle-order-edit');
let removeGroupButtons = document.querySelectorAll('.remove-group-button');
let addOptionButtons = document.querySelectorAll('.add-option-button');
let removeOptionButtons = document.querySelectorAll('.remove-option-button');
let addAdditionButton = document.querySelector('#add-addition-button');
let removeAdditionButtons = document.querySelectorAll('.remove-addition-button');
let additionSelectList = document.querySelector('#addition-select-list');
const menuItemID = document.querySelector('#menu-item-id').value;

let inChoiceEditMode = false;
let inAdditionEditMode = false;

let choicesContainer = document.querySelector('#choices-container');
let additionsContainer = document.querySelector('#additions-container');

const removeOptionHandler = (e) => {
    e.preventDefault();
    let optionID = e.target.id.split('-')[0];
    let choiceContainer = e.target.closest('.choice-option');
    
    let data = {"option-id" : optionID};
    const url = '/Dashboard/menu/item/removeChoiceOption';

    choiceContainer.remove();

    postJSON(url, data).then(response => response.text()).then(result => {

    });
};

removeOptionButtons.forEach(button => {
    button.addEventListener('click', removeOptionHandler);
});

const newChoiceOption = (optionID, name = "", price = 0) => {
    let optionContainer = document.createElement('div');
    optionContainer.id = `${optionID}-choice-option`;
    optionContainer.classList.add('choice-option');
    
    let removeOptionButton = document.createElement('input');
    removeOptionButton.type = 'button';
    removeOptionButton.id = `${optionID}-remove-option`;
    removeOptionButton.value = 'Remove Option';
    removeOptionButton.classList.add('remove-option-button');
    removeOptionButton.addEventListener('click', removeOptionHandler);

    optionContainer.appendChild(removeOptionButton);
    
    let nameLabel = document.createElement('label');
    nameLabel.setAttribute('for', `${optionID}-option-name`);
    nameLabel.innerText = 'Name';

    optionContainer.appendChild(nameLabel);
    
    let nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.id = `${optionID}-option-name`;
    nameInput.classList.add('option-name');
    nameInput.name = 'name';
    nameInput.value = name;
    nameInput.required = true;

    optionContainer.appendChild(nameInput);

    let priceLabel = document.createElement('label');
    priceLabel.setAttribute('for', `${optionID}-option-price`);
    priceLabel.innerText = 'Price Modifier';

    optionContainer.appendChild(priceLabel);
    
    let priceInput = document.createElement('input');
    priceInput.type = 'number';
    priceInput.id = `${optionID}-option-price`;
    priceInput.classList.add('option-price');
    priceInput.name = 'price';
    priceInput.setAttribute('step', '0.01');
    priceInput.setAttribute('min', '0');
    priceInput.value = price;
    priceInput.required = true;

    optionContainer.appendChild(priceInput);

    return optionContainer;
};

const addOptionHandler = (e) => {
    e.preventDefault();
    let groupID = e.target.id.split('-')[0];
    let data = {"group-id" : groupID};
    const url = '/Dashboard/menu/item/addChoiceOption';
    
    postJSON(url, data).then(response => response.text()).then(optionID => {
        let choiceContainer = e.target.closest('.choice-group');
        let optionContainer = newChoiceOption(optionID);
        choiceContainer.appendChild(optionContainer);


    });
};

addOptionButtons.forEach(button => {
    button.addEventListener('click', addOptionHandler);
});

const newChoiceGroup = (groupID, name = "", minPicks = 0, maxPicks = 0) => {
    let groupContainer = document.createElement('div');
    groupContainer.id = `${groupID}-choice-group`;
    groupContainer.classList.add('choice-group');
    
    let removeGroupButton = document.createElement('input');
    removeGroupButton.type = 'button';
    removeGroupButton.id = `${groupID}-remove-group`;
    removeGroupButton.value = 'Remove Group';
    removeGroupButton.classList.add('remove-group-button');
    removeGroupButton.addEventListener('click', removeGroupHandler);
    
    groupContainer.appendChild(removeGroupButton);
    
    let addOptionButton = document.createElement('input');
    addOptionButton.type = 'button';
    addOptionButton.id = `${groupID}-add-option`;
    addOptionButton.value = 'Add Option';
    addOptionButton.classList.add('add-option-button');
    addOptionButton.addEventListener('click', addOptionHandler);

    groupContainer.appendChild(addOptionButton);

    let nameLabel = document.createElement('label');
    nameLabel.setAttribute('for', `${groupID}-group-name`);
    nameLabel.innerText = 'Name';

    groupContainer.appendChild(nameLabel);

    let nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.id = `${groupID}-group-name`;
    nameInput.classList.add('group-name');
    nameInput.name = 'name';
    nameInput.value = name;
    nameInput.required = true;

    groupContainer.appendChild(nameInput);

    let minPicksLabel = document.createElement('label');
    minPicksLabel.setAttribute('for', `${groupID}-group-min-picks`);
    minPicksLabel.innerText = 'Minimum Picks';

    groupContainer.appendChild(minPicksLabel);

    let minPicksInput = document.createElement('input');
    minPicksInput.type = 'number';
    minPicksInput.id = `${groupID}-group-min-picks`;
    minPicksInput.classList.add('group-min-picks');
    minPicksInput.name = 'min-picks';
    minPicksInput.setAttribute('step', '1');
    minPicksInput.setAttribute('min', '0');
    minPicksInput.value = minPicks;
    minPicksInput.required = true;

    groupContainer.appendChild(minPicksInput);

    let maxPicksLabel = document.createElement('label');
    maxPicksLabel.setAttribute('for', `${groupID}-group-max-picks`);
    maxPicksLabel.innerText = 'Maximum Picks';

    groupContainer.appendChild(maxPicksLabel);

    let maxPicksInput = document.createElement('input');
    maxPicksInput.type = 'number';
    maxPicksInput.id = `${groupID}-group-max-picks`;
    maxPicksInput.classList.add('group-max-picks');
    maxPicksInput.name = 'max-picks';
    maxPicksInput.setAttribute('step', '1');
    maxPicksInput.setAttribute('min', '0');
    maxPicksInput.value = maxPicks;
    maxPicksInput.required = true;

    groupContainer.appendChild(maxPicksInput);

    return groupContainer;
};

addGroupButton.addEventListener('click', (e) => {
    e.preventDefault();
    let postData = {"item-id" : menuItemID};
    const url = '/Dashboard/menu/item/addChoiceGroup';
    
    postJSON(url, postData).then(response => response.text()).then(groupID => {
        let choiceGroup = newChoiceGroup(groupID);
        choicesContainer.appendChild(choiceGroup);


    });
});

const removeGroupHandler = (e) => {
    e.preventDefault();
    // if no children attached, then remove.
    // Otherwise send some message about removing children.
    let groupID = e.target.id.split('-')[0];
    let groupContainer = e.target.closest(`.choice-group`);
    let options = groupContainer.querySelector('.choice-option');
    if(options === null){
        let data = {"group-id" : groupID};
        const url = '/Dashboard/menu/item/removeChoiceGroup';

        groupContainer.remove();

        postJSON(url, data).then(response => response.text()).then(result => {

        });
    } else {
        // TODO: Handle this more gracefully in the UI.
        alert("Remove options before removing group.");
    }
};

removeGroupButtons.forEach(button => {
    button.addEventListener('click', removeGroupHandler);
});

const getChoiceData = () => {
    let groups = choicesContainer.querySelectorAll('.choice-group');
    
    let result = {};
    groups.forEach(group => {
        result[group.id] = {};
        let groupName = group.querySelector('.group-name').value;
        let groupMinPicks = group.querySelector('.group-min-picks').value;
        let groupMaxPicks = group.querySelector('.group-max-picks').value;
        

        result[group.id]["group-data"] = {
            "name" : groupName,
            "min-picks" : groupMinPicks,
            "max-picks" : groupMaxPicks
        };

        group.querySelectorAll('.choice-option').forEach(option => {
            let optionName = option.querySelector('.option-name').value;
            let optionPrice = option.querySelector('.option-price').value;
            result[group.id][option.id] = {
                "name" : optionName,
                "price" : optionPrice
            };
        });
    });

    return result;
};

submitChoicesButton.addEventListener('click', (event) => {
    event.preventDefault();

    let data = getChoiceData();

    const url = '/Dashboard/menu/item/updateChoices';

    postJSON(url, data).then(response => response.text()).then(result => {

    });
});

// Toggle edit choices order

let draggedElement;

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
    let startGroupID = draggedElement.parentElement.id;
    let endGroupID = dropTarget.parentElement.id;

    // Element can only be dragged onto elements of same class and within same Group.
    if(dropTarget !== null && startGroupID === endGroupID){
        dropTarget.insertAdjacentElement('beforebegin', draggedElement);
    }
};

const addDragAndDropHandlers = (element) => {
    element.addEventListener('dragstart', handleDragStart);
    element.addEventListener('dragover', handleDragOver);
    element.addEventListener('drop', handleDrop);
};

let choiceData;

const beginChoiceOrderEditMode = () => {
    inChoiceEditMode = true;

    submitChoicesButton.setAttribute('hidden', '');
    addGroupButton.setAttribute('hidden', '');
    choiceToggleEditButton.value = 'Update Choices';

    choiceData = getChoiceData();

    while(choicesContainer.firstChild) {
        choicesContainer.removeChild(choicesContainer.firstChild);
    }

    for(var groupID in choiceData){
        let splitGroupID = groupID.split('-')[0];

        let groupContainer = document.createElement('div');
        groupContainer.id = groupID;
        groupContainer.classList.add('draggable-choice-group');
        addDragAndDropHandlers(groupContainer);
        groupContainer.draggable = true;

        let groupName = document.createElement('h3');
        groupName.id = `${splitGroupID}-group-name`;
        groupName.classList.add('draggable-group-name');
        groupName.innerText = choiceData[groupID]['group-data']['name'];

        groupContainer.appendChild(groupName);

        let optionsList = document.createElement('ul');
        optionsList.id = `${splitGroupID}-options-list`;
        optionsList.classList.add('draggable-options-list');

        for(var optionID in choiceData[groupID]){
            if(optionID !== 'group-data'){
                let splitOptionID = optionID.split('-')[0];
                let optionElement = document.createElement('li');
                optionElement.id = `${splitOptionID}-choice-option`;
                optionElement.classList.add('draggable-option');
                optionElement.draggable = true;
                optionElement.innerText = choiceData[groupID][optionID]['name'];

                optionsList.appendChild(optionElement);
            }
        }

        groupContainer.appendChild(optionsList);

        choicesContainer.appendChild(groupContainer);
    }
};

const endChoiceOrderEditMode = () => {
    inChoiceEditMode = false;

    submitChoicesButton.removeAttribute('hidden');
    addGroupButton.removeAttribute('hidden');
    choiceToggleEditButton.value = 'Edit Choices Order';

    let orderedChoiceData = {};
    choicesContainer.querySelectorAll('.draggable-choice-group').forEach(group => {
        orderedChoiceData[group.id] = {};

        let groupName = choiceData[group.id]['group-data']['name'];
        let groupMinPicks = choiceData[group.id]['group-data']['min-picks'];
        let groupMaxPicks = choiceData[group.id]['group-data']['max-picks'];
        orderedChoiceData[group.id]['group-data'] = {
            "name" : groupName,
            "min-picks" : groupMinPicks,
            "max-picks" : groupMaxPicks
        };

        group.querySelectorAll('.draggable-option').forEach(option =>{
            let optionName = choiceData[group.id][option.id]['name'];
            let optionPrice = choiceData[group.id][option.id]['price'];
            orderedChoiceData[group.id][option.id] = {
                "name" : optionName,
                "price" : optionPrice
            };
        });
    });
    
    choiceData = orderedChoiceData;

    while(choicesContainer.firstChild) {
        choicesContainer.removeChild(choicesContainer.firstChild);
    }

    // Build the inputs back up based on choiceData.
    for(var groupID in choiceData){
        let groupName = choiceData[groupID]["group-data"]["name"];
        let groupMinPicks = choiceData[groupID]["group-data"]["min-picks"];
        let groupMaxPicks = choiceData[groupID]["group-data"]["max-picks"];

        let splitGroupID = groupID.split('-')[0];
        let groupElement = newChoiceGroup(splitGroupID, groupName, groupMinPicks, groupMaxPicks);
        for(var optionID in choiceData[groupID]){
            if(optionID !== 'group-data'){
                let optionName = choiceData[groupID][optionID]["name"];
                let optionPrice = choiceData[groupID][optionID]["price"];

                let splitOptionID = optionID.split('-')[0];
                let choiceElement = newChoiceOption(splitOptionID, optionName, optionPrice);
                groupElement.appendChild(choiceElement);
            }
        }

        choicesContainer.appendChild(groupElement);
    }

    submitChoicesButton.click();
};

choiceToggleEditButton.addEventListener('click', e => {
    e.preventDefault();

    if(inChoiceEditMode){
        endChoiceOrderEditMode();
    } else {
        beginChoiceOrderEditMode();
    }
});

const removeAdditionHandler = e => {
    e.preventDefault();

    let container = e.target.closest('.addition');
    let additionID = e.target.id.split('-')[0];

    container.remove();

    let url = '/Dashboard/menu/item/removeAddition';
    let data = {
        "item-id" : menuItemID,
        "addition-id" : additionID
    };

    postJSON(url, data).then(response => response.text()).then(result => {
        
    });
};

removeAdditionButtons.forEach(button => {
    button.addEventListener('click', removeAdditionHandler);
});

const newAddition = (id, text) => {
    let container = document.createElement('div');
    container.id = `${id}-addition`;
    container.classList.add('addition');

    let p = document.createElement('p');
    p.innerText = text;

    container.appendChild(p);

    let removeButton = document.createElement('input');
    removeButton.type = 'button';
    removeButton.classList.add('remove-addition-button');
    removeButton.id = `${id}-remove-addition-button`;
    removeButton.addEventListener('click', removeAdditionHandler);
    removeButton.value = 'Remove';

    container.appendChild(removeButton);

    return container;
};

addAdditionButton.addEventListener('click', e =>{
    e.preventDefault();
    
    let index = additionSelectList.selectedIndex;
    let additionID = additionSelectList.options[index].value;
    let additionText = additionSelectList.options[index].text;
    
    additionSelectList.options[index].remove();

    let addition = newAddition(additionID, additionText);
    additionsContainer.appendChild(addition);

    const url = '/Dashboard/menu/item/addAddition';
    let data = {
        "item-id" : menuItemID,
        "addition-id" : additionID
    };
    
    postJSON(url, data).then(response => response.text()).then(result => {

    });
});

let additionData;

const beginAdditionOrderEditMode = () => {
    inAdditionEditMode = true;

    additionToggleEditButton.value = 'Update Order';
    addAdditionButton.setAttribute('hidden', '');
    additionSelectList.setAttribute('hidden', '');

    let additions = additionsContainer.querySelectorAll('.addition');

    let previousData = {};
    additions.forEach(addition => {
        let text = addition.querySelector('p').innerText;

        previousData[addition.id] = { "text" : text };
    });

    additionData = previousData;

    while(additionsContainer.firstChild) {
        additionsContainer.removeChild(additionsContainer.firstChild);
    }

    let draggableList = document.createElement('ul');
    for(var additionID in additionData){
        let draggableItem = document.createElement('li');
        draggableItem.id = additionID;
        draggableItem.classList.add('draggable-addition');
        draggableItem.setAttribute('draggable','true');
        draggableItem.innerText = additionData[additionID].text;

        addDragAndDropHandlers(draggableItem);

        draggableList.appendChild(draggableItem);
    }

    additionsContainer.appendChild(draggableList);
};

const endAdditionOrderEditMode = () => {
    inAdditionEditMode = false;

    additionToggleEditButton.value = 'Edit Order';
    addAdditionButton.removeAttribute('hidden');
    additionSelectList.removeAttribute('hidden');

    let additions = additionsContainer.querySelectorAll('li');

    let orderedAdditionData = {};
    let jsonData = {};
    jsonData.ids = [];
    additions.forEach(addition => {
        orderedAdditionData[addition.id] = additionData[addition.id];
        jsonData.ids.push(addition.id);
    });

    additionData = orderedAdditionData;
    
    
    while(additionsContainer.firstChild) {
        additionsContainer.removeChild(additionsContainer.firstChild);
    }

    for(var additionID in additionData){
        let splitAdditionID = additionID.split('-')[0];
        let addition = newAddition(splitAdditionID, additionData[additionID].text);
        
        additionsContainer.appendChild(addition);
    }

    jsonData.itemID = menuItemID;
    let url = '/Dashboard/menu/item/updateAdditionPositions';
    postJSON(url, jsonData);
};

additionToggleEditButton.addEventListener('click', e => {
    e.preventDefault();

    if(inAdditionEditMode){
        endAdditionOrderEditMode();
    } else {
        beginAdditionOrderEditMode();
    }
});