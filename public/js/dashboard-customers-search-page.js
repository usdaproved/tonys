// (C) Copyright 2020 by Trystan Brock All Rights Reserved.
import { initSearchUsersComponent } from './utility.js';

"use strict";

initSearchUsersComponent((e) => {
    let container = e.target.closest('.search-result');
    let uuid = container.id;
    window.open(`/Dashboard/customers?uuid=${uuid}`);
});