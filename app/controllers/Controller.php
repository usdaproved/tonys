<?php

class Controller{

    // Every controller, no matter the page, will need to know
    // about a session and the user.
    protected $sessionManager;
    protected $userManager;

    public $pageTitle;

    private $messages;

    public function __construct(){
        $this->userManager = new User();
        $this->sessionManager = new Session();
        $this->handleOneTimeMessages();

        if(!$this->sessionManager->isUserLoggedIn() && isset($_COOKIE["remember_me"])){
            $rememberMeCookie = explode(":", $_COOKIE["remember_me"]);
            $selector = $rememberMeCookie[0];
            $token = $rememberMeCookie[1];
            $selectorBytes = UUID::arrangedStringToOrderedBytes($selector);
            $rememberMeInfo = $this->userManager->getRememberMeInfo($selectorBytes);

            if(!empty($rememberMeInfo)){
                $hashedToken = hash("sha256", $token);
                if(hash_equals(bin2hex($rememberMeInfo["hashed_token"]), $hashedToken)){
                    $this->sessionManager->login($rememberMeInfo["user_uuid"]);
                    $this->sessionManager->setReauthRequired(true);
                    // Remove the used token and generate a new one.
                    $this->userManager->deleteRememberMeToken($rememberMeInfo["user_uuid"], $selectorBytes);
                    $this->initializeRememberMe($rememberMeInfo["user_uuid"]);
                }
            }
        }
    }

    public function initializeRememberMe(string $userUUID) : void {
        // generates a random token that we only store a hash of for security.
        $token = bin2hex(random_bytes(32));
        // stored in byte-form to save space.
        $hashedTokenBytes = hash("sha256", $token, true);
        $selectorBytes = UUID::generateOrderedBytes();
        $selector = UUID::orderedBytesToArrangedString($selectorBytes);
        $selector = str_replace("-", "", $selector);
        $cookie = $selector . ":" . $token;
        $expires = time() + (60*60*24*30); // One month from now.

        $this->userManager->setRememberMeToken($userUUID, $selectorBytes, $hashedTokenBytes);
        setcookie("remember_me", $cookie, $expires);
    }

    public static function display404Page() : void {
        header("HTTP/1.0 404 Not Found");
        require_once APP_ROOT . "/views/page-not-found.php";
        exit;
    }
    
    public static function getFile(string $fileType, string $file) : string {
        if(strpos($file, ".php") !== false){
            $file = explode("/", $file);
            $file = end($file);
            $file = explode(".", $file)[0];
        }

        // TODO: This should be https only
        $protocol = "https://";
        if(empty($_SERVER["HTTPS"])) $protocol = "http://"; 

        // TODO(Trystan): When this is live, only accept the official doamin.
        $acceptableHosts = array("tonys.db", "localhost", "tonys.trystanbrock.dev");
        
        if(!isset($_SERVER['HTTP_HOST']) || !in_array($_SERVER['HTTP_HOST'], $acceptableHosts)) {
            http_response_code(404);
            exit;
        }
        
        return $protocol . $_SERVER["HTTP_HOST"] . "/" . $fileType . "/" . $file . "." . $fileType;
    }

    /**
     * Returns either a user UUID if registered or unregistered, or NULL if user not found.
     */
    public function getUserUUID() : ?string {
        if($this->sessionManager->isUserLoggedIn()){
            return $this->sessionManager->getUserUUID();
        }
        
        return $this->userManager->getUserUUIDByUnregisteredSessionID();
    }

    /**
     * Returns the userUUID of the validated user, null otherwise.
     */
    public function validateCredentials(string $email, string $password) : ?string {
        $credentials = $this->userManager->getRegisteredCredentialsByEmail($email);

        if(empty($credentials)){
            return NULL;
        }
        if(password_verify($password, $credentials["password_hash"])){
            return $credentials["user_uuid"];
        }
        
        return NULL;
    }

    public function validateNewUserInformation() : bool {
        $valid = true;
        
        if(!isset($_POST["email"], $_POST["name_first"], $_POST["name_last"], $_POST["phone"])){
            // We exit the function so that empty values aren't checked.
            $message = "Missing information.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
            return $valid;
        }
        
        if(!$this->validateEmail($_POST["email"])){
            $message = "Please enter a valid email.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
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
        
        if(!empty($this->userManager->getRegisteredCredentialsByEmail($_POST["email"]))){
            $message = MESSAGE_EMAIL_IN_USE;
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        
        return $valid;
    }

    public function validateAddress() : bool {
        $valid = true;
        
        if(!isset($_POST["address_line"], $_POST["city"], $_POST["state"], $_POST["zip_code"])){
            // We exit the function so that empty values aren't checked.
            $message = "Missing information.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
            return $valid;
        }
        
        if(strlen($_POST["address_line"]) > MAX_LENGTH_ADDRESS_LINE){
            $message = "Street address must be fewer than " . MAX_LENGTH_ADDRESS_LINE . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        if(strlen($_POST["city"]) > MAX_LENGTH_ADDRESS_CITY){
            $message = "City must be fewer than " . MAX_LENGTH_ADDRESS_CITY . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        if(strlen($_POST["state"]) > MAX_LENGTH_ADDRESS_STATE){
            $message = "State must be fewer than " . MAX_LENGTH_ADDRESS_STATE . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }
        if(strlen($_POST["zip_code"]) > MAX_LENGTH_ADDRESS_ZIP_CODE){
            $message = "Zip code must be fewer than " . MAX_LENGTH_ADDRESS_ZIP_CODE . " characters.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            $valid = false;
        }

        return $valid;
    }

    public function validateAddressUSPS() : array {
        $url = 'https://secure.shippingapis.com/ShippingAPI.dll';
        $postData = array(
            'API' => 'Verify',
            'XML' => "<AddressValidateRequest USERID=\"" . USPS_USER_ID . "\">
<Address ID=\"0\">
<Address1></Address1>
<Address2>{$_POST['address_line']}</Address2>
<City>{$_POST['city']}</City>
<State>{$_POST['state']}</State>
<Zip5>{$_POST['zip_code']}</Zip5>
<Zip4></Zip4>
</Address>
</AddressValidateRequest>",
        );

        $postDataString = http_build_query($postData);

        //open connection
        $curlHandle = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_POST, count($postData));
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postDataString);

        //execute post
        $xml = curl_exec($curlHandle);

        //close connection
        curl_close($curlHandle);

        // Parse the response, beginning with checking for an error.
        if(strpos($xml, "<Error>")){
            // We don't care WHAT the error is, we just know it's not valid.
            return array();
        }
        
        // if no error, then grab the values we need from the address.
        $addressLineIndexBegin = strpos($xml, "<Address2>") + 10;
        $addressLineIndexEnd = strpos($xml, "</Address2>");
        $addressLineLength = $addressLineIndexEnd - $addressLineIndexBegin;

        $addressLine = substr($xml, $addressLineIndexBegin, $addressLineLength);

        $cityIndexBegin = strpos($xml, "<City>") + 6;
        $cityIndexEnd = strpos($xml, "</City>");
        $cityLength = $cityIndexEnd - $cityIndexBegin;

        $city = substr($xml, $cityIndexBegin, $cityLength);

        $stateIndexBegin = strpos($xml, "<State>") + 7;

        $state = substr($xml, $stateIndexBegin, 2); // state will always be 2 characters.

        $zipIndexBegin = strpos($xml, "<Zip5>") + 6;

        $zip = substr($xml, $zipIndexBegin, 5); // zip will always be 5 characters.

        return ["address_line" => $addressLine, "city" => $city, "state" => $state, "zip_code" => $zip];
    }

    // TODO(Trystan): At some point we will need to do some more complex checking here.
    // But for the moment this suits our purposes while still introducing the concept of 'undelivarable'.
    public function isAddressDeliverable(array $address) : bool {
        if(in_array($address["zip_code"], DELIVERY_ZIP_CODES)){
            return true;
        }
        return false;
    }

    public function validateEmail(string $email) : bool {
        $valid = true;

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if(strlen($email) > MAX_LENGTH_EMAIL || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $valid = false;
        }

        return $valid;
    }

    public function redirect(string $page) : void {
        header("Location: " . $page);
        exit;
    }

    private $htmlChars = array(
        "&" => "&amp;",
        "<" => "&lt",
        ">" => "&gt",
        "\"" => "&quot",
        "\'" => "&#x27",     
        "/" => "&#x2F"
    );

    public function escapeForHTML(string $string = NULL) : string {
        $result = "";
        $stringLength = strlen($string);
        for($i = 0; $i < $stringLength; $i++){
            $char = $string[$i];
            if(array_key_exists($char, $this->htmlChars)){
                $char = $this->htmlChars[$char];
            }

            $result = $result . $char;
        }
        
        return $result;
    }

    public function escapeForAttributes(string $string = NULL) : string {
        $result = "";
        $stringLength = strlen($string);
        for($i = 0; $i < $stringLength; $i++){
            $char = $string[$i];
            if(!ctype_alnum($char)){
                $char = "&#" . ord($char) . ";";
            }

            $result = $result . $char;
        }
        
        return $result;
    }

    /**
     * This is how the address is formatted:
     * STREET_ADDRESS
     * CITY, STATE ZIP_CODE
     *
     * No wrappers are added.
     */
    public function formatAddressForHTML(array $addressArray = NULL) : string {
	if($addressArray === NULL || empty($addressArray)) return "";
        $string  = "<span class='address-line-1'>";
        $string .= $this->escapeForHTML($addressArray["line"]);
        $string .= "</span>";
        // TODO: think about how to handle this two seperate line thing.
        $string .= "<br>";
        $string .= "<span class='address-line-2'>";
        $string .= $this->escapeForHTML($addressArray["city"]);
        $string .= ", ";
        $string .= $this->escapeForHTML($addressArray["state"]);
        $string .= " ";
        $string .= $this->escapeForHTML($addressArray["zip_code"]);
        $string .= "</span>";
        return $string;
    }

    public function formatOrderForHTML(array $order = NULL) : string {
        $string = "";
        $string .= "<ul class='line-items-container'>";
        foreach($order['line_items'] ?? array() as $lineItem){
            $string .= "<li class='line-item' id='{$lineItem['uuid']}'>";
            $string .= "<span class='line-item-quantity'>";
            $string .= $lineItem['quantity'];
            $string .= "</span>";
            $string .= ' ' . $lineItem['name'];
            foreach($lineItem['choices'] as $choice){
                $string .= "<div class='line-item-choice'>";
                $string .= $choice['name'];
                $string .= "<ul class='options-container'>";
                foreach($choice['options'] as $option){
                    $string .= "<li class='line-item-option'>";
                    $string .= $option['name'];
                    $string .= "</li>";
                }
                $string .= "</ul>";
                $string .= "</div>";
            }
            if(count($lineItem['additions']) != 0){
                $string .= "Additions";
                $string .= "<ul class='additions-container'>";
                foreach($lineItem['additions'] as $addition){
                    $string .= "<li class='line-item-addition'>";
                    $string .= $addition['name'];
                    $string .= "</li>";
                }
                $string .= "</ul>";
            }
            $string .= "<div class='line-item-comment'>";
            $string .= $this->escapeForHTML($lineItem['comment']);
            $string .= "</div>";
            
            $string .= "</li>";
        }
        $string .= "</ul>";

        return $string;
    }

    public function intToCurrency(int $price = NULL) : string {
	if(empty($price)) return "";
        return number_format((float)($price / 100.0), 2);
    }

    public function printOneTimeMessages(string $messageType) : void {
        if(!isset($this->messages[$messageType])) return;
        foreach($this->messages[$messageType] as $message){
            echo $message;
        }
    }

    private function handleOneTimeMessages() : void {
        $arrays = $this->sessionManager->getOneTimeMessages();

        if(is_null($arrays)) return;

        foreach($arrays as $messageType => $messages){
            foreach($messages as $message){
                $this->messages[$messageType][] =  "<div class=\"$messageType\">$message</div>";
            }
        }
    }
}


?>
