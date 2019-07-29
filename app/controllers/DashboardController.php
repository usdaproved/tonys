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
            echo json_encode(NULL);
            exit;
        }

        $orders = $this->orderManager->getAllActiveOrders();
        
        echo json_encode($orders);
    }

    public function updateOrderStatus_post() : void {
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        $orders = $_POST;
        unset($orders["CSRFToken"]);

        if(empty($orders)){
            echo json_encode(NULL);
            exit;
        }

        $updatedInfo = [];

        foreach($orders["status"] as $orderID){
            $order = $this->orderManager->getOrderByID($orderID);

            $index = array_search((int)$order["status"], ORDER_STATUS_FLOW[$order["order_type"]]);
            $updatedStatus = ORDER_STATUS_FLOW[$order["order_type"]][$index + 1];

            $this->orderManager->updateOrderStatus($orderID, $updatedStatus);
            $updatedInfo[$orderID] = $updatedStatus;
        }
        
        echo json_encode($updatedInfo);
    }

    private function validateAuthority(int $requiredAuthority, int $userID = NULL) : bool {
        if(!$this->sessionManager->isUserLoggedIn()){
            return false;
        }

        $userAuthority = $this->userManager->getUserAuthorityLevelByID($userID);
        
        return $userAuthority >= $requiredAuthority;
    }
}

?>
