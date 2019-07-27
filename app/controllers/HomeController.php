<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Order.php";
require_once APP_ROOT . "/models/Menu.php";

class HomeController extends Controller{
    private $orderManager;
    private $menuManager;
    
    public $user;
    public $orders;
    public $isLoggedIn;
    
    public function __construct(){
        parent::__construct();
        
        $this->orderManager = new Order();
        $this->menuManager = new Menu();
    }

    public function get() : void {
        $userID = $this->getUserID();
        $this->user = $this->userManager->getUserInfoByID($userID);

        $this->isLoggedIn = $this->sessionManager->isUserLoggedIn();

        require_once APP_ROOT . "/views/home/home-page.php";
    }
}

?>
