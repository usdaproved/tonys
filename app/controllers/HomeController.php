<?php

// TODO(trystan): Something to think about.
// Get rid of login and logout and register controllers.
// Have them all reside within js calls with the home contoller.
// Or just fall under the home contoller to begin with.
// Or some over arching contoller that handles all things user auth and user view related.

class HomeController extends Controller{
    private $orderManager;
    
    public $user;
    public $isLoggedIn;
    // TODO: Update this with javascript.
    public $activeOrderStatus;
    
    public function __construct(){
        parent::__construct();
        
        $this->orderManager = new Order();
    }

    public function get() : void {
        $userID = $this->getUserID();
        $this->user = $this->userManager->getUserInfoByID($userID);

        $this->isLoggedIn = $this->sessionManager->isUserLoggedIn();

        $this->activeOrderStatus = $this->orderManager->getUserActiveOrderStatus($userID);

        require_once APP_ROOT . "/views/home/home-page.php";
    }

    // TODO(Trystan): Consider throwing these functions in a UserController.
    // That would also make a good place to view user info such as address and past orders.
    // Or maybe only those things belong there and logging in and out doesn't.
    // Can't decide.

    // TODO(Trystan): Set up a redirect path back to the submit page
    // if a redirect is set from the User/new page. ?redirect=submit
    public function register_get() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        $userID = $this->getUserID();

        $this->user = $this->userManager->getUserInfoByID($userID);
        
        require_once APP_ROOT . "/views/register/register-page.php";
    }

    public function register_post() : void {
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

    public function login_get() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }
        
        require_once APP_ROOT . "/views/login/login-page.php";
    }

    public function login_post() : void {
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

    public function logout_get() : void 
    {
        $this->sessionManager->logout();
        
        $this->redirect("/");
    }
}

?>
