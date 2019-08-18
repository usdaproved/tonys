<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Order.php";
require_once APP_ROOT . "/models/Menu.php";

class HomeController extends Controller{
    private $orderManager;
    
    public $user;
    public $isLoggedIn;
    // TODO: Update this with javascript.
    public $activeOrderStatus;
    
    public function __construct(){
        parent::__construct();
        
        $this->orderManager = new Order();
    }

    public function get() : void {
        $userID = $this->getUserID();
        $this->user = $this->userManager->getUserInfoByID($userID);

        $this->isLoggedIn = $this->sessionManager->isUserLoggedIn();

        $this->activeOrderStatus = $this->orderManager->getUserActiveOrderStatus($userID);

        require_once APP_ROOT . "/views/home/home-page.php";
    }
}

?>
