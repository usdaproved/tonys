"use strict";

const goToOrderView = async () => {
    window.location.href = '/Order';
};



let button = document.getElementsByClassName('order_button')[0];

button.addEventListener('click', goToOrderView);
