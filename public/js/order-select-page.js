import { postJSON } from './utility.js';

"use strict";

let itemElements = document.querySelectorAll('.item-container');
let CSRFToken = document.querySelector('#CSRFToken').value;

let itemData;

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
    let maxPicks = itemData.choices[e.target.name].max_picks;
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
                optionInputLabel.innerText += ` (+ $${options[option].price_modifier})`;
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
                additionInputLabel.innerText += ` (+ $${additions[addition].price_modifier})`;
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
    let dialog = newDialog(itemData);
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
    userItemData.itemID = itemData.id;
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
        if(numberSelected < itemData.choices[choiceID].min_picks
          || numberSelected > itemData.choices[choiceID].max_picks){
            validated = false;
        }
    }

    if(!validated){
        // TODO(trystan): create some message of failure.
        return;
    }

    let url = '/Order/addItemToCart';
    postJSON(url, userItemData, CSRFToken).then(response => response.text()).then(result => {
        console.log(result);
    });

    endDialogMode();
};



itemElements.forEach(element => {
    element.addEventListener('click', e => {
        e.preventDefault();

        let itemContainer = e.target.closest('.item-container');

        let itemID = itemContainer.id.split('-')[0];

        let data = {"itemID" : itemID};
        let url = '/Order/getItemDetails';
        
        postJSON(url, data, CSRFToken).then(response => response.json()).then(data => {
            itemData = data;

            beginDialogMode();
        });
    });
});