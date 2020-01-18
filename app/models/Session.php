<?php

class Session{

    public function __construct(){
        // TODO(Trystan): Look more into PHP Sessions.
        // We at some point want to set up "keep me logged in" type tokens.
        if (isset($_COOKIE['PHPSESSID'])){
            session_id($_COOKIE['PHPSESSID']);
        }

        session_start();

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
    public function validateCSRFToken(string $token) : bool {
        $valid = hash_equals($_SESSION["CSRFToken"], $token);
        
        if(!$valid){
            $message = MESSAGE_INVALID_CSRF_TOKEN;
            $this->pushOneTimeMessage(USER_ALERT, $message);
        }
        
        return $valid;
    }

    public function getCSRFToken() : string {
        return $_SESSION["CSRFToken"];
    }

    public function login($userID) : void {
        $_SESSION["user_id"] = $userID;
    }

    public function logout() : void {
        unset($_SESSION["user_id"]);

        session_destroy();
    }

    public function isUserLoggedIn() : bool {
        return isset($_SESSION["user_id"]);
    }

    public function getUserID() : int {
        return $_SESSION["user_id"];
    }

    public function pushOneTimeMessage(string $type, string $message) : void {
        $_SESSION["one_time_messages"][$type][] = $message;
    }

    public function getOneTimeMessages() : array {
        if(!isset($_SESSION["one_time_messages"])) return array();
        $messages =  $_SESSION["one_time_messages"];
        unset($_SESSION["one_time_messages"]);
        return $messages;
    }

    public function setRedirect() : void {
        if(isset($_GET[INTERNAL_REDIRECT]) && in_array($_GET[INTERNAL_REDIRECT], REDIRECT_ADDRESSES)){
            $_SESSION[INTERNAL_REDIRECT] = $_GET[INTERNAL_REDIRECT];
        }
    }

    public function getRedirect() : ?string {
        if(!isset($_SESSION[INTERNAL_REDIRECT])){
            return NULL;
        }
        
        $redirect = $_SESSION[INTERNAL_REDIRECT];
        unset($_SESSION[INTERNAL_REDIRECT]);
        return $redirect;
    }
}

?>
