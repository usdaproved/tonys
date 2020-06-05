import { postJSON, createOrderElement, initSearchUsersComponent } from './utility.js';

"use strict";

initSearchUsersComponent((e) => {
    let container = e.target.closest('.order-container');
    let uuid = container.id;
    window.open(`/Dashboard/customers?uuid=${uuid}`);
});