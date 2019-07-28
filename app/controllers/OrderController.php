<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Menu.php";
require_once APP_ROOT . "/models/Order.php";

class OrderController extends Controller{

    private $menuManager;
    private $orderManager;
        
    public $menu;
    public $orderStorage;
    public $user;
    public $hasUserInfo;
    
    public function __construct(){
        parent::__construct();
        
        $this->menuManager = new Menu();
        $this->orderManager = new Order();
    }

    public function get() : void {
        $this->menu = $this->menuManager->getWholeMenu();

        $userID = $this->getUserID();

        $cartID = $this->orderManager->getCartID($userID);

        if(!is_null($cartID)){
            $this->orderStorage = $this->orderManager->getOrderByID($cartID);
        }
        
        // TODO: Think about changing the name scheme to match the methods.
        // i.e. order-submit-page, order-confirmed-page, order-page/order-index-page.
        require_once APP_ROOT . "/views/order/order-select-page.php";
    }

    public function post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/Order/submit");
        }
        
        $userID = $this->getUserID();

        if(is_null($userID)){
            $userID = $this->userManager->createUnregisteredCredentials();
        }

        $order = $_POST;
        unset($order["CSRFToken"]);
        
        // TODO: something needs to be done about this.
        $totalPrice = $this->menuManager->calculateTotalPrice($order);

        $cartID = $this->orderManager->getCartID($userID);

        if(!$this->validateOrder($order, $totalPrice)){
            $this->redirect("/Order");
        }
        
        if(is_null($cartID)){
            $this->orderManager->createCart($userID, $order, $totalPrice);
        } else {
            $this->orderManager->updateCart($cartID, $order, $totalPrice);
        }
        // Take the user to the next page to fill out info.
        $this->redirect("/Order/submit");
    }

    public function submit_get() : void {
        $userID = $this->getUserID();
        
        $cartID = $this->orderManager->getCartID($userID);
        if(is_null($cartID)){
            $this->redirect("/Order");
        }

        $this->orderStorage = $this->orderManager->getOrderByID($cartID);

        $this->user = $this->userManager->getUserInfoByID($userID);

        $this->hasUserInfo = true;
        foreach($this->user as $credential){
            if(is_null($credential)){
                $this->hasUserInfo = false;
            }
        }

        require_once APP_ROOT . "/views/order/order-submit-page.php";
    }

    public function submit_post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/Order/submit");
        }
                
        $userID = $this->getUserID();
        
        $cartID = $this->orderManager->getCartID($userID);
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

        $this->orderManager->submitOrder($cartID);

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

    private function validateOrder(array $order, float $totalPrice) : bool {
        $valid = true;

        $totalQuantity = 0;
        $negativityFound = false;
        foreach($order as $item => $quantity){
            if($quantity && $quantity > 0){
                $totalQuantity += $quantity;
            } else if($quantity && $quantity < 0){
                $negativityFound = true;
            }
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

        if($totalPrice > MAX_ORDER_PRICE){
            $message = MESSAGE_INVALID_ORDER_PRICE;
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        
        return $valid;
    }
}

?>
