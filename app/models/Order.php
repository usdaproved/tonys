<?php

class Order extends Model{

    /**
     * A cart is just an order that hasn't been submitted yet.
     */
    public function createCart(int $userID) : int {
        // Create order, then add line items associated with order.
        $sql = "INSERT INTO orders (user_id)
VALUES (:user_id);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $cartID = $this->db->lastInsertID();

        // Create payment tokens and anything explicitly tied to the cart.
        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
        $paymentIntent = \Stripe\PaymentIntent::create([
            // TODO(Trystan): Figure out what other options to set here.
            "amount" => 50, // create a stub transaction of 50 cents, stripe min.
            "currency" => "usd",
            "metadata" => [
                "order_id" => $cartID,
            ],
        ]);
        
        $stripePaymentID = $paymentIntent["id"];

        // We can then insert all necessary payment tokens into the same table.
        $sql = "INSERT INTO order_payment_tokens (order_id, stripe_token)
VALUES (:order_id, :stripe_token);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":order_id", $cartID);
        $this->db->bindValueToStatement(":stripe_token", $stripePaymentID);

        $this->db->executeStatement();

        return $cartID;
    }

    public function addLineItemToCart(int $cartID, int $itemID,
                                       int $quantity, float $price, string $comment) : int {
        $sql = "INSERT INTO order_line_items (order_id, menu_item_id, quantity, price, comment) 
VALUES (:order_id, :menu_item_id, :quantity, :price, :comment);";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":order_id", $cartID);
        $this->db->bindValueToStatement(":menu_item_id", $itemID);
        $this->db->bindValueToStatement(":quantity", $quantity);
        $this->db->bindValueToStatement(":price", $price);
        $this->db->bindValueToStatement(":comment", $comment);
        
        $this->db->executeStatement();

        $lineItemID = $this->db->lastInsertID();

        $this->updateCartSubtotal($cartID);

        return $lineItemID;
    }

    public function getCartTotalPrice(int $cartID) : float {
        // TODO(Trystan): Update this to use the actual total post tax.
        $sql = "SELECT subtotal FROM orders WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $cartID);
        $this->db->executeStatement();

        return $this->db->getResult()["subtotal"];
    }

    public function addOptionToLineItem(int $lineItemID, int $choiceID, int $optionID) : void {
        $sql = "INSERT INTO line_item_choices (line_item_id, choice_parent_id, choice_child_id)
VALUES (:line_item_id, :choice_parent_id, :choice_child_id);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":line_item_id", $lineItemID);
        $this->db->bindValueToStatement(":choice_parent_id", $choiceID);
        $this->db->bindValueToStatement(":choice_child_id", $optionID);

        $this->db->executeStatement();
    }

    public function addAdditionToLineItem(int $lineItemID, int $additionID) : void {
        $sql = "INSERT INTO line_item_additions (line_item_id, addition_id)
VALUES (:line_item_id, :addition_id);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":line_item_id", $lineItemID);
        $this->db->bindValueToStatement(":addition_id", $additionID);

        $this->db->executeStatement();
    }

    // TODO(trystan): function that need made
    // update line item
    // update option to line item. Which is probably best to just remove and add new.
    // same goes for additions.
    // remove line item.
    // remove addition.
    // we could just make all these update line item, where we remove
    // the line item first, then we just add all new information.
    // Where the new information would be submitted all together anyway.

    /**
     * Cart specified because there should be no reason why
     * a completed order would have it's price updated after submission.
     */
    public function updateCartSubtotal(int $cartID) : void {
        $sql = "UPDATE orders SET 
subtotal = (SELECT SUM(price) FROM order_line_items WHERE order_id = :id)  
WHERE id = :id;";
        
        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $cartID);
        $this->db->executeStatement();
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

    public function getOrderByID(int $orderID = NULL) : ?array {
        $sql = "SELECT * FROM orders WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->executeStatement();

        $order = $this->db->getResult();

        $sql = "SELECT 
li.id,
li.order_id,
mi.name,
li.price,
li.quantity,
li.comment
FROM order_line_items AS li
LEFT JOIN menu_items AS mi
ON li.menu_item_id = mi.id
WHERE li.order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        $lineItems = $this->db->getResultSet();

        $order["line_items"] = [];
        foreach($lineItems as $lineItem){
            $id = $lineItem["id"];
            $order["line_items"][$id] = $lineItem;

            $sql = "SELECT
DISTINCT cp.name,
cp.id
FROM choices_parents AS cp
LEFT JOIN line_item_choices AS lic
ON lic.choice_parent_id = cp.id
WHERE lic.line_item_id = :line_item_id;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":line_item_id", $id);
            $this->db->executeStatement();

            $choices = $this->db->getResultSet();
            
            $order["line_items"][$id]["choices"] = [];
            
            foreach($choices as $choice){
                $choiceID = $choice["id"];
                $sql = "SELECT 
lic.choice_child_id AS id,
cc.name,
cc.price_modifier AS price
FROM line_item_choices AS lic
LEFT JOIN choices_children AS cc
ON lic.choice_child_id = cc.id
WHERE lic.line_item_id = :line_item_id 
AND lic.choice_parent_id = :choice_parent_id;";

                $this->db->beginStatement($sql);
                $this->db->bindValueToStatement(":line_item_id", $id);
                $this->db->bindValueToStatement(":choice_parent_id", $choiceID);
                $this->db->executeStatement();

                $options = $this->db->getResultSet();
                $order["line_items"][$id]["choices"][$choiceID] = [];
                foreach($options as $option){
                    $optionID = $option["id"];
                    $order["line_items"][$id]["choices"][$choiceID]["options"][$optionID]["price"]
                        = $option["price"];
                    $order["line_items"][$id]["choices"][$choiceID]["options"][$optionID]["name"]
                        = $option["name"];
                }
                $order["line_items"][$id]["choices"][$choiceID]["name"] = $choice["name"];
            }

            // These are guaranteed to be DISTINCT.
            $sql = "SELECT 
lia.addition_id AS id,
a.name,
a.price_modifier AS price
FROM line_item_additions AS lia
LEFT JOIN additions AS a
ON lia.addition_id = a.id
WHERE lia.line_item_id = :line_item_id;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":line_item_id", $id);
            $this->db->executeStatement();

            $additions = $this->db->getResultSet();

            $order["line_items"][$id]["additions"] = [];
            foreach($additions as $addition){
                $additionID = $addition["id"];
                $order["line_items"][$id]["additions"][$additionID]["price"]
                    = $addition["price"];
                $order["line_items"][$id]["additions"][$additionID]["name"]
                    = $addition["name"];
            }
        }

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

    public function getAllOrdersByStatus(int $status) : array {
        $sql = "SELECT id FROM orders o WHERE status = :status ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":status", $status);
        $this->db->executeStatement();

        $ids = $this->db->getResultSet();
        if(is_bool($ids)) return array();
        
        $orders = [];

        foreach($ids as $id){
            $orders[] = $this->getOrderByID($id["id"]);
        }

        return $orders;
    }

    public function getAllActiveOrders() : array {
        $sql = "SELECT id FROM orders o 
WHERE status NOT IN (" . CART . "," . DELIVERED . "," . COMPLETE . ") 
ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $ids = $this->db->getResultSet();
        if(is_bool($ids)) return array();
        
        $orders = [];

        foreach($ids as $id){
            $orders[] = $this->getOrderByID($id["id"]);
        }

        return $orders;
    }

    public function getActiveOrdersAfterDate(string $date) : array {
        $sql = "SELECT id FROM orders o 
WHERE status NOT IN (" . CART . "," . DELIVERED . "," . COMPLETE . ")
AND date > :date 
ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":date", $date);
        $this->db->executeStatement();

        $ids = $this->db->getResultSet();
        if(is_bool($ids)) return array();
        
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

    public function getPaymentTokens(int $orderID) : array {
        $sql = "SELECT * FROM order_payment_tokens WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }
}

?>
