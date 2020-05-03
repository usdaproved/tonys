<?php

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
        $userID = $this->getUserID();
        $this->user = ["user_type" => $this->userManager->getUserAuthorityLevelByID($userID)];

        $cartID = $this->orderManager->getCartID($userID);

        $this->orderStorage = $this->orderManager->getOrderByID($cartID);
        $this->menuStorage = $this->menuManager->getEntireMenu();

        $day = date('l');
        $this->menuStorage["daily_special"] = $this->menuManager->getDailySpecial($day);

        require_once APP_ROOT . "/views/order/order-select-page.php";
    }

    public function submit_get() : void {
        $userID = $this->getUserID();
        
        $cartID = $this->orderManager->getCartID($userID);
        $this->orderStorage = $this->orderManager->getOrderByID($cartID);
        if(is_null($cartID) || empty($this->orderStorage["line_items"]) || is_null($this->orderStorage["order_type"])){
            $this->redirect("/Order");
        }

        $this->user = $this->userManager->getUserInfoByID($userID);

        // Check if we need to collect more info about the customer.
        if(!$this->sessionManager->isUserLoggedIn()){
            $infoLevel = $this->userManager->getUnregisteredInfoLevel($userID);
            if($infoLevel == INFO_NONE || ($infoLevel == INFO_PARTIAL && $this->orderStorage["order_type"] == DELIVERY)){
                $this->redirect("/User/new");
            }
        }

        // At this point in our flow, we have all necessary info to complete the transaction.

        // TODO(Trystan): This might be the most logical place to update the price
        // for either delivery or pickup. If we wanted to modify the delivery price
        // based on some type of distance filter, this would be the best place to do it.
        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
        $stripeToken = $this->orderManager->getStripeToken($cartID);
        $stripePaymentIntent = \Stripe\PaymentIntent::retrieve($stripeToken);
        $this->user["stripe_client_secret"] = $stripePaymentIntent["client_secret"];

        $cost = $this->orderManager->getCost($cartID);
        $cost["total"] = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
        $this->orderStorage["cost"] = $cost;

        // Load selected address, which is the default address at the start.
        // Can be updated by selecting another address.
        // This is the delivery address that gets submitted.
        $this->user["other_addresses"] = $this->userManager->getNonDefaultAddresses($userID);

        require_once APP_ROOT . "/views/order/order-submit-page.php";
    }

    /**
     * Gets passed the orderID of the confirmed order.
     */
    // TODO(Trystan): The submit page is getting bounced back as the stripe webhook isn't called
    // until after the redirect happens. We need to think of a solution.
    public function confirmed_get() : void {
        if(!isset($_GET["order"])){
            $this->redirect("/Order");
        }
        $orderID = $_GET["order"];
        
        $this->orderStorage = $this->orderManager->getOrderByID($orderID);

        $userID = $this->getUserID();

        if($this->orderStorage["user_id"] != $userID || $this->orderStorage["status"] == CART){
            $this->redirect("/Order/submit");
        }

        $this->user = $this->userManager->getUserInfoByID($userID);

        $cost = $this->orderManager->getCost($orderID);
        $cost["total"] = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
        $this->orderStorage["cost"] = $cost;
        
        require_once APP_ROOT . "/views/order/order-confirmed-page.php";
    }

    
    // JS CALLS

    // TODO(Trystan): We now have two entry points into creating an order.
    // We should absolutely only have one. How best to go about doing that, not sure.
    // Perhaps we disable adding things to the cart until an order type is selected.


    // Another more sensible solution would be to just create a user when they first open the order page.
    // This also makes sense regarding tracking funneling customers into a purchase.
    public function setOrderType_post() : void {
        $userID = $this->getUserID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        if(is_null($userID)){
            $userID = $this->userManager->createUnregisteredCredentials();
        }

        $cartID = $this->orderManager->getCartID($userID);
        
        if(is_null($cartID)){
            $cartID = $this->orderManager->createCart($userID);
        }

        $orderType = (int)$postData["order_type"];

        switch ($orderType) {
        case DELIVERY:
            $this->orderManager->setOrderType($cartID, DELIVERY);
            $this->orderManager->updateFee($cartID, 500);
            $this->updateStripeOrderCost($cartID);
            break;
        case PICKUP:
            $this->orderManager->setOrderType($cartID, PICKUP);
            $this->orderManager->updateFee($cartID, 0);
            $this->updateStripeOrderCost($cartID);
            break;
        case IN_RESTAURANT:
            $authorityLevel = $this->userManager->getUserAuthorityLevelByID($userID);
            if($authorityLevel < EMPLOYEE){
                echo "fail";
                exit;
            }
            $this->orderManager->setOrderType($cartID, IN_RESTAURANT);
            $this->orderManager->updateFee($cartID, 0);
            $this->updateStripeOrderCost($cartID);
            break;
        default:
            echo "fail";
            exit;
        }
        
    }

    public function submit_setDeliveryAddress_post() : void {
        $userID = $this->getUserID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $addressID = $postData["address_id"];

        $addresses = $this->userManager->getNonDefaultAddresses($userID);
        $addressIDFound = false;
        foreach($addresses as $address){
            if($address["id"] === $addressID){
                $addressIDFound = true;
            }
        }
        if(!$addressIDFound){
            echo "fail";
            exit;
        }
        
        $this->orderManager->setDeliveryAddressID($orderID, $addressID);
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

    public function addItemToCart_post() : void {
        $userID = $this->getUserID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        if(is_null($userID)){
            $userID = $this->userManager->createUnregisteredCredentials();
        }

        $cartID = $this->orderManager->getCartID($userID);
        
        if(is_null($cartID)){
            $cartID = $this->orderManager->createCart($userID);
        }

        $userItemData = $postData;
        unset($userItemData["CSRFToken"]);

        $itemID = $userItemData["itemID"];
        $quantity = (int)$userItemData["quantity"];
        $comment = $userItemData["comment"];

        $item = $this->menuManager->getItemInfo($itemID);

        $totalPrice = (float)$item["price"];

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

        foreach($userItemData["additions"] as $additionID){
            if(array_key_exists($additionID, $item["additions"])){
                $totalPrice += $item["additions"][$additionID]["price_modifier"];
            } else {
                echo json_encode(NULL);
                exit;
            }
        }

        if($quantity > 0 && is_numeric($quantity)){
            $totalPrice *= $quantity;
        } else {
            echo json_encode(NULL);
            exit;
        }

        $lineItemID = $this->orderManager->addLineItemToCart($cartID, $itemID,
                                                             $quantity, $totalPrice, $comment);
        
        foreach($userItemData["choices"] as $choiceID => $optionIDs){
            $choiceID = explode("-", $choiceID)[0];
            foreach($optionIDs as $optionID){
                $this->orderManager->addOptionToLineItem($lineItemID, $choiceID, $optionID);
            }
        }

        foreach($userItemData["additions"] as $additionID){
            $this->orderManager->addAdditionToLineItem($lineItemID, $additionID);
        }

        $this->updateStripeOrderCost($cartID);

        $order = $this->orderManager->getOrderByID($cartID);
        $lineItem = $order["line_items"][$lineItemID];
        echo json_encode($lineItem);
    }

    public function removeItemFromCart_post() : void {
        $userID = $this->getUserID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $cartID = $this->orderManager->getCartID($userID);
        $lineItem = $postData["line_item_id"];
        if(!$this->orderManager->isLineItemInOrder($cartID, $lineItem)){
            echo "fail";
            exit;
        }

        $this->orderManager->deleteLineItem($cartID, $lineItem);
    }

    // This function exists because the redirect from submit to confirmed
    // would finish before the stripe webhook completed.
    public function checkOrderConfirmation_post() : void {
        // We want to wait a little bit to give time for the webhook to process.
        // TODO(Trystan): localhost testing shows this occurs within 2 seconds.
        // Real numbers may need tweaked when we go live.
        // The longer a client takes to load this request, the more likely it is to succeed the first time.
        sleep(2);
        
        $userID = $this->getUserID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $orderID = $postData["order_id"];
        $orderInfo = $this->orderManager->getBasicOrderInfo($orderID);

        if($userID != $orderInfo["user_id"]){
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
        case "payment_intent.succeeded":
            $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
            $orderID = $paymentIntent["metadata"]["order_id"];
            $userID = $paymentIntent["metadata"]["user_id"];

            $this->submitOrder($userID, $orderID, $paymentIntent->amount, PAYMENT_STRIPE);

            // TODO(Trystan): Get the user and send them an email here.
            // TODO: Setup an SMTP Server in order for email to go out.
            // TODO: Construct a better email, each line in an email cannot be more than 70 chars.
            // Cox blocks communication over SMTP. Won't be able to test this for awhile.
            //mail($this->user["email"], "Tony's Taco House Order", "Your order has been confirmed.");
            
            break;
        default:
            // Unexpected event type
            http_response_code(400);
            exit();
        }

        http_response_code(200);
    }

    public function paypalCreateOrder_post() : void {
        // In order to fill out the information in the body,
        // we must get the info from the order.
        $userID = $this->getUserID();

        $cartID = $this->orderManager->getCartID($userID);
        if(is_null($cartID)){
            http_response_code(400);
        }
        $cost = $this->orderManager->getCost($cartID);

        $paymentTotal = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
        $paymentTotal = $paymentTotal * 0.01; // NOTE(Trystan): Paypal expects 0.00 format
        
        $request = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = array(
            'intent' => 'CAPTURE',
            'purchase_units' =>
                array(
                    0 =>
                        array(
                            'amount' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => $paymentTotal
                                )
                        )
                )
        );

        $environment = new \PayPalCheckoutSdk\Core\SandboxEnvironment(PAYPAL_PUBLIC_KEY, PAYPAL_PRIVATE_KEY);
        $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);
        
        $response = $client->execute($request);

        $data = ["paypalID" => $response->result->id];

        echo json_encode($data);
    }

    public function paypalCaptureOrder_post() : void {
        $userID = $this->getUserID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);

        $cartID = $this->orderManager->getCartID($userID);
        if(is_null($cartID)){
            http_response_code(400);
        }

        $paypalID = $postData["paypalID"];
        $request = new \PayPalCheckoutSdk\Orders\OrdersCaptureRequest($paypalID);

        $environment = new \PayPalCheckoutSdk\Core\SandboxEnvironment(PAYPAL_PUBLIC_KEY, PAYPAL_PRIVATE_KEY);
        $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);

        // NOTE(Trystan): This response is where we would gather information about the customer.
        // such as name and address. Instead of asking the customer for info twice, we could gather once here,
        // but only if they're using paypal of course.
        // Using Stripe we would still have to collect address info manually.
        // response->result->purchase_units[0]->shipping->address
        $response = $client->execute($request);

        $paypalToken = $response->result->id;
        $paymentTotal = 0;
        // Please just look at paypal compared to stripe. Use Stripe exclusively if you can, I beg you.
        // I would imagine there would only ever be one purchase_unit/capture for every transaction,
        // but if PayPal allows you to split your own transaction, then this would be necessary.
        foreach($response->result->purchase_units as $purchaseUnit){
            foreach($purchaseUnit->payments->captures as $capture){
                $paymentTotal += ((float)$capture->amount->value * 100);
            }
        }

        $this->orderManager->setPaypalToken($cartID, $paypalToken);
        $this->submitOrder($userID, $cartID, $paymentTotal, PAYMENT_PAYPAL);
        
        echo json_encode($response);
    }

    // HELPER FUNCTIONS

    private function updateStripeOrderCost(int $cartID) : void {
        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);

        $cost = $this->orderManager->getCost($cartID);
        $paymentTotal = $cost["subtotal"] + $cost["fee"] + $cost["tax"];

        $stripeToken = $this->orderManager->getStripeToken($cartID);
        
        \Stripe\PaymentIntent::update($stripeToken, [
            // Stripe will throw an exception if given a value less than 50 cents
            "amount" => max($paymentTotal, 50), 
        ]);
    }

    private function submitOrder($userID, $orderID, $amount, $paymentMethod) : void {
        $this->orderManager->submitPayment($orderID, $amount, $paymentMethod);
        $this->orderManager->submitOrder($orderID);

        $orderType = $this->orderManager->getOrderType($orderID);
        if($orderType === DELIVERY){
            // TODO(Trystan): This may become a little more complex when
            // a user has more than one address tied to their account.
            // We may want to submit some address ID info with the order when the user selects it.
            $addressID = $this->userManager->getAddressID($userID);
            $this->orderManager->setDeliveryAddressID($orderID, $addressID);
        }
    }

    // Depricated: orders no longer work this way.
    // TODO: check for things like number of rows in order_line_items in the cart.
    private function validateOrder(array $order) : bool {
        $valid = true;

        $totalQuantity = 0;
        $negativityFound = false;
        $nonIntFound = false;
        foreach($order as $item => $quantity){
            if($quantity > 0){
                $totalQuantity += $quantity;
            } else if($quantity < 0){
                $negativityFound = true;
            }
            else if(!is_numeric($quantity) && $quantity !== ""){
                $nonIntFound = true;
            }
        }

        if($nonIntFound){
            $message = "Non-numerical input supplied.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }

        if($negativityFound){
            $message = "A negative quantity cannot be accepted.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }

        if($totalQuantity > MAX_ORDER_QUANTITY){
            $message = MESSAGE_INVALID_ORDER_QUANTITY;
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        
        return $valid;
    }
}

?>
