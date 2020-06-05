<?php

// TODO(trystan): Something to think about.
// Get rid of login and logout and register controllers.
// Have them all reside within js calls with the home contoller.
// Or just fall under the home contoller to begin with.
// Or some over arching contoller that handles all things user auth and user view related.

class HomeController extends Controller{
    private Order $orderManager;
    
    public $user;
    // TODO: Update this with javascript.
    public $activeOrderStatus;
    
    public function __construct(){
        parent::__construct();
        
        $this->orderManager = new Order();
    }

    public function get() : void {
        $userUUID = $this->getUserUUID();
        $this->user = $this->userManager->getUserInfo($userUUID);

        $this->user["logged_in"] = $this->sessionManager->isUserLoggedIn();

        $this->activeOrderStatus = $this->orderManager->getUserActiveOrderStatus($userUUID);

        require_once APP_ROOT . "/views/home/home-page.php";
    }

    public function register_get() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        $userUUID = $this->getUserUUID();

        $this->user = $this->userManager->getUserInfo($userUUID);

        $this->sessionManager->setRedirect();
        
        require_once APP_ROOT . "/views/register/register-page.php";
    }

    public function register_post() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        // TODO: Decide how to handle a bad CSRFToken.
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $redirect = $this->sessionManager->getRedirect();
            if(!is_null($redirect)){
                $this->redirect("/register?redirect=" . $redirect);
            }
            $this->redirect("/register");
        }
        
        $validPassword = true;
        if(empty($_POST["password"]) || strlen($_POST["password"]) < '8'){
            $message = "Password must be at least 8 characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $validPassword = false;
        }

        if(!$this->validateNewUserInformation() || !$this->validateAddress() || !$validPassword){
            $redirect = $this->sessionManager->getRedirect();
            if(!is_null($redirect)){
                $this->redirect("/register?redirect=" . $redirect);
            }
            $this->redirect("/register");
        }

        $USPS = $this->validateAddressUSPS();
        if(empty($USPS)){
            $this->pushOneTimeMessage(USER_ALERT, "Address not found.");
            $redirect = $this->sessionManager->getRedirect();
            if(!is_null($redirect)){
                $this->redirect("/register?redirect=" . $redirect);
            }
            $this->redirect("/register");
        }

        
        $userUUID = $this->getUserUUID();
        // If userUUID is already set, we just get the same value back. Otherwise we get new userUUID
        $userUUID = $this->userManager->createRegisteredCredentials($_POST["email"], $_POST["password"], $userUUID);
        // Remove the unregistered credentials if there were any.
        $this->userManager->deleteUnregisteredCredentials($userUUID);
        
        $this->userManager->setName($userUUID, $_POST["name_first"], $_POST["name_last"]);
        $this->userManager->setPhoneNumber($userUUID, $_POST["phone"]);
        $addressUUID = $this->userManager->addAddress($userUUID, $USPS["address_line"], $USPS["city"],
                                                      $USPS["state"], $USPS["zip_code"]);

        $validAddress = true;
        if($this->isAddressDeliverable($USPS)){
            $this->userManager->setDefaultAddress($userUUID, $addressUUID);
            $this->userManager->setAddressDeliverable($addressUUID);
        } else {
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Delivery to this address is currently unavailable.");
            $validAddress = false;
        }

        // If there is another session that used this email prior to registering we want
        // to begin the process of bringing over that data to this user.
        // We will require them to verify that email however.
        $unregisteredUsers = $this->userManager->getAllUnregisteredUserUUIDsWithEmail($_POST["email"]);
        if(!empty($unregisteredUsers)){
            $this->userManager->setVerificationRequired($userUUID);
            
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Please verify email to link account history.");
            // create a token exactly like the remember me token. With a much shorter expiration length.
            $token = bin2hex(random_bytes(32));
            $hashedTokenBytes = hash("sha256", $token, true);
            $selectorBytes = UUID::generateOrderedBytes();
            $selector = UUID::orderedBytesToArrangedString($selectorBytes);
            $selector = str_replace("-", "", $selector);

            $emailToken = $selector . "-" . $token;

            $expires = time() + (60*60); // expires in one hour.
            $this->userManager->setEmailVerificationToken($userUUID, $selectorBytes, $hashedTokenBytes);

            // TODO(Trystan): send email containing token link
        }

        $this->sessionManager->login($userUUID);

        if(!$validAddress){
            $this->redirect("/User/address");
        }
        $this->redirect($this->sessionManager->getRedirect() ?? "/");
    }

    public function login_get() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        $this->sessionManager->setRedirect();
        
        require_once APP_ROOT . "/views/login/login-page.php";
    }

    public function login_post() : void {
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $redirect = $this->sessionManager->getRedirect();
            if(!is_null($redirect)){
                $this->redirect("/login?redirect=" . $redirect);
            }
            $this->redirect("/login");
        }

        $userUUID = $this->validateCredentials($_POST["email"], $_POST["password"]);
        
        if(is_null($userUUID)){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, MESSAGE_INVALID_LOGIN);
            $redirect = $this->sessionManager->getRedirect();
            if(!is_null($redirect)){
                $this->redirect("/login?redirect=" . $redirect);
            }
            $this->redirect("/login");
        }
        // TODO(Trystan): Check if verification required.

        $this->sessionManager->login($userUUID);

        if(isset($_POST["remember_me"])){
            $userAuthorityLevel = $this->userManager->getUserAuthorityLevel($userUUID);
            // Only allow customers to be remembered. Don't want the risk of always logged in employees.
            if($userAuthorityLevel === CUSTOMER){
                $this->initializeRememberMe($userUUID);
            }
        }

        $this->redirect($this->sessionManager->getRedirect() ?? "/");
    }

    public function logout_get() : void 
    {
        if(!$this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }
        if(isset($_COOKIE["remember_me"])){
            $selector = explode(':', $_COOKIE["remember_me"])[0];
            $selectorBytes = UUID::arrangedStringToOrderedBytes($selector);
            $this->userManager->deleteRememberMeToken($this->getUserUUID(), $selectorBytes);
            setcookie("remember_me", "", time() - 3600);
        }
        $this->sessionManager->logout();
        
        $this->redirect("/");
    }
}

?>
