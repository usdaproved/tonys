<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Order.php";

class DashboardController extends Controller{

    private $orderManager;

    public $orders;
    
    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
    }

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

        $this->orders = $this->orderManager->getAllOrdersByStatus("submitted");

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-page.php";
    }

    public function getOrders_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }
        $test = $this->orderManager->getAllOrdersByStatus("submitted");
        echo json_encode($test);
    }

    //public function updateOrderStatus_//post or get?

    private function orderToJSON(array $order) : string {
        $result;

        return $result;
    }

    public function orders_post() : void {
        
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
