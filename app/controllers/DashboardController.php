<?php

// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

class DashboardController extends Controller{

    private Order $orderManager;
    private Menu $menuManager;
    private RestaurantSettings $settingsManager;

    public $menuStorage;
    public $orderStorage;
    public $userStorage; // This is for other user data. customers/employees
    public $user; // This is the user currently accessing the page.

    public function __construct(){
        parent::__construct();

        $this->orderManager = new Order();
        $this->menuManager = new Menu();
        $this->settingsManager = new RestaurantSettings();
    }

    // NOTE(Trystan): Now that we have a navigation header, this isn't necessary.
    // Under the previous setup it was just an aggregation of links to dashboard pages.
    /*
    public function get() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);
        
        require_once APP_ROOT . "/views/dashboard/dashboard-page.php";
    }
    */

    // Tentative, not sure if we need this.
    // But it would be a way to go to a specific customer and view all their actvity.
    // It would show total spent and list all orders made by customer.
    // Maybe even something like amount spent in the last 30 days.
    public function customers_get() : void {
        $this->pageTitle = "Dashboard - Customer";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
        }

        if(!isset($_GET["uuid"])){
            $this->redirect("/Dashboard/customers/search");
        }

        $userUUIDBytes = UUID::arrangedStringToOrderedBytes($_GET["uuid"]);
        $this->userStorage = $this->userManager->getUserInfo($userUUIDBytes);
        if(empty($this->userStorage)){
            $this->redirect("/Dashboard/customers/search");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        $this->orderStorage = $this->orderManager->getAllOrdersByUserUUID($userUUIDBytes);

        require_once APP_ROOT . "/views/dashboard/dashboard-customers-page.php";
    }

    public function customers_search_get() : void {
        $this->pageTitle = "Dashboard - Search Customers";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);


        require_once APP_ROOT . "/views/dashboard/dashboard-customers-search-page.php";
    }

    public function orders_get() : void {
        $this->pageTitle = "Dashboard - Order";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
        }

        if(!isset($_GET["uuid"])){
            $this->redirect("/Dashboard/orders/search");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        $orderUUIDBytes = UUID::arrangedStringToOrderedBytes($_GET["uuid"]);
        $this->orderStorage = $this->orderManager->getOrderByUUID($orderUUIDBytes);
        $this->orderStorage["user_info"] = $this->userManager->getUserInfo($this->orderStorage["user_uuid"]);
        // TODO(Trystan): We can grab any specifics from the respective processors if data is necessary.
        $this->orderStorage["payments"] = $this->orderManager->getPayments($orderUUIDBytes);
        $cost = $this->orderManager->getCost($orderUUIDBytes);
        $cost["total"] = $cost["subtotal"] + $cost["fee"] + $cost["tax"];
        $this->orderStorage["cost"] = $cost;

        foreach($this->orderStorage["payments"] as &$payment){
            $payment["refund_total"] = $this->orderManager->getRefundTotal($payment["id"]);
        }
        // A quirk of PHP references: you have to break them after the scope ends. If you were to use $payment
        // without doing that, it would point to the last element of the previous foreach loop. Like what?
        unset($payment);
        
        require_once APP_ROOT . "/views/dashboard/dashboard-orders-page.php";
    }

    // Handles all types of refunds in one function. Cash, Stripe, paypal, apple.
    public function orders_refund_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $payment = $this->orderManager->getPaymentByID($postData["payment_id"]);
        $refundTotal = $this->orderManager->getRefundTotal($postData["payment_id"]);

        if(($payment["amount"] - $refundTotal) < $postData["amount"]){
            echo "fail";
            exit;
        }

        // TODO(Trystan): Paypal refunds. And then whenever we add Apple Pay.
        switch($payment["method"]){
        case PAYMENT_STRIPE:
            \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
            $paymentIntentID = $this->orderManager->getStripeToken($payment["order_uuid"]);
            \Stripe\Refund::create(['amount' => $postData["amount"], 'payment_intent' => $paymentIntentID]);
            $this->orderManager->submitRefund($postData["payment_id"], $postData["amount"]);
            break;
        case PAYMENT_CASH:
            $this->orderManager->submitRefund($postData["payment_id"], $postData["amount"]);
            break;
        }

        echo "success";
    }

    public function orders_active_get() : void {
        $this->pageTitle = "Dashboard - Active Orders";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-active-page.php";
    }

    public function orders_search_get() : void {
        $this->pageTitle = "Dashboard - Search Orders";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
        }        

        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-search-page.php";
    }
    
    public function orders_submit_get() : void {
        $this->pageTitle = "Dashboard - Submit Order";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
        }

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        $this->orderStorage = $this->orderManager->getOrderByUUID($cartUUID);

        if(count($this->orderStorage["line_items"]) === 0){
            $this->redirect("/Order");
        }

        $getAddress = false;
        if($this->orderStorage["order_type"] == DELIVERY){
            $this->orderStorage["delivery_address"] = $this->orderManager->getDeliveryAddress($cartUUID);
            
            if(empty($this->orderStorage["delivery_address"])){
                $getAddress = true;
            }
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-submit-page.php";
    }

    // Technically called by javascript. Just wanted to keep it close to the other for clarity.
    public function orders_submit_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo "fail";
            exit;
        }

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        $this->orderStorage = $this->orderManager->getOrderByUUID($cartUUID);
        if($cartUUID === NULL){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);

        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $customerUUID = NULL;

        if(!is_null($postData["user_uuid"])){
            $customerUUID = UUID::arrangedStringToOrderedBytes($postData["user_uuid"]);
        }

        // No matter what we want to assign user here, we override the employee with null if none selected.
        $this->orderManager->assignUserToOrder($cartUUID, $customerUUID);

        $this->orderManager->submitOrder($cartUUID);
        
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Order successfully submitted.");
        echo "success";
    }

    public function orders_submit_setDeliveryAddress_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            $this->redirect("/");
            exit;
        }

        $cartUUID = $this->orderManager->getCartUUID($userUUID);
        if($cartUUID === NULL){
            $this->redirect("/Order");
            exit;
        }

        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/Dashboard/orders/submit");
            exit;
        }

        // Add an address to nobody so that we can associate it with an order.
        $addressUUID = $this->userManager->addNullUserAddress($_POST["address_line"], $_POST["city"],
                                                      $_POST["state"], $_POST["zip_code"]);

        $this->orderManager->setDeliveryAddress($cartUUID, $addressUUID);

        $this->redirect("/Dashboard/orders/submit");
    }

    public function menu_get() : void {
        $this->pageTitle = "Dashboard - Menu";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }
        
        $this->menuStorage = $this->menuManager->getEntireMenu();
        $this->user = $this->userManager->getUserInfo($userUUID);
        
        require_once APP_ROOT . "/views/dashboard/dashboard-menu-item-select-page.php";
    }

    public function menu_categories_get() : void {
        $this->pageTitle = "Dashboard - Menu Categories";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }

        $this->menuStorage = $this->menuManager->getCategories();
        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/dashboard/dashboard-menu-categories-edit-page.php";
    }

    public function menu_categories_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
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
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Category successfully $itemStatus.");

        $this->redirect("/Dashboard/menu/categories");
    }

    public function menu_item_get() : void {
        $this->pageTitle = "Dashboard - Menu Item";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }

        if(isset($_GET["id"])){
            $this->menuStorage = $this->menuManager->getItemInfo($_GET["id"]);
            $this->menuStorage["categories"] = $this->menuManager->getCategories();

            $this->user = $this->userManager->getUserInfo($userUUID);

            require_once APP_ROOT . "/views/dashboard/dashboard-menu-item-edit-page.php";
        } else {
            $this->redirect("/Dashboard/menu");
        }
    }

    // TODO(Trystan): Validate inputs. Especially ensure price is a number within range.
    public function menu_item_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        $activeState = isset($_POST["active"]) ? 1 : 0;

        $isNewItem = ((int)$_POST["id"] === 0);
        $itemID = NULL;

        if($isNewItem){
            $itemID = $this->menuManager->createMenuItem($activeState, $_POST["category"],
                                                         $_POST["name"], $_POST["price"] * 100,
                                                         $_POST["description"]);
        } else {
            $itemID = $_POST["id"];
            $this->menuManager->updateMenuItem($_POST["id"], $activeState, $_POST["category"],
                                               $_POST["name"], $_POST["price"] * 100,
                                               $_POST["description"]);
        }

        $isSpecialOnly = isset($_POST["special_only"]) ? 1 : 0;
        $specialDay = $_POST["special_day"];
        if($specialDay == "null") $specialDay = NULL;

        $this->menuManager->setSpecialPrice($itemID, $_POST["special_price"] * 100);
        $this->menuManager->setSpecialDay($itemID, $specialDay);
        $this->menuManager->setSpecialOnly($itemID, $isSpecialOnly);

        $encodedName = $this->escapeForHTML($_POST["name"]);
        $itemStatus = $isNewItem ? "created" : "updated";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "$encodedName successfully $itemStatus.");

        $this->redirect("/Dashboard/menu/item?id=" . $itemID);
    }

    public function employees_get() : void {
        $this->pageTitle = "Dashboard - Employees";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }
        
        $this->user = $this->userManager->getUserInfo($userUUID);
        $this->userStorage = $this->userManager->getAllEmployees();

        require_once APP_ROOT . "/views/dashboard/dashboard-employees-edit-page.php";
    }

    public function settings_get() : void {
        $this->pageTitle = "Dashboard - Settings";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        $settings = [];
        $settings["delivery_on"] = $this->orderManager->isDeliveryOn();
        $settings["pickup_on"] = $this->orderManager->isPickupOn();

        require_once APP_ROOT . "/views/dashboard/dashboard-settings-page.php";
    }

    public function settings_schedule_get() : void {
        $this->pageTitle = "Settings - Schedule";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        $settings = [];
        $settings["delivery_schedule"] = $this->settingsManager->getDeliverySchedule();
        $settings["pickup_schedule"] = $this->settingsManager->getPickupSchedule();
        $settings["delivery_on"] = $this->orderManager->isDeliveryOn();
        $settings["pickup_on"] = $this->orderManager->isPickupOn();
        $week = array_keys(DAY_TO_INT);

        require_once APP_ROOT . "/views/dashboard/dashboard-settings-schedule-page.php";
    }

    public function settings_printers_get() : void {
        $this->pageTitle = "Dashboard - Printers";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        $printers = $this->settingsManager->getAllPrinters();

        require_once APP_ROOT . "/views/dashboard/dashboard-settings-printers-page.php";
    }

    public function settings_printers_add_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "Bad auth";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "invalid token";
            exit;
        }

        $name = $postData["name"];
        $token = random_bytes(64);
        $selector = random_bytes(16);
        $hashedToken = hash("sha512", $token, true);

        $this->settingsManager->addOrderPrinter($selector, $name, $hashedToken);

        $message = "Please copy the following string onto your printer program<br>"
                 . bin2hex($selector) . ":" . bin2hex($token);
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);

        echo "success";
    }

    public function settings_printers_remove_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "Bad auth";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "invalid token";
            exit;
        }

        $selector = hex2bin($postData["selector"]);

        $this->settingsManager->removeOrderPrinter($selector);
        
        echo "success";
    }

    // JS CALLS

    public function searchOrders_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        unset($postData["CSRFToken"]);

        // If every filter is empty then we'd return the entire database.
        $emptyFilters = true;
        $acceptableFilters = ["start_date", "end_date", "start_amount", "end_amount",
                              "first_name", "last_name", "email", "phone_number", "order_type"];
        foreach($postData as $key => $filter){
            if(!empty($filter) && in_array($key, $acceptableFilters)){
                $emptyFilters = false;
            } else {
                if($key === "order_type" && !is_null($filter)){
                    $emptyFilters = false;
                } else {
                    $postData[$key] = NULL;
                }
            }
        }

        if($emptyFilters){
            echo json_encode(NULL);
            exit;
        }

        $uuids = $this->orderManager->getOrdersMatchingFilters($postData["start_date"], $postData["end_date"],
                                                                $postData["start_amount"], $postData["end_amount"],
                                                                $postData["order_type"], $postData["first_name"],
                                                                $postData["last_name"], $postData["email"],
                                                                $postData["phone_number"]);

        $orders = [];
        foreach($uuids as $uuid){
            $order = $this->orderManager->getBasicOrderInfo($uuid["uuid"]);
            $order["uuid"] = UUID::orderedBytesToArrangedString($order["uuid"]);
            $order["user_uuid"] = UUID::orderedBytesToArrangedString($order["user_uuid"]);
            $order["user_info"] = $this->userManager->getUserInfo($uuid["user_uuid"]);
            $order["date"] = date("F d, Y g:i A", strtotime($order["date"]));

            $orders[] = $order;
        }
        
        echo json_encode($orders);
    }

    public function searchUsers_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        unset($postData["CSRFToken"]);

        // If every filter is empty then we'd return the entire database.
        $emptyFilters = true;
        // Registered users alone is not an acceptable filter, we don't want to return every registered user.
        $acceptableFilters = ["first_name", "last_name", "email", "phone_number"];
        foreach($postData as $key => $filter){
            if(!empty($filter) && in_array($key, $acceptableFilters)){
                $emptyFilters = false;
            }
        }

        if($emptyFilters){
            echo json_encode(NULL);
            exit;
        }
        $users = $this->userManager->getUsersMatchingFilters($postData["first_name"], $postData["last_name"],
                                                             $postData["email"], $postData["phone_number"], $postData["registered"]);

        echo json_encode($users);
    }

    public function orders_active_getOrders_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $lastReceived = $postData["last_received"] ?? "NULL";

        $orders = array();
        if($lastReceived === "NULL"){
            $orders = $this->orderManager->getAllActiveOrders();
        } else {
            $orders = $this->orderManager->getActiveOrdersAfterDate($lastReceived);
        }

        $orderCount = count($orders);
        for($i = 0; $i < $orderCount; $i++){
            if($orders[$i]["user_uuid"] != NULL){
                $orders[$i]["user_info"] = $this->userManager->getUserInfo($orders[$i]["user_uuid"]);
                $orders[$i]["user_uuid"] = UUID::orderedBytesToArrangedString($orders[$i]["user_uuid"]);
            }
            if($orders[$i]["order_type"] == DELIVERY){
                $orders[$i]["address"] = $this->orderManager->getDeliveryAddress($orders[$i]["uuid"]);
                // NOTE(Trystan): I don't think this function actually utilizes this value, but just to be safe.
                $orders[$i]["address"]["uuid"] = UUID::orderedBytesToArrangedString($orders[$i]["address"]["uuid"]);
            }
            $orders[$i]["is_paid"] = $this->orderManager->isPaid($orders[$i]["uuid"]);
            $orders[$i]["uuid"] = UUID::orderedBytesToArrangedString($orders[$i]["uuid"]);
            $orders[$i]["date"] = date("g:i A", strtotime($orders[$i]["date"]));
        }

        echo json_encode($orders);
    }

    public function orders_active_getStatus_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $result = [];
        foreach($postData["orderUUIDs"] as $orderUUID){
            $orderUUID = UUID::arrangedStringToOrderedBytes($orderUUID);
            $result[] = $this->orderManager->getOrderStatus($orderUUID);
        }

        echo json_encode($result);
    }

    public function orders_active_updateStatus_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $orders = $postData;
        unset($orders["CSRFToken"]);

        if(empty($orders)){
            echo json_encode(NULL);
            exit;
        }

        $updatedInfo = [];

        foreach($orders["status"] as $orderUUID){
            $orderUUIDBytes = UUID::arrangedStringToOrderedBytes($orderUUID);
            $order = $this->orderManager->getOrderByUUID($orderUUIDBytes);

            $index = array_search((int)$order["status"], ORDER_STATUS_FLOW[$order["order_type"]]);
            $updatedStatus = ORDER_STATUS_FLOW[$order["order_type"]][$index + 1];

            $this->orderManager->updateOrderStatus($orderUUIDBytes, $updatedStatus);
            $updatedInfo[$orderUUID] = $updatedStatus;
        }
        
        echo json_encode($updatedInfo);
    }

    public function orders_active_checkPayment_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $orders = $postData;
        unset($orders["CSRFToken"]);

        if(empty($orders)){
            echo json_encode(null);
            exit;
        }

        $isPaid = [];
        
        foreach($orders["uuid"] as $orderUUID){
            $isPaid[$orderUUID] = $this->orderManager->isPaid($orderUUID);
        }

        echo json_encode($isPaid);
    }

    // Returns both the order_cost and order_payment
    public function orders_active_getPaymentInfo_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode(NULL);
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $orderUUID = UUID::arrangedStringToOrderedBytes($postData["uuid"]);

        $result = [];
        $result["cost"] = $this->orderManager->getCost($orderUUID);
        $result["payments"] = $this->orderManager->getPayments($orderUUID);

        echo json_encode($result);
    }

    public function orders_active_submitPayment_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo "fail";
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $orderUUID = UUID::arrangedStringToOrderedBytes($postData["uuid"]);
        $amount = $postData["amount"];
        $method = $postData["method"];

        $this->orderManager->submitPayment($orderUUID, $amount, $method);
        $this->orderManager->updateOrderStatus($orderUUID, COMPLETE);
        
        echo "success";
    }

    public function orders_active_stripeConnectionToken_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode("fail");
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode("fail");
            exit;
        }

        \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
        $connectionToken = \Stripe\Terminal\ConnectionToken::create();

        echo json_encode($connectionToken);
    }

    public function orders_active_getStripeCheckoutInfo_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(EMPLOYEE, $userUUID)){
            echo json_encode("fail");
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode("fail");
            exit;
        }

        $orderUUID = UUID::arrangedStringToOrderedBytes($postData["order_uuid"]);
        $stripeToken = $this->orderManager->getStripeToken($orderUUID);
        if(is_null($stripeToken)){
            echo json_encode("fail");
            exit;
        }


        $stripe = new \Stripe\StripeClient(STRIPE_PRIVATE_KEY);

        // Updating the payment type to allow us to complete transaction from POS
        $stripePaymentIntent = $stripe->paymentIntents->update($stripeToken, [
            'payment_method_types' => ['card_present'],
        ]);

        echo json_encode($stripePaymentIntent["client_secret"]);
    }

    // TODO(Trystan): This function needs a major relook. Lots has changed.
    public function orders_printerStream_post() : void {
        $selectorBytes = NULL;
        if(!isset($_POST["token"])){
            http_response_code(400);
            exit;
        } else {
            // remove any new lines if there are any.
            $string = str_replace(array("\n", "\r"), '', $_POST["token"]);
            $tokenParts = explode(":", $string);
            $selectorBytes = hex2bin($tokenParts[0]);
            $tokenBytes = hex2bin($tokenParts[1]);

            $printerInfo = $this->settingsManager->getPrinterInfo($selectorBytes);

            if(!empty($printerInfo)){
                $hashedToken = hash("sha512", $tokenBytes, true); // we want raw binary.
                if(hash_equals($printerInfo["hashed_bytes"], $hashedToken)){
                    // validated.
                    $this->settingsManager->setPrinterConnection($selectorBytes, true);
                } else {
                    http_response_code(401);
                    exit;
                }
            } else {
                http_response_code(401);
                exit;
            }
        }

        // The printer program sends "NULL" if no date file found.
        $lastReceived = NULL;
        if(isset($_POST["lastReceived"])){
            if($_POST["lastReceived"] !== "NULL"){
                // TODO(Trystan): Might need to check if valid date.
                // If the power gets cut when the program is writing the date to file.
                // And the file gets corrupted, we could have garbage here that would mess up the nextActiveOrder call.
                $lastReceived = $_POST["lastReceived"];
            }
        }

        $order = $this->orderManager->printer_getNextActiveOrder($lastReceived);

        header("Content-Type: text/event-stream");
        header("Cache-Control: no-cache");
        header("X-Accel-Buffering: no");

        // NOTE(Trystan): The connection will abort before connection_aborted()
        // code path is executed without this setting.
        ignore_user_abort(true);

        while (true) {
            if(!empty($order)){
                $lastReceived = $order["date"];

                // TODO(trystan): Find out the max char length per line of the printer,
                // ensure we don't go over that.

                // TODO(Trystan): Take another look at how we want to format printer.

                echo "TIMESTAMP " . $lastReceived . PHP_EOL;

                echo PHP_EOL;
            
                // Maybe print some over arching order info.
                echo "ORDER " . UUID::orderedBytesToArrangedString($order["uuid"]) . PHP_EOL;
                $customer = $this->userManager->getUserInfo($order["user_uuid"]);
                echo PHP_EOL;
                echo PHP_EOL;
                echo "Order Type: " . strtoupper(ORDER_TYPE_ARRAY[$order["order_type"]]) . PHP_EOL;
                echo "Name: " . $customer["name_first"] . " " . $customer["name_last"] . PHP_EOL;
                if($order["order_type"] == DELIVERY){
                    $address = $this->orderManager->getDeliveryAddress($order["uuid"]);
                    echo $address["line"] . PHP_EOL;
                    echo $address["city"] . ", " . $address["state"] . " " . $address["zip_code"] . PHP_EOL;
                    
                }
                echo PHP_EOL;

                echo date("F d, Y g:i A", strtotime($order["date"])) . PHP_EOL;

                echo PHP_EOL;
            
                foreach($order["line_items"] as $lineItem){
                    echo $lineItem["name"] .  " - " . $lineItem["quantity"] . "       $" . $this->intToCurrency($lineItem["price"]) . PHP_EOL;
                    foreach($lineItem["choices"] as $choice){
                        echo " - " . $choice["name"] . PHP_EOL;
                        foreach($choice["options"] as $option){
                            echo "    - " . $option["name"] . PHP_EOL;
                        }
                    }
                    if(!empty($lineItem["comment"])){
                        echo "COMMENT: " . $lineItem["comment"] . PHP_EOL;
                    }
                    echo PHP_EOL;
                }

                $cost = $this->orderManager->getCost($order["uuid"]);
                echo "Subtotal: $" . $this->intToCurrency($cost["subtotal"]) . PHP_EOL;
                echo "Tax:      $" . $this->intToCurrency($cost["tax"]) . PHP_EOL;
                if($cost["fee"] != 0){
                    echo "Fee:      $" . $this->intToCurrency($cost["fee"]) . PHP_EOL;
                }
                $total = $cost["subtotal"] + $cost["tax"] + $cost["fee"];
                echo "Total:    $" . $this->intToCurrency($total) . PHP_EOL;
                
                // Give a little padding between orders.
                echo PHP_EOL;
                echo PHP_EOL;
            } else {
                echo "Still alive" . PHP_EOL;
            }

            // flush the output buffer and send echoed messages to the browser
            while(ob_get_level() > 0){
                ob_end_flush();
            }
            flush();

            if(connection_aborted()){
                $this->settingsManager->setPrinterConnection($selectorBytes, false);
                break;
            }

            sleep(5);

            $order = $this->orderManager->printer_getNextActiveOrder($lastReceived);
        }
    }

    public function menu_updateMenuSequence_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
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

        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Menu order has been successfully updated.");
    }

    public function menu_categories_delete_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $isEmpty = $this->menuManager->isCategoryEmpty($postData["id"]);
        if(!$isEmpty){
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Category must be empty in order to delete.");
            exit;
        }

        $this->menuManager->removeCategory($postData["id"]);
        
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Category has been succesfully removed.");
    }

    public function menu_item_delete_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $this->menuManager->removeMenuItem($postData["id"]);

        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "Item has been succesfully removed.");
    }
    
    public function menu_item_updateChoices_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $choiceGroups = $postData;
        unset($choiceGroups["CSRFToken"]);

        foreach($choiceGroups as $groupID => $choiceGroup){
            $groupID = explode("-", $groupID)[0];
            $groupName = $choiceGroup["group-data"]["name"];
            // TODO: Check that picks does not exceed number of choices.
            $groupMinPicks = $choiceGroup["group-data"]["min-picks"];
            // TODO: Check that max picks greater than min.
            $groupMaxPicks = $choiceGroup["group-data"]["max-picks"];

            $this->menuManager->updateChoiceGroup($groupID, $groupName,
                                                  $groupMinPicks, $groupMaxPicks);

            unset($choiceGroup["group-data"]);
            
            foreach($choiceGroup as $choiceID => $choice){
                $choiceID = explode("-", $choiceID)[0];
                $choiceName = $choice["name"];
                $choicePrice = $choice["price"] * 100;
                $choiceSpecialPrice = $choice["special_price"] * 100;

                $this->menuManager->updateChoiceOption($choiceID, $choiceName, $choicePrice);
                $this->menuManager->setSpecialOptionPrice($choiceID, $choiceSpecialPrice);
                $this->menuManager->setChoiceOptionActiveState($choiceID, $choice["active"]);
            }
        }

        echo "success";
    }

    public function menu_item_updateChoicesSequence_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $choiceGroups = $postData;
        unset($choiceGroups["CSRFToken"]);

        $groupPosition = 1;
        foreach($choiceGroups as $groupID => $choiceGroup){
            $groupID = explode("-", $groupID)[0];
            $this->menuManager->updateChoiceGroupPosition($groupID, $groupPosition);

            $choicePosition = 1;
            foreach($choiceGroup as $choiceID){
                $choiceID = explode("-", $choiceID)[0];
                $this->menuManager->updateChoiceOptionPosition($choiceID, $choicePosition);
                $choicePosition++;
            }
            $groupPosition++;
        }
        
        
    }

    public function menu_item_addChoiceGroup_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $itemID = $postData["item-id"];

        // Create a blank group. This will be filled during the update function.
        $groupID = $this->menuManager->createChoiceGroup($itemID, "", 0, 0);
        
        echo $groupID;
    }

    public function menu_item_removeChoiceGroup_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $groupID = $postData["group-id"];

        $this->menuManager->removeChoiceGroup($groupID);
        
        echo "success";
    }

    public function menu_item_addChoiceOption_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $groupID = $postData["group-id"];

        $optionID = $this->menuManager->createChoiceOption($groupID, "", 0);
        
        echo $optionID;
    }

    public function menu_item_removeChoiceOption_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $optionID = $postData["option-id"];

        $this->menuManager->removeChoiceOption($optionID);
        
        echo "success";
    }

    public function employees_add_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $employeeUUID = UUID::arrangedStringToOrderedBytes($postData["user_uuid"]);
        $this->userManager->setUserAsEmployee($employeeUUID);

        $message = "Employee successfully added.";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);

        // TODO(Trystan): Send out emails to both admins and the new employee about status updates.
        // Other employee related emails might need to be sent out for deletion and admin.
        
        echo "success";
    }
    
    public function employees_delete_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $employeeUUID = UUID::arrangedStringToOrderedBytes($postData["user_uuid"]);
        if($userUUID === $employeeUUID){
            $message = "The system will not allow you to remove yourself.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            exit;
        }

        $employeeLevel = $this->userManager->getUserAuthorityLevel($employeeUUID);
        if($employeeLevel == OWNER){
            $message = "Owners cannot be deleted.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            exit;
        }
        
        $this->userManager->removeEmployee($employeeUUID);

        $message = "Employee successfully removed.";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);

        echo "success";
    }

    public function employees_toggleAdmin_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $employeeUUID = UUID::arrangedStringToOrderedBytes($postData["user_uuid"]);
        if($userUUID === $employeeUUID){
            $message = "The system will not allow you to remove your own admin status.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            exit;
        }

        $employeeLevel = $this->userManager->getUserAuthorityLevel($employeeUUID);
        if($employeeLevel == OWNER){
            $message = "Owners status cannot be changed.";
            $this->sessionManager->pushOneTimeMessage(USER_ALERT, $message);
            exit;
        }

        $this->userManager->toggleEmployeeAdminStatus($employeeUUID);

        $message = "Employee admin status updated.";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, $message);
        
        echo "success";
    }

    public function settings_updateDeliveryStatus_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $status = false;
        if($postData["status"]) $status = true;
        
        $this->settingsManager->switchDelivery($status);
        echo "success";
    }

    public function settings_updatePickupStatus_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $status = false;
        if($postData["status"]) $status = true;
        
        $this->settingsManager->switchPickup($status);
        echo "success";
    }

    public function settings_sechedule_updateDelivery_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        foreach($postData["days"] as $day) {
            if(strtotime($day["end_time"]) < strtotime($day["start_time"])){
                continue;
            }
            $this->settingsManager->updateDeliverySchedule($day["day"], $day["start_time"], $day["end_time"]);
        }

        echo "success";
    }

    public function settings_schedule_updatePickup_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        foreach($postData["days"] as $day) {
            if(strtotime($day["end_time"]) < strtotime($day["start_time"])){
                continue;
            }
            $this->settingsManager->updatePickupSchedule($day["day"], $day["start_time"], $day["end_time"]);
        }

        echo "success";
    }

    private function validateAuthority(int $requiredAuthority, string $userUUID = NULL) : bool {
        if(!$this->sessionManager->isUserLoggedIn()){
            return false;
        }

        $userAuthority = $this->userManager->getUserAuthorityLevel($userUUID);
        
        return $userAuthority >= $requiredAuthority;
    }
}

?>
