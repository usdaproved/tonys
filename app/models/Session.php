<?php

// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

class Session{

    public function __construct(){
        if (isset($_COOKIE['session_id'])){
            session_id($_COOKIE['session_id']);
        }

        session_name("session_id");
        session_start([
            "cookie_samesite" => "strict"
        ]);

        // If the session hasn't been seen for 3 hours. Generate a new one.
        if(isset($_SESSION["last_seen"]) && (time() - $_SESSION["last_seen"] > 10800)){
            $_SESSION = array();
            session_regenerate_id(true);
        }
            
        $_SESSION["last_seen"] = time();

        if (empty($_SESSION["CSRFToken"])) {
            $_SESSION["CSRFToken"] = bin2hex(random_bytes(32));
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

    public function login(string $userUUID) : void {
        $_SESSION["user_uuid"] = $userUUID;
    }

    public function logout() : void {
        $_SESSION = array();
        setcookie(session_name(), '', time() - 3600);
        session_destroy();
    }

    public function isUserLoggedIn() : bool {
        return isset($_SESSION["user_uuid"]);
    }

    public function getUserUUID() : string {
        return $_SESSION["user_uuid"];
    }

    
    public function setReauthRequired(bool $value) : void {
        $_SESSION["reauth_required"] = $value;
    }
    
    /**
     * If the user logged in using a remember me cookie, make them reauth before allowing
     * any changes to sensitive info.
     */
    public function isReauthRequired() : bool {
        return $_SESSION["reauth_required"];
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
