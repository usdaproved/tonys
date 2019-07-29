<?php

const USER_ALERT = "alert";

const MAX_ORDER_QUANTITY = 30;
const MAX_ORDER_PRICE = 100;

const MESSAGE_INVALID_ORDER_QUANTITY = "Please contact restaurant for any order larger than " . MAX_ORDER_QUANTITY . " items.";
const MESSAGE_INVALID_ORDER_PRICE = "Please contact restaurant for any order greater than $" . MAX_ORDER_PRICE . ".";

const MESSAGE_INVALID_CSRF_TOKEN = "Operation could not complete due to invalid session.";
const MESSAGE_INVALID_LOGIN = "Invalid login credentials.";
const MESSAGE_EMAIL_IN_USE = "Email already in use. <a href=\"/Login\">Log in?</a>";


const CUSTOMER = 0;
const EMPLOYEE = 1;
const ADMIN = 2;

const DELIVERY = 0;
const PICKUP = 1;
const IN_RESTAURANT = 2;

const CART = 0;
const SUBMITTED = 1;
const PREPARING = 2;
const PREPARED = 3;
const DELIVERING = 4;
const DELIVERED = 5;
const COMPLETE = 6;

const ORDER_STATUS_FLOW = array(
    DELIVERY => array(SUBMITTED, PREPARING, PREPARED, DELIVERING, DELIVERED),
    PICKUP => array(SUBMITTED, PREPARING, PREPARED, COMPLETE),
    IN_RESTAURANT => array(SUBMITTED, PREPARING, COMPLETE)
);

const MAX_LENGTH_NAME_FIRST = 50;
const MAX_LENGTH_NAME_LAST = 50;
const MAX_LENGTH_EMAIL = 100;
const MAX_LENGTH_PHONE_NUMBER = 15;

const MAX_LENGTH_ADDRESS_LINE = 100;
const MAX_LENGTH_ADDRESS_CITY = 100;
const MAX_LENGTH_ADDRESS_STATE = 20;
const MAX_LENGTH_ADDRESS_ZIP_CODE = 5;

?>