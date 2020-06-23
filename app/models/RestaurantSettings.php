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
    
}
