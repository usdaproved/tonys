<?php

class Session{

    public function __construct(){
        if(!isset($_SESSION)){
            session_start();
        }
        if (empty($_SESSION["CSRFToken"])) {
            if (function_exists("random_bytes")) {
                $_SESSION["CSRFToken"] = bin2hex(random_bytes(32));
            } else if (function_exists("mcrypt_create_iv")) {
                $_SESSION["CSRFToken"] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
            } else {
                $_SESSION["CSRFToken"] = bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
    }

    public function validateCSRFToken($token){
        return !hash_equals($_SESSION["CSRFToken"], $token);
    }

    public function getCSRFToken(){
        return $_SESSION["CSRFToken"];
    }

    public function login($userID){
        $_SESSION["user_id"] = $userID;
    }

    public function logout(){
        unset($_SESSION["user_id"]);

        session_destroy();
    }

    public function isUserLoggedIn(){
        return isset($_SESSION["user_id"]);
    }

    public function getUserID(){
        return $_SESSION["user_id"];
    }
    

    /* This function may not be necessary.
    public function getSessionValue($key)
    {
        return $_SESSION[$key]) ?? null;
    }
    */

}

?>
