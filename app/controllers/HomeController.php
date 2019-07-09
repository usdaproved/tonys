<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Order.php";
require_once APP_ROOT . "/models/Menu.php";

class HomeController extends Controller{
    private $orderManager;
    private $menuManager;
    
    public $user;
    public $orders;
    
    public function __construct(){
        parent::__construct();
        
        $this->orderManager = new Order();
        $this->menuManager = new Menu();
    }

    public function get(){
        // Check if user is logged in. Then fallback with attempting to get
        // credentials from unregistered_users.
        if($this->sessionManager->isUserLoggedIn()){
            $this->user = $this->userManager->getUserByID($this->sessionManager->getUserID());
        }
        
        /* TODO: Move this to a "ViewOrders" type page.
        $this->userWholeName = $this->userManager->getUserWholeName($userID);

        $orderIDs = $this->orderManager->getAllOrderIDsByUserID($userID);
        
        foreach($orderIDs as $orderID){
            $orderID = $orderID["id"];
            $this->orders[] = $this->orderManager->getEntireOrderByOrderID($orderID);
        }
        */

        require_once APP_ROOT . "/views/home/home-page.php";
    }
}

?>
