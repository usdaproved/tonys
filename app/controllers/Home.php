<?php

class Home{
    private $db;

    public function __construct(){
        $this->db = new DatabaseAccess;
    }

    public function index(){
        require_once APP_ROOT . "/views/home/home-page.php";
    }
}

?>
