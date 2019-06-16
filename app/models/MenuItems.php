<?php

class MenuItems {

    private $db;
    
    public function __construct(){
        $this->db = new DatabaseAccess();
    }
    
    public function getWholeMenu(){
        $sqlStatement = "SELECT * FROM menu_items;";

        $this->db->beginStatement($sqlStatement);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }
}

?>
