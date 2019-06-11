<?php

class Order{
    private $db;

    public function __construct(){
        $this->db = new DatabaseAccess;
    }

    public function index(){
        // TODO: Pull menu from database.
        require_once APP_ROOT . "/views/order/order-page.php";
    }
}
