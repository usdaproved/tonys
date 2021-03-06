<?php

// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

class HomeController extends Controller{
    private Order $orderManager;
    private RestaurantSettings $restaurantManger;
    
    public $user;
    
    public function __construct(){
        parent::__construct();
        
        $this->orderManager = new Order();
        $this->restaurantManager = new RestaurantSettings();
    }

    public function get() : void {
        $this->pageTitle = "Tony's | Taco House";
                
        $userUUID = $this->getUserUUID();
        $this->user = $this->userManager->getUserInfo($userUUID);

        $schedule = [];
        $schedule["delivery"] = $this->restaurantManager->getDeliverySchedule();
        $schedule["pickup"] = $this->restaurantManager->getPickupSchedule();
        $week = array_keys(DAY_TO_INT);

        require_once APP_ROOT . "/views/home/home-page.php";
    }

    public function register_get() : void {
        $this->pageTitle = "Register";

        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        $userUUID = $this->getUserUUID();

        $this->user = $this->userManager->getUserInfo($userUUID);
        $this->user["address"] = $this->userManager->getDefaultAddress($userUUID);

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
        if(empty($_POST["password"]) || !$this->validatePasswordRequirements($_POST['password'])){
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
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Address not found.");
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

            $this->userManager->setEmailVerificationToken($userUUID, $selectorBytes, $hashedTokenBytes);

            $message = $this->constructVerifyEmail($emailToken);
            $this->sendHTMLEmail($_POST["email"], "Tony's Taco House - Verify Email", $message);
        }

        $this->sessionManager->login($userUUID);

        if(!$validAddress){
            $this->redirect("/User/address");
        }
        $this->redirect($this->sessionManager->getRedirect() ?? "/");
    }

    public function login_get() : void {
        $this->pageTitle = "Login";
        
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        $userUUID = $this->getUserUUID();
        $this->sessionManager->setRedirect();
        $this->user = $this->userManager->getUserInfo($userUUID);
        
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

    public function logout_get() : void {
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
