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

    // TODO: Perhaps we don't want to set the message here.
    // Not every call wants to set a message, javascript calls.
    public function validateCSRFToken($token){
        $valid = hash_equals($_SESSION["CSRFToken"], $token);
        
        if(!$valid){
            $message = MESSAGE_INVALID_CSRF_TOKEN;
            $this->pushOneTimeMessage(USER_ALERT, $message);
        }
        
        return $valid;
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

    public function pushOneTimeMessage($type, $message){
        $_SESSION["one_time_messages"][$type][] = $message;
    }

    public function getOneTimeMessages() : ?Array {
        if(!isset($_SESSION["one_time_messages"])) return NULL;
        $messages =  $_SESSION["one_time_messages"];
        unset($_SESSION["one_time_messages"]);
        return $messages;
    }
}

?>
