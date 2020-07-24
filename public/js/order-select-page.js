// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, createLineItemElement, intToCurrency } from './utility.js';

"use strict";

const radioUncheckedSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0V0z" fill="none"/>
<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
</svg>`;
const radioCheckedSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0V0z" fill="none"/>
<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
<circle cx="12" cy="12" r="5"/>
</svg>`;
const checkboxUnchekedSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0z" fill="none"/>
<path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
</svg>`;
const checkboxCheckedSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0z" fill="none"/>
<path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
</svg>`;

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

const onAdditionSelected = (e) => {
    let targetButton = e.target.closest('button');

    let svgToRemove = targetButton.querySelector('svg');
    targetButton.removeChild(svgToRemove);
    
    if(targetButton.classList.contains('selected')){
        targetButton.classList.remove('selected');
        targetButton.innerHTML += checkboxUnchekedSVG;
    } else {
        targetButton.classList.add('selected');
        targetButton.innerHTML += checkboxCheckedSVG;
    }
}

const isReadyToSubmit = () => {
    let dialog = document.querySelector('#dialog-container');
    let choiceOptionInputs = dialog.querySelectorAll('.choice-option-input');

    let selectedOptions = {};
    selectedOptions.choices = {};
    
    choiceOptionInputs.forEach(input => {
        if(typeof selectedOptions.choices[`${input.name}-choice`] === 'undefined'){
            selectedOptions.choices[`${input.name}-choice`] = [];
        }

        let inputButton = input.closest('button');

        if(inputButton.classList.contains('selected')){
            selectedOptions.choices[`${input.name}-choice`].push(input.value);
        }
    });

    let validated = true;
    for(var choice in selectedOptions.choices){
        let choiceID = choice.split('-')[0];
        let numberSelected = selectedOptions.choices[choice].length;
        if(numberSelected < itemDataStorage.choices[choiceID].min_picks
          || numberSelected > itemDataStorage.choices[choiceID].max_picks){
            validated = false;
        }
    }

    return validated;
}

const onChoiceSelection = (e) => {
    let targetButton = e.target.closest('button');
    let targetInput = targetButton.querySelector('input');
    let maxPicks = itemDataStorage.choices[targetInput.name].max_picks;
    // if already checked, then we are unchecking it and no other work is necessary.
    if(targetButton.classList.contains('selected')){
        targetButton.classList.remove('selected');

        let svgToRemove = targetButton.querySelector('svg');
        targetButton.removeChild(svgToRemove);

        if(maxPicks == 1){
            targetButton.innerHTML += radioUncheckedSVG;
        } else {
            targetButton.innerHTML += checkboxUnchekedSVG;
        }

        if(!isReadyToSubmit()){
            const submitButton = document.querySelector('.item-submit-button');
            if(submitButton.classList.contains('valid')){
                submitButton.classList.remove('valid');
            }
        }

        return;
    }

    let choiceContainer = e.target.closest('.item-choice-container');
    let optionButtons = choiceContainer.querySelectorAll('.item-option-button');
    
    let amountSelected = 0;
    optionButtons.forEach(option => {
        if(option.classList.contains('selected')){
            amountSelected++;
        }
    });

    if(amountSelected == maxPicks){
        if(maxPicks == 1){
            optionButtons.forEach(option => {
                option.classList.remove('selected');

                let svgToRemove = option.querySelector('svg');
                option.removeChild(svgToRemove);
                option.innerHTML += radioUncheckedSVG;
            });

            targetButton.classList.add('selected');

            let svgToRemove = targetButton.querySelector('svg');
            targetButton.removeChild(svgToRemove);
            targetButton.innerHTML += radioCheckedSVG;
        }
    } else {
        targetButton.classList.add('selected');

        let svgToRemove = targetButton.querySelector('svg');
        targetButton.removeChild(svgToRemove);

        if(maxPicks == 1){
            targetButton.innerHTML += radioCheckedSVG;
        } else {
            targetButton.innerHTML += checkboxCheckedSVG;
        }
    }

    const submitButton = document.querySelector('.item-submit-button');
    if(isReadyToSubmit()){
        submitButton.classList.add('valid');
    } else {
        if(submitButton.classList.contains('valid')){
            submitButton.classList.remove('valid');
        }
    }
};

const onQuantityChange = (e) => {
    let targetButton = e.target.closest('button');
    let quantityElement = e.target.closest('.item-quantity-container').querySelector('.item-quantity-value');
    let quantityValue = parseInt(quantityElement.innerText);

    if(targetButton.classList.contains('item-quantity-down-button')){
        if(quantityValue === 1){
            return;
        }

        quantityElement.innerText = quantityValue - 1;
    } else {
        if(quantityValue === 15){
            return;
        }

        quantityElement.innerText = quantityValue + 1;
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

    let exitButton = document.createElement('button');
    exitButton.innerHTML = `<svg class="navigation-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>`;
    exitButton.classList.add('dialog-exit-button');
    exitButton.classList.add('svg-button');
    exitButton.addEventListener('click', exitDialogHandler);

    dialogInfoContainer.appendChild(exitButton);

    let header = document.createElement('h1');
    header.innerText = itemData.name;

    dialogInfoContainer.appendChild(header);

    let choices = itemData.choices;
    for(var choice in choices){
        let choiceContainer = document.createElement('div');
        choiceContainer.classList.add('item-choice-container');

        let choiceHeader = document.createElement('h3');
        choiceHeader.innerText = choices[choice].name;

        choiceContainer.appendChild(choiceHeader);

        let choicePicksDescription = document.createElement('p');
        choicePicksDescription.innerText = 
            getChoicePickDescription(choices[choice].min_picks, choices[choice].max_picks);

        choiceContainer.appendChild(choicePicksDescription);

        let options = choices[choice].options;
        for(var option in options){
            let optionContainer = document.createElement('div');
            optionContainer.classList.add('item-option-container');

            let optionInputButton = document.createElement('button');
            optionInputButton.classList.add('svg-button');
            optionInputButton.classList.add('item-option-button');
            
            optionInputButton.addEventListener('click', onChoiceSelection);
            if(parseInt(choices[choice].max_picks) === 1 
            && parseInt(choices[choice].min_picks) === 1){
                optionInputButton.innerHTML = radioUncheckedSVG;
            } else {
                optionInputButton.innerHTML = checkboxUnchekedSVG;
            }

            let optionInput = document.createElement('input');
            optionInput.type = 'hidden';
            optionInput.classList.add('choice-option-input');
            optionInput.name = choices[choice].id;
            optionInput.value = options[option].id;
            optionInput.id = choices[choice].id + '-' + options[option].id;

            optionInputButton.appendChild(optionInput);

            optionContainer.appendChild(optionInputButton);

            let optionInputLabel = document.createElement('span');
            optionInputLabel.classList.add('item-option-name');
            optionInputLabel.innerText = options[option].name;
            if(parseFloat(options[option].price_modifier) !== 0){
                optionInputLabel.innerText += ` (+ $${intToCurrency(options[option].price_modifier)})`;
            }

            optionContainer.appendChild(optionInputLabel);

            choiceContainer.appendChild(optionContainer);
        }

        dialogInfoContainer.appendChild(choiceContainer);
    }

    if(!Array.isArray(itemData.additions)){
        let additionsContainer = document.createElement('div');
        additionsContainer.classList.add('item-additions-container');
        let additionsHeader = document.createElement('h3');
        additionsHeader.innerText = 'Additions';

        additionsContainer.appendChild(additionsHeader);

        let additions = itemData.additions;
        for(var addition in additions){
            let additionContainer = document.createElement('div');
            additionContainer.classList.add('item-addition-container');

            let additionsInputButton = document.createElement('button');
            additionsInputButton.classList.add('svg-button');
            additionsInputButton.classList.add('item-additions-button');
            additionsInputButton.innerHTML = checkboxUnchekedSVG;
            additionsInputButton.addEventListener('click', onAdditionSelected);
            
            let additionInput = document.createElement('input');
            additionInput.type = 'hidden';
            additionInput.name = additions[addition].id;
            additionInput.id = additionInput.name + '-addition';
            additionInput.value = additionInput.name;
            additionInput.classList.add('addition-input');

            additionsInputButton.appendChild(additionInput);

            additionContainer.appendChild(additionsInputButton);

            let additionInputLabel = document.createElement('span');
            additionInputLabel.classList.add('item-addition-name');
            additionInputLabel.innerText = additions[addition].name;
            if(parseFloat(additions[addition].price_modifier) !== 0){
                additionInputLabel.innerText += ` (+ $${intToCurrency(additions[addition].price_modifier)})`;
            }

            additionContainer.appendChild(additionInputLabel);

            additionsContainer.appendChild(additionContainer);
        }

        dialogInfoContainer.appendChild(additionsContainer);
    }

    let dialogBottomContainer = document.createElement('div');
    dialogBottomContainer.classList.add('dialog-bottom-container');

    let commentContainer = document.createElement('div');
    commentContainer.classList.add('item-comment-container');

    let commentLabel = document.createElement('label');
    commentLabel.classList.add('item-comment-label');
    commentLabel.setAttribute('for', 'comment-input');
    commentLabel.innerText = 'Comment';

    commentContainer.appendChild(commentLabel);

    let commentInput = document.createElement('textarea');
    commentInput.id = 'comment-input';
    commentInput.maxLength = 200;
    commentInput.placeholder = 'Optional note for the kitchen. (200 characters)';

    commentContainer.appendChild(commentInput);

    dialogBottomContainer.appendChild(commentContainer);

    let quantityContainer = document.createElement('div');
    quantityContainer.classList.add('item-quantity-container');

    let quantityInputDown = document.createElement('button');
    quantityInputDown.classList.add('svg-button');
    quantityInputDown.classList.add('item-quantity-down-button');
    quantityInputDown.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
        <path d="M0 0h24v24H0V0z" fill="none"/>
        <path d="M7 12c0 .55.45 1 1 1h8c.55 0 1-.45 1-1s-.45-1-1-1H8c-.55 0-1 .45-1 1zm5-10C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
        </svg>`;
    quantityInputDown.addEventListener('click', onQuantityChange);

    quantityContainer.appendChild(quantityInputDown);

    let quantityValue = document.createElement('span');
    quantityValue.classList.add('item-quantity-value');
    quantityValue.innerText = '1';

    quantityContainer.appendChild(quantityValue);

    let quantityInputUp = document.createElement('button');
    quantityInputUp.classList.add('svg-button');
    quantityInputUp.classList.add('item-quantity-up-button');
    quantityInputUp.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
        <path d="M0 0h24v24H0V0z" fill="none"/>
        <path d="M12 7c-.55 0-1 .45-1 1v3H8c-.55 0-1 .45-1 1s.45 1 1 1h3v3c0 .55.45 1 1 1s1-.45 1-1v-3h3c.55 0 1-.45 1-1s-.45-1-1-1h-3V8c0-.55-.45-1-1-1zm0-5C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
        </svg>`;
    quantityInputUp.addEventListener('click', onQuantityChange);

    quantityContainer.appendChild(quantityInputUp);

    dialogBottomContainer.appendChild(quantityContainer);

    let submitButtonContainer = document.createElement('div');
    submitButtonContainer.classList.add('item-submit-button-container');

    let submitButton = document.createElement('a');
    submitButton.classList.add('item-submit-button');
    submitButton.href = '#';
    submitButton.innerText = 'Submit';
    submitButton.addEventListener('click', submitDialogHandler);

    submitButtonContainer.appendChild(submitButton);

    dialogBottomContainer.appendChild(submitButtonContainer);

    dialogInfoContainer.appendChild(dialogBottomContainer);

    dialog.appendChild(dialogInfoContainer);

    dialogContainer.appendChild(dialog);

    return dialogContainer;
};

const beginDialogMode = () => {
    let dialog = newDialog(itemDataStorage);
    document.body.appendChild(dialog);

    // we need to check if there are no requirements.
    // otherwise the green submit won't show until the customer interacts with something that calls. isReadyToSubmit()
    const submitButton = document.querySelector('.item-submit-button');
    if(isReadyToSubmit()){
        submitButton.classList.add('valid');
    } else {
        if(submitButton.classList.contains('valid')){
            submitButton.classList.remove('valid');
        }
    }

    document.body.style.overflow = 'hidden';
};

const endDialogMode = () => {
    let dialog = document.querySelector('#dialog-container');
    dialog.remove();

    document.body.style.overflow = 'auto';
};

const exitDialogHandler = e => {
    if(e.target.id === 'dialog-container' || e.target.closest('.dialog-exit-button')){
        e.preventDefault();

        if(document.querySelector('#dialog-container')){
            endDialogMode();
        }
    }
};

const submitDialogHandler = (e) => {
    e.preventDefault();

    let dialog = document.querySelector('#dialog-container');
    let choiceOptionInputs = dialog.querySelectorAll('.choice-option-input');
    let additionInputs = dialog.querySelectorAll('.addition-input');

    let userItemData = {};
    userItemData.itemID = itemDataStorage.id;
    userItemData.quantity = parseInt(dialog.querySelector('.item-quantity-value').innerText);
    userItemData.comment = dialog.querySelector('#comment-input').value;
    userItemData.choices = {};
    userItemData.additions = [];
    
    choiceOptionInputs.forEach(input => {
        if(typeof userItemData.choices[`${input.name}-choice`] === 'undefined'){
            userItemData.choices[`${input.name}-choice`] = [];
        }

        let inputButton = input.closest('button');

        if(inputButton.classList.contains('selected')){
            userItemData.choices[`${input.name}-choice`].push(input.value);
        }
    });

    additionInputs.forEach(input => {
        let inputButton = input.closest('button');
        if(inputButton.classList.contains('selected')){
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