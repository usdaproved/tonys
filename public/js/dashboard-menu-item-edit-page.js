// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, intToCurrency } from './utility.js';

"use strict";

const submitChoicesButton = document.querySelector('#update-choices');
const addGroupButton = document.querySelector('#add-group-button');
let choiceToggleEditButton = document.querySelector('#choice-toggle-order-edit');
let removeGroupButtons = document.querySelectorAll('.remove-group-button');
let addOptionButtons = document.querySelectorAll('.add-option-button');
let removeOptionButtons = document.querySelectorAll('.remove-option-button');
const menuItemID = document.querySelector('#menu-item-id').value;

const deleteSVG = `<svg xmlns="http://www.w3.org/2000/svg" fill="red" height="24" viewBox="0 0 24 24" width="24">
<path d="M0 0h24v24H0z" fill="none"/>
<path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
</svg>`;

let inChoiceEditMode = false;

let choicesContainer = document.querySelector('#choices-container');

const removeOptionHandler = (e) => {
    e.preventDefault();
    let optionID = e.target.closest('button').id.split('-')[0];
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

    let inputContainer = document.createElement('div');
    inputContainer.classList.add('input-shared-line');
    
    let removeOptionButton = document.createElement('button');
    removeOptionButton.type = 'button';
    removeOptionButton.id = `${optionID}-remove-option`;
    removeOptionButton.innerHTML = deleteSVG;
    removeOptionButton.classList.add('remove-option-button');
    removeOptionButton.classList.add('svg-button');
    removeOptionButton.addEventListener('click', removeOptionHandler);

    inputContainer.appendChild(removeOptionButton);

    let nameActiveContainer = document.createElement('div');
    nameActiveContainer.classList.add('option-price-container');

    let nameInputContainer = document.createElement('div');
    nameInputContainer.classList.add('input-container');
    
    let nameLabel = document.createElement('label');
    nameLabel.setAttribute('for', `${optionID}-option-name`);
    nameLabel.innerText = 'Name';

    nameInputContainer.appendChild(nameLabel);
    
    let nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.id = `${optionID}-option-name`;
    nameInput.classList.add('option-name');
    nameInput.name = 'name';
    nameInput.value = name;
    nameInput.style.maxWidth = '8rem';
    nameInput.required = true;

    nameInputContainer.appendChild(nameInput);

    nameActiveContainer.appendChild(nameInputContainer);

    let activeInputContainer = document.createElement('div');
    activeInputContainer.classList.add('remember-container');

    let activeCheckbox = document.createElement('input');
    activeCheckbox.type = 'checkbox';
    activeCheckbox.id = `${optionID}-option-active`;
    activeCheckbox.classList.add('option-active');
    activeCheckbox.checked = true;

    activeInputContainer.appendChild(activeCheckbox);

    let activeCheckboxLabel = document.createElement('label');
    activeCheckboxLabel.setAttribute('for', `${optionID}-option-active`);
    activeCheckboxLabel.innerText = 'Active';

    activeInputContainer.appendChild(activeCheckboxLabel);

    nameActiveContainer.appendChild(activeInputContainer);

    inputContainer.appendChild(nameActiveContainer);

    let pricesContainer = document.createElement('div');
    pricesContainer.classList.add('option-price-container');

    let priceInputContainer = document.createElement('div');
    priceInputContainer.classList.add('input-container');

    let priceLabel = document.createElement('label');
    priceLabel.setAttribute('for', `${optionID}-option-price`);
    priceLabel.innerText = 'Price Modifier';

    priceInputContainer.appendChild(priceLabel);
    
    let priceInput = document.createElement('input');
    priceInput.type = 'number';
    priceInput.id = `${optionID}-option-price`;
    priceInput.classList.add('option-price');
    priceInput.name = 'price';
    priceInput.setAttribute('step', '0.01');
    priceInput.setAttribute('min', '0');
    priceInput.value = price;
    priceInput.style.width = '4rem';
    priceInput.required = true;

    priceInputContainer.appendChild(priceInput);

    pricesContainer.appendChild(priceInputContainer);

    let specialPriceInputContainer = document.createElement('div');
    specialPriceInputContainer.classList.add('input-container');

    let specialPriceLabel = document.createElement('label');
    specialPriceLabel.setAttribute('for', `${optionID}-option-special-price`);
    specialPriceLabel.innerText = 'Special Price';

    specialPriceInputContainer.appendChild(specialPriceLabel);

    let specialPriceInput = document.createElement('input');
    specialPriceInput.type = 'number';
    specialPriceInput.id = `${optionID}-option-special-price`;
    specialPriceInput.classList.add('option-special-price');
    specialPriceInput.name = 'price';
    specialPriceInput.setAttribute('step', '0.01');
    specialPriceInput.setAttribute('min', '0');
    specialPriceInput.value = price;
    specialPriceInput.style.width = '4rem';
    specialPriceInput.required = true;

    specialPriceInputContainer.appendChild(specialPriceInput);

    pricesContainer.appendChild(specialPriceInputContainer);

    inputContainer.appendChild(pricesContainer);

    optionContainer.appendChild(inputContainer);

    return optionContainer;
};

const addOptionHandler = (e) => {
    e.preventDefault();
    let groupID = e.target.id.split('-')[0];
    let data = {"group-id" : groupID};
    const url = '/Dashboard/menu/item/addChoiceOption';
    
    postJSON(url, data).then(response => response.text()).then(optionID => {
        let choiceContainer = e.target.closest('.choice-group').querySelector('.choice-option-container');
        let optionContainer = newChoiceOption(optionID);
        choiceContainer.appendChild(optionContainer);
    });
};

addOptionButtons.forEach(button => {
    button.addEventListener('click', addOptionHandler);
});

addGroupButton.addEventListener('click', (e) => {
    e.preventDefault();
    let postData = {"item-id" : menuItemID};
    const url = '/Dashboard/menu/item/addChoiceGroup';
    
    postJSON(url, postData).then(response => response.text()).then(groupID => {
        location.reload();
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
            let optionSpecialPrice = option.querySelector('.option-special-price').value;
            let optionActive = option.querySelector('.option-active').checked ? 1 : 0;
            result[group.id][option.id] = {
                "name" : optionName,
                "price" : optionPrice,
                "special_price" : optionSpecialPrice,
                "active" : optionActive
            };
        });
    });

    return result;
};

const serializeMenu = () => {
    let result = {};

    let choiceContainers = document.querySelectorAll('.choice-group');
    choiceContainers.forEach(choice => {
        let options = choice.querySelectorAll('.choice-option');
        let optionIDs = [];
        options.forEach(option => {
            optionIDs.push(option.id);
        });
        result[choice.id] = optionIDs;
    });

    return result;
};



// Toggle edit choices order

let draggedElement;
let allDraggableElements = null;

const handleDragStart = (e) => {
    if(!inChoiceEditMode) return;
    e.dataTransfer.setData('text/html', null);

    let target = e.target.closest('.choice-option');
    if(!target) target = e.target.closest('.choice-group');

    draggedElement = e.target;
};

const handleDragEnter = (e) => {
    e.preventDefault();
    if(!inChoiceEditMode) return;

    if(!e.target.closest) return;

    let target = null;

    if(draggedElement.classList.contains('choice-option')){
        target = e.target.closest('.choice-option');
        
        // The dragged element is looking to join another menu item and hasn't found one.
        if(!target) return;

        // also check if they are in the same group.
        let targetChoiceID = target.closest('.choice-group').id;
        let draggedElementChoiceID = draggedElement.closest('.choice-group').id;
        if(targetChoiceID !== draggedElementChoiceID) return;
    }

    // If we are here, and no target, the dragged element and target are both groups.
    if(!target) target = e.target.closest('.choice-group');

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
    let startGroupID = draggedElement.parentElement.id;
    let endGroupID = dropTarget.parentElement.id;

    // Element can only be dragged onto elements of same class and within same Group.
    if(dropTarget !== null && startGroupID === endGroupID){
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

const addDragAndDropHandlers = (element) => {
    element.addEventListener('dragstart', handleDragStart);
    element.addEventListener('dragenter', handleDragEnter);
    element.addEventListener('dragover', handleDragOver);
    element.addEventListener('drop', handleDrop);
    element.addEventListener('dragend', handleDragEnd);
};

const submitChoiceData = (e) => {
    e.preventDefault();

    let data = getChoiceData();

    const url = '/Dashboard/menu/item/updateChoices';

    postJSON(url, data).then(response => response.text()).then(result => {
        location.reload();
    });
};



const submitChoiceSequence = (e) => {
    e.preventDefault();

    let data = serializeMenu();

    const url = '/Dashboard/menu/item/updateChoicesSequence';

    postJSON(url, data).then(response => response.text()).then(result => {
        location.reload();
    });
};

let previousState = null;
let choiceData = null;

const beginChoiceOrderEditMode = () => {
    inChoiceEditMode = true;

    previousState = choicesContainer.innerHTML;
    choiceData = getChoiceData();

    submitChoicesButton.removeEventListener('click', submitChoiceData);
    submitChoicesButton.addEventListener('click', submitChoiceSequence);

    addGroupButton.classList.add('inactive');
    addGroupButton.disabled = true;

    choiceToggleEditButton.innerText = 'Cancel';
    choiceToggleEditButton.classList.add('cancel');

    while(choicesContainer.firstChild) {
        choicesContainer.removeChild(choicesContainer.firstChild);
    }

    let editContainer = document.createElement('div');
    editContainer.classList.add('shadow');
    editContainer.classList.add('text-form-inner-container');

    for(var groupID in choiceData){
        let splitGroupID = groupID.split('-')[0];

        let groupContainer = document.createElement('div');
        groupContainer.id = groupID;
        groupContainer.classList.add('draggable');
        groupContainer.classList.add('choice-group');
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
                optionElement.classList.add('draggable');
                optionElement.classList.add('choice-option');
                optionElement.draggable = true;
                optionElement.innerText = choiceData[groupID][optionID]['name'];

                optionsList.appendChild(optionElement);
            }
        }

        groupContainer.appendChild(optionsList);

        editContainer.appendChild(groupContainer);
    }

    choicesContainer.appendChild(editContainer);

    allDraggableElements = document.querySelectorAll('.choice-group, .choice-option');
};

const endChoiceOrderEditMode = () => {
    inChoiceEditMode = false;

    allDraggableElements = null;

    submitChoicesButton.removeEventListener('click', submitChoiceSequence);
    submitChoicesButton.addEventListener('click', submitChoiceData);

    addGroupButton.classList.remove('inactive');
    addGroupButton.disabled = false;

    choiceToggleEditButton.innerText = 'Edit Order';
    choiceToggleEditButton.classList.remove('cancel');

    choicesContainer.innerHTML = previousState;
    choiceData = null;
};

choiceToggleEditButton.addEventListener('click', e => {
    e.preventDefault();

    if(inChoiceEditMode){
        endChoiceOrderEditMode();
    } else {
        beginChoiceOrderEditMode();
    }
});

// initial state is to submit the normal data.
submitChoicesButton.addEventListener('click', submitChoiceData);

const deleteButton = document.querySelector('#delete-item');
deleteButton.addEventListener('click', (e) => {
    let result = confirm("Are you sure you want to delete this item?");
    if(result){
        const id = {'id' : e.target.closest('button').dataset.id};
        const url = '/Dashboard/menu/item/delete';
        postJSON(url, id).then(response => response.text).then(result => {
            window.location.replace('/Dashboard/menu');
        });
    }
});