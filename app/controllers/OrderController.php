<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Menu.php";
require_once APP_ROOT . "/models/Order.php";

class OrderController extends Controller{

    private $menuManager;
    private $orderManager;
        
    public $menu;
    public $cart;
    
    public function __construct(){
        parent::__construct();
        
        $this->menuManager = new Menu();
        $this->orderManager = new Order();
    }

    public function get(){
        $this->menu = $this->menuManager->getWholeMenu();

        $userID = $this->getUserID();

        $cartID = $this->orderManager->getCartID($userID);

        if(!is_null($cartID)){
            $this->cart = $this->orderManager->getEntireOrderByOrderID($cartID);
        }
        

        require_once APP_ROOT . "/views/order/order-select-page.php";
    }

    public function post(){
        // TODO: Create a utility function for filtering post.
        $post = filter_input_array(INPUT_POST);
        $post = array_map('trim', $post);
        $post = array_map('htmlspecialchars', $post);

        $userID = $this->getUserID();

        if(is_null($userID)){
            $userID = $this->userManager->createUnregisteredCredentials();
        }
        
        // TODO: something needs to be done about this.
        $totalPrice = $this->menuManager->calculateTotalPrice($post);

        $cartID = $this->orderManager->getCartID($userID);
        
        if(is_null($cartID)){
            $this->orderManager->createCart($userID, $totalPrice, $post);
        } else {
            $this->orderManager->updateCart($cartID, $totalPrice, $post);
        }
        // Take the user to the next page to fill out info.
        header("Location: /Order/submit");
        exit;
    }

    public function submit_get(){
        $userID = $this->getUserID();
        
        $cartID = $this->orderManager->getCartID($userID);
        if(is_null($cartID)){
            header("Location: /Order");
            exit;
        }

        $this->cart = $this->orderManager->getEntireOrderByOrderID($cartID);

        echo "Order has been added to cart.";
    }

    public function submit_post(){
        echo "POSTED.";
    }

}

?>
