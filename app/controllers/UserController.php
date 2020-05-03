<?php

class UserController extends Controller{

    private Order $orderManager;

    public array $user;
    public array $orderStorage;

    public bool $getAddress = false;

    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
    }

    public function new_get() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/Order/submit");
        }

        $userID = $this->getUserID();
        $this->user = $this->userManager->getUserInfoByID($userID);

        $cartID = $this->orderManager->getCartID($userID);
        $order = $this->orderManager->getOrderByID($cartID);

        if(!isset($order["order_type"]) || $order["order_type"] == DELIVERY){
            $this->getAddress = true;
        }

        require_once APP_ROOT . "/views/user/user-new-page.php";
    }

    public function new_post() : void {
        // Checking out as guest.
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/User/new");
        }

        // Check to see if we should validate address.
        // If no cart is found then the default behavior is to collect the address.
        $userID = $this->getUserID();
        $cartID = $this->orderManager->getCartID($userID);
        $order = $this->orderManager->getOrderByID($cartID);

        $collectAddress = !isset($order["order_type"]) || $order["order_type"] == DELIVERY;
        
        if(!$this->validateNewUserInformation() || ($collectAddress && !$this->validateAddress())){
            $this->redirect("/User/new");
        }
        
        $this->userManager->setEmail($userID, $_POST["email"]);
        $this->userManager->setName($userID, $_POST["name_first"], $_POST["name_last"]);
        $this->userManager->setPhoneNumber($userID, $_POST["phone"]);
        $this->userManager->setUnregisteredInfoLevel($userID, INFO_PARTIAL);

        if($collectAddress){
            $this->userManager->setUnregisteredInfoLevel($userID, INFO_FULL);
            $this->userManager->setAddress($userID, $_POST["address_line"], $_POST["city"], $_POST["state"], $_POST["zip_code"]);
        }
        
        $this->redirect("/Order/submit");
    }

    public function info_get() : void {
        // TODO(Trystan): This is where users can view and edit all
        // their information.
        $userID = $this->getUserID();
        if(is_null($userID)){
            $this->redirect("/");
        }
        if(!$this->sessionManager->isUserLoggedIn()){
            $infoLevel = $this->userManager->getUnregisteredInfoLevel($userID);
            if($infoLevel == INFO_NONE){
                $this->redirect("/register");
            }
        }
        
        $this->user = $this->userManager->getUserInfoByID($userID);

        require_once APP_ROOT . "/views/user/user-info-page.php";
    }

    // TODO(Trystan): At the moment we aren't allowing updating of email address.
    // Should we allow?
    public function info_post() : void {
        $userID = $this->getUserID();
        if(is_null($userID)){
            $this->redirect("/");
        }

        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/User/info");
        }

        $valid = true;

        if(strlen($_POST["name_first"]) > MAX_LENGTH_NAME_FIRST){
            $message = "First name must be fewer than " . MAX_LENGTH_NAME_FIRST . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        if(strlen($_POST["name_first"]) > MAX_LENGTH_NAME_LAST){
            $message = "Last name must be fewer than " . MAX_LENGTH_NAME_LAST . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        if(!$valid){
            $this->redirect("/User/info");
        }

        $this->userManager->setName($userID, $_POST["name_first"], $_POST["name_last"]);
        $this->userManager->setPhoneNumber($userID, $_POST["phone"]);
        
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Info updated successfully.");
        $this->redirect("/User/info");
    }

    public function address_get() : void {
        // If no addresses exist we can add one here.
        // Multiple addresses, select default address.
        $userID = $this->getUserID();
        if(is_null($userID)){
            $this->redirect("/");
        }
        if(!$this->sessionManager->isUserLoggedIn()){
            $infoLevel = $this->userManager->getUnregisteredInfoLevel($userID);
            if($infoLevel == INFO_NONE){
                $this->redirect("/register");
            }
        }

        $this->user = $this->userManager->getUserInfoByID($userID);
        $this->user["other_addresses"] = $this->userManager->getNonDefaultAddresses($userID);

        require_once APP_ROOT . "/views/user/user-address-page.php";
    }

    public function address_post() : void {
        $userID = $this->getUserID();
        if(is_null($userID)){
            $this->redirect("/");
        }

        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/User/address");
        }
        
        if(!$this->validateAddress()){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Invalid address.");
            $this->redirect("/User/address");
        }
        
        $addressID = $this->userManager->addAddress($userID, $_POST["address_line"], $_POST["city"], $_POST["state"], $_POST["zip_code"]);

        if($_POST["set_default"]){
            $this->userManager->setDefaultAddress($userID, $addressID);
        }
        
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Address added successfully.");
        
        $this->redirect("/User/address");
    }

    public function orders_get() : void {
        // TODO(Trystan): This is where customers can view order history.
        $userID = $this->getUserID();
        if(is_null($userID)){
            $this->redirect("/");
        }

        $orders = $this->orderManager->getAllOrdersByUserID($userID);

        foreach($orders as &$order){
            $cost = $this->orderManager->getCost($order["id"]);
            $cost["total"] = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
            $order["cost"] = $cost;
        }
        unset($order);
        
        require_once APP_ROOT . "/views/user/user-orders-page.php";
    }

    // JS functions

    public function address_setDefault_post() : void {
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

        $this->userManager->setDefaultAddress($userID, $addressID);

        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "New default selected.");

        echo "success";
    }

    public function updateInfo_post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        
    }
}

?>
