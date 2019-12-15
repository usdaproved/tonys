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
}

?>
