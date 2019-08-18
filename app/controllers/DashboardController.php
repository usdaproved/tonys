<?php

require_once APP_ROOT . "/controllers/Controller.php";
require_once APP_ROOT . "/models/Order.php";
require_once APP_ROOT . "/models/Menu.php";

class DashboardController extends Controller{

    private $orderManager;
    private $menuManager;

    public $menuStorage;
    public $employeeStorage;

    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
        $this->menuManager = new Menu();
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

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-page.php";
    }

    public function menu_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dasboard");
        }
        
        $this->menuStorage = $this->menuManager->getEntireMenu();

        require_once APP_ROOT . "/views/dashboard/dashboard-menu-item-select-page.php";
    }

    public function menu_categories_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dasboard");
        }

        $this->menuStorage = $this->menuManager->getCategories();

        require_once APP_ROOT . "/views/dashboard/dashboard-menu-categories-edit-page.php";
    }

    public function menu_categories_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        $isNewCategory = false;
        if(isset($_POST["category"])){
            $isNewCategory = true;

            $this->menuManager->createCategory($_POST["category"]);
        } else {
            $categories = $_POST;
            unset($categories["CSRFToken"]);

            foreach($categories as $categoryID => $categoryName){
                $this->menuManager->updateCategory($categoryID, $categoryName);
            }
        }

        $itemStatus = $isNewCategory ? "created" : "updated";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "categories successfully $itemStatus.");

        $this->redirect("/Dashboard/menu");
    }

    public function menu_item_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dasboard");
        }

        if(isset($_GET["id"])){
            $this->menuStorage = $this->menuManager->getMenuItemInfoByID($_GET["id"]);
            $this->menuStorage["categories"] = $this->menuManager->getCategories();
            
            require_once APP_ROOT . "/views/dashboard/dashboard-menu-item-edit-page.php";
        } else {
            $this->redirect("/Dashboard/menu");
        }
    }

    public function menu_item_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        $activeState = isset($_POST["active"]) ? 1 : 0;

        $isNewItem = ((int)$_POST["id"] === 0);

        if($isNewItem){
            $this->menuManager->createMenuItem($activeState, $_POST["category"],
                                               $_POST["name"], $_POST["price"],
                                               $_POST["description"]);
        } else {
            $this->menuManager->updateMenuItem($_POST["id"], $activeState, $_POST["category"],
                                               $_POST["name"], $_POST["price"],
                                               $_POST["description"]);
        }

        $encodedName = $this->escapeForHTML($_POST["name"]);
        $itemStatus = $isNewItem ? "created" : "updated";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "$encodedName successfully $itemStatus.");

        $this->redirect("/Dashboard/menu");
    }

    // TODO: This page is for adding employees and setting them as admins.
    // Removing employees and removing admin-ship.
    public function employees_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }

        $this->employeeStorage = $this->userManager->getAllEmployees();

        require_once APP_ROOT . "/views/dashboard/dashboard-employees-edit-page.php";
    }

    public function employees_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        if(isset($_POST["email"])){
            $employeeAdded = $this->userManager->addEmployeeByEmail($_POST["email"]);
            if($employeeAdded){
                $message = "Employee successfully added.";
                $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);
            } else {
                $message = "Email not linked to any user.";
                $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            }
        }
        if(isset($_POST["delete"])){
            foreach($_POST["employees"] as $employeeID){
                $this->userManager->removeEmployee($employeeID);
            }
            $message = "Employee(s) successfully removed.";
            $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);
        }
        if(isset($_POST["admin"])){
            foreach($_POST["employees"] as $employeeID){
                $this->userManager->toggleEmployeeAdminStatus($employeeID);
            }
            $message = "Employee(s) admin status updated.";
            $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);
        }

        // TODO: Send email informing user of status change.
        $this->redirect("/Dashboard/employees");
    }

    // JS CALLS

    public function orders_getOrders_get() : void {
        // TODO: in a get request we could have the following things:
        // view - this would change either full view or just order view.
        // showComplete - this would show all orders
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo json_encode(NULL);
            exit;
        }

        $orders = $this->orderManager->getAllActiveOrders();

        $numberOfOrders = count($orders);
        for($i = 0; $i < $numberOfOrders; $i++){
            $orders[$i]["user_info"] = $this->userManager->getUserInfoByID($orders[$i]["user_id"]);
        }

        echo json_encode($orders);
    }

    public function orders_updateOrderStatus_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo json_encode(NULL);
            exit;
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            echo json_encode(NULL);
            exit;
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

    public function menu_updateMenuSequence_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            echo "fail";
            exit;
        }

        // Getting passed a json string works differently than other POST requests.
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $menu = $postData;
        unset($menu["CSRFToken"]);

        // Positions start at one in the database. Sorry Dijkstra.
        $categoryPosition = 1;
        foreach($menu as $categoryID => $category){
            $categoryID = explode("-", $categoryID)[0];
            $this->menuManager->updateCategoryPosition($categoryID, $categoryPosition);

            $itemPosition = 1;
            foreach($category as $itemID){
                $this->menuManager->updateItemPosition($itemID, $categoryID, $itemPosition);
                $itemPosition++;
            }

            $categoryPosition++;
        }
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
