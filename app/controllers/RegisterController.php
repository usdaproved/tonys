<?php

class RegisterController extends Controller{

    public $user;
    
    public function __construct(){
        parent::__construct();

        
    }

    public function get() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        $userID = $this->getUserID();

        $this->user = $this->userManager->getUserInfoByID($userID);
        
        require_once APP_ROOT . "/views/register/register-page.php";
    }

    public function post() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }
        
        // TODO: Decide how to handle a bad CSRFToken.
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/Register");
        }
        
        $validPassword = true;
        if(empty($_POST["password"]) || strlen($_POST["password"]) < '8'){
            $message = "Password must be at least 8 characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $validPassword = false;
        }

        if(!$this->validateNewUserInformation() || !$validPassword){
            $this->redirect("/Register");
        }

        
        $userID = $this->getUserID();
        if(is_null($userID)){
            $userID = $this->userManager->createRegisteredCredentials($_POST["email"], $_POST["password"]);
        } else {
            $this->userManager->createRegisteredCredentials($_POST["email"], $_POST["password"], $userID);
            $this->userManager->deleteUnregisteredCredentials($userID);
        }

        $this->userManager->setName($userID, $_POST["name_first"], $_POST["name_last"]);
        $this->userManager->setPhoneNumber($userID, $_POST["phone"]);
        $this->userManager->setAddress($userID,
                                       $_POST["address_line"],
                                       $_POST["city"],
                                       $_POST["state"],
                                       $_POST["zip_code"]
        );
        $this->sessionManager->login($userID);

        
        $this->redirect("/");
    }
}
