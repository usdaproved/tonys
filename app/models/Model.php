<?php

require_once APP_ROOT . "/core/Database.php";

class Model{
    protected $db;

    public function  __construct(){
        $this->db = new Database();
    }
}

?>
