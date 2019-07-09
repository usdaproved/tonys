<?php

require_once APP_ROOT . "/models/Model.php";

class User extends Model{

    // At this point in the program we know absolutely nothing about the user.
    // However, we'd like to be able to associate their session with any future actions.
    public function createUnregisteredCredentials(){
        // Create an empty user to associate with this session.
        $sql = "INSERT INTO users () VALUES ();";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $userID = $this->db->lastInsertID();
        
        $sql = "INSERT INTO unregistered_credentials (user_id, session_id) 
VALUES (:user_id, :session_id);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":session_id", session_id());
        $this->db->executeStatement();

        return $userID;
    }

    // Accepts a null value and creates a user with the email,
    // otherwise updates the current user.
    public function createRegisteredCredentials($email, $password, $userID = NULL){
        if(is_null($userID)){
            $sql = "INSERT INTO users (email) VALUES (:email);";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":email", $email);
            $this->db->executeStatement();

            $userID = $this->db->lastInsertID();
        } else {
            $sql = "UPDATE users SET email = :email WHERE id = :id";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":email", $email);
            $this->db->bindValueToStatement(":id", $userID);
            $this->db->executeStatement();
        }

        // Password hasing occurs here to ensure no plaintext passwords are stored.
        // TODO: Think about switching to Argon2id.
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));


        $sql = "INSERT INTO registered_credentials (user_id, password)
VALUES (:user_id, :password);";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":password", $passwordHash);

        $this->db->executeStatement();

        return $userID;
    }

    public function deleteUnregisteredCredentials($userID){
        $sql = "DELETE FROM unregistered_credentials WHERE user_id = :user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();
    }

    public function deleteUser($userID){
        $sql = "DELETE FROM users WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();
    }

    // returns null if no user_id associated with unreg session yet.
    public function getUserIDByUnregisteredSessionID(){
        $sql = "SELECT user_id FROM unregistered_credentials
WHERE session_id = :session_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":session_id", session_id());
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return NULL;
        return $result["user_id"];
    }

    public function getUserByID($userID){
        $sql = "SELECT * FROM users WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    public function getRegisteredCredentialsByEmail($email){
        $sql = "SELECT * FROM registered_credentials
WHERE user_id = (SELECT id FROM users WHERE email = :email);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return NULL;
        return $result;
    }

}

?>
