<?php

class User extends Model{

    /**
     * At this point in the program we know absolutely nothing about the user.
     * However, we'd like to be able to associate their session with any future actions.
     */
    public function createUnregisteredCredentials() : int {
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

    /**
     * @userID defaults null value and creates a user with the email,
     * otherwise if passed value updates the current user.
     */
    public function createRegisteredCredentials(string $email, string $password, int $userID = NULL) : int {
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

        // Password hashing occurs here to ensure no plaintext passwords are stored.
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);


        $sql = "INSERT INTO registered_credentials (user_id, password)
VALUES (:user_id, :password);";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":password", $passwordHash);

        $this->db->executeStatement();

        return $userID;
    }

    // TODO(Trystan): Allow this to be updated more than once?
    public function setEmail(int $userID, string $email) : void {
        $sql = "UPDATE users SET email = :email WHERE id = :id";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();
    }
    
    public function setName(int $userID, string $nameFirst, string $nameLast) : void {
        $sql = "UPDATE users SET 
name_first = :name_first, name_last = :name_last
 WHERE id = :id";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":name_first", $nameFirst);
        $this->db->bindValueToStatement(":name_last", $nameLast);            
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();
    }

    public function setPhoneNumber(int $userID, string $phoneNumber) : void {
        $sql = "UPDATE users SET phone_number = :phone_number WHERE id = :id";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":phone_number", $phoneNumber);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();
    }

    public function addAddress(int $userID, string $line, string $city, string $state, string $zipCode) : int {
        $sql = "INSERT INTO address (user_id, line, city, state, zip_code)
 VALUES (:user_id, :line, :city, :state, :zip_code);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":line", $line);
        $this->db->bindValueToStatement(":city", $city);
        $this->db->bindValueToStatement(":state", $state);
        $this->db->bindValueToStatement(":zip_code", $zipCode);
        $this->db->executeStatement();

        return $this->db->lastInsertID();
    }

    public function setDefaultAddress(int $userID, int $addressID) : void {
        $sql = "UPDATE users SET default_address = :default_address WHERE id = :id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $userID);
        $this->db->bindValueToStatement(":default_address", $addressID);

        $this->db->executeStatement();
    }

    public function addEmployeeByEmail(string $email) : bool {
        $sql = "UPDATE users SET user_type = " . EMPLOYEE . " WHERE email = :email;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->executeStatement();

        return ((int)$this->db->rowCount() !== 0);
    }

    public function toggleEmployeeAdminStatus(int $userID) : void {
        $sql = "UPDATE `users` SET `user_type` = CASE
    WHEN user_type = " . EMPLOYEE . " THEN " . ADMIN . " 
    WHEN user_type = " . ADMIN . " THEN " . EMPLOYEE . " 
    END
    WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();
    }

    public function removeEmployee(int $userID) : void {
        $sql = "UPDATE users SET user_type = " . CUSTOMER . " WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();
    }
    
    public function deleteUnregisteredCredentials(int $userID) : void {
        $sql = "DELETE FROM unregistered_credentials WHERE user_id = :user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();
    }

    public function deleteUser(int $userID) : void {
        $sql = "DELETE FROM users WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();
    }

    /**
     * return null if no user_id associated with unreg session yet.
     */
    public function getUserIDByUnregisteredSessionID() : ?int {
        $sql = "SELECT user_id FROM unregistered_credentials
WHERE session_id = :session_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":session_id", session_id());
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return NULL;
        return $result["user_id"];
    }

    public function getUserInfoByID(int $userID = NULL) : array {
        $sql = "SELECT * FROM users WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();

        $userInfo = $this->db->getResult();
        $userInfo["address"] = $this->getDefaultAddress($userID);

        if(is_bool($userInfo)) return array();
        return $userInfo;
    }

    public function getUsersMatchingFilters(string $firstName = NULL, string $lastName = NULL,
                                            string $email = NULL, string $phoneNumber = NULL) : array {
        $sql = "SELECT * FROM users WHERE 
(name_first   LIKE CONCAT('%', :name_first, '%')   OR :name_first   IS NULL) AND
(name_last    LIKE CONCAT('%', :name_last, '%')    OR :name_last    IS NULL) AND
(email        LIKE CONCAT('%', :email, '%')        OR :email        IS NULL) AND
(phone_number LIKE CONCAT('%', :phone_number, '%') OR :phone_number IS NULL);";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":name_first", $firstName);
        $this->db->bindValueToStatement(":name_last", $lastName);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->bindValueToStatement(":phone_number", $phoneNumber);
        
        $this->db->executeStatement();

        $users = $this->db->getResultSet();

        if(is_bool($users)) return array();
        return $users;
    }

    public function getDefaultAddress(int $userID = NULL) : array {
        $sql = "SELECT a.id, line, city, state, zip_code 
FROM address a
LEFT JOIN users u
ON a.user_id = u.id
WHERE a.user_id = :user_id
AND u.default_address = a.id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return array();
        return $result;
    }

    // This isn't all addresses because any time we grab addresses we already know the default one.
    // What we really want is to know the others, we could grab all at once
    // but then we'd just filter out the default one. Best to just grab exactly what we want.
    public function getNonDefaultAddresses(int $userID = NULL) : array {
        $sql = "SELECT a.id, line, city, state, zip_code FROM address a
LEFT JOIN users u
ON a.user_id = u.id
WHERE a.user_id = :user_id
AND u.default_address != a.id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();

        if(is_bool($result)) return array();
        return $result;
    }
    
    public function getAddressID(int $userID) : int {
        $sql = "SELECT id FROM address WHERE user_id = :user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        return $this->db->getResult()["id"];
    }

    public function getRegisteredCredentialsByEmail(string $email) : array {
        $sql = "SELECT * FROM registered_credentials
WHERE user_id = (SELECT id FROM users WHERE email = :email);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return array();
        return $result;
    }

    public function getUserIDByEmail(string $email = NULL) : ?int {
        $sql = "SELECT id FROM users WHERE email = :email;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return NULL;
        return $result["id"];
    }

    public function getUnregisteredInfoLevel(int $userID) : int {
        $sql = "SELECT info_level FROM unregistered_credentials
WHERE user_id = :user_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        return (int)$result["info_level"];
    }

    public function setUnregisteredInfoLevel(int $userID, int $infoLevel) : void {
        $sql = "UPDATE unregistered_credentials
SET info_level = :info_level WHERE user_id = :user_id;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":user_id", $userID);
        $this->db->bindValueToStatement(":info_level", $infoLevel);

        $this->db->executeStatement();
    }

    public function getUserAuthorityLevelByID(int $userID = NULL) : int {
        $sql = "SELECT user_type FROM users WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $userID);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        // If no user is found, they have the lowest level auth.
        if(is_bool($result)) return CUSTOMER;
        return $result["user_type"];
    }

    public function getAllEmployees() : array {
        $sql = "SELECT * FROM users WHERE user_type > 0;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $employees = $this->db->getResultSet();
        
        if(is_bool($employees)) return array();
        return $employees;
    }

}

?>
