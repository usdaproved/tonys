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
        $this->user = $this->userManager->getUserInfoByID($userID);

        require_once APP_ROOT . "/views/user/user-info-page.php";
    }

    public function orders_get() : void {
        // TODO(Trystan): This is where customers can view order history.

    }
}

?>
