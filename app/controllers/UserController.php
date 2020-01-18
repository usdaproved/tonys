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
        // TODO(Trystan): we want to only get the relevant info
        // for the type of order that it is.
        // If it's pickup just get the first and last name, email, phone number.
        // Only get address if it's a delivery. Don't want to scare away people
        // by collecting too much information.
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/Order/submit");
        }
        $userID = $this->getUserID();

        $this->user = $this->userManager->getUserInfoByID($userID);

        if(isset($_GET["orderType"]) && $_GET["orderType"] === "delivery"){
            $this->getAddress = true;
        }

        require_once APP_ROOT . "/views/user/user-new-page.php";
    }

    public function new_post() : void {
        // Checking out as guest.
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/Order/submit");
        }

        if(!$this->validateNewUserInformation() || !$this->validateAddress()){
            // TODO(Trystan): Keep this information alive from the first time.
            $this->redirect("/User/new?orderType=delivery");
        }

        $userID = $this->getUserID();
        
        $this->userManager->setEmail($userID, $_POST["email"]);
        $this->userManager->setName($userID, $_POST["name_first"], $_POST["name_last"]);
        $this->userManager->setPhoneNumber($userID, $_POST["phone"]);
        $this->userManager->setAddress($userID, $_POST["address_line"], $_POST["city"], $_POST["state"], $_POST["zip_code"]);
        
        $this->redirect("/Order/submit");
    }

    public function info_get() : void {
        // TODO(Trystan): This is where users can view and edit all
        // their information.
    }

    public function orders_get() : void {
        // TODO(Trystan): This is where customers can view order history.

    }
}

?>
