<?php

// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

class UserController extends Controller{

    private Order $orderManager;

    public array $user;
    public array $orderStorage;

    public bool $getAddress = false;

    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
    }

    public function new_get() : void {
        $this->pageTitle = "Tony's - New User";
        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/Order/submit");
        }

        $userUUID = $this->getUserUUID();
        $this->user = $this->userManager->getUserInfo($userUUID);
        $this->user["address"] = $this->userManager->getDefaultAddress($userUUID);

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        $order = $this->orderManager->getOrderByUUID($cartUUID);

        if(!isset($order["order_type"]) || $order["order_type"] == DELIVERY){
            $this->getAddress = true;
        }

        require_once APP_ROOT . "/views/user/user-new-page.php";
    }

    public function new_post() : void {
        // Checking out as guest.
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/User/new");
        }

        // Check to see if we should validate address.
        // If no cart is found then the default behavior is to collect the address.
        $userUUID = $this->getUserUUID();
        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        $order = $this->orderManager->getOrderByUUID($cartUUID);

        $collectAddress = !isset($order["order_type"]) || $order["order_type"] == DELIVERY;
        
        if(!$this->validateNewUserInformation() || ($collectAddress && !$this->validateAddress())){
            $this->redirect("/User/new");
        }
        
        $this->userManager->setEmail($userUUID, $_POST["email"]);
        $this->userManager->setName($userUUID, $_POST["name_first"], $_POST["name_last"]);
        $this->userManager->setPhoneNumber($userUUID, $_POST["phone"]);
        $this->userManager->setUnregisteredInfoLevel($userUUID, INFO_PARTIAL);

        if($collectAddress){
            $USPS = $this->validateAddressUSPS();
            if(empty($USPS)){
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Address not found.");
                $this->redirect("/User/new");
            }

            $addressUUID = $this->userManager->addAddress($userUUID, $USPS["address_line"], $USPS["city"],
                                                          $USPS["state"], $USPS["zip_code"]);

            if($this->isAddressDeliverable($USPS)){
                $this->userManager->setAddressDeliverable($addressUUID);
                $this->userManager->setDefaultAddress($userUUID, $addressUUID);
                $this->userManager->setUnregisteredInfoLevel($userUUID, INFO_FULL);
            } else {
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Delivery to this address is currently unavailable.");
                $this->redirect("/User/new");
            }
        }
        
        $this->redirect("/Order/submit");
    }

    public function info_get() : void {
        $this->pageTitle = "Tony's - User Info";
        // TODO(Trystan): Add a resend verification email button here.
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID)){
            $this->redirect("/");
        }
        if(!$this->sessionManager->isUserLoggedIn()){
            $infoLevel = $this->userManager->getUnregisteredInfoLevel($userUUID);
            if($infoLevel == INFO_NONE){
                $this->redirect("/register");
            }
        }
        
        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/user/user-info-page.php";
    }

    // TODO(Trystan): At the moment we aren't allowing updating of email address.
    // Should we allow?
    public function info_post() : void {
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID)){
            $this->redirect("/");
        }

        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/User/info");
        }

        $valid = true;

        if(strlen($_POST["name_first"]) > MAX_LENGTH_NAME_FIRST){
            $message = "First name must be fewer than " . MAX_LENGTH_NAME_FIRST . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        if(strlen($_POST["name_first"]) > MAX_LENGTH_NAME_LAST){
            $message = "Last name must be fewer than " . MAX_LENGTH_NAME_LAST . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        if(!$valid){
            $this->redirect("/User/info");
        }

        $this->userManager->setName($userUUID, $_POST["name_first"], $_POST["name_last"]);
        $this->userManager->setPhoneNumber($userUUID, $_POST["phone"]);
        
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Info updated successfully.");
        $this->redirect("/User/info");
    }

    // A page to change the current password, not displaying the actual password obviously.
    public function password_get() : void {
        $this->pageTitle = "Tony's - Change Password";
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID) || !$this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/user/user-password-page.php";
    }

    public function password_post() : void {
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID) || !$this->sessionManager->isUserLoggedIn()){
            $this->redirect("/");
        }

        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/User/password");
        }

        if(!isset($_POST["current_password"])
           || !isset($_POST["new_password"])
           || !isset($_POST["repeat_password"])){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Missing input, please try again.");
            $this->redirect("/User/password");
        }

        if(!$this->validatePassword($userUUID, $_POST["current_password"])){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Current password incorrect.");
            $this->redirect("/User/password");
        }

        if(!$this->validatePasswordRequirements($_POST["new_password"])){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "New password must be at least 8 characters long.");
            $this->redirect("/User/password");
        }

        if(strcmp($_POST["new_password"], $_POST["repeat_password"]) == 0){
            $this->userManager->setPassword($userUUID, $_POST['new_password']);
            $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Password changed successfully.");
            $this->redirect("/User/password");
        } else {
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Repeat password does not match the new password.");
            $this->redirect("/User/password");
        }
    }

    public function address_get() : void {
        $this->pageTitle = "Tony's - User Address";
        // If no addresses exist we can add one here.
        // Multiple addresses, select default address.
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID)){
            $this->redirect("/");
        }
        if(!$this->sessionManager->isUserLoggedIn()){
            $infoLevel = $this->userManager->getUnregisteredInfoLevel($userUUID);
            if($infoLevel == INFO_NONE){
                $this->redirect("/register");
            }
        }

        $this->user = $this->userManager->getUserInfo($userUUID);
        $this->user["default_address"] = $this->userManager->getDefaultAddress($userUUID);
        $this->user["other_addresses"] = $this->userManager->getNonDefaultAddresses($userUUID);

        require_once APP_ROOT . "/views/user/user-address-page.php";
    }

    public function address_post() : void {
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID)){
            $this->redirect("/");
        }

        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/User/address");
        }

        
        if(!$this->validateAddress()){
            $this->redirect("/User/address");
        }

        $USPS = $this->validateAddressUSPS();
        if(empty($USPS)){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Address not found.");
            $this->redirect("/User/address");
        }
        
        $addressUUID = $this->userManager->addAddress($userUUID, $USPS["address_line"], $USPS["city"], $USPS["state"], $USPS["zip_code"]);
        
        if($this->isAddressDeliverable($USPS)){
            // TODO(Trystan): Check if user is unregistered and has partial_info.
            // If they do then upgrade them to full_info
            $this->userManager->setAddressDeliverable($addressUUID);
            // We only want addresses to be default if they're deliverable.
            // We also want to automatically set the first valid address as default.
            if(isset($_POST["set_default"]) || empty($this->userManager->getDefaultAddress($userUUID))){
                $this->userManager->setDefaultAddress($userUUID, $addressUUID);
            }
        } else {
            // Note(Trystan): Design choice, allowing user to add address even if not deliverable.
            // Another option would be to reject adding it completely. But that means making the user type it in again later.
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Delivery to this address is currently unavailable.");
        }
        
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Address added successfully.");
        
        $this->redirect("/User/address");
    }

    public function orders_get() : void {
        $this->pageTitle = "Tony's - Order History";

        $userUUID = $this->getUserUUID();
        if(is_null($userUUID)){
            $this->redirect("/");
        }

        $orders = $this->orderManager->getAllOrdersByUserUUID($userUUID);

        foreach($orders as &$order){
            $cost = $this->orderManager->getCost($order["uuid"]);
            $cost["total"] = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
            $order["cost"] = $cost;
        }
        unset($order);

        $this->user = $this->userManager->getUserInfo($userUUID);
        
        require_once APP_ROOT . "/views/user/user-orders-page.php";
    }

    // NOTE(Trystan): We could require the user to verify that they know additional information inside the other
    // sessions. However, things like delivery address, order dollar amount would already be sent to their email.
    public function verify_get() : void {
        $userUUID = $this->getUserUUID();
        if(is_null($userUUID)){
            $this->redirect("/");
        }
        if(!$this->sessionManager->isUserLoggedIn() || !$this->userManager->isVerificationRequired($userUUID)){
            $this->pushOneTimeMessage(USER_ALERT, "Email verification not required.");
            $this->redirect("/");
        }

        $verified = false;
        if(isset($_GET["token"])){
            $userToken = explode("-", $_GET["token"]);
            $selector = $userToken[0];
            $token = $userToken[1];
            $selectorBytes = UUID::arrangedStringToOrderedBytes($selector);
            $emailVerifyInfo = $this->userManager->getEmailVerificationInfo($selectorBytes);

            if(!empty($emailVerifyInfo) && (session_id() === $emailVerifyInfo["session_id"])){
                $hashedToken = hash("sha256", $token);
                if(hash_equals(bin2hex($emailVerifyInfo["hashed_token"]), $hashedToken)){
                    $this->userManager->deleteEmailVerificationToken($userUUID);
                    // We want the expired token to be deleted, but not verified.
                    if(strtotime($emailVerifyInfo["expires"]) > time()){
                        $verified = true;
                    }
                }
            }
        }

        $this->user = $this->userManager->getUserInfo($userUUID);
        if($verified){
            // CONSOLIDATION
            $unregisteredUsers = $this->userManager->getAllUnregisteredUserUUIDsWithEmail($this->user["email"]);
            foreach($unregisteredUsers as $unregistered){
                $this->orderManager->updateOrdersFromUnregisteredToRegistered($unregistered["user_uuid"], $userUUID);
                $this->userManager->updateAddressesFromUnregisteredToRegistered($unregistered["user_uuid"], $userUUID);
                $this->userManager->deleteUnregisteredCredentials($unregistered["user_uuid"]);
                $this->userManager->deleteUser($unregistered["user_uuid"]);
            }
        }

        if($verified){
            $this->pageTitle = "Tony's - Email Verified";
            require_once APP_ROOT . "/views/user/user-verify-success-page.php";
        } else {
            $this->pageTitle = "Tony's - Verification Failed";
            require_once APP_ROOT . "/views/user/user-verify-fail-page.php";
        }
    }

    public function forgot_get() : void {
        $this->pageTitle = "Tony's - Forgot Password";
        $userUUID = $this->getUserUUID();

        if($this->sessionManager->isUserLoggedIn()){
            $this->redirect("/User/info");
        }
        

        require_once APP_ROOT . "/views/user/user-forgot-page.php";
    }

    public function forgot_post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Invalid session. Try again.");
            $this->redirect("/User/forgot");
        }

        $message = "If " . $this->escapeForHTML($_POST['email']) . " has an account, a reset link has been sent.";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);

        $credentials = $this->userManager->getRegisteredCredentialsByEmail($_POST['email']);
        if(empty($credentials)){
            $this->redirect("/User/forgot");
        }

        // Valid registered email, generate a token and send it.
        $token = bin2hex(random_bytes(32));
        $hashedTokenBytes = hash("sha256", $token, true);
        $selectorBytes = UUID::generateOrderedBytes();
        $selector = UUID::orderedBytesToArrangedString($selectorBytes);
        $selector = str_replace("-", "", $selector);

        $emailToken = $selector . "-" . $token;

        $this->userManager->setForgotToken($credentials['user_uuid'], $selectorBytes, $hashedTokenBytes);

        $message = $this->constructForgotPasswordEmail($emailToken);

        $this->sendHTMLEmail($_POST["email"], "Tony's Taco House - Reset Password", $message);

        $this->redirect("/User/forgot");
    }

    public function reset_get() : void {
        $userUUID = $this->getUserUUID();
        header('Referrer-Policy: no-referrer');
        
        $verified = false;
        if(isset($_GET["token"])){
            // I now see it is possible for an attacker to submit a bad token that doesn't conform
            // to what our expectations are. However, I am not seeing anything that breaks
            // this code in a way that leads to a verified result. It would only lead to the failed redirect.
            $userToken = explode("-", $_GET["token"]);
            $selector = $userToken[0];
            $token = $userToken[1];
            $selectorBytes = UUID::arrangedStringToOrderedBytes($selector);
            $forgotTokenInfo = $this->userManager->getForgotTokenInfo($selectorBytes);
            
            if(!empty($forgotTokenInfo)){
                $hashedToken = hash("sha256", $token);
                if(hash_equals(bin2hex($forgotTokenInfo["hashed_token"]), $hashedToken)){
                    $this->userManager->deleteAllForgotTokens($forgotTokenInfo["user_uuid"]);
                    // Even if the token is expired I think it would be good security practice
                    // to remove all current sessions.
                    $this->userManager->deleteAllRememberMeTokens($forgotTokenInfo["user_uuid"]);
                    
                    if(strtotime($forgotTokenInfo["expires"]) > time()){
                        $verified = true;

                        $_SESSION['reset_uuid'] = UUID::orderedBytesToArrangedString($forgotTokenInfo['user_uuid']);
                    }
                }
            }
        }

        // If the token has already been used, this user can end up making an additional reset_get request.
        // For example if they type in a password that doesn't conform to the requirements.
        if(!$verified){
            if(isset($_GET["reset"])){
                $resetTokenHash = hash("sha256", $_GET['reset']);
                if(hash_equals($_SESSION['reset_token_hash'], $resetTokenHash)){
                    $verified = true;
                }
            }
        }
        
        if($verified){
            // Display a page where a user can type in a new password.
            $this->pageTitle = "Tony's - Reset Password";

            // Generate a one time use token that is embedded in the page.
            // like the CSRFToken and sent with the reset post.
            $resetToken = bin2hex(random_bytes(32));
            $_SESSION['reset_token_hash'] = hash("sha256", $resetToken);
            
            require_once APP_ROOT . "/views/user/user-forgot-success-page.php";
        } else {
            $message = "Reset link either expired or invalid. Please enter email to send a new link.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $this->redirect("/User/forgot");
        }
    }

    public function reset_post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"]) || !isset($_SESSION['reset_token_hash'])){
            $message = "Session associated with the reset link has expired. Please enter email to send a new link.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $this->redirect("/User/forgot");
        }

        if(!isset($_POST['reset']) || !isset($_POST['password'])){
            $message = "Values missing, try resubmitting again.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $this->redirect("/User/reset");
        }

        $resetTokenHash = hash("sha256", $_POST["reset"]);
        if(hash_equals($_SESSION['reset_token_hash'], $resetTokenHash)){
            if(!$this->validatePasswordRequirements($_POST['password'])){
                $message = "Password does not meet requirements, must be at least 8 characters long.";
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
                $this->redirect("/User/reset?reset=" . $_POST["reset"]);
            } else {
                // everything is valid and ready to set the new password.
                $userUUID = UUID::ArrangedStringToOrderedBytes($_SESSION['reset_uuid']);
                $this->userManager->setPassword($userUUID, $_POST['password']);
                
                unset($_SESSION['reset_token_hash']);
                unset($_SESSION['reset_uuid']);
                                                
                $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Password changed successfully.");
                $this->sessionManager->login($userUUID);
                $this->redirect("/User/info");
            }
        }
    }

    // JS functions

    // TODO(Trystan): I'm not even sure this needs to be a javascript function.
    // Can't remember why I did it this way.
    public function verify_post() : void {
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        // Remove old token if there is one.
        $this->userManager->deleteEmailVerificationToken($userUUID);
        
        $token = bin2hex(random_bytes(32));
        $hashedTokenBytes = hash("sha256", $token, true);
        $selectorBytes = UUID::generateOrderedBytes();
        $selector = UUID::orderedBytesToArrangedString($selectorBytes);
        $selector = str_replace("-", "", $selector);

        $emailToken = $selector . "-" . $token;

        $expires = time() + (60*60); // expires in one hour.
        $this->userManager->setEmailVerificationToken($userUUID, $token["selector"], $token["hash"]);
    }

    public function address_setDefault_post() : void {
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }
        
        $addressUUID = UUID::arrangedStringToOrderedBytes($postData["address_uuid"]);

        $addresses = $this->userManager->getNonDefaultAddresses($userUUID);
        $ourAddress = array();
        foreach($addresses as $address){
            if($address["uuid"] === $addressUUID){
                $ourAddress = $address;
            }
        }

        if(empty($ourAddress)){
            echo "fail";
            exit;
        }

        if($this->isAddressDeliverable($ourAddress)){
            $this->userManager->setDefaultAddress($userUUID, $addressUUID);
            $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "New default selected.");
        } else {
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Delivery to this address is currently unavailable.");
        }


        echo "success";
    }

    public function address_delete_post() : void {
        $userUUID = $this->getUserUUID();

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }
        
        $addressUUID = UUID::arrangedStringToOrderedBytes($postData["address_uuid"]);

        $addresses = $this->userManager->getNonDefaultAddresses($userUUID);
        $addressUUIDFound = false;
        foreach($addresses as $address){
            if($address["uuid"] === $addressUUID){
                $addressUUIDFound = true;
            }
        }
        if(!$addressUUIDFound){
            echo "fail";
            exit;
        }

        $this->userManager->deleteAddress($addressUUID);

        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Address successfully deleted.");

        echo "success";
    }

}

?>
