// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON } from './utility.js';

"use strict";

const sendVerificationEmailButton = document.querySelector('#send-verification-email');
sendVerificationEmailButton.addEventListener('click', (e) => {
    let url = '/User/verify';
    let json = {};
    postJSON(url, json);
});