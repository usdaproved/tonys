<?php

require_once APP_ROOT . "/models/Session.php";
require_once APP_ROOT . "/models/User.php";

class Controller{

    // Every controller, no matter the page will need to know
    // about a session and the user.
    protected $sessionManager;
    protected $userManager;

    public function __construct(){
        $this->userManager = new User();
        $this->sessionManager = new Session();
    }
    
    public function getFile($fileType, $file){
        if(strpos($file, ".php") !== false){
            $file = explode("/", $file);
            $file = end($file);
            $file = explode(".", $file)[0];
        }
        return "//" . $_SERVER["HTTP_HOST"] . "/" . $fileType . "/" . $file . ".css";
    }

    public function getUserID(){
        if($this->sessionManager->isUserLoggedIn()){
            return $this->sessionManager->getUserID();
        }
        
        return $this->userManager->getUserIDByUnregisteredSessionID();
    }

    // Returns the userID of the validated user, null otherwise.
    public function validateCredentials($post){
        $credentials = $this->userManager->getRegisteredCredentialsByEmail($post["email"]);

        if(password_verify($post["password"], $credentials["password"])){
            return $credentials["user_id"];
        }
        
        return NULL;
    }

    public function validateEmail($email){
        $email = filter_var($post["email"], FILTER_SANITIZE_EMAIL);

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}


?>
