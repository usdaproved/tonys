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

        if(!$userID && is_bool($userID)){
            require_once APP_ROOT . "/views/home/home-register-page.php";

            return;
        }

        $this->userWholeName = $this->userManager->getUserWholeName($userID);

        // TODO: There's absolutely a better way of loading orders.
        // Make an SQL statement that joins all these together.
        // Something like $this->orderManager->getEntireOrderByID($orderID);
        $this->orders = $this->orderManager->getAllOrdersByUserID($userID);
        for($i = 0; $i < sizeof($this->orders); $i++){
            $this->orders[$i]["order_line_item"] =
                 $this->orderManager->getOrderLineItems(
                     $this->orders[$i]["id"]
            );
            for($j = 0; $j < sizeof($this->orders[$i]["order_line_item"]); $j++){
                $this->orders[$i]["order_line_item"][$j]["name"] =
                     $this->menuManager->getItemNameByID(
                         $this->orders[$i]["order_line_item"][$j]["menu_item_id"]
                );
            }
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
