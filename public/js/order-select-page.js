// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON, createLineItemElement, intToCurrency } from './utility.js';

"use strict";

// Alright. I know this is a very unelegant solution.
// But I want the SVG's to be pre-loaded.
const radioUncheckedSVGHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0V0z" fill="none"/>
<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
</svg>`;
const radioCheckedSVGHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0V0z" fill="none"/>
<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
<circle cx="12" cy="12" r="5"/>
</svg>`;
const checkboxUncheckedSVGHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0z" fill="none"/>
<path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
</svg>`;
const checkboxCheckedSVGHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="24px" height="24px">
<path d="M0 0h24v24H0z" fill="none"/>
<path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
</svg>`;

const temp_radioUncheckedSVG = document.createElement('template');
temp_radioUncheckedSVG.innerHTML = radioUncheckedSVGHTML;
const radioUncheckedSVG = temp_radioUncheckedSVG.content.firstChild;
const temp_radioCheckedSVG = document.createElement('template');
temp_radioCheckedSVG.innerHTML = radioCheckedSVGHTML;
const radioCheckedSVG = temp_radioCheckedSVG.content.firstChild;
const temp_checkboxUncheckedSVG = document.createElement('template');
temp_checkboxUncheckedSVG.innerHTML = checkboxUncheckedSVGHTML;
const checkboxUncheckedSVG = temp_checkboxUncheckedSVG.content.firstChild;
const temp_checkboxCheckedSVG = document.createElement('template');
temp_checkboxCheckedSVG.innerHTML = checkboxCheckedSVGHTML;
const checkboxCheckedSVG = temp_checkboxCheckedSVG.content.firstChild;

let itemElements = document.querySelectorAll('.order-container');
let cartContainer = document.querySelector('#cart-container');
let cartButtonElement = document.querySelector('#cart-button');
let cartItemCountElement = document.querySelector('#cart-item-count');
const orderSubmitButton = document.querySelector('#submit-container');

const svgDelete = `<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
    <path d="M0 0h24v24H0z" fill="none"/>
    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="#a11010"/>
    </svg>`;

// If the selection is hidden on page load, then there is no selection.
let isOrderTypeSelected = !document.querySelector('#order-type-selection').hidden;

// Given a number, modify the count by that number. -1 remove 1 or 3 add 3.
const updateCartItemCount = (modifier) => {
    let currentCount = parseInt(cartItemCountElement.innerText);
    cartItemCountElement.innerText = currentCount + modifier;

    if(parseInt(cartItemCountElement.innerText) > 0){
        cartItemCountElement.removeAttribute('hidden');
    } else {
        cartItemCountElement.setAttribute('hidden', 'true');
    }
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
    });
};

const addRemoveToLineItem = (item) => {
    let removeButton = document.createElement('button');
    removeButton.classList.add('svg-button');
    removeButton.classList.add('remove-line-item-button');
    removeButton.innerHTML = svgDelete;
    removeButton.addEventListener('click', clickRemoveLineItem);
    item.prepend(removeButton);
};

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

const cartExitButton = document.querySelector('#cart-exit-button');
cartExitButton.addEventListener('click', (e) => {
    cartContainer.style.display = 'none';
});


cartButtonElement.addEventListener('click', (e) => {
    if(cartContainer.style.display === 'block'){
        cartContainer.style.display = 'none';
    } else {
        cartContainer.style.display = 'block';
    }
});

const checkSubmitLink = () => {
    if(isOrderTypeSelected && (parseInt(cartItemCountElement.innerText) > 0)){
        document.querySelector('#submit-container').removeAttribute('hidden');
    }
};

let itemDataStorage;

let addedCost = 0;

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

const updateTotalPrice = (targetButton) => {
    let input = targetButton.querySelector('input');

    let added = targetButton.classList.contains('selected');
    let isOption = targetButton.classList.contains('item-option-button');
    let price = 0;

    if(isOption){
        price = itemDataStorage.choices[input.name].options[input.value].price_modifier;
    } else {
        price = itemDataStorage.additions[input.value].price_modifier;
    }

    price = parseInt(price);

    addedCost = addedCost + (added ? price : -price);

    const quantity = parseInt(document.querySelector('.item-quantity-value').innerText);
    const priceTextElement = document.querySelector('.item-price-total');
    priceTextElement.innerText = intToCurrency((parseInt(itemDataStorage.price) + addedCost) * quantity);
};

const onAdditionSelected = (e) => {
    let targetButton = e.target.closest('button');

    let svgToRemove = targetButton.querySelector('svg');
    targetButton.removeChild(svgToRemove);
    
    if(targetButton.classList.contains('selected')){
        targetButton.classList.remove('selected');
        targetButton.prepend(checkboxUncheckedSVG.cloneNode(true));
    } else {
        targetButton.classList.add('selected');
        targetButton.prepend(checkboxCheckedSVG.cloneNode(true));
    }

    updateTotalPrice(targetButton);
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
            targetButton.prepend(radioUncheckedSVG.cloneNode(true));
        } else {
            targetButton.prepend(checkboxUncheckedSVG.cloneNode(true));
        }

        if(!isReadyToSubmit()){
            const submitButton = document.querySelector('.item-submit-button');
            if(submitButton.classList.contains('valid')){
                submitButton.classList.remove('valid');
            }
        }

        updateTotalPrice(targetButton);

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

    let updateRequired = false;
    if(amountSelected == maxPicks){
        if(maxPicks == 1){
            updateRequired = true;
            optionButtons.forEach(option => {
                if(option.classList.contains('selected')){
                    option.classList.remove('selected');

                    let svgToRemove = option.querySelector('svg');
                    option.removeChild(svgToRemove);
                    option.prepend(radioUncheckedSVG.cloneNode(true));
                    updateTotalPrice(option);
                }
            });

            targetButton.classList.add('selected');

            let svgToRemove = targetButton.querySelector('svg');
            targetButton.removeChild(svgToRemove);
            targetButton.prepend(radioCheckedSVG.cloneNode(true));
        }
    } else {
        updateRequired = true;
        targetButton.classList.add('selected');

        let svgToRemove = targetButton.querySelector('svg');
        targetButton.removeChild(svgToRemove);

        if(maxPicks == 1){
            targetButton.prepend(radioCheckedSVG.cloneNode(true));
        } else {
            targetButton.prepend(checkboxCheckedSVG.cloneNode(true));
        }
    }

    if(!updateRequired) return;
    
    updateTotalPrice(targetButton);

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

    quantityValue = parseInt(quantityElement.innerText);
    const priceTextElement = document.querySelector('.item-price-total');
    priceTextElement.innerText = intToCurrency((parseInt(itemDataStorage.price) + addedCost) * quantityValue);
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
                optionInputButton.appendChild(radioUncheckedSVG.cloneNode(true));
            } else {
                optionInputButton.appendChild(checkboxUncheckedSVG.cloneNode(true));
            }

            let optionInput = document.createElement('input');
            optionInput.type = 'hidden';
            optionInput.classList.add('choice-option-input');
            optionInput.name = choices[choice].id;
            optionInput.value = options[option].id;
            optionInput.id = choices[choice].id + '-' + options[option].id;

            optionInputButton.appendChild(optionInput);

            let optionInputLabel = document.createElement('span');
            optionInputLabel.classList.add('item-option-name');
            optionInputLabel.innerText = options[option].name;
            if(parseFloat(options[option].price_modifier) !== 0){
                optionInputLabel.innerText += ` (+ $${intToCurrency(options[option].price_modifier)})`;
            }

            optionInputButton.appendChild(optionInputLabel);

            optionContainer.appendChild(optionInputButton);

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
            additionsInputButton.appendChild(checkboxUncheckedSVG.cloneNode(true));
            additionsInputButton.addEventListener('click', onAdditionSelected);
            
            let additionInput = document.createElement('input');
            additionInput.type = 'hidden';
            additionInput.name = additions[addition].id;
            additionInput.id = additionInput.name + '-addition';
            additionInput.value = additionInput.name;
            additionInput.classList.add('addition-input');

            additionsInputButton.appendChild(additionInput);

            let additionInputLabel = document.createElement('span');
            additionInputLabel.classList.add('item-addition-name');
            additionInputLabel.innerText = additions[addition].name;
            if(parseFloat(additions[addition].price_modifier) !== 0){
                additionInputLabel.innerText += ` (+ $${intToCurrency(additions[addition].price_modifier)})`;
            }

            additionsInputButton.appendChild(additionInputLabel);

            additionContainer.appendChild(additionsInputButton);

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
    submitButton.addEventListener('click', submitDialogHandler);

    let priceTotalContainer = document.createElement('span');
    priceTotalContainer.classList.add('item-price-total-container');

    let dollarSign = document.createTextNode('$');
    priceTotalContainer.appendChild(dollarSign);

    let priceTotal = document.createElement('span');
    priceTotal.classList.add('item-price-total');
    priceTotal.innerText = intToCurrency(itemData.price);
    priceTotalContainer.appendChild(priceTotal);

    submitButton.appendChild(priceTotalContainer);

    let submitText = document.createElement('span');
    submitText.classList.add('item-submit-text');
    submitText.innerText = 'Submit';
    submitButton.appendChild(submitText);

    submitButtonContainer.appendChild(submitButton);

    dialogBottomContainer.appendChild(submitButtonContainer);

    dialogInfoContainer.appendChild(dialogBottomContainer);

    dialog.appendChild(dialogInfoContainer);

    dialogContainer.appendChild(dialog);

    return dialogContainer;
};

const beginDialogMode = () => {
    addedCost = 0;
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

    document.body.classList.add('dialog-active');
};

const endDialogMode = () => {
    addedCost = 0;
    let dialog = document.querySelector('#dialog-container');
    dialog.remove();

    document.body.classList.remove('dialog-active');
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

const orderTypeSelected = (svg) => {
    orderTypeButtonContainer.setAttribute("hidden", true);
    orderTypeSelectionContainer.removeAttribute('hidden');

    svg.removeAttribute('hidden');
    
    isOrderTypeSelected = true;
    checkSubmitLink();
};

orderTypeChangeButton.addEventListener('click', (e) => {
    orderTypeButtonContainer.removeAttribute('hidden');
    orderTypeSelectionContainer.setAttribute('hidden', 'true');
    orderSubmitButton.setAttribute('hidden', 'true');

    const svgs = orderTypeChangeButton.querySelectorAll('svg');
    svgs.forEach(svg => {
        svg.setAttribute('hidden', 'true');
    });
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

    const svg = document.querySelector('#delivery-selected-svg');
    orderTypeSelected(svg);
});

pickupButton.addEventListener('click', (e) => {
    e.preventDefault();

    let data = {"order_type" : 1};

    postJSON(orderTypeUpdateURL, data);
    submitLink.href = "/Order/submit";
    
    const svg = document.querySelector('#pickup-selected-svg');
    orderTypeSelected(svg);
});


if(restaurantButton){
    restaurantButton.addEventListener('click', (e) => {
        e.preventDefault();

        let data = {"order_type" : 2};

        postJSON(orderTypeUpdateURL, data);
        submitLink.href = "/Dashboard/orders/submit";
        
        const svg = document.querySelector('#restaurant-selected-svg');
        orderTypeSelected(svg);
    });

    // If not set, automatically set the order type to restaurant.
    // The worker can manually update it if they are actually ordering for themselves.
    if(!orderTypeButtonContainer.hidden){
        restaurantButton.click();
    }
}