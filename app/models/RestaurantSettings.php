<?php

// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

class RestaurantSettings extends Model{

    public function switchDelivery(int $status) : void {
        $sql = "UPDATE toggle_options SET status = :status WHERE id = 'delivery';";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":status", $status);
        $this->db->executeStatement();
    }

    public function switchPickup(int $status) : void {
        $sql = "UPDATE toggle_options SET status = :status WHERE id = 'pickup';";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":status", $status);
        $this->db->executeStatement();
    }

    public function updateDeliverySchedule(int $day, string $startTime, string $endTime){
        $sql = "UPDATE delivery_schedule 
SET start_time = :start_time, end_time = :end_time
WHERE day = :day;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":day", $day);
        $this->db->bindValueToStatement(":start_time", $startTime);
        $this->db->bindValueToStatement(":end_time", $endTime);
        
        $this->db->executeStatement();
    }

    public function updatePickupSchedule(int $day, string $startTime, string $endTime){
        $sql = "UPDATE pickup_schedule 
SET start_time = :start_time, end_time = :end_time
WHERE day = :day;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":day", $day);
        $this->db->bindValueToStatement(":start_time", $startTime);
        $this->db->bindValueToStatement(":end_time", $endTime);
        
        $this->db->executeStatement();
    }

    public function getDeliverySchedule() : array {
        $sql = "SELECT * FROM delivery_schedule;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }

    public function getPickupSchedule() : array {
        $sql = "SELECT * FROM pickup_schedule;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }

    public function addOrderPrinter(string $selector, string $name, string $hashedBytes) : void {
        $sql = "INSERT INTO order_printers (selector, name, hashed_bytes) 
VALUES (:selector, :name, :hashed_bytes);";

        $this->db->beginStatement($sql);

        
        $this->db->bindValueToStatement(":selector", $selector);
        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":hashed_bytes", $hashedBytes);
        
        $this->db->executeStatement();
    }

    public function removeOrderPrinter(string $selector) : void {
        $sql = "DELETE FROM order_printers WHERE selector = :selector;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":selector", $selector);
        $this->db->executeStatement();
    }

    public function getPrinterInfo(string $selector) : array {
        $sql = "SELECT * FROM order_printers WHERE selector = :selector;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":selector", $selector);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return array();
        return $result;
    }

    public function setPrinterConnection(string $selector, bool $connected) : void {
        $sql = "UPDATE order_printers SET connected = :connected WHERE selector = :selector;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":connected", $connected);
        $this->db->bindValueToStatement(":selector", $selector);

        $this->db->executeStatement();
    }

    public function getAllPrinters() : array {
        $sql = "SELECT selector, name, connected FROM order_printers;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(is_bool($result)) return array();
        return $result;
    }
}
