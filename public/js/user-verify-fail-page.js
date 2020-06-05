import { postJSON } from './utility.js';

"use strict";

let CSRFToken = document.querySelector('#CSRFToken').value;

const sendVerificationEmailButton = document.querySelector('#send-verification-email');
sendVerificationEmailButton.addEventListener('click', (e) => {
    let url = '/User/verify';
    let json = {};
    postJSON(url, json, CSRFToken);
});