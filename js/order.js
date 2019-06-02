"use strict";

// Get every menu item, add the '- 0 +', keep track of the values to be submitted.
let menuItemsE = document.getElementsByClassName('flex-item');
let menuItemsText = new Array(menuItemsE.length);
let menuItemsQuantity = new Array(menuItemsE.length);

for (let i = 0; i < menuItemsE.length; i++){
    menuItemsText[i] = menuItemsE[i].innerHTML;
    // initialize to zero in case that's a thing in js.
    menuItemsQuantity[i] = 0;
    // Add on click function call.
    menuItemsE[i].addEventListener("click", function handler(e){
	e.stopPropagation();
	onInitialClick(i);
	menuItemsE[i].removeEventListener("click", handler);
    });
}


function onInitialClick(i){
    menuItemsQuantity[i] += 1;
    menuItemsE[i].innerHTML = menuItemsText[i] +
	" <span class='quantifier'>-</span> " +
	"<span class='quantity'>" + menuItemsQuantity[i] + "</span>" +
	" <span class='quantifier'>+</span>";
    // Remove the hover effect, add a new one to the quantifiers.
    menuItemsE[i].classList.add("hover-disabled");
    for (let x = 0; x < 2; x++){
	menuItemsE[i].getElementsByClassName('quantifier')[x].addEventListener("click", function(e){
	    e.stopPropagation();
	    onClick(i, x);
	});
    }

    // Add a submit button to the flex-container, as we have stuff on our plate.
    if(!getSubmitButton()){
	let newSubmit = document.createElement('input');
	newSubmit.setAttribute('type', 'submit');
	newSubmit.setAttribute('value', 'Order');
	newSubmit.setAttribute('name', 'submit');
	newSubmit.setAttribute('id', 'order_button');
	document.getElementsByClassName('flex-container')[0].appendChild(newSubmit);
    }
}

function onClick(i, x){
    menuItemsQuantity[i] += x ? 1 : -1;

    if (menuItemsQuantity[i] <= 0){
	// return the state of the block back to before onInitialClick.
	menuItemsQuantity[i] = 0;
	menuItemsE[i].innerHTML = menuItemsText[i];
	menuItemsE[i].classList.remove('hover-disabled');
	menuItemsE[i].addEventListener('click', function handler(e){
	    e.stopPropagation();
	    onInitialClick(i);
	    menuItemsE[i].removeEventListener('click', handler);
	});

	// check to see if we should remove the submit button.
	// This only needs to be done if something hits zero,
	// otherwise we know something has to have value.
	if(!quantityCheck()) {
	    document.getElementsByTagName('input')[0].remove();
	}
	return;
    }

    menuItemsE[i].getElementsByClassName('quantity')[0].innerHTML = menuItemsQuantity[i];
    // Be sure to do server side check for negative values.
    
}

// returns false if none found.
function getSubmitButton(){
    let inputs = document.getElementsByTagName('input');
    for (let i = 0; i < inputs.length; i++){
	if (inputs[i].getAttribute('type') == 'submit') return inputs[i];
    }

    return false;
}

function quantityCheck(){
    for (let i = 0; i < menuItemsQuantity.length; i++){
	// As long as one item is above 0.
	if(menuItemsQuantity[i] > 0) return true;
    }
    return false;
}
