<?php

require_once APP_ROOT . "/models/Model.php";

class Menu extends Model{
    
    public function getWholeMenu() : array {
        $sql = "SELECT * FROM menu_items;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }

    // TODO: Come back to this funciton.
    // Should it even be in "Menu"?
    // Is there a more efficient MySQL based way of doing this?
    // TODO: This shouldn't exist.
    // We should just have getItemPricesByItemNames(array $itemNames)
    public function calculateTotalPrice(array $order) : float {
        $totalPrice = 0.00;
        
        foreach($order as $key => $value){
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

    public function getItemNameByID(int $menuItemID) : string {
        $sql = "SELECT name FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        return $this->db->getResult()["name"];
    }
}

?>
