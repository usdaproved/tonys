<?php

require_once APP_ROOT . "/controllers/Controller.php";

class RegisterController extends Controller{

    public $email;
    
    public function __construct(){
        parent::__construct();

        
    }

    public function get(){
        if($this->sessionManager->isUserLoggedIn()){
            header("Location: /");
            exit;
        }

        $this->email = NULL;
        $userID = $this->userManager->getUserIDByUnregisteredSessionID();
        if(!is_null($userID)){
            $this->email = $this->userManager->getUserByID($userID)["email"];
        }
        
        require_once APP_ROOT . "/views/register/register-page.php";
    }

    public function post(){
        if($this->sessionManager->isUserLoggedIn()){
            header("Location: /");
            exit;
        }
        
        // At the end of registering a user, redirect to the "/" page.
        $post = filter_input_array(INPUT_POST);
        $post = array_map('trim', $post);
        $post = array_map('htmlspecialchars', $post);

        // TODO: Decide how to handle a bad CSRFToken.
        if($this->sessionManager->validateCSRFToken($post["CSRFToken"])){
            echo "Operation could not complete due to invalid session.";
            exit;
        }

        $this->validateForm($post);
        
        // This would be getting the userID associated with an unregistered user.
        $userID = $this->getUserID();
        if(is_null($userID)){
            $userID = $this->userManager->createRegisteredCredentials($post["email"], $post["password"]);
        } else {
            $this->userManager->createRegisteredCredentials($post["email"], $post["password"], $userID);
            $this->userManager->deleteUnregisteredCredentials($userID);
        }
        
        $this->sessionManager->login($userID);

        
        header("Location: /");
        exit;
    }

    // TODO: Return error messages instead of handling it inside here.
    private function validateForm($post){
        if($this->validateEmail($post["email"])){
            echo "<h1>Please enter valid email.</h1>";
            exit;
        }
        if(!is_null($this->userManager->getRegisteredCredentialsByEmail($post["email"]))){
            echo "<h1>email already in use. Have you forgotten your password?</h1>";
            exit;
        }
        if(empty($post["password"]) || strlen($post["password"]) < '8'){
            echo "<h1>Password must be at least 8 characters.</h1>";
            exit;
        } 
    }
}
