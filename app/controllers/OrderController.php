<?php

// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

class OrderController extends Controller{

    private Menu $menuManager;
    private Order $orderManager;
        
    public array $menuStorage;
    public array $orderStorage;
    public array $user;
    public bool $hasUserInfo;
    
    public function __construct(){
        parent::__construct();
        
        $this->menuManager = new Menu();
        $this->orderManager = new Order();
    }

    public function get() : void {
        $this->pageTitle = "Tony's - Order";
        
        $userUUID = $this->getUserUUID();
        $this->user = $this->userManager->getUserInfo($userUUID);
        $this->user["user_type"] = $this->userManager->getUserAuthorityLevel($userUUID);

        $cartUUID = $this->orderManager->getCartUUID($userUUID);

        $this->orderStorage = $this->orderManager->getOrderByUUID($cartUUID);
        $this->menuStorage = $this->menuManager->getEntireMenu();

        //$day = date('l');
        //$this->menuStorage["daily_special"] = $this->menuManager->getDailySpecial($day);

        $day = DAY_TO_INT[date('D')];
        
        $deliveryOn = $this->orderManager->isDeliveryOn();
        $validDeliveryTime = $this->orderManager->isValidDeliveryTime($day);
        $this->orderStorage["is_delivery_off"] = !$deliveryOn || !$validDeliveryTime;

        $pickupOn = $this->orderManager->isPickupOn();
        $validPickupTime = $this->orderManager->isValidPickupTime($day);
        $this->orderStorage["is_pickup_off"] = !$pickupOn || !$validPickupTime;

        $this->orderStorage["is_closed"] = (!$deliveryOn || !$validDeliveryTime) && (!$pickupOn || !$validPickupTime);
        if($this->orderStorage["is_closed"]) $this->pageTitle = "Tony's - Closed";

        $this->orderStorage["subtotal"] = 0;
        if($cartUUID != NULL){
            $cost = $this->orderManager->getCost($cartUUID);
            $this->orderStorage["subtotal"] = $cost["subtotal"];
        }
        
        require_once APP_ROOT . "/views/order/order-select-page.php";
    }

    public function submit_get() : void {
        $this->pageTitle = "Tony's - Submit Order";

        $userUUID = $this->getUserUUID();
        
        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        $this->orderStorage = $this->orderManager->getOrderByUUID($cartUUID);
        if(is_null($cartUUID) || empty($this->orderStorage["line_items"]) || is_null($this->orderStorage["order_type"])){
            $this->redirect("/Order");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        // Check if we need to collect more info about the customer.
        if(!$this->sessionManager->isUserLoggedIn()){
            $infoLevel = $this->userManager->getUnregisteredInfoLevel($userUUID);
            if($infoLevel == INFO_NONE || ($infoLevel == INFO_PARTIAL && $this->orderStorage["order_type"] == DELIVERY)){
                $this->redirect("/User/new");
            }
        }

        $day = DAY_TO_INT[date('D')];
        if($this->orderStorage["order_type"] == DELIVERY){
            $deliveryOn = $this->orderManager->isDeliveryOn();
            $validTime = $this->orderManager->isValidDeliveryTime($day);
            if(!$deliveryOn || !$validTime){
                $message = "Orders are not currently being accepted for delivery.";
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
                $this->redirect("/Order");
            }
            // It is possible for a registered user to have no default address here.
            // As we do not set undeliverable addresses as default. You can register with an undeliverable address.
            $this->user["default_address"] = $this->userManager->getDefaultAddress($userUUID);
            if(empty($this->user["default_address"])){
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Please enter valid delivery address.");
                $this->redirect("/User/address");
            }
        } else {
            $pickupOn = $this->orderManager->isPickupOn();
            $validTime = $this->orderManager->isValidPickupTime($day);
            if(!$pickupOn || !$validTime){
                $message = "Orders are not currently being accepted for pickup.";
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
                $this->redirect("/Order");
            }
        }

        $cartModified = false;
        foreach($this->orderStorage["line_items"] as $lineItem){
            if(!$this->menuManager->isItemActive($lineItem["menu_item_id"])){
                $this->orderManager->deleteLineItem($cartUUID, UUID::arrangedStringToOrderedBytes($lineItem["uuid"]));
                $message = $this->escapeForHTML($lineItem["name"]) . " removed from cart due to availability.";
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
                $cartModified = true;
            }
        }

        if($cartModified){
            $this->redirect("/Order");
        }

        // At this point in our flow, we have all necessary info to complete the transaction.

        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
        $stripeToken = $this->orderManager->getStripeToken($cartUUID);
        $stripePaymentIntent = \Stripe\PaymentIntent::retrieve($stripeToken);
        $this->user["stripe_client_secret"] = $stripePaymentIntent["client_secret"];

        $cost = $this->orderManager->getCost($cartUUID);
        $cost["total"] = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
        $this->orderStorage["cost"] = $cost;

        if($this->orderStorage["order_type"] == DELIVERY){
            // We want to seperate the delivery address from the other addresses.
            // The delivery address could be set, or empty. If empty use default address.
            // if already set, gather up all addresses then remove delivery address from other addresses.
            $this->user["other_addresses"] = $this->userManager->getNonDefaultAddresses($userUUID);
            $this->user["delivery_address"] = $this->orderManager->getDeliveryAddress($cartUUID);
            if(empty($this->user["delivery_address"])){
                $this->user["delivery_address"] = $this->user["default_address"];
                $this->orderManager->setDeliveryAddress($cartUUID, $this->user["delivery_address"]["uuid"]);
            } else {
                $this->user["other_addresses"][] = $this->userManager->getDefaultAddress($userUUID);
                $addressCount = count($this->user["other_addresses"]);
                for($i = 0; $i < $addressCount; $i++){
                    if($this->user["delivery_address"]["uuid"] === $this->user["other_addresses"][$i]["uuid"]){
                        unset($this->user["other_addresses"][$i]);
                    }
                }
            }
        }

        require_once APP_ROOT . "/views/order/order-submit-page.php";
    }

    /**
     * Gets passed the orderUUID of the confirmed order.
     */
    public function status_get() : void {
        $this->pageTitle = "Tony's - Order Status";
        
        if(!isset($_GET["order"])){
            $this->redirect("/Order");
        }

        $orderUUID = UUID::arrangedStringToOrderedBytes($_GET["order"]);
        
        $this->orderStorage = $this->orderManager->getOrderByUUID($orderUUID);

        if(empty($this->orderStorage)){
            $this->redirect("/Order/submit");
        }

        $userUUID = $this->getUserUUID();

        if((strcmp($this->orderStorage["user_uuid"], $userUUID) !== 0) || $this->orderStorage["status"] == CART){
            $this->redirect("/Order/submit");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        $this->orderStorage["delivery_address"] = $this->orderManager->getDeliveryAddress($orderUUID);

        $cost = $this->orderManager->getCost($orderUUID);
        $cost["total"] = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
        $this->orderStorage["cost"] = $cost;

        require_once APP_ROOT . "/views/order/order-status-page.php";
    }

    
    // JS CALLS

    public function getStatus_post() : void {
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID)){
            echo json_encode("fail");
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode("fail");
            exit;
        }

        $orderUUID = UUID::arrangedStringToOrderedBytes($postData["order_uuid"]);

        $order = $this->orderManager->getBasicOrderInfo($orderUUID);

        if(empty($order)){
            echo json_encode("fail");
            exit;
        }

        if((strcmp($order["user_uuid"], $userUUID) !== 0)){
            echo json_encode("fail");
            exit;
        }

        echo json_encode($order["status"]);
    }

    public function getItemDetails_post() : void {
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $itemID = $postData["itemID"];

        $item = $this->menuManager->getItemInfo($itemID);

        echo json_encode($item);
    }

    // TODO(Trystan): We now have two entry points into creating an order.
    // We should absolutely only have one. How best to go about doing that, not sure.
    // Perhaps we disable adding things to the cart until an order type is selected.


    // Another more sensible solution would be to just create a user when they first open the order page.
    // This also makes sense regarding tracking funneling customers into a purchase.
    public function setOrderType_post() : void {
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        if(is_null($userUUID)){
            $userUUID = $this->userManager->createUnregisteredCredentials();
        }

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        
        if(is_null($cartUUID)){
            $cartUUID = $this->orderManager->createCart($userUUID);
        }

        $orderType = (int)$postData["order_type"];

        switch ($orderType) {
        case DELIVERY:
            $day = DAY_TO_INT[date('D')];
            $deliveryOn = $this->orderManager->isDeliveryOn();
            $validTime = $this->orderManager->isValidDeliveryTime($day);
            if(!$deliveryOn || !$validTime){
                echo "fail";
                exit;
            }
            $this->orderManager->setOrderType($cartUUID, DELIVERY);
            $this->orderManager->updateFee($cartUUID, 500);
            $this->updateStripeOrderCost($cartUUID);
            break;
        case PICKUP:
            $day = DAY_TO_INT[date('D')];
            $pickupOn = $this->orderManager->isPickupOn();
            $validTime = $this->orderManager->isValidPickupTime($day);
            if(!$pickupOn || !$validTime){
                echo "fail";
                exit;
            }
            $this->orderManager->setOrderType($cartUUID, PICKUP);
            $this->orderManager->updateFee($cartUUID, 0);
            $this->updateStripeOrderCost($cartUUID);
            $this->orderManager->setDeliveryAddress($cartUUID, NULL);
            break;
        case IN_RESTAURANT:
            $authorityLevel = $this->userManager->getUserAuthorityLevel($userUUID);
            if($authorityLevel < EMPLOYEE){
                echo "fail";
                exit;
            }
            $this->orderManager->setOrderType($cartUUID, IN_RESTAURANT);
            $this->orderManager->updateFee($cartUUID, 0);
            $this->updateStripeOrderCost($cartUUID);
            $this->orderManager->setDeliveryAddress($cartUUID, NULL);
            break;
        default:
            echo "fail";
            exit;
        }
        
    }

    public function addItemToCart_post() : void {
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        if(is_null($userUUID)){
            $userUUID = $this->userManager->createUnregisteredCredentials();
        }

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        
        if(is_null($cartUUID)){
            $cartUUID = $this->orderManager->createCart($userUUID);
        }

        $userItemData = $postData;
        unset($userItemData["CSRFToken"]);

        $itemID = $userItemData["itemID"];
        $quantity = (int)$userItemData["quantity"];
        $comment = $userItemData["comment"];

        $item = $this->menuManager->getItemInfo($itemID);
        if($item["active"] != 1){
            echo json_encode(NULL);
            exit;
        }

        $totalPrice = $item["price"];

        foreach($userItemData["choices"] as $choiceID => $optionIDs){
            $choiceID = explode("-", $choiceID)[0];

            foreach($optionIDs as $optionID){
                if(array_key_exists($choiceID, $item["choices"])
                   && array_key_exists($optionID, $item["choices"][$choiceID]["options"])){
                    $totalPrice += $item["choices"][$choiceID]["options"][$optionID]["price_modifier"];
                } else {
                    echo json_encode(NULL);
                    exit;
                }
            }
        }

        if($quantity > 0 && is_numeric($quantity)){
            $totalPrice *= $quantity;
        } else {
            echo json_encode(NULL);
            exit;
        }

        $lineItemUUID = $this->orderManager->addLineItemToCart($cartUUID, $itemID,
                                                               $quantity, $totalPrice, $comment);
        
        foreach($userItemData["choices"] as $choiceID => $optionIDs){
            $choiceID = explode("-", $choiceID)[0];
            foreach($optionIDs as $optionID){
                $this->orderManager->addOptionToLineItem($lineItemUUID, $choiceID, $optionID);
            }
        }

        $this->updateStripeOrderCost($cartUUID);

        $order = $this->orderManager->getOrderByUUID($cartUUID);
        $lineItem = $this->orderManager->getLineItem($lineItemUUID);
        $lineItem["uuid"] = UUID::orderedBytesToArrangedString($lineItemUUID);

        echo json_encode($lineItem);
    }

    public function removeItemFromCart_post() : void {
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        $lineItemUUIDString = $postData["line_item_uuid"];
        $lineItemUUID = UUID::arrangedStringToOrderedBytes($lineItemUUIDString);
        if(!$this->orderManager->isLineItemInOrder($cartUUID, $lineItemUUID)){
            echo "fail";
            exit;
        }

        $this->orderManager->deleteLineItem($cartUUID, $lineItemUUID);
    }

    public function submit_setDeliveryAddress_post() : void {
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        if(is_null($cartUUID)){
            echo "fail";
            exit;
        }

        $addressUUID = UUID::arrangedStringToOrderedBytes($postData["address_uuid"]);

        $addresses = $this->userManager->getNonDefaultAddresses($userUUID);
        $addresses[] = $this->userManager->getDefaultAddress($userUUID);

        // TODO(Trystan): We need to echo different messages so we can display to the user
        // the reason why it's failed. Not deliverable vs invalid request.
        $addressUUIDFound = false;
        foreach($addresses as $address){
            if($address["uuid"] === $addressUUID && $this->isAddressDeliverable($address)){
                $addressUUIDFound = true;
            }
        }
        if(!$addressUUIDFound){
            echo "fail";
            exit;
        }
        
        $this->orderManager->setDeliveryAddress($cartUUID, $addressUUID);
        echo "success";
        exit;
    }

    // This function exists because the redirect from submit to confirmed
    // would finish before the stripe webhook completed.
    public function submit_checkOrderConfirmation_post() : void {
        // We want to wait a little bit to give time for the webhook to process.
        // TODO(Trystan): localhost testing shows this occurs within 2 seconds.
        // Real numbers may need tweaked when we go live.
        // The longer a client takes to load this request, the more likely it is to succeed the first time.
        sleep(2);
        
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $orderUUID = UUID::arrangedStringToOrderedBytes($postData["order_uuid"]);
        $orderInfo = $this->orderManager->getBasicOrderInfo($orderUUID);

        if(empty($orderInfo) || ($userUUID != $orderInfo["user_uuid"])){
            echo "fail";
            exit;
        }

        if($orderInfo["status"] > 0){
            echo "confirmed";
        } else {
            echo "unconfirmed";
        }
    }

    // WEBHOOKS

    public function stripeWebhook_post() : void {
        $payload = file_get_contents("php://input");
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = NULL;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, STRIPE_ENDPOINT_KEY
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        // Handle the event
        switch ($event->type) {
        case "charge.succeeded":
            // Note(Trystan): Stripe payments have been updated to require a manual capture.
            // This was to allow us to make in-person payments route through the same code paths.
            // I believe this will also have the added benifit of allowing tip amounts to change,
            // if we wanted to do that, so a user could say they want to give X tip before everything is done.
            // then afterward they could update to Y amount. That is, if we don't capture right away,
            // which we will for now for simplicity and because tips don't exist yet.

            $chargeObject = $event->data->object;
            $paymentIntentID = $chargeObject->payment_intent;
            \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentID);
            $intent->capture();
            break;
        case "payment_intent.succeeded":
            $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
            $orderUUID = UUID::arrangedStringToOrderedBytes($paymentIntent["metadata"]["order_uuid"]);

            $this->orderManager->submitPayment($orderUUID, $paymentIntent->amount, PAYMENT_STRIPE);
            
            $order = $this->orderManager->getOrderByUUID($orderUUID);

            // An order was already submitted if it was placed in_restaurant.
            if($order["order_type"] != IN_RESTAURANT){
                $this->orderManager->submitOrder($orderUUID);
            }

            $this->user = $this->userManager->getUserInfo($orderUUID['user_uuid']);

            // If the order was placed in_restaurant, and wasn't associated with anyone.
            if(!empty($this->user)){
                $order["delivery_address"] = $this->orderManager->getDeliveryAddress($order["uuid"]);
                
                $cost = $this->orderManager->getCost($order["uuid"]);
                $cost["total"] = $cost["subtotal"] + $cost["fee"] + $cost["tax"];
                $order["cost"] = $cost;

                $message = $this->constructOrderConfirmationEmail($this->user, $order);
                $this->sendHTMLEmail($this->user["email"], "Tony's Taco House - Order Confirmed", $message);
            }
            
            break;
        default:
            // Unexpected event type
            http_response_code(400);
            exit();
        }

        http_response_code(200);
    }

    // HELPER FUNCTIONS

    private function updateStripeOrderCost(string $cartUUID) : void {
        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);

        $cost = $this->orderManager->getCost($cartUUID);
        $paymentTotal = $cost["subtotal"] + $cost["fee"] + $cost["tax"];

        $stripeToken = $this->orderManager->getStripeToken($cartUUID);
        
        \Stripe\PaymentIntent::update($stripeToken, [
            // Stripe will throw an exception if given a value less than 50 cents
            "amount" => max($paymentTotal, 50), 
        ]);
    }
}

?>
