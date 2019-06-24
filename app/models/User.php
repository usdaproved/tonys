<?php

require_once APP_ROOT . "/models/Model.php";

class User extends Model{

    // Creates a new user and returns it's id.
    public function createUser($post){
        $sql = "INSERT INTO users (session_id, name_first, name_last) 
VALUES (:session_id, :name_first, :name_last);";
        
        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":session_id", session_id());
        $this->db->bindValueToStatement(":name_first", $post["name_first"]);
        $this->db->bindValueToStatement(":name_last", $post["name_last"]);

        $this->db->executeStatement();

        return $this->db->lastInsertID();
    }

    public function getUserID(){
        $sql = "SELECT id FROM users WHERE session_id = :session_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":session_id", session_id());
        $this->db->executeStatement();

        // Will either return false or array containing id.
        $userID = $this->db->getResult();

        if(!is_bool($userID)) return $userID["id"];

        return false;
    }

    public function getUserWholeName($userID){
        $sql = "SELECT name_first, name_last FROM users WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();

        return $this->db->getResultSet()[0];
    }
    
}

?>
