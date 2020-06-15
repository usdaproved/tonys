<?php

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
        // TODO(Trystan): This is where customers can view order history.
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
                    $verified = true;
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

    // JS functions

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
