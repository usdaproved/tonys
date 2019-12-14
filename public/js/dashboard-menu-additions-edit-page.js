"use strict";

const CSRFToken = document.querySelector('#CSRFToken').value;
const isLinkedURL = window.location.origin + '/Dashboard/menu/additions/isLinkedToItem'

let updateButtons = document.querySelectorAll('.addition-update-button');
let removeButtons = document.querySelectorAll('.addition-remove-button');

const postJSON = (url, json) => {
    url = window.location.origin + url;
    json["CSRFToken"] = CSRFToken;
    const data = JSON.stringify(json);
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: data
    });
};

const updateHandler = e => {
    e.preventDefault();
    let additionContainer = e.target.closest('.addition');

    let additionID = additionContainer.id.split('-')[0];
    let name = additionContainer.querySelector('.addition-name').value;
    let price = additionContainer.querySelector('.addition-price').value;

    let data = {
        "addition-id" : additionID,
        "name" : name,
        "price" : price
    };

    let url = '/Dashboard/menu/additions/updateAddition';

    postJSON(url, data).then(response => response.text()).then(result => {

    });
};

const removeHandler = e => {
    e.preventDefault();
    let additionContainer = e.target.closest('.addition');
    let additionID = additionContainer.id.split('-')[0];

    // Make request to see if addition is attached to items.
    // if it is notify the user, they can accept whether to remove from everything,
    // or cancel.
    let url = isLinkedURL + `?id=${additionID}`;
    fetch(url).then(response => response.text()).then(isLinkedToItem => {
        if(isLinkedToItem === 'true'){
            let confirmed = confirm('This addition is currently linked to menu items, remove all instances?');
            if(!confirmed){
                return;
            }
        }
        url = '/Dashboard/menu/additions/removeAddition';
        let data = {"addition-id": additionID};
        postJSON(url, data);

        additionContainer.remove();
    });
};

updateButtons.forEach(button => {
    button.addEventListener('click', updateHandler);
});

removeButtons.forEach(button => {
    button.addEventListener('click', removeHandler);
});

