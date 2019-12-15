<?php

class LoginController extends Controller{

    private $orderManager;

    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
    }

    public function get() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }
        
        require_once APP_ROOT . "/views/login/login-page.php";
    }

    public function post() : void {
        if($this->sessionManager->isUserLoggedIn()){
            echo "ALREADY LOGGED IN";
            exit;
            $this->redirect("/");
        }

        // TODO: Decide how to handle a bad CSRFToken.
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/Login");
        }

        $userID = $this->validateCredentials($_POST["email"], $_POST["password"]);
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
        
        $this->sessionManager->pushOneTimeMessage(USER_ALERT, MESSAGE_INVALID_LOGIN);
        $this->redirect("/Login");
    }
}
?>
