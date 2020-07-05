// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, createLineItemElement, intToCurrency } from './utility.js';

"use strict";

let itemElements = document.querySelectorAll('.order-container');
let cartContainer = document.querySelector('#cart-container');
let cartButtonElement = document.querySelector('#cart-button');
let cartItemCountElement = document.querySelector('#cart-item-count');

// If the selection is hidden on page load, then there is no selection.
let isOrderTypeSelected = !document.querySelector('#order-type-selection').hidden;

// Given a number, modify the count by that number. -1 remove 1 or 3 add 3.
const updateCartItemCount = (modifier) => {
    let currentCount = parseInt(cartItemCountElement.innerText);
    cartItemCountElement.innerText = currentCount + modifier;
};

const clickRemoveLineItem = (e) => {
    let lineItem = e.target.closest('.line-item');
    let lineItemUUID = lineItem.id;
    let quantity = parseInt(lineItem.querySelector('.line-item-quantity').innerText);

    let data = {"line_item_uuid": lineItemUUID}
    let url = '/Order/removeItemFromCart';
    postJSON(url, data).then(response => response.text()).then(result => {
        updateCartItemCount(-quantity);
        lineItem.remove();
        if(parseInt(cartItemCountElement.innerText) === 0){
            cartButtonElement.setAttribute('hidden', 'true');
        }
    });
};

const addRemoveToLineItem = (item) => {
    let removeButton = document.createElement('button');
    removeButton.classList.add('remove-line-item');
    removeButton.innerText = 'Remove';
    removeButton.addEventListener('click', clickRemoveLineItem);
    item.prepend(removeButton);
}

const initializeCart = () => {
    let lineItems = cartContainer.querySelectorAll('.line-item');
    lineItems.forEach(item => {
        addRemoveToLineItem(item);
    });
};

initializeCart();

const removeLineItemButtons = document.querySelectorAll('.remove-line-item');

removeLineItemButtons.forEach(button => {
    button.addEventListener('click', clickRemoveLineItem);
});



cartButtonElement.addEventListener('click', (e) => {
    if(cartContainer.hidden){
        cartContainer.removeAttribute('hidden');
    } else {
        cartContainer.setAttribute('hidden', 'true');
    }
});

const checkSubmitLink = () => {
    if(isOrderTypeSelected && (parseInt(cartItemCountElement.innerText) > 0)){
        document.querySelector('#submit-container').removeAttribute('hidden');
    }
};

let itemDataStorage;

const getChoicePickDescription = (minPicks, maxPicks) => {
    let pluralizedMinOption = (minPicks > 1) ? 'options' : 'option';
    let pluralizedMaxOption = (maxPicks > 1) ? 'options' : 'option';
    
    let result = "";

    if(minPicks === '0'){
        result = `Pick up to ${maxPicks} ${pluralizedMaxOption}.`;
    } else {
        if(minPicks === maxPicks){
            result = `Pick ${maxPicks} ${pluralizedMaxOption}.`;
        } else {
            result = `Pick at least ${minPicks} ${pluralizedMinOption} or up to ${maxPicks}.`;
        }
    }

    return result;
};

const onCheckboxChange = (e) => {
    let maxPicks = itemDataStorage.choices[e.target.name].max_picks;
    let checkboxes = document.querySelectorAll(`input.choice-option-input[name='${e.target.name}']`);
    let checkedBoxCount = 0;
    checkboxes.forEach(checkbox => {
        if(checkbox.checked){
            checkedBoxCount++;
        }
    });

    if(checkedBoxCount > maxPicks){
        e.target.checked = false;
    }
};

const newDialog = (itemData) => {
    let dialogContainer = document.createElement('div');
    dialogContainer.id = 'dialog-container';
    dialogContainer.classList.add('dialog-container');
    dialogContainer.addEventListener('click', exitDialogHandler);

    let dialog = document.createElement('div');
    dialog.setAttribute('role', 'dialog');
    dialog.classList.add('dialog');

    let dialogInfoContainer = document.createElement('div');
    dialogInfoContainer.classList.add('dialog-info-container');

    let header = document.createElement('h1');
    header.innerText = itemData.name;

    dialogInfoContainer.appendChild(header);

    let choices = itemData.choices;
    for(var choice in choices){
        let choiceHeader = document.createElement('h3');
        choiceHeader.innerText = choices[choice].name;

        dialogInfoContainer.appendChild(choiceHeader);

        let choicePicksDescription = document.createElement('p');
        choicePicksDescription.innerText = 
            getChoicePickDescription(choices[choice].min_picks, choices[choice].max_picks);

        dialogInfoContainer.appendChild(choicePicksDescription);

        let options = choices[choice].options;
        for(var option in options){
            let optionInput = document.createElement('input');
            if(parseInt(choices[choice].max_picks) === 1 
            && parseInt(choices[choice].min_picks) === 1){
                optionInput.type = 'radio';
            } else {
                optionInput.type = 'checkbox';
                optionInput.addEventListener('change', onCheckboxChange);
            }

            optionInput.classList.add('choice-option-input');
            optionInput.name = choices[choice].id;
            optionInput.value = options[option].id;
            optionInput.id = choices[choice].id + '-' + options[option].id;

            dialogInfoContainer.appendChild(optionInput);

            let optionInputLabel = document.createElement('label');
            optionInputLabel.setAttribute('for', optionInput.id);
            optionInputLabel.innerText = options[option].name;
            if(parseFloat(options[option].price_modifier) !== 0){
                optionInputLabel.innerText += ` (+ $${intToCurrency(options[option].price_modifier)})`;
            }

            dialogInfoContainer.appendChild(optionInputLabel);
        }
    }

    if(itemData.additions !== null){
        let additionsHeader = document.createElement('h3');
        additionsHeader.innerText = 'Additions';

        dialogInfoContainer.appendChild(additionsHeader);

        let additions = itemData.additions;
        for(var addition in additions){
            let additionInput = document.createElement('input');
            additionInput.type = 'checkbox';
            additionInput.name = additions[addition].id;
            additionInput.id = additionInput.name + '-addition';
            additionInput.value = additionInput.name;
            additionInput.classList.add('addition-input');

            dialogInfoContainer.appendChild(additionInput);

            let additionInputLabel = document.createElement('label');
            additionInputLabel.setAttribute('for', additionInput.id);
            additionInputLabel.innerText = additions[addition].name;
            if(parseFloat(additions[addition].price_modifier) !== 0){
                additionInputLabel.innerText += ` (+ $${intToCurrency(additions[addition].price_modifier)})`;
            }

            dialogInfoContainer.appendChild(additionInputLabel);

            dialogInfoContainer.appendChild(document.createElement('br'));
        }
    }

    let commentLabel = document.createElement('label');
    commentLabel.setAttribute('for', 'comment-input');
    commentLabel.innerText = 'Comment:';

    dialogInfoContainer.appendChild(commentLabel);

    let commentInput = document.createElement('textarea');
    commentInput.id = 'comment-input';
    commentInput.maxLength = 200;
    commentInput.placeholder = 'Optional note for the kitchen. (200 characters)';

    dialogInfoContainer.appendChild(commentInput);

    let quantityLabel = document.createElement('label');
    quantityLabel.setAttribute('for', 'quantity-input');
    quantityLabel.innerText = 'Quantity:';

    dialogInfoContainer.appendChild(quantityLabel);

    let quantityInput = document.createElement('input');
    quantityInput.type = 'number';
    quantityInput.id = 'quantity-input';
    quantityInput.name = 'quantity';
    quantityInput.value = 1;
    quantityInput.setAttribute('min', '1');

    dialogInfoContainer.appendChild(quantityInput);

    let submitButton = document.createElement('input');
    submitButton.type = 'submit';
    submitButton.value = 'submit';
    submitButton.addEventListener('click', submitDialogHandler);

    dialogInfoContainer.appendChild(submitButton);

    dialog.appendChild(dialogInfoContainer);

    dialogContainer.appendChild(dialog);

    return dialogContainer;
};

const beginDialogMode = () => {
    let dialog = newDialog(itemDataStorage);
    document.body.appendChild(dialog);

    document.body.style.overflow = 'hidden';
};

const endDialogMode = () => {
    let dialog = document.querySelector('#dialog-container');
    dialog.remove();

    document.body.style.overflow = 'auto';
};

const exitDialogHandler = e => {
    if(e.target.id === 'dialog-container'){
        e.preventDefault();

        endDialogMode();
    }
};

const submitDialogHandler = (e) => {
    e.preventDefault();

    let dialog = document.querySelector('#dialog-container');
    let choiceOptionInputs = dialog.querySelectorAll('.choice-option-input');
    let additionInputs = dialog.querySelectorAll('.addition-input');

    let userItemData = {};
    userItemData.itemID = itemDataStorage.id;
    userItemData.quantity = dialog.querySelector('#quantity-input').value;
    userItemData.comment = dialog.querySelector('#comment-input').value;
    userItemData.choices = {};
    userItemData.additions = [];
    
    choiceOptionInputs.forEach(input => {
        if(typeof userItemData.choices[`${input.name}-choice`] === 'undefined'){
            userItemData.choices[`${input.name}-choice`] = [];
        }

        if(input.checked){
            userItemData.choices[`${input.name}-choice`].push(input.value);
        }
    });

    additionInputs.forEach(input => {
        if(input.checked){
            userItemData.additions.push(input.value);
        }
    });

    let validated = true;
    for(var choice in userItemData.choices){
        let choiceID = choice.split('-')[0];
        let numberSelected = userItemData.choices[choice].length;
        if(numberSelected < itemDataStorage.choices[choiceID].min_picks
          || numberSelected > itemDataStorage.choices[choiceID].max_picks){
            validated = false;
        }
    }

    if(!validated){
        // TODO(trystan): create some message of failure.
        return;
    }

    let url = '/Order/addItemToCart';
    postJSON(url, userItemData).then(response => response.json()).then(lineItem => {
        let element = createLineItemElement(lineItem);
        addRemoveToLineItem(element);
        cartContainer.querySelector('.line-items-container').appendChild(element);
        updateCartItemCount(parseInt(userItemData.quantity));
        if(parseInt(cartItemCountElement.innerText) > 0) {
            cartButtonElement.removeAttribute('hidden');
        }
        checkSubmitLink();
    });

    endDialogMode();
};



itemElements.forEach(element => {
    if(element.classList.contains('inactive')){
        return;
    }
    element.addEventListener('click', e => {
        e.preventDefault();

        let itemContainer = e.target.closest('.order-container');

        let itemID = itemContainer.id.split('-')[0];

        let data = {"itemID" : itemID};
        let url = '/Order/getItemDetails';
        
        postJSON(url, data).then(response => response.json()).then(result => {
            itemDataStorage = result;

            beginDialogMode();
        });
    });
});


const orderTypeButtonContainer = document.querySelector('#order-type-buttons');
const orderTypeSelectionContainer = document.querySelector('#order-type-selection');
const orderTypeChangeButton = orderTypeSelectionContainer.querySelector('#order-type-change-button');
const orderTypeText = orderTypeSelectionContainer.querySelector('#order-type-text');

const orderTypeSelected = (displayText) => {
    orderTypeButtonContainer.setAttribute("hidden", true);
    orderTypeSelectionContainer.removeAttribute('hidden');

    orderTypeText.innerText = displayText;
    
    isOrderTypeSelected = true;
    checkSubmitLink();
};

const orderTypeChange = () => {
    orderTypeButtonContainer.removeAttribute('hidden');
    orderTypeSelectionContainer.setAttribute('hidden', true);
};

orderTypeChangeButton.addEventListener('click', (e) => {
    orderTypeChange();
});

const deliveryButton = orderTypeButtonContainer.querySelector('#order-type-delivery-button');
const pickupButton = orderTypeButtonContainer.querySelector('#order-type-pickup-button');
const restaurantButton = orderTypeButtonContainer.querySelector('#order-type-restaurant-button');
const submitLink = document.querySelector('#order-submit-link');

const orderTypeUpdateURL = "/Order/setOrderType";

deliveryButton.addEventListener('click', (e) => {
    e.preventDefault();

    let data = {"order_type" : 0};

    postJSON(orderTypeUpdateURL, data);
    submitLink.href = "/Order/submit";
    orderTypeSelected("Delivery");
});

pickupButton.addEventListener('click', (e) => {
    e.preventDefault();

    let data = {"order_type" : 1};

    postJSON(orderTypeUpdateURL, data);
    submitLink.href = "/Order/submit";
    orderTypeSelected("Pickup");
});

if(restaurantButton){
    restaurantButton.addEventListener('click', (e) => {
        e.preventDefault();

        let data = {"order_type" : 2};

        postJSON(orderTypeUpdateURL, data);
        submitLink.href = "/Dashboard/orders/submit";
        orderTypeSelected("Restaurant");
    });

    // If not set, automatically set the order type to restaurant.
    // The worker can manually update it if they are actually ordering for themselves.
    if(!orderTypeButtonContainer.hidden){
        restaurantButton.click();
    }
}