import { postJSON } from './utility.js';

"use strict";

const addPrinterButton = document.querySelector('#add-printer');
const removePrinterButtons = document.querySelectorAll('.remove-printer');

addPrinterButton.addEventListener('click', (e) => {
    const printerName = document.querySelector('#add-printer-name').value;

    const url = '/Dashboard/settings/printers/add';
    const json = {'name':printerName};
    postJSON(url, json).then(response => response.text()).then(result => {
        location.reload();
    });
});

removePrinterButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
        const container = e.target.closest('.printer');
        const selector = container.id;

        const printerName = container.querySelector('.printer-name').innerText;
        // Make an 'are you sure?' pop up.
        let confirmed = confirm(`Are you sure you wish to remove the ${printerName} printer?`);
        if(confirmed){
            const url = '/Dashboard/settings/printers/remove';
            const json = {'selector':selector};
            postJSON(url, json).then(response => response.text()).then(result => {
                location.reload();
            });
        }
    });
});