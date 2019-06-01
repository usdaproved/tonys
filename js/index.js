"use strict";

let button = document.getElementsByClassName('order_button')[0];

button.addEventListener ("click", function() {
    location.href = "/order.php";
});
