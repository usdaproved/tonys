<?php

class User extends Model{

    /**
     * At this point in the program we know absolutely nothing about the user.
     * However, we'd like to be able to associate their session with any future actions.
     */
    public function createUnregisteredCredentials() : string {
        // Create an empty user to associate with this session.
        $userUUID = $this->db->generateUUID();
        
        $sql = "INSERT INTO users (uuid) VALUES (:uuid);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();

        $sql = "INSERT INTO unregistered_credentials (user_uuid, session_id) 
VALUES (:user_uuid, :session_id);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->bindValueToStatement(":session_id", session_id());
        $this->db->executeStatement();

        return $userUUID;
    }

    /**
     * @userUUID defaults null value and creates a user with the email,
     * otherwise if passed value updates the current user.
     */
    public function createRegisteredCredentials(string $email, string $password, string $userUUID = NULL) : string {
        if(is_null($userUUID)){
            $userUUID = $this->db->generateUUID();

            $sql = "INSERT INTO users (uuid) VALUES (:uuid);";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":uuid", $userUUID);
            $this->db->executeStatement();
        }
        
        $this->setEmail($userUUID, $email);

        // Password hashing occurs here to ensure no plaintext passwords are stored.
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);


        $sql = "INSERT INTO registered_credentials (user_uuid, password_hash)
VALUES (:user_uuid, :password_hash);";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->bindValueToStatement(":password_hash", $passwordHash);

        $this->db->executeStatement();

        return $userUUID;
    }

    public function setVerificationRequired(string $userUUID) : void {
        $sql = "UPDATE registered_credentials SET verification_required = 1 WHERE user_uuid = :user_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();
    }

    public function isVerificationRequired(string $userUUID) : bool {
        $sql = "SELECT verification_required FROM registered_credentials WHERE user_uuid = :user_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return false;
        if($result["verification_required"] == 0) return false;
        return true;
    }

    public function setEmailVerificationToken(string $userUUID, string $selector, string $hash){
        $sql = "INSERT INTO email_verification_tokens (user_uuid, selector, hashed_token, session_id, expires) 
VALUES (:user_uuid, :selector, :hashed_token, :session_id, DATE_ADD(NOW(), INTERVAL 1 HOUR));";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->bindValueToStatement(":selector", $selector);
        $this->db->bindValueToStatement(":hashed_token", $hash);
        $this->db->bindValueToStatement(":session_id", session_id());
        
        $this->db->executeStatement();
    }

    public function deleteEmailVerificationToken(string $userUUID) : void {
        // There will only ever be one verification email active per user.
        $sql = "DELETE FROM email_verification_tokens WHERE user_uuid = :user_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();
    }

    public function getEmailVerificationInfo(string $selectorBytes) : array {
        $sql = "SELECT user_uuid, hashed_token, session_id, expires FROM email_verification_tokens
WHERE selector = :selector;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":selector", $selectorBytes);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return array();
        return $result;
    }

    public function setRememberMeToken(string $userUUID, string $selectorBytes, string $hashedTokenBytes) : void {
        $sql = "INSERT INTO remember_me_tokens (user_uuid, selector, hashed_token, expires) 
VALUES (:user_uuid, :selector, :hashed_token, DATE_ADD(NOW(), INTERVAL 1 MONTH));";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->bindValueToStatement(":selector", $selectorBytes);
        $this->db->bindValueToStatement(":hashed_token", $hashedTokenBytes);
        
        $this->db->executeStatement();
    }

    public function deleteRememberMeToken(string $userUUID, string $selectorBytes) : void {
        $sql = "DELETE FROM remember_me_tokens WHERE user_uuid = :user_uuid AND selector = :selector;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":selector", $selectorBytes);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        
        $this->db->executeStatement();
    }


    public function getRememberMeInfo(string $selectorBytes) : array {
        $sql = "SELECT user_uuid, hashed_token, expires FROM remember_me_tokens
WHERE selector = :selector;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":selector", $selectorBytes);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return array();
        return $result;
    }

    // TODO(Trystan): Allow this to be updated more than once?
    public function setEmail(string $userUUID, string $email) : void {
        $sql = "UPDATE users SET email = :email WHERE uuid = :uuid";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();
    }
    
    public function setName(string $userUUID, string $nameFirst, string $nameLast) : void {
        $sql = "UPDATE users SET 
name_first = :name_first, name_last = :name_last
 WHERE uuid = :uuid";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":name_first", $nameFirst);
        $this->db->bindValueToStatement(":name_last", $nameLast);            
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();
    }

    public function setPhoneNumber(string $userUUID, string $phoneNumber) : void {
        $sql = "UPDATE users SET phone_number = :phone_number WHERE uuid = :uuid";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":phone_number", $phoneNumber);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();
    }

    public function addAddress(string $userUUID, string $line, string $city, string $state, string $zipCode) : string {
        $addressUUID = $this->db->generateUUID();
        
        $sql = "INSERT INTO address (uuid, user_uuid, line, city, state, zip_code)
 VALUES (:uuid, :user_uuid, :line, :city, :state, :zip_code);";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":uuid", $addressUUID);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->bindValueToStatement(":line", $line);
        $this->db->bindValueToStatement(":city", $city);
        $this->db->bindValueToStatement(":state", $state);
        $this->db->bindValueToStatement(":zip_code", $zipCode);
        
        $this->db->executeStatement();

        return $addressUUID;
    }

    public function setAddressDeliverable(string $addressUUID) : void {
        $sql = "UPDATE address SET deliverable = 1 WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $addressUUID);
        $this->db->executeStatement();
    }

    public function setDefaultAddress(string $userUUID, string $addressUUID) : void {
        $sql = "UPDATE users SET default_address = :default_address WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->bindValueToStatement(":default_address", $addressUUID);

        $this->db->executeStatement();
    }

    /**
     * We can't actually delete addresses since they are tied to address delivery information.
     * But we can "sever" the connection to the user by removing the user_uuid.
     */
    public function deleteAddress(string $addressUUID) : void {
        $sql = "UPDATE address SET user_uuid = NULL WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $addressUUID);
        $this->db->executeStatement();
    }

    /**
     * See my note above Order::updateOrdersFromUnregisteredToRegistered
     * for explanation of why this exists.
     */
    public function updateAddressesFromUnregisteredToRegistered(string $unregisteredUserUUID, string $registeredUserUUID) : void {
        $sql = "UPDATE address SET user_uuid = :registered_user_uuid
WHERE user_uuid = :unregistered_user_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":registered_user_uuid", $registeredUserUUID);
        $this->db->bindValueToStatement(":unregistered_user_uuid", $unregisteredUserUUID);
        $this->db->executeStatement();
    }

    public function setUserAsEmployee(string $userUUID) : void {
        $sql = "UPDATE users SET user_type = " . EMPLOYEE . " WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();
    }

    public function toggleEmployeeAdminStatus(string $userUUID) : void {
        $sql = "UPDATE `users` SET `user_type` = CASE
    WHEN user_type = " . EMPLOYEE . " THEN " . ADMIN . " 
    WHEN user_type = " . ADMIN . " THEN " . EMPLOYEE . " 
    END
    WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();
    }

    public function removeEmployee(string $userUUID) : void {
        $sql = "UPDATE users SET user_type = " . CUSTOMER . " WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();
    }
    
    public function deleteUnregisteredCredentials(string $userUUID) : void {
        $sql = "DELETE FROM unregistered_credentials WHERE user_uuid = :user_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();
    }

    public function deleteUser(string $userUUID) : void {
        $sql = "DELETE FROM users WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();
    }

    /**
     * return null if no user_uuid associated with unreg session yet.
     */
    public function getUserUUIDByUnregisteredSessionID() : ?string {
        $sql = "SELECT user_uuid FROM unregistered_credentials
WHERE session_id = :session_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":session_id", session_id());
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return NULL;
        return $result["user_uuid"];    }

    public function getUserInfo(string $userUUID = NULL) : array {
        $sql = "SELECT name_first, name_last, email, phone_number FROM users WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $userUUID);
        $this->db->executeStatement();

        $userInfo = $this->db->getResult();

        if(is_bool($userInfo)) return array();
        return $userInfo;
    }

    // TODO(Trystan): One more filter, or a new version of this function: Registered users only.
    public function getUsersMatchingFilters(string $firstName = NULL, string $lastName = NULL,
                                            string $email = NULL, string $phoneNumber = NULL, bool $registered = false) : array {

        $sql = "";
        if($registered){
            $sql = "SELECT bin_to_uuid(u.uuid, true) as uuid, u.name_first, u.name_last, u.email, u.phone_number FROM users u 
RIGHT JOIN registered_credentials ru 
ON ru.user_uuid = u.uuid WHERE
(u.name_first   LIKE CONCAT('%', :name_first, '%')   OR :name_first   IS NULL) AND
(u.name_last    LIKE CONCAT('%', :name_last, '%')    OR :name_last    IS NULL) AND
(u.email        LIKE CONCAT('%', :email, '%')        OR :email        IS NULL) AND
(u.phone_number LIKE CONCAT('%', :phone_number, '%') OR :phone_number IS NULL);";
        } else {
            $sql = "SELECT bin_to_uuid(uuid, true) as uuid, name_first, name_last, email, phone_number FROM users WHERE 
(name_first   LIKE CONCAT('%', :name_first, '%')   OR :name_first   IS NULL) AND
(name_last    LIKE CONCAT('%', :name_last, '%')    OR :name_last    IS NULL) AND
(email        LIKE CONCAT('%', :email, '%')        OR :email        IS NULL) AND
(phone_number LIKE CONCAT('%', :phone_number, '%') OR :phone_number IS NULL);";
        }

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

    public function getDefaultAddress(string $userUUID = NULL) : array {
        $sql = "SELECT a.uuid, line, city, state, zip_code, deliverable 
FROM address a
LEFT JOIN users u
ON a.user_uuid = u.uuid
WHERE a.user_uuid = :user_uuid
AND u.default_address = a.uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return array();
        return $result;
    }

    // This isn't all addresses because any time we grab addresses we already know the default one.
    // What we really want is to know the others, we could grab all at once
    // but then we'd just filter out the default one. Best to just grab exactly what we want.
    public function getNonDefaultAddresses(string $userUUID = NULL) : array {
        $sql = "SELECT a.uuid, line, city, state, zip_code, deliverable FROM address a
LEFT JOIN users u
ON a.user_uuid = u.uuid
WHERE a.user_uuid = :user_uuid
AND (u.default_address != a.uuid OR u.default_address IS NULL);";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();

        if(is_bool($result)) return array();
        return $result;
    }
    
    public function getRegisteredCredentialsByEmail(string $email) : array {
        $sql = "SELECT ru.user_uuid, ru.password_hash FROM registered_credentials ru
LEFT JOIN users u
ON ru.user_uuid = u.uuid
WHERE u.email = :email;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return array();
        return $result;
    }

    /**
     * This is for consolidation when a user has previously used their email
     * during a now expired session, and they are registering using that email.
     */
    public function getAllUnregisteredUserUUIDsWithEmail(string $email) : array {
        $sql = "SELECT uu.user_uuid FROM unregistered_credentials uu
LEFT JOIN users u
ON uu.user_uuid = u.uuid
WHERE u.email = :email;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();

        if(is_bool($result)) return array();
        return $result;
    }

    public function getUserUUIDByEmail(string $email = NULL) : ?string {
        $sql = "SELECT uuid FROM users WHERE email = :email;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":email", $email);
        $this->db->executeStatement();

        $result = $this->db->getResult();

        if(is_bool($result)) return NULL;
        return $result["uuid"];
    }

    public function getUnregisteredInfoLevel(string $userUUID) : int {
        $sql = "SELECT info_level FROM unregistered_credentials
WHERE user_uuid = :user_uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        return (int)$result["info_level"];
    }

    public function setUnregisteredInfoLevel(string $userUUID, int $infoLevel) : void {
        $sql = "UPDATE unregistered_credentials
SET info_level = :info_level WHERE user_uuid = :user_uuid;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":user_uuid", $userUUID);
        $this->db->bindValueToStatement(":info_level", $infoLevel);

        $this->db->executeStatement();
    }

    public function getUserAuthorityLevel(string $userUUID = NULL) : int {
        $sql = "SELECT user_type FROM users WHERE uuid = :uuid;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":uuid", $userUUID);
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
