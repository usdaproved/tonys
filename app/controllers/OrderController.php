<?php

class OrderController extends Controller{

    private $menuManager;
    private $orderManager;
        
    public $menuStorage;
    public $orderStorage;
    public $user;
    public $hasUserInfo;
    
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


    // TODO(trystan): Break up the part where we get customer information
    // and submission of payment.
    

    public function submit_get() : void {
        $userID = $this->getUserID();
        
        $cartID = $this->orderManager->getCartID($userID);
        $this->orderStorage = $this->orderManager->getOrderByID($cartID);
        if(is_null($cartID) || !isset($this->orderStorage["line_items"])){
            $this->redirect("/Order");
        }

        $this->user = $this->userManager->getUserInfoByID($userID);

        $this->hasUserInfo = true;
        foreach($this->user as $credential){
            // TODO(Trystan): Should this be empty() instead?
            if(is_null($credential)){
                $this->hasUserInfo = false;
            }
        }

        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);

        $paymentTokens = $this->orderManager->getPaymentTokens($cartID);
        
        $stripeToken = $paymentTokens["stripe_token"];
        $stripePaymentIntent = \Stripe\PaymentIntent::retrieve($stripeToken);
        $this->user["stripe_client_secret"] = $stripePaymentIntent["client_secret"];

        require_once APP_ROOT . "/views/order/order-submit-page.php";
    }

    // TODO(trystan): We won't need to validate user information here,
    // because we should already have it.
    public function submit_post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/Order/submit");
        }
                
        $userID = $this->getUserID();
        
        $cartID = $this->orderManager->getCartID($userID);
        // TODO(trystan): Verify that the cart is not empty when the order is submitted.
        // no point in having an order with no line items.
        if(is_null($cartID)){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Empty cart.");
            $this->redirect("/Order");
        }

        $this->user = $this->userManager->getUserInfoByID($userID);
        
        $this->hasUserInformation = true;
        foreach($this->user as $credential){
            if(is_null($credential)){
                $this->hasUserInformation = false;
            }
        }
        
        if(!$this->hasUserInformation && !$this->validateNewUserInformation()){
            $this->redirect("/Order/submit");
        }

        if(!$this->hasUserInformation){
            $this->userManager->setEmail($userID, $_POST["email"]);
            $this->userManager->setName($userID, $_POST["name_first"], $_POST["name_last"]);
            $this->userManager->setPhoneNumber($userID, $_POST["phone"]);
            $this->userManager->setAddress($userID, $_POST["address_line"], $_POST["city"], $_POST["state"], $_POST["zip_code"]);

            // Need to get the filled out information since it wasn't present the first time.
            $this->user = $this->userManager->getUserInfoByID($userID);
        }

        // TODO: validate payment.

        // TODO: Have the user set what type of order they want to place.
        $orderType = DELIVERY;
        $this->orderManager->submitOrder($cartID, $orderType);

        // TODO: Setup an SMTP Server in order for email to go out.
        // TODO: Construct a better email, each line in an email cannot be more than 70 chars.
        // Cox blocks communication over SMTP. Won't be able to test this for awhile.
        mail($this->user["email"], "Tony's Taco House Order", "Your order has been confirmed.");

        $this->redirect("/Order/confirmed?order=" . $cartID);
    }

    /**
     *gets passed the orderID of the confirmed order.
     */
    public function confirmed_get() : void {
        if(!isset($_GET["order"])){
            $this->redirect("/Order");
        }
        $orderID = $_GET["order"];
        
        $this->orderStorage = $this->orderManager->getOrderByID($orderID);

        $userID = $this->getUserID();

        if($this->orderStorage["user_id"] != $userID){
            $this->redirect("/Order");
        }

        $this->user = $this->userManager->getUserInfoByID($userID);
        
        require_once APP_ROOT . "/views/order/order-confirmed-page.php";
    }

    public function view_get() : void {
        $userID = $this->getUserID();
        if(is_null($userID)){
            // TODO: Perhaps handle this differently, just show some message saying
            // something about this is where your orders will show up when you make one.
            $this->redirect("/");
        }
        $this->orderStorage = $this->orderManager->getAllOrdersByUserID($userID);

        require_once APP_ROOT . "/views/order/order-view-page.php";
    }

    
    // JS CALLS

    
    public function getItemDetails_post() : void {
        //$userID = $this->getUserID();

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

    
    // TODO(Trystan): As soon as we create a user, when they add something to the cart
    // we should also be creating a stripe PaymentIntents object.
    // Do the same with any other payment API's that track the transaction before the checkout.
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
            'amount' => $transactionTotal,
        ]);
        
        echo $lineItemID;
    }

    public function stripeWebhook() : void {
        $payload = file_get_contents("php://input");
        $event = NULL;

        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        }

        // Handle the event
        switch ($event->type) {
        case "payment_intent.succeeded":
            $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
            //handlePaymentIntentSucceeded($paymentIntent);
            break;
        default:
            // Unexpected event type
            http_response_code(400);
            exit();
        }

        http_response_code(200);
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
