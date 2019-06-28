<?php

require_once APP_ROOT . "/models/Model.php";

class Menu extends Model{
    
    public function getWholeMenu(){
        $sql = "SELECT * FROM menu_items;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }

    public function calculateTotalPrice($post){
        $totalPrice = 0.00;
        
        foreach($post as $key => $value){
            if($value && $value > 0){
                $sql = "SELECT price FROM menu_items WHERE name = :name";
                
                $this->db->beginStatement($sql);
                $this->db->bindValueToStatement(":name", $key);
                $this->db->executeStatement();

                $itemPrice = $this->db->getResult();
                $itemPrice = $itemPrice["price"];

                if(!is_bool($itemPrice)) $totalPrice += $itemPrice * $value;
                
            }
        }

        // TODO: Taxes.
        return $totalPrice;
    }

    public function getItemNameByID($menuItemID){
        $sql = "SELECT name FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        return $this->db->getResult()["name"];
    }
}

?>
