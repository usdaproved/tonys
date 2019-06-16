<?php

class UserOrder implements JsonSerializable{

    private $orderID;
    private $itemIDs;
    private $totalPrice;
    private $status; // bit flag
    private $dateOrdered;

    function __get($name){
        return $this->$name;
    }

    function __set($name, $value){
        $this->$name = $value;
    }

    public function jsonSerialize(){
        return get_object_vars($this);
    }
    
}

?>
