<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Order.php";

class LoginController extends Controller{

    private $orderManager;

    public $message;

    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
    }

    public function get(){
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }
        
        require_once APP_ROOT . "/views/login/login-page.php";
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
            $this->redirect("/Login");
        }

        $userID = $this->validateCredentials($post);
        if(!is_null($userID)){
            $this->sessionManager->login($userID);

            $unregisteredUserID = $this->userManager->getUserIDByUnregisteredSessionID();
            if(!is_null($unregisteredUserID) && $unregisteredUserID !== $userID){
                $this->orderManager->updateOrdersFromUnregisteredToRegistered($unregisteredUserID, $userID);
                $this->userManager->deleteUnregisteredCredentials($unregisteredUserID);
                $this->userManager->deleteUser($unregisteredUserID);
            }

            $this->redirect("/");
        }
        
        $message = "Invalid login credentials.";
        $this->sessionManager->setOneTimeMessage($message);
        $this->redirect("/Login");
    }
}
?>
