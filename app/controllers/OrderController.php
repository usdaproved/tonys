<?php

require_once APP_ROOT . "/models/Menu.php";
require_once APP_ROOT . "/models/Order.php";
require_once APP_ROOT . "/models/User.php";

class OrderController{

    // TODO: see if this is the best naming scheme.
    private $menuManager;
    private $orderManager;
    private $userManager;
    
    public $menu;
    public $order;
    public $userWholeName;
    
    public function __construct(){
        $this->menuManager = new Menu();
        $this->orderManager = new Order();
        $this->userManager = new User();

        // TODO: Could create some $menuItemName => $price pair.
        // Or $menuItemName => $menuItemID.
    }

    public function get(){
        $userID = $this->userManager->getUserID();
        if(!$userID && is_bool($userID)){
            echo "<h1>Must be logged in to order. <a href='/'>Register now</a></h1>";
            return;
        }
        $this->menu = $this->menuManager->getWholeMenu();

        require_once APP_ROOT . "/views/order/order-selection-page.php";
    }

    public function post(){
        // Construct order based on php session.
        // TODO: Add actual support for logging in and tracking users.

        // filter post and then send it off.
        $post = filter_input_array(INPUT_POST);
        $post = array_map('trim', $post);
        $post = array_map('htmlspecialchars', $post);

        // TODO: we are already making N*2 sql requests getting id's and price's.
        // We could at least divide that in half by getting them once here and passing along.
        
        $userID = $this->userManager->getUserID();
        
        if(!$userID && is_bool($userID)){
            // if not logged in, show error.
            echo "<h1> Must be logged in for this operation. </h1>";
            return;
        }

        $totalPrice = $this->menuManager->calculateTotalPrice($post);

        $orderID = $this->orderManager->createOrder($userID, $totalPrice, $post);

        $this->order = $this->orderManager->getEntireOrderByOrderID($orderID);
                
        $this->userWholeName = $this->userManager->getUserWholeName($userID);        

        require_once APP_ROOT . "/views/order/order-payment-page.php";
    }

}

?>
