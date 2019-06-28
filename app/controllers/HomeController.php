<?php

require_once APP_ROOT . "/models/User.php";
require_once APP_ROOT . "/models/Order.php";
require_once APP_ROOT . "/models/Menu.php";

class HomeController{
    private $userManager;
    private $orderManager;
    private $menuManager;
    
    public $userWholeName;
    public $orders;
    
    public function __construct(){
        $this->userManager = new User();
        $this->orderManager = new Order();
        $this->menuManager = new Menu();
    }

    public function get(){
        $userID = $this->userManager->getUserID();

        // TODO: setup a redirect to a new /register page.
        if(!$userID && is_bool($userID)){
            require_once APP_ROOT . "/views/home/home-register-page.php";

            return;
        }

        $this->userWholeName = $this->userManager->getUserWholeName($userID);

        $orderIDs = $this->orderManager->getAllOrderIDsByUserID($userID);
        
        // No point in iterating through and creating an array of id's.
        // That would mean we'd be iterating through this list twice.
        
        foreach($orderIDs as $orderID){
            $this->orders[] = $this->orderManager->getEntireOrderByOrderID($orderID["id"]);
        }

        require_once APP_ROOT . "/views/home/home-page.php";
    }

    public function post(){
        $post = filter_input_array(INPUT_POST);
        $post = array_map('trim', $post);
        $post = array_map('htmlspecialchars', $post);

        $userID = $this->userManager->createUser($post);

        $this->userWholeName = $this->userManager->getUserWholeName($userID);

        require_once APP_ROOT . "/views/home/home-page.php";
    }
}

?>
