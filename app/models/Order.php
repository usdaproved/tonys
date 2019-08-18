<?php

require_once APP_ROOT . "/models/Model.php";

class Order extends Model{

    // TODO: Get rid of all $_POST

    // A cart is just an order that hasn't been submitted yet.
    public function createCart(int $userID, array $order, float $totalPrice) : void {
        // Create order, then add line items associated with order.
        $sqlOrder = "INSERT INTO orders (user_id, total_price)
VALUES (:user_id, :total_price);";

        $this->db->beginStatement($sqlOrder);

        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":total_price", $totalPrice);

        $this->db->executeStatement();
        
        $cartID = $this->db->lastInsertID();
        
        $this->addLineItemsToOrder($cartID, $order);
    }

    // TODO: Perhaps we can just get all menu_items id's in one statement?
    // This would turn an N*2 request into an N+1.
    // TODO: Strip all this logic out into the controller.
    // This should only function as an SQL call.
    public function addLineItemsToOrder(int $orderID, array $order) : void {
        foreach($order as $key => $value){
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

    public function updateCart(int $cartID, array $order, float $totalPrice) : void {
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

        $this->addLineItemsToOrder($cartID, $order);
    }

    /**
     *Updates the cart into a submitted order.
     */
    public function submitOrder(int $cartID, int $orderType) : void {
        $sql = "UPDATE orders SET 
order_type = :order_type, status = " . SUBMITTED . ", date = NOW() WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $cartID);
        $this->db->bindValueToStatement(":order_type", $orderType);
        $this->db->executeStatement();
    }

    /** 
     * If an unregistered user already had orders tied to it, and logged into an account
     * that has a different user associated with it. Then we should bring over all their data
     * to the registered account. Also update the cart to what it was on the device they
     * just logged into as that 'should' be the most recent one, logically. 
     */
    public function updateOrdersFromUnregisteredToRegistered(int $unregisteredUserID, int $registeredUserID) : void {
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

    public function updateOrderStatus(int $orderID, int $status) : void {
        $sql = "UPDATE orders SET status = :status WHERE id = :id";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":status", $status);
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->executeStatement();
    }

    public function getCartID(int $userID = NULL) : ?int {
        $sql = "SELECT id FROM orders WHERE user_id = :user_id AND status = " . CART . ";";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $orderID = $this->db->getResult();

        if(is_bool($orderID)) return NULL;
        return $orderID["id"];
    }

    public function getOrderByID(int $orderID) : ?array {
        $sql = "SELECT * FROM orders WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->executeStatement();

        $order = $this->db->getResult();

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

        if(is_bool($order)) return NULL;
        return $order;
    }

    public function getAllOrdersByUserID(int $userID) : ?array {
        $sql = "SELECT id FROM orders o WHERE user_id = :user_id ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $ids = $this->db->getResultSet();
        if(is_bool($ids)) return NULL;
        
        $orders = [];

        foreach($ids as $id){
            $orders[] = $this->getOrderByID($id["id"]);
        }

        return $orders;
    }

    public function getAllOrdersByStatus(int $status) : ?array {
        $sql = "SELECT id FROM orders o WHERE status = :status ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":status", $status);
        $this->db->executeStatement();

        $ids = $this->db->getResultSet();
        if(is_bool($ids)) return NULL;
        
        $orders = [];

        foreach($ids as $id){
            $orders[] = $this->getOrderByID($id["id"]);
        }

        return $orders;
    }

    public function getAllActiveOrders() : ?array {
        $sql = "SELECT id FROM orders o 
WHERE status NOT IN (" . CART . "," . DELIVERED . "," . COMPLETE . ") 
ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $ids = $this->db->getResultSet();
        if(is_bool($ids)) return NULL;
        
        $orders = [];

        foreach($ids as $id){
            $orders[] = $this->getOrderByID($id["id"]);
        }

        return $orders;
    }

    public function getUserActiveOrderStatus(int $userID = NULL) : ?int {
        $sql = "SELECT x.status
FROM orders x
LEFT OUTER JOIN orders y
ON x.user_id = y.user_id
AND x.date < y.date
AND y.status NOT IN (" . CART . "," . DELIVERED . "," . COMPLETE . ")
WHERE x.user_id = :user_id
AND y.user_id IS NULL
AND x.status NOT IN (" . CART . "," . DELIVERED . "," . COMPLETE . ")
ORDER BY x.date desc;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $status = $this->db->getResult();
        
        if(is_bool($status)) return NULL;
        return $status["status"];
    }
}

?>
