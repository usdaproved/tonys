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

        $cartID = $this->orderManager->getCartID($userID);

        $this->orderStorage = $this->orderManager->getOrderByID($cartID);

        // If a cart is already set, and they didn't explicitly request to edit the cart
        // then skip ahead to the submit page.
        // From the submit page there will be a link to ?edit the cart.
        // Debatable whether this is the desired effect. For now I prefer it.
        if(!isset($_GET["edit"]) && !is_null($cartID)
           && isset($this->orderStorage["line_items"])){
            $this->redirect("/Order/submit");
        }
        
        $this->menuStorage = $this->menuManager->getEntireMenu();
        $day = date('l');

        $this->menuStorage["daily_special"] = $this->menuManager->getDailySpecial($day);

        require_once APP_ROOT . "/views/order/order-select-page.php";
    }

    public function submit_get() : void {
        $userID = $this->getUserID();
        
        $cartID = $this->orderManager->getCartID($userID);
        $this->orderStorage = $this->orderManager->getOrderByID($cartID);
        if(is_null($cartID) || !isset($this->orderStorage["line_items"])){
            $this->redirect("/Order");
        }

        $this->user = $this->userManager->getUserInfoByID($userID);

        // TODO(Trystan): Check the type of order, we don't need to collect address info
        // if it's just a pickup.
        foreach($this->user as $credential){
            if(is_null($credential)){
                // TODO(Trystan): Send the actual type of order, pickup or delivery
                $this->redirect("/User/new?orderType=delivery");
            }
        }

        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);

        $paymentTokens = $this->orderManager->getPaymentTokens($cartID);
        
        $stripeToken = $paymentTokens["stripe_token"];
        $stripePaymentIntent = \Stripe\PaymentIntent::retrieve($stripeToken);
        $this->user["stripe_client_secret"] = $stripePaymentIntent["client_secret"];

        require_once APP_ROOT . "/views/order/order-submit-page.php";
    }

    /**
     * Gets passed the orderID of the confirmed order.
     */
    public function confirmed_get() : void {
        if(!isset($_GET["order"])){
            $this->redirect("/Order");
        }
        $orderID = $_GET["order"];
        
        $this->orderStorage = $this->orderManager->getOrderByID($orderID);

        $userID = $this->getUserID();

        if($this->orderStorage["user_id"] != $userID || $this->orderStorage["status"] == CART){
            $this->redirect("/Order");
        }

        $this->user = $this->userManager->getUserInfoByID($userID);
        
        require_once APP_ROOT . "/views/order/order-confirmed-page.php";
    }

    
    // JS CALLS

    
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
                    echo "invalid";
                    exit;
                }
            }
        }

        foreach($userItemData["additions"] as $additionID){
            if(array_key_exists($additionID, $item["additions"])){
                $totalPrice += $item["additions"][$additionID]["price_modifier"];
            } else {
                echo "invalid";
                exit;
            }
        }

        if($quantity > 0 && is_numeric($quantity)){
            $totalPrice *= $quantity;
        } else {
            echo "invalid";
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

        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);

        // TODO(Trystan): We need to update the database to use pennies instead of decimals.
        // This does make the most sense to do integer math as opposed to messing with floats.
        $transactionTotal = $this->orderManager->getCartTotalPrice($cartID) * 100;

        $paymentTokens = $this->orderManager->getPaymentTokens($cartID);
        
        $stripeToken = $paymentTokens["stripe_token"];
        \Stripe\PaymentIntent::update($stripeToken, [
            "amount" => $transactionTotal,
        ]);
        
        echo $lineItemID;
    }

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
            $orderType = DELIVERY; // TODO(Trystan): Actually implement selection of DELIVERY or PICKUP or IN_RESTAURANT
            $this->orderManager->submitOrder($orderID, $orderType);
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
        $order = $this->orderManager->getOrderByID($cartID);
        
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
                                    'value' => $order["subtotal"] // Paypal expects 0.00 format
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
        $response = $client->execute($request);
        // 4. Save the capture ID to your database. Implement logic to save capture to your database for future reference.
        // TODO(Trystan): In the database, in the orders table. Add two columns, payment method, and payment token.
        $orderType = DELIVERY; // TODO(Trystan): Actually implement selection of DELIVERY or PICKUP or IN_RESTAURANT
        $this->orderManager->submitOrder($cartID, $orderType);
        
        echo json_encode($response);
    }

    // HELPER FUNCTIONS

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
