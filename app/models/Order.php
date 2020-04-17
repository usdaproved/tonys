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
                "user_id" => $userID,
                "order_id" => $cartID,
            ],
        ]);
        
        $stripePaymentID = $paymentIntent["id"];

        // We can then insert all necessary payment tokens into the same table.
        $sql = "INSERT INTO stripe_tokens (order_id, stripe_token)
VALUES (:order_id, :stripe_token);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":order_id", $cartID);
        $this->db->bindValueToStatement(":stripe_token", $stripePaymentID);

        $this->db->executeStatement();

        $sql = "INSERT INTO order_cost (order_id) VALUES (:order_id);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $cartID);
        $this->db->executeStatement();

        return $cartID;
    }

    public function addLineItemToCart(int $cartID, int $itemID,
                                       int $quantity, int $price, string $comment) : int {
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

        $this->updateCost($cartID);

        return $lineItemID;
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

    public function deleteLineItem(int $cartID, int $lineItemID) : void {
        $sql = "DELETE FROM line_item_choices WHERE line_item_id = :line_item_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":line_item_id", $lineItemID);
        $this->db->executeStatement();

        $sql = "DELETE FROM line_item_additions WHERE line_item_id = :line_item_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":line_item_id", $lineItemID);
        $this->db->executeStatement();

        $sql = "DELETE FROM order_line_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $lineItemID);
        $this->db->executeStatement();

        // At the end make sure we update the new cost
        $this->updateCost($cartID);
    }

    public function setOrderType(int $cartID, int $orderType) : void {
        $sql = "UPDATE orders SET order_type = :order_type WHERE id = :id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $cartID);
        $this->db->bindValueToStatement(":order_type", $orderType);        
        
        $this->db->executeStatement();
    }

    public function getOrderType(int $orderID) : int {
        $sql = "SELECT order_type FROM orders WHERE id = :id";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult()["order_type"];
    }

    public function updateFee(int $cartID, int $amount) : void {
        $sql = "UPDATE order_cost SET fee = :fee WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":order_id", $cartID);
        $this->db->bindValueToStatement(":fee", $amount);

        $this->db->executeStatement();
    }

    /**
     * Only to be used by employees to associate a customer with IN_RESTAURANT orders.
     * Also to not fill up the employees order history with other customer orders.
     */
    public function assignUserToOrder(int $orderID, $userID = NULL) : void {
        $sql = "UPDATE orders SET user_id = :user_id WHERE id = :id;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->bindValueToStatement(":user_id", $userID);
        
        $this->db->executeStatement();
    }

    /**
     *Updates the cart into a submitted order.
     */
    public function submitOrder(int $cartID) : void {
        $sql = "UPDATE orders SET 
status = " . SUBMITTED . ", date = NOW() WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $cartID);
        $this->db->executeStatement();
    }

    public function setDeliveryAddressID(int $orderID, int $addressID) : void {
        $sql = "INSERT INTO delivery_address 
(order_id, address_id) VALUES (:order_id, :address_id);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->bindValueToStatement(":address_id", $addressID);
        
        $this->db->executeStatement();
    }

    public function getDeliveryAddress(int $orderID) : array {
        $sql = "SELECT * FROM address 
WHERE id = (SELECT address_id FROM delivery_address WHERE order_id = :order_id);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    public function submitPayment(int $cartID, int $amount, int $method) : void {
        $sql = "INSERT INTO order_payments (order_id, amount, method) VALUES (:order_id, :amount, :method);";
        
        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":order_id", $cartID);
        $this->db->bindValueToStatement(":amount", $amount);
        $this->db->bindValueToStatement(":method", $method);

        $this->db->executeStatement();
    }

    // TODO(Trystan): We probably want to add a Date column. So that way we know when refunds were issued.
    public function submitRefund(int $paymentID, int $amount) : void {
        $sql = "INSERT INTO payment_refunds (payment_id, amount) VALUES (:payment_id, :amount);";
        
        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":payment_id", $paymentID);
        $this->db->bindValueToStatement(":amount", $amount);

        $this->db->executeStatement();
    }

    public function isPaid(int $orderID) : bool {
        $sql = "SELECT 
CASE WHEN (SUM(op.amount) = (oc.subtotal + oc.tax + oc.fee)) THEN 1 ELSE 0 END AS is_paid 
FROM order_payments op 
LEFT JOIN order_cost oc
ON oc.order_id = op.order_id 
WHERE op.order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        return (bool)$this->db->getResult()["is_paid"];
    }

    /** 
     * If an unregistered user already had orders tied to it, and logged into an account
     * that has a different user associated with it. Then we should bring over all their data
     * to the registered account. Also update the cart to what it was on the device they
     * just logged into as that 'should' be the most recent one, logically. 
     */
    // TODO(Trystan): Carts don't look like they used to. Now there's all kinds of line items and additions,
    // deleting a cart ain't this easy anymore. When we get to deleting cart items, we will re-implement this.
    public function updateOrdersFromUnregisteredToRegistered(int $unregisteredUserID, int $registeredUserID) : void {
        //$previousCartID = $this->getCartID($registeredUserID);
        
        $sql = "UPDATE orders SET user_id = :registered_user_id
WHERE user_id = :unregistered_user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":registered_user_id", $registeredUserID);
        $this->db->bindValueToStatement(":unregistered_user_id", $unregisteredUserID);
        $this->db->executeStatement();

        /*
        $sql = "DELETE FROM orders WHERE id = :previous_cart_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":previous_cart_id", $previousCartID);
        $this->db->executeStatement();
        */
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

    /***
     * Returns top level order info, no line items etc.
     */
    public function getBasicOrderInfo(int $orderID) : array {
        $sql = "SELECT * FROM orders WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    // TODO(Trystan): It's not even possible to get a NULL return here.
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
        $sql = "SELECT id FROM orders o 
WHERE user_id = :user_id
AND status > " . CART . "
ORDER BY o.date ASC;";

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

    // TODO(Trystan): Nobody calls this. Something from a bygone era probably.
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
WHERE status NOT IN (" . CART . "," . COMPLETE . ") 
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
WHERE status NOT IN (" . CART . ","  . COMPLETE . ")
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

    public function getActiveOrderStatus() : array {
        $sql = "SELECT id, status FROM orders o 
WHERE status NOT IN (" . CART . ","  . COMPLETE . ")
ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(is_bool($result)) return array();

        return $result;
    }

    public function getUserActiveOrderStatus(int $userID = NULL) : ?int {
        $sql = "SELECT x.status
FROM orders x
LEFT OUTER JOIN orders y
ON x.user_id = y.user_id
AND x.date < y.date
AND y.status NOT IN (" . CART . "," . COMPLETE . ")
WHERE x.user_id = :user_id
AND y.user_id IS NULL
AND x.status NOT IN (" . CART . "," . COMPLETE . ")
ORDER BY x.date desc;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $status = $this->db->getResult();
        
        if(is_bool($status)) return NULL;
        return $status["status"];
    }

    /***
     * For verification that user owns the line item they are requesting to delete.
     */
    public function isLineItemInOrder(int $orderID, int $lineItem) : bool {
        $sql = "SELECT 
IF(oli.order_id = :order_id,TRUE,FALSE) AS is_line_item_in_order 
FROM order_line_items oli 
WHERE oli.id = :id;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->bindValueToStatement(":id", $lineItem);

        $this->db->executeStatement();

        $result = $this->db->getResult();
        return (bool)$result["is_line_item_in_order"];
    }

    public function getOrdersMatchingFilters(string $startDate = NULL, string $endDate = NULL, int $startAmount = NULL,
                                             int $endAmount = NULL, int $orderType = NULL, string $firstName = NULL,
                                             string $lastName = NULL, string $email = NULL, string $phoneNumber = NULL) : array {
        $sql = "SELECT o.id, o.user_id FROM orders o 
LEFT JOIN order_cost c
ON c.order_id = o.id 
LEFT JOIN users u
ON o.user_id = u.id
WHERE
(u.name_first   LIKE CONCAT('%', :name_first, '%')   OR :name_first   IS NULL) AND
(u.name_last    LIKE CONCAT('%', :name_last, '%')    OR :name_last    IS NULL) AND
(u.email        LIKE CONCAT('%', :email, '%')        OR :email        IS NULL) AND
(u.phone_number LIKE CONCAT('%', :phone_number, '%') OR :phone_number IS NULL) AND 
((o.date BETWEEN :start_date AND :end_date) OR (:start_date IS NULL AND :end_date IS NULL)) AND
(((c.fee + c.subtotal + c.tax) BETWEEN :start_amount AND :end_amount) OR (:start_amount IS NULL AND :end_amount IS NULL)) AND
(o.order_type = :order_type OR :order_type IS NULL) AND
o.status = " . COMPLETE . "
GROUP BY o.id
ORDER BY o.date DESC;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":start_date", $startDate);
        $this->db->bindValueToStatement(":end_date", $endDate);
        $this->db->bindValueToStatement(":start_amount", $startAmount);
        $this->db->bindValueToStatement(":end_amount", $endAmount);
        $this->db->bindValueToStatement(":order_type", $orderType);
        $this->db->bindValueToStatement(":name_first", $firstName);
        $this->db->bindValueToStatement(":name_last", $lastName);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->bindValueToStatement(":phone_number", $phoneNumber);
        
        $this->db->executeStatement();

        $result = $this->db->getResultSet();

        if(is_bool($result)) return array();

        return $result;
    }

    public function getCost(int $orderID) : array {
        $sql = "SELECT * FROM order_cost WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    public function getPayments(int $orderID) : array {
        $sql = "SELECT id, amount, method FROM order_payments WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(is_bool($result)) return array();
        return $result;
    }

    public function getPaymentByID(int $paymentID) : array {
        $sql = "SELECT order_id, amount, method FROM order_payments WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $paymentID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    // Refund total for the individual payments.
    // TODO(Trystan): We may want to add a date column. So that way we can track when refunds were given out.
    public function getRefundTotal(int $paymentID) : int {
        $sql = "SELECT SUM(amount) as refund_total FROM payment_refunds WHERE payment_id = :payment_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":payment_id", $paymentID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_null($result)) return 0;
        return (int)$result["refund_total"];
    }

    public function setPaypalToken(int $orderID, string $token) : void {
        $sql = "INSERT INTO paypal_tokens (order_id, paypal_token) VALUES (:order_id, :paypal_token);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->bindValueToStatement(":paypal_token", $token);

        $this->db->executeStatement();
    }

    public function getPaypalToken(int $orderID) : string {
        $sql = "SELECT * FROM paypal_tokens WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult()["paypal_token"];
    }

    public function getStripeToken(int $orderID) : string {
        $sql = "SELECT * FROM stripe_tokens WHERE order_id = :order_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_id", $orderID);
        $this->db->executeStatement();

        return $this->db->getResult()["stripe_token"];
    }

    /**
     * Updates the subtotal and the tax.
     */
    private function updateCost(int $cartID) : void {
        $sql = "UPDATE order_cost o SET 
o.subtotal = (SELECT SUM(price) FROM order_line_items WHERE order_id = :id),
o.tax = ROUND(o.subtotal * 0.07)
WHERE order_id = :id;"; // NOTE(Trystan): 0.07 is IOWA tax rate.
        
        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $cartID);
        $this->db->executeStatement();
    }
}

?>
