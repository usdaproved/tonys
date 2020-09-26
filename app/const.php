<?php

const DAY_TO_INT = ["Mon" => 0, "Tue" => 1, "Wed" => 2, "Thu" => 3, "Fri" => 4, "Sat" => 5, "Sun" => 6];
const PHP_INT_TO_DAY = [0 => "Sunday", 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday",
                        4 => "Thursday", 5 => "Friday", 6 => "Saturday"];

const USER_ALERT = "alert";
const USER_SUCCESS = "success";

const INTERNAL_REDIRECT = "redirect";
const REDIRECT_ADDRESSES = ["/Order/submit"];

const MAX_ORDER_QUANTITY = 30;
const MAX_ORDER_PRICE   = 100;

const MESSAGE_INVALID_ORDER_QUANTITY = "Please contact restaurant for any order larger than " . MAX_ORDER_QUANTITY . " items.";
const MESSAGE_INVALID_ORDER_PRICE = "Please contact restaurant for any order greater than $" . MAX_ORDER_PRICE . ".";

const MESSAGE_INVALID_CSRF_TOKEN = "Operation could not complete due to invalid session.";
const MESSAGE_INVALID_LOGIN = "Invalid login credentials.";
const MESSAGE_EMAIL_IN_USE = "Email already in use. <a href=\"/login\">Log in?</a>";


const CUSTOMER = 0;
const EMPLOYEE = 1;
const ADMIN    = 2;
const OWNER    = 3; // The person who setup the restaurant page. cannot be removed or remove admin status. 

const USER_TYPE_ARRAY = ['customer','employee','admin','owner'];

// An unregistered user can have varying levels of info about them.
const INFO_NONE    = 0;
const INFO_PARTIAL = 1; // No address.
const INFO_FULL    = 2;

const DELIVERY      = 0;
const PICKUP        = 1;
const IN_RESTAURANT = 2;

const ORDER_TYPE_ARRAY = ['delivery', 'pickup', 'in restaurant'];

const CART       = 0;
const SUBMITTED  = 1;
const PREPARING  = 2;
const PREPARED   = 3;
const DELIVERING = 4;
// NOTE(Trystan): The order is 'complete' when the order has ended. Delivered, picked up, paid.
const COMPLETE   = 5;

const STATUS_ARRAY = ['cart','submitted','preparing','prepared','delivering','complete'];

const MINUTES_UNTIL_PREPARED = 15;

// TODO(Trystan): We may be able to get rid of this whole flow.
// Take a peek at the javascript which handles order flow without this structure.
const ORDER_STATUS_FLOW = array(
    DELIVERY => array(SUBMITTED, PREPARING, PREPARED, DELIVERING, COMPLETE),
    PICKUP => array(SUBMITTED, PREPARING, PREPARED, COMPLETE),
    // TODO(Trystan): These two are redundant.
    // Previously there was a difference in semantics, but I feel this better represents more simply what
    // is happening. 
    IN_RESTAURANT => array(SUBMITTED, PREPARING, PREPARED, COMPLETE) 
);

const PAYMENT_CASH   = 0;
const PAYMENT_STRIPE = 1;
const PAYMENT_PAYPAL = 2;
const PAYMENT_APPLE  = 3;

const PAYMENT_ARRAY = ['cash', 'stripe', 'paypal', 'apple'];

const MAX_LENGTH_NAME_FIRST   = 50;
const MAX_LENGTH_NAME_LAST    = 50;
const MAX_LENGTH_EMAIL       = 100;
const MAX_LENGTH_PHONE_NUMBER = 15;

const MAX_LENGTH_ADDRESS_LINE   = 100;
const MAX_LENGTH_ADDRESS_CITY   = 100;
const MAX_LENGTH_ADDRESS_STATE   =  2;
const MAX_LENGTH_ADDRESS_ZIP_CODE = 5;

?>
