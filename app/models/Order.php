<?php

require_once APP_ROOT . "/models/Model.php";

class Order extends Model{

    public function createOrder($userID, $totalPrice, $post){
        // Create order, then add line items associated with order.
        $sqlOrder = "INSERT INTO orders (user_id, total_price, status, date)
VALUES (:user_id, :total_price, :status, :date);";

        $this->db->beginStatement($sqlOrder);

        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":total_price", $totalPrice);
        $this->db->bindValueToStatement(":status", "Submitted"); // Initial status
        $this->db->bindValueToStatement(":date", date("Y-m-d H:i:s"));

        $this->db->executeStatement();
        
        $orderID = $this->db->lastInsertID();
        
        // Go through each item in list
        // ensure valid values,
        // prepare SQL statement for each
        // then execute.
        foreach($post as $key => $value){
            if($value && $value > 0){
                $sqlGetMenuItemID = "SELECT id FROM menu_items WHERE name = :name";

                $this->db->beginStatement($sqlGetMenuItemID);
                $this->db->bindValueToStatement(":name", $key);
                $this->db->executeStatement();

                $menuItemID = $this->db->getResult();
                $menuItemID = $menuItemID["id"];
                
                $sqlLineItem = "INSERT INTO order_line_items (order_id, menu_item_id, quantity) 
VALUES (:order_id, :menu_item_id, :quantity);";

                $this->db->beginStatement($sqlLineItem);

                $this->db->bindValueToStatement(":order_id", $orderID);
                $this->db->bindValueToStatement(":menu_item_id", $menuItemID);
                $this->db->bindValueToStatement(":quantity", $value);

                $this->db->executeStatement();
            }
        }

        return $orderID;
    }

    public function getOrderByOrderID($orderID){
        $sql = "SELECT * FROM orders WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResultSet()[0];
    }

    public function getAllOrdersByUserID($userID){
        $sql = "SELECT * FROM orders WHERE user_id = :user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }

    public function getOrderLineItems($orderID){
        $sql = "SELECT * FROM order_line_items WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }
}

?>
