<?php

require_once APP_ROOT . "/models/MenuItems.php";

class Order{
    private $menuItems;

    public $menu;
    
    public function __construct(){
        $this->menuItems = new MenuItems();
    }

    public function index(){
        // TODO: Pull menu from database.
        $this->menu = $this->menuItems->getWholeMenu();

        require_once APP_ROOT . "/views/order/order-page.php";
    }

}

?>
