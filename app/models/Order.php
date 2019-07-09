<?php

require_once APP_ROOT . "/models/Model.php";

class Order extends Model{

    // A cart is just an order that hasn't been submitted yet.
    public function createCart($userID, $totalPrice, $post){
        // Create order, then add line items associated with order.
        $sqlOrder = "INSERT INTO orders (user_id, total_price, status)
VALUES (:user_id, :total_price, :status);";

        $this->db->beginStatement($sqlOrder);

        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":total_price", $totalPrice);
        $this->db->bindValueToStatement(":status", "cart"); // Initial status

        $this->db->executeStatement();
        
        $cartID = $this->db->lastInsertID();
        
        $this->addLineItemsToOrder($cartID, $post);
    }

    public function updateCart($cartID, $totalPrice, $post){
        // Update totalPrice in order table
        $sql = "UPDATE orders SET total_price = :total_price WHERE id = :id;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":total_price", $totalPrice);
        $this->db->bindValueToStatement(":id", $cartID);
        
        $this->db->executeStatement();
        
        // Delete all line items and create all new ones.
        $sql = "DELETE FROM order_line_items WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $cartID);
        $this->db->executeStatement();

        $this->addLineItemsToOrder($cartID, $post);
    }

    // Updates the cart into a submitted order.
    public function submitOrder($cartID){
        
    }

    // If an unregistered user already had orders tied to it, and logged into an account
    // that has a different user associated with it. Then we should bring over all their data
    // to the registered account. Also update the cart to what it was on the device they
    // just logged into as that 'should' be the most recent one, logically. 
    public function updateOrdersFromUnregisteredToRegistered($unregisteredUserID, $registeredUserID){
        $previousCartID = $this->getCartID($registeredUserID);
        
        $sql = "UPDATE orders SET user_id = :registered_user_id
WHERE user_id = :unregistered_user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":registered_user_id", $registeredUserID);
        $this->db->bindValueToStatement(":unregistered_user_id", $unregisteredUserID);
        $this->db->executeStatement();
        
        $sql = "DELETE FROM orders WHERE id = :previous_cart_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":previous_cart_id", $previousCartID);
        $this->db->executeStatement();
    }

    // TODO: Perhaps we can just get all menu_items id's in one statement?
    // This would turn an N*2 request into an N+1.
    public function addLineItemsToOrder($orderID, $post){
        foreach($post as $key => $value){
            if($value && $value > 0){
                $sql = "SELECT id FROM menu_items WHERE name = :name";

                $this->db->beginStatement($sql);
                $this->db->bindValueToStatement(":name", $key);
                $this->db->executeStatement();

                $menuItemID = $this->db->getResult();
                $menuItemID = $menuItemID["id"];
                
                $sql = "INSERT INTO order_line_items (order_id, menu_item_id, quantity) 
VALUES (:order_id, :menu_item_id, :quantity);";

                $this->db->beginStatement($sql);

                $this->db->bindValueToStatement(":order_id", $orderID);
                $this->db->bindValueToStatement(":menu_item_id", $menuItemID);
                $this->db->bindValueToStatement(":quantity", $value);

                $this->db->executeStatement();
            }
        }
    }

    public function getCartID($userID){
        $sql = "SELECT id FROM orders WHERE user_id = :user_id AND status = 'cart';";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $orderID = $this->db->getResult();

        if(is_bool($orderID)) return NULL;
        return $orderID["id"];
    }

    public function getOrderByOrderID($orderID){
        $sql = "SELECT * FROM orders WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    public function getEntireOrderByOrderID($orderID){
        $order = $this->getOrderByOrderID($orderID);

        $sql = "SELECT
	menu_items.name,
	order_line_items.quantity
FROM order_line_items
LEFT JOIN menu_items ON menu_items.id = order_line_items.menu_item_id
WHERE order_line_items.order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        $order["order_line_items"] = $this->db->getResultSet();

        return $order;
    }

    public function getAllOrderIDsByUserID($userID){
        $sql = "SELECT id FROM orders WHERE user_id = :user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }
}

?>
