<?php

// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

class Order extends Model{

    /**
     * A cart is just an order that hasn't been submitted yet.
     */
    public function createCart(string $userUUID) : string {
        // Create order, then add line items associated with order.
        $cartUUID = $this->db->generateUUID();

        $sql = "INSERT INTO orders (uuid, user_uuid)
VALUES (:uuid, :user_uuid);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $cartUUID);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        // Create payment tokens and anything explicitly tied to the cart.
        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
        $paymentIntent = \Stripe\PaymentIntent::create([
            // TODO(Trystan): Figure out what other options to set here.
            "amount" => 50, // create a stub transaction of 50 cents, stripe min.
            "currency" => "usd",
            "capture_method" => 'manual',
            "metadata" => [
                "order_uuid" => UUID::orderedBytesToArrangedString($cartUUID),
            ],
        ]);
        
        $stripePaymentID = $paymentIntent["id"];

        // We can then insert all necessary payment tokens into the same table.
        $sql = "INSERT INTO stripe_tokens (order_uuid, stripe_token)
VALUES (:order_uuid, :stripe_token);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":order_uuid", $cartUUID);
        $this->db->bindValueToStatement(":stripe_token", $stripePaymentID);

        $this->db->executeStatement();

        $sql = "INSERT INTO order_cost (order_uuid) VALUES (:order_uuid);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $cartUUID);
        $this->db->executeStatement();

        // Even if the order isn't a delivery we want to be able to update this value.
        $sql = "INSERT INTO delivery_address 
(order_uuid, address_uuid) VALUES (:order_uuid, NULL);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $cartUUID);
        $this->db->executeStatement();

        return $cartUUID;
    }

    public function addLineItemToCart(string $cartUUID, int $itemID,
                                       int $quantity, int $price, string $comment) : string {
        $lineItemUUID = $this->db->generateUUID();

        $sql = "INSERT INTO order_line_items (uuid, order_uuid, menu_item_id, quantity, price, comment) 
VALUES (:uuid, :order_uuid, :menu_item_id, :quantity, :price, :comment);";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":uuid", $lineItemUUID);
        $this->db->bindValueToStatement(":order_uuid", $cartUUID);
        $this->db->bindValueToStatement(":menu_item_id", $itemID);
        $this->db->bindValueToStatement(":quantity", $quantity);
        $this->db->bindValueToStatement(":price", $price);
        $this->db->bindValueToStatement(":comment", $comment);
        
        $this->db->executeStatement();

        $this->updateCost($cartUUID);

        return $lineItemUUID;
    }

    public function addOptionToLineItem(string $lineItemUUID, int $choiceID, int $optionID) : void {
        $sql = "INSERT INTO line_item_choices (line_item_uuid, choice_parent_id, choice_child_id)
VALUES (:line_item_uuid, :choice_parent_id, :choice_child_id);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":line_item_uuid", $lineItemUUID);
        $this->db->bindValueToStatement(":choice_parent_id", $choiceID);
        $this->db->bindValueToStatement(":choice_child_id", $optionID);

        $this->db->executeStatement();
    }

    public function deleteLineItem(string $cartUUID, string $lineItemUUID) : void {
        $sql = "DELETE FROM line_item_choices WHERE line_item_uuid = :line_item_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":line_item_uuid", $lineItemUUID);
        $this->db->executeStatement();

        $sql = "DELETE FROM order_line_items WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $lineItemUUID);
        $this->db->executeStatement();

        // At the end make sure we update the new cost
        $this->updateCost($cartUUID);
    }

    public function setOrderType(string $cartUUID, int $orderType) : void {
        $sql = "UPDATE orders SET order_type = :order_type WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":uuid", $cartUUID);
        $this->db->bindValueToStatement(":order_type", $orderType);        
        
        $this->db->executeStatement();
    }

    public function getOrderType(string $orderUUID) : int {
        $sql = "SELECT order_type FROM orders WHERE uuid = :uuid";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $orderUUID);
        $this->db->executeStatement();

        return $this->db->getResult()["order_type"];
    }

    public function updateFee(string $cartUUID, int $amount) : void {
        $sql = "UPDATE order_cost SET fee = :fee WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":order_uuid", $cartUUID);
        $this->db->bindValueToStatement(":fee", $amount);

        $this->db->executeStatement();
    }

    /**
     * Only to be used by employees to associate a customer with IN_RESTAURANT orders.
     * Also to not fill up the employees order history with other customer orders.
     */
    public function assignUserToOrder(string $orderUUID, $userUUID = NULL) : void {
        $sql = "UPDATE orders SET user_uuid = :user_uuid WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":uuid", $orderUUID);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        
        $this->db->executeStatement();
    }

    /**
     *Updates the cart into a submitted order.
     */
    public function submitOrder(string $cartUUID) : void {
        $sql = "UPDATE orders SET 
status = " . SUBMITTED . ", date = NOW() WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $cartUUID);
        $this->db->executeStatement();
    }

    public function setDeliveryAddress(string $orderUUID, string $addressUUID = NULL) : void {
        $sql = "UPDATE delivery_address SET
address_uuid = :address_uuid WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->bindValueToStatement(":address_uuid", $addressUUID);
        
        $this->db->executeStatement();
    }

    public function getDeliveryAddress(string $orderUUID) : array {
        $sql = "SELECT uuid, line, city, state, zip_code FROM address 
WHERE uuid = (SELECT address_uuid FROM delivery_address WHERE order_uuid = :order_uuid);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return array();
        return $result;
    }

    public function submitPayment(string $cartUUID, int $amount, int $method) : void {
        $sql = "INSERT INTO order_payments (order_uuid, amount, method) VALUES (:order_uuid, :amount, :method);";
        
        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":order_uuid", $cartUUID);
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

    public function isPaid(string $orderUUID) : bool {
        $sql = "SELECT 
CASE WHEN (SUM(op.amount) = (oc.subtotal + oc.tax + oc.fee)) THEN 1 ELSE 0 END AS is_paid 
FROM order_payments op 
LEFT JOIN order_cost oc
ON oc.order_uuid = op.order_uuid 
WHERE op.order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->executeStatement();

        return (bool)$this->db->getResult()["is_paid"];
    }

    /** 
     * If an unregistered user generated multiple sessions before registering,
     * it's possible for them to have orders tied to the other session that should be brought over.
     * We then want to delete the cart of the old account.
     * This should be done for each unregistered user that shares an email with the newly registered one.
     * Only after email verification has been completed.
     */
    public function updateOrdersFromUnregisteredToRegistered(string $unregisteredUserUUID, string $registeredUserUUID) : void {
        $previousCartUUID = $this->getCartUUID($unregisteredUserUUID);
        
        $sql = "UPDATE orders SET user_uuid = :registered_user_uuid
WHERE user_uuid = :unregistered_user_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":registered_user_uuid", $registeredUserUUID);
        $this->db->bindValueToStatement(":unregistered_user_uuid", $unregisteredUserUUID);
        $this->db->executeStatement();

        $cart = $this->getOrderByUUID($previousCartUUID);
        foreach($cart["line_items"] ?? array() as $lineItem){
            $this->deleteLineItem($previousCartUUID, $lineItem["uuid"]);
        }

        $sql = "DELETE FROM delivery_address WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $previousCartUUID);
        $this->db->executeStatement();

        $sql = "DELETE FROM order_cost WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $previousCartUUID);
        $this->db->executeStatement();

        $sql = "DELETE FROM stripe_tokens WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $previousCartUUID);
        $this->db->executeStatement();

        $sql = "DELETE FROM orders WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $previousCartUUID);
        $this->db->executeStatement();
    }

    public function updateOrderStatus(string $orderUUID, int $status) : void {
        $sql = "UPDATE orders SET status = :status WHERE uuid = :uuid";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":status", $status);
        $this->db->bindValueToStatement(":uuid", $orderUUID);
        $this->db->executeStatement();
    }

    public function getCartUUID(string $userUUID = NULL) : ?string {
        $sql = "SELECT uuid FROM orders WHERE user_uuid = :user_uuid AND status = " . CART . ";";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        $orderUUID = $this->db->getResult();

        if(is_bool($orderUUID)) return NULL;
        return (string)$orderUUID["uuid"];
    }

    /***
     * Returns top level order info, no line items etc.
     */
    public function getBasicOrderInfo(string $orderUUID = NULL) : array {
        // NOTE(Trystan): The timezone table needs to be loaded in, in order to make this work.
        // Refer to: mysql_tzinfo_to_sql
        $sql = "SELECT uuid, user_uuid, order_type, status, CONVERT_TZ(date, @@global.time_zone, 'US/Central') as date FROM orders WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $orderUUID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return array();
        return $result;
    }
    
    public function getOrderByUUID(string $orderUUID = NULL) : array {
        $order = $this->getBasicOrderInfo($orderUUID);
        if(empty($order)){
            return $order;
        }

        $sql = "SELECT uuid FROM order_line_items WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->executeStatement();
        
        $lineItemUUIDs = $this->db->getResultSet();

        $order["line_items"] = [];

        foreach($lineItemUUIDs as $lineItemUUID){
            $lineItem = $this->getLineItem($lineItemUUID["uuid"]);
            $lineItem["uuid"] = UUID::orderedBytesToArrangedString($lineItemUUID["uuid"]);
            $order["line_items"][] = $lineItem;
        }

        return $order;
    }

    public function getLineItem(string $UUID) : array {
        $sql = "SELECT 
li.menu_item_id,
mi.name,
li.price,
li.quantity,
li.comment
FROM order_line_items AS li
LEFT JOIN menu_items AS mi
ON li.menu_item_id = mi.id
WHERE li.uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $UUID);
        $this->db->executeStatement();
        
        $lineItem = $this->db->getResult();

        $sql = "SELECT
DISTINCT cp.name,
cp.id
FROM choices_parents AS cp
LEFT JOIN line_item_choices AS lic
ON lic.choice_parent_id = cp.id
WHERE lic.line_item_uuid = :line_item_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":line_item_uuid", $UUID);
        $this->db->executeStatement();

        $choices = $this->db->getResultSet();
            
        $lineItem["choices"] = [];
            
        foreach($choices as $choice){
            $choiceID = $choice["id"];
            $sql = "SELECT 
lic.choice_child_id AS id,
cc.name,
cc.price_modifier AS price
FROM line_item_choices AS lic
LEFT JOIN choices_children AS cc
ON lic.choice_child_id = cc.id
WHERE lic.line_item_uuid = :line_item_uuid 
AND lic.choice_parent_id = :choice_parent_id;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":line_item_uuid", $UUID);
            $this->db->bindValueToStatement(":choice_parent_id", $choiceID);
            $this->db->executeStatement();

            $options = $this->db->getResultSet();
            $lineItem["choices"][$choiceID] = [];
            foreach($options as $option){
                $optionID = $option["id"];
                $lineItem["choices"][$choiceID]["options"][$optionID]["price"]
                    = $option["price"];
                $lineItem["choices"][$choiceID]["options"][$optionID]["name"]
                    = $option["name"];
            }
            $lineItem["choices"][$choiceID]["name"] = $choice["name"];
        }

        return $lineItem;
    }

    public function getAllOrdersByUserUUID(string $userUUID) : array {
        $sql = "SELECT uuid FROM orders o 
WHERE user_uuid = :user_uuid
AND status > " . CART . "
ORDER BY o.date DESC;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        $uuids = $this->db->getResultSet();
        if(is_bool($uuids)) return array();
        
        $orders = [];

        foreach($uuids as $uuid){
            $orders[] = $this->getOrderByUUID($uuid["uuid"]);
        }

        return $orders;
    }

    public function getAllActiveOrders() : array {
        $sql = "SELECT uuid FROM orders o 
WHERE status NOT IN (" . CART . "," . COMPLETE . ") 
ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $uuids = $this->db->getResultSet();
        if(is_bool($uuids)) return array();
        
        $orders = [];

        foreach($uuids as $uuid){
            $orders[] = $this->getOrderByUUID($uuid["uuid"]);
        }

        return $orders;
    }

    public function getActiveOrdersAfterDate(string $date) : array {
        $sql = "SELECT uuid FROM orders o 
WHERE status NOT IN (" . CART . ","  . COMPLETE . ")
AND date > CONVERT_TZ(:date, 'US/Central', @@global.time_zone) 
ORDER BY o.date ASC;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":date", $date);
        $this->db->executeStatement();

        $uuids = $this->db->getResultSet();
        if(is_bool($uuids)) return array();
        
        $orders = [];

        foreach($uuids as $uuid){
            $orders[] = $this->getOrderByUUID($uuid["uuid"]);
        }

        return $orders;
    }

    public function printer_getNextActiveOrder(string $date = NULL) : array {
        $uuid = NULL;
        
        if($date !== NULL){
            $sql = "SELECT uuid FROM orders o 
WHERE status NOT IN (" . CART . ","  . COMPLETE . ")
AND date > CONVERT_TZ(:date, 'US/Central', @@global.time_zone) 
ORDER BY o.date ASC LIMIT 1;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":date", $date);
            $this->db->executeStatement();

            $uuid = $this->db->getResult();
        } else {
            $sql = "SELECT uuid FROM orders o 
WHERE status NOT IN (" . CART . ","  . COMPLETE . ")
ORDER BY o.date ASC LIMIT 1;";

            $this->db->beginStatement($sql);
            $this->db->executeStatement();

            $uuid = $this->db->getResult();
        }

        if(is_bool($uuid)) return array();
        return $this->getOrderByUUID($uuid["uuid"]);
    }

    public function getOrderStatus(string $orderUUID) : array {
        $sql = "SELECT bin_to_uuid(uuid, true) as uuid, status FROM orders 
WHERE uuid = :uuid";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $orderUUID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    public function getUserActiveOrderStatus(string $userUUID = NULL) : ?int {
        $sql = "SELECT x.status
FROM orders x
LEFT OUTER JOIN orders y
ON x.user_uuid = y.user_uuid
AND x.date < y.date
AND y.status NOT IN (" . CART . "," . COMPLETE . ")
WHERE x.user_uuid = :user_uuid
AND y.user_uuid IS NULL
AND x.status NOT IN (" . CART . "," . COMPLETE . ")
ORDER BY x.date desc;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        $status = $this->db->getResult();
        
        if(is_bool($status)) return NULL;
        return $status["status"];
    }

    /***
     * For verification that user owns the line item they are requesting to delete.
     */
    public function isLineItemInOrder(string $orderUUID, string $lineItemUUID) : bool {
        $sql = "SELECT 
IF(oli.order_uuid = :order_uuid,TRUE,FALSE) AS is_line_item_in_order 
FROM order_line_items oli 
WHERE oli.uuid = :uuid;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->bindValueToStatement(":uuid", $lineItemUUID);

        $this->db->executeStatement();

        $result = $this->db->getResult();
        return (bool)$result["is_line_item_in_order"];
    }

    public function getOrdersMatchingFilters(string $startDate = NULL, string $endDate = NULL, int $startAmount = NULL,
                                             int $endAmount = NULL, int $orderType = NULL, string $firstName = NULL,
                                             string $lastName = NULL, string $email = NULL, string $phoneNumber = NULL) : array {
        $sql = "SELECT o.uuid, o.user_uuid FROM orders o 
LEFT JOIN order_cost c
ON c.order_uuid = o.uuid 
LEFT JOIN users u
ON o.user_uuid = u.uuid
WHERE
(u.name_first   LIKE CONCAT('%', :name_first, '%')   OR :name_first   IS NULL) AND
(u.name_last    LIKE CONCAT('%', :name_last, '%')    OR :name_last    IS NULL) AND
(u.email        LIKE CONCAT('%', :email, '%')        OR :email        IS NULL) AND
(u.phone_number LIKE CONCAT('%', :phone_number, '%') OR :phone_number IS NULL) AND 
((o.date BETWEEN :start_date AND :end_date) OR (:start_date IS NULL AND :end_date IS NULL)) AND
(((c.fee + c.subtotal + c.tax) BETWEEN :start_amount AND :end_amount) OR (:start_amount IS NULL AND :end_amount IS NULL)) AND
(o.order_type = :order_type OR :order_type IS NULL) AND
o.status = " . COMPLETE . "
GROUP BY o.uuid
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

    public function getCost(string $orderUUID) : array {
        $sql = "SELECT subtotal, tax, fee FROM order_cost WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    public function getPayments(string $orderUUID) : array {
        $sql = "SELECT id, amount, method FROM order_payments WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(is_bool($result)) return array();
        return $result;
    }

    public function getPaymentByID(int $paymentID) : array {
        $sql = "SELECT order_uuid, amount, method FROM order_payments WHERE id = :id;";

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

    public function setPaypalToken(string $orderUUID, string $token) : void {
        $sql = "INSERT INTO paypal_tokens (order_uuid, paypal_token) VALUES (:order_uuid, :paypal_token);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->bindValueToStatement(":paypal_token", $token);

        $this->db->executeStatement();
    }

    public function getPaypalToken(string $orderUUID) : string {
        $sql = "SELECT * FROM paypal_tokens WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->executeStatement();

        return $this->db->getResult()["paypal_token"];
    }

    public function getStripeToken(string $orderUUID) : ?string {
        $sql = "SELECT * FROM stripe_tokens WHERE order_uuid = :order_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":order_uuid", $orderUUID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return NULL;

        return $result["stripe_token"];
    }

    /**
     * Updates the subtotal and the tax.
     */
    private function updateCost(string $cartUUID) : void {
        $sql = "UPDATE order_cost o SET 
o.subtotal = (SELECT SUM(price) FROM order_line_items WHERE order_uuid = :uuid),
o.tax = ROUND(o.subtotal * 0.07)
WHERE order_uuid = :uuid;"; // NOTE(Trystan): 0.07 is IOWA tax rate.
        
        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $cartUUID);
        $this->db->executeStatement();
    }

    public function isDeliveryOn() : bool {
        $sql = "SELECT status FROM toggle_options WHERE id = 'delivery';";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResult()["status"];

        if($result == 1) return true;
        return false;
    }

    public function isValidDeliveryTime(int $day) : bool {
        $sql = "SELECT CASE WHEN CURTIME() > ds.start_time AND CURTIME() < ds.end_time
THEN 1
ELSE 0
END result
FROM delivery_schedule ds 
WHERE ds.day = :day;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":day", $day);
        $this->db->executeStatement();

        $result = $this->db->getResult()["result"];

        if($result == 1) return true;
        return false;
    }

    public function isPickupOn() : bool {
        $sql = "SELECT status FROM toggle_options WHERE id = 'pickup';";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResult()["status"];

        if($result == 1) return true;
        return false;
    }

    public function isValidPickupTime(int $day) : bool {
        $sql = "SELECT CASE WHEN CURTIME() > ps.start_time AND CURTIME() < ps.end_time
THEN 1
ELSE 0
END result
FROM pickup_schedule ps 
WHERE ps.day = :day;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":day", $day);
        $this->db->executeStatement();

        $result = $this->db->getResult()["result"];

        if($result == 1) return true;
        return false;
    }    
}

?>
