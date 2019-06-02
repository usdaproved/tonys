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
    menuItemsE[i].addEventListener("click", function handler(){
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
	menuItemsE[i].getElementsByClassName('quantifier')[x].addEventListener("click", function(){onClick(i, x);});
    }
}

function onClick(i, x){
    menuItemsQuantity[i] += x ? 1 : -1;

    if (menuItemsQuantity[i] < 0) menuItemsQuantity[i] = 0; 

    menuItemsE[i].getElementsByClassName('quantity')[0].innerHTML = menuItemsQuantity[i];
    // Be sure to do server side check for no negative values.
    
}
