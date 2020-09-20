// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { postJSON } from './utility.js';

"use strict";

let deleteButtons = document.querySelectorAll('.remove-category-button');
deleteButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
        let id = {'id' : e.target.closest('button').dataset.id};

        let url = '/Dashboard/menu/categories/delete';
        postJSON(url, id).then(response => response.text()).then(result => {
            location.reload();
        });
    });
});