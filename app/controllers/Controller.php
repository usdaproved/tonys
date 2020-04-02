<?php

class Controller{

    // Every controller, no matter the page, will need to know
    // about a session and the user.
    protected $sessionManager;
    protected $userManager;

    private $messages;

    public function __construct(){
        $this->userManager = new User();
        $this->sessionManager = new Session();
        $this->handleOneTimeMessages();
    }
    
    public function getFile(string $fileType, string $file) : string {
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
     * Returns either a user ID if registered or unregistered, or NULL if user not found.
     */
    public function getUserID() : ?int {
        if($this->sessionManager->isUserLoggedIn()){
            return $this->sessionManager->getUserID();
        }
        
        return $this->userManager->getUserIDByUnregisteredSessionID();
    }

    /**
     * Returns the userID of the validated user, null otherwise.
     */
    public function validateCredentials(string $email, string $password) : ?int {
        $credentials = $this->userManager->getRegisteredCredentialsByEmail($email);

        if(password_verify($password, $credentials["password"])){
            return $credentials["user_id"];
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
        $string = $this->escapeForHTML($addressArray["line"]);
        // TODO: think about how to handle this two seperate line thing.
        $string .= "<br>";
        $string .= $this->escapeForHTML($addressArray["city"]);
        $string .= ", ";
        $string .= $this->escapeForHTML($addressArray["state"]);
        $string .= " ";
        $string .= $this->escapeForHTML($addressArray["zip_code"]);
        return $string;
    }

    public function formatOrderForHTML(array $order = NULL) : string {
        $string = "";
        $string .= "<ul class='line-items-container'>";
        foreach($order['line_items'] as $lineItem){
            $string .= "<li class='line-item' id='{$lineItem['id']}-line-item'>";
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
            $string .= "</li>";
        }
        $string .= "</ul>";

        return $string;
    }

    public function intToCurrency(int $price) : string {
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
