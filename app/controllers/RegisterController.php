<?php

require_once APP_ROOT . "/controllers/Controller.php";

class RegisterController extends Controller{

    public $email;
    
    public function __construct(){
        parent::__construct();

        
    }

    public function get(){
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
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
            $this->redirect("/");
        }
        
        // At the end of registering a user, redirect to the "/" page.
        $post = filter_input_array(INPUT_POST);
        $post = array_map('trim', $post);
        $post = array_map('htmlspecialchars', $post);

        // TODO: Decide how to handle a bad CSRFToken.
        if($this->sessionManager->validateCSRFToken($post["CSRFToken"])){
            $errorMessage = "Operation could not complete due to invalid session.";
            $this->sessionManager->setOneTimeMessage($errorMessage);
            $this->redirect("/Register");
        }

        $errorMessage = $this->validateForm($post);
        if(!is_null($errorMessage)){
            $this->sessionManager->setOneTimeMessage($errorMessage);
            $this->redirect("/Register");
        }

        
        // This would be getting the userID associated with an unregistered user.
        $userID = $this->getUserID();
        if(is_null($userID)){
            $userID = $this->userManager->createRegisteredCredentials($post["email"], $post["password"]);
        } else {
            $this->userManager->createRegisteredCredentials($post["email"], $post["password"], $userID);
            $this->userManager->deleteUnregisteredCredentials($userID);
        }
        
        $this->sessionManager->login($userID);

        
        $this->redirect("/");
    }

    // TODO: Return error messages instead of handling it inside here.
    private function validateForm($post){
        $message = NULL;
        if(!$this->validateEmail($post["email"])){
            $message = "Please enter valid email.";
        }
        if(!is_null($this->userManager->getRegisteredCredentialsByEmail($post["email"]))){
            $message = "email already in use.";
        }
        if(empty($post["password"]) || strlen($post["password"]) < '8'){
            $message = "Password must be at least 8 characters.";
        }

        return $message;
    }
}
