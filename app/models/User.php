<?php

class User implements JsonSerializable{

    private $userID;
    private $orderIDs;
    
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
