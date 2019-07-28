<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Order.php";

class DashboardController extends Controller{

    private $orderManager;

    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
    }

    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    // TODO: Don't do anything without a CSRFToken
    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    
    public function get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }
        
        require_once APP_ROOT . "/views/dashboard/dashboard-page.php";
    }

    public function orders_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-page.php";
    }

    public function getOrders_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }
        $orders = $this->orderManager->getAllOrdersByStatus("submitted");
        echo json_encode($orders);
    }

    public function updateOrderStatus_post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            echo "failure";
        }
        
        echo "success";
    }

    // TODO: Make a function that responds to an AJAX call,
    // grabs a new list of orders, perhaps using a $_GET ?filter=kitchen
    // Also AJAX calls for updating the order, i.e. from submitted to preparing
    // Submit CSRFToken with the POST call.

    private function validateAuthority(int $requiredAuthority, int $userID = NULL) : bool {
        if(!$this->sessionManager->isUserLoggedIn()){
            return false;
        }

        $userAuthority = $this->userManager->getUserAuthorityLevelByID($userID);
        
        return $userAuthority >= $requiredAuthority;
    }
}

?>
