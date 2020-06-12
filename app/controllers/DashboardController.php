<?php

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
        if($this->orderStorage["order_type"] != IN_RESTAURANT){
            $this->redirect("/Order/submit");
        }
        if(count($this->orderStorage["line_items"]) === 0){
            $this->redirect("/Order");
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
        if($cartUUID === NULL || $this->orderStorage["order_type"] != IN_RESTAURANT){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);

        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $customerUUID = UUID::arrangedStringToOrderedBytes($postData["user_uuid"]);

        $this->orderManager->assignUserToOrder($cartUUID, $customerUUID);
        $this->orderManager->submitOrder($cartUUID);
        
        $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Order successfully submitted.");
        echo "success";
    }

    public function menu_get() : void {
        $this->pageTitle = "Dashboard - Menu";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/Dashboard");
        }
        
        $this->menuStorage = $this->menuManager->getEntireMenu();
        $this->user = $this->userManager->getUserInfo($userUUID);
        
        require_once APP_ROOT . "/views/dashboard/dashboard-menu-item-select-page.php";
    }

    public function menu_categories_get() : void {
        $this->pageTitle = "Dashboard - Menu Categories";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/Dashboard");
        }

        $this->menuStorage = $this->menuManager->getCategories();
        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/dashboard/dashboard-menu-categories-edit-page.php";
    }

    public function menu_categories_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
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

    public function menu_additions_get() : void {
        $this->pageTitle = "Dashboard - Menu Additions";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/Dashboard");
        }

        $this->menuStorage = $this->menuManager->getAllAdditions();
        $this->user = $this->userManager->getUserInfo($userUUID);

        require_once APP_ROOT . "/views/dashboard/dashboard-menu-additions-edit-page.php";
    }

    public function menu_additions_post() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/Dashboard");
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        $this->menuManager->createAddition($_POST["name"], $_POST["price"] * 100);

        // TODO(trystan): Probably should push a message here.
        $this->redirect("/Dashboard/menu/additions");
    }

    public function menu_item_get() : void {
        $this->pageTitle = "Dashboard - Menu Item";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/Dashboard");
        }

        if(isset($_GET["id"])){
            $this->menuStorage = $this->menuManager->getItemInfo($_GET["id"]);
            $this->menuStorage["categories"] = $this->menuManager->getCategories();
            $this->menuStorage["all_additions"] = $this->menuManager->getAllAdditions();

            // Cull out additions that are already associated with this item.
            foreach($this->menuStorage["additions"] as $addition){
                $index = array_search($addition, $this->menuStorage["all_additions"]);

                if($index !== false){
                    unset($this->menuStorage["all_additions"][$index]);
                }
            }
            
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
            $this->redirect("/Dashboard");
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        $activeState = isset($_POST["active"]) ? 1 : 0;

        $isNewItem = ((int)$_POST["id"] === 0);

        if($isNewItem){
            $this->menuManager->createMenuItem($activeState, $_POST["category"],
                                               $_POST["name"], $_POST["price"] * 100,
                                               $_POST["description"]);
        } else {
            $this->menuManager->updateMenuItem($_POST["id"], $activeState, $_POST["category"],
                                               $_POST["name"], $_POST["price"] * 100,
                                               $_POST["description"]);
        }

        $encodedName = $this->escapeForHTML($_POST["name"]);
        $itemStatus = $isNewItem ? "created" : "updated";
        $this->sessionManager->pushOneTimeMessage(USER_SUCCESS, "$encodedName successfully $itemStatus.");

        $this->redirect("/Dashboard/menu");
    }

    public function employees_get() : void {
        $this->pageTitle = "Dashboard - Employees";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/Dashboard");
        }
        
        $this->user = $this->userManager->getUserInfo($userUUID);
        $this->userStorage = $this->userManager->getAllEmployees();

        require_once APP_ROOT . "/views/dashboard/dashboard-employees-edit-page.php";
    }

    public function settings_get() : void {
        $this->pageTitle = "Dashboard - Settings";
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            $this->redirect("/Dashboard");
        }

        $this->user = $this->userManager->getUserInfo($userUUID);

        $settings = [];
        $settings["delivery_schedule"] = $this->settingsManager->getDeliverySchedule();
        $settings["pickup_schedule"] = $this->settingsManager->getPickupSchedule();
        $settings["delivery_on"] = $this->orderManager->isDeliveryOn();
        $settings["pickup_on"] = $this->orderManager->isPickupOn();
        $week = array_keys(DAY_TO_INT);

        require_once APP_ROOT . "/views/dashboard/dashboard-settings-page.php";
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
            $order = $this->orderManager->getOrderByUUID($uuid["uuid"]);
            $order["uuid"] = UUID::orderedBytesToArrangedString($order["uuid"]);
            $order["user_uuid"] = UUID::orderedBytesToArrangedString($order["user_uuid"]);
            $order["user_info"] = $this->userManager->getUserInfo($uuid["user_uuid"]);

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

    // TODO(Trystan): Update the c code to reflect the switch to orders_active
    // Leaving for now so as to not break anything.
    // TODO(Trystan): This function needs a major relook. Lots has changed.
    public function orders_printerStream_get(){
        $userUUID = $this->getUserUUID();
        if($this->userManager->getUserAuthorityLevel($userUUID) != PRINTER){
            echo "User Access Denied" . PHP_EOL;
            exit;
        }

        header("Content-Type: text/event-stream");

        // The printer program sends "NULL" if no date file found.
        $lastReceived = "NULL";
        if(isset($_GET["lastReceived"])){
            $lastReceived = $_GET["lastReceived"];
        }

        $orders = array();
        if($lastReceived === "NULL"){
            $orders = $this->orderManager->getAllActiveOrders();
        } else {
            $orders = $this->orderManager->getActiveOrdersAfterDate($lastReceived);
        }

        while (true) {
            if(!empty($orders)){
                $lastReceived = $orders[count($orders) - 1]["date"];
            }
            // TODO(trystan): Find out the max char length per line of the printer,
            // ensure we don't go over that.
            echo PRINTER_DELIMITER;
            foreach($orders as $order){
                // Maybe print some over arching order info.
                echo "ORDER " . UUID::orderedBytesToArrangedString($order["uuid"]) . PHP_EOL;
                $customer = $this->userManager->getUserInfo($order["user_uuid"]);
                // Note(Trystan): We may want to print the address on this receipt.
                echo "NAME " . $customer["name_first"] . " " . $customer["name_last"] . PHP_EOL;
            
                foreach($order["line_items"] as $lineItem){
                    echo $lineItem["name"] .  " - " . $lineItem["quantity"]  . PHP_EOL;
                    foreach($lineItem["choices"] as $choice){
                        echo " - " . $choice["name"] . PHP_EOL;
                        foreach($choice["options"] as $option){
                            echo "    - " . $option["name"] . PHP_EOL;
                        }
                    }
                    if(!empty($lineItem["additions"])){
                        echo " - Additions" . PHP_EOL;
                    }
                    foreach($lineItem["additions"] as $addition){
                        echo "    - " . $addition["name"] . PHP_EOL;
                    }
                    if(!empty($lineItem["comment"])){
                        echo "COMMENT: " . $lineItem["comment"] . PHP_EOL;
                    }
                    echo PHP_EOL;
                }
                // Give a little padding between orders.
                echo PHP_EOL;
            }
            echo PRINTER_DELIMITER;
            echo "TIMESTAMP " . $lastReceived . PHP_EOL;

            if ( connection_aborted() ) break;
            // flush the output buffer and send echoed messages to the browser
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
            // TODO(trystan): Decide what sleep interval we want to set here.
            sleep(5);

            // TODO(Trystan): Two things happen here.
            // if we keep these two lines in this order:
            // We will double print orders on start up.
            // However if we get the date before we get the orders,
            // we will never get new orders because the date will always be after
            // an order has been submitted.
            // So we actually need "now" to be now - sleep interval.
            $orders = $this->orderManager->getActiveOrdersAfterDate($lastReceived);
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
    }
    
    /**
     * Updates the all the values and positions of the choice groups and options.
     */
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

        $groupPosition = 1;
        foreach($choiceGroups as $groupID => $choiceGroup){
            $groupID = explode("-", $groupID)[0];
            $groupName = $choiceGroup["group-data"]["name"];
            // TODO: Check that picks does not exceed number of choices.
            $groupMinPicks = $choiceGroup["group-data"]["min-picks"];
            // TODO: Check that max picks greater than min.
            $groupMaxPicks = $choiceGroup["group-data"]["max-picks"];

            $this->menuManager->updateChoiceGroup($groupID, $groupName,
                                                  $groupMinPicks, $groupMaxPicks);
            $this->menuManager->updateChoiceGroupPosition($groupID, $groupPosition);

            unset($choiceGroup["group-data"]);
            
            $choicePosition = 1;
            foreach($choiceGroup as $choiceID => $choice){
                $choiceID = explode("-", $choiceID)[0];
                $choiceName = $choice["name"];
                $choicePrice = $choice["price"] * 100;

                $this->menuManager->updateChoiceOption($choiceID, $choiceName, $choicePrice);
                $this->menuManager->updateChoiceOptionPosition($choiceID, $choicePosition);
                
                $choicePosition++;
            }
            $groupPosition++;
        }

        echo "success";
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

    public function menu_item_addAddition_post() : void {
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
        $additionID = $postData["addition-id"];

        $this->menuManager->addAdditionToItem($itemID, $additionID);
        
        echo "success";
    }
    
    public function menu_item_updateAdditionPositions_post() : void {
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

        $ids = $postData["ids"];
        $itemID = $postData["itemID"];

        $position = 1;
        foreach($ids as $id){
            $id = explode("-", $id)[0];
            $this->menuManager->updateItemAdditionPosition($itemID, $id, $position);
            $position++;
        }
    }

    public function menu_item_removeAddition_post() : void {
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
        $additionID = $postData["addition-id"];

        $this->menuManager->removeAdditionFromItem($itemID, $additionID);

        echo "success";
    }

    public function menu_additions_isLinkedToItem_get() : void {
        $userUUID = $this->getUserUUID();
        if(!$this->validateAuthority(ADMIN, $userUUID)){
            echo "fail";
            exit;
        }

        $isLinked = $this->menuManager->isAdditionLinkedToItem($_GET["id"]);

        
        
        echo ($isLinked) ? "true" : "false";
    }

    public function menu_additions_updateAddition_post() : void {
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

        $additionID = $postData["addition-id"];
        $name = $postData["name"];
        $price = $postData["price"] * 100;

        $this->menuManager->updateAddition($additionID, $name, $price);

        echo "success";
    }

    public function menu_additions_removeAddition_post() : void {
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

        $additionID = $postData["addition-id"];

        $this->menuManager->removeAdditionEntirely($additionID);

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

    public function settings_updateDeliverySchedule_post() : void {
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

    public function settings_updatePickupSchedule_post() : void {
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

    private function searchUserComponent(bool $getRegisteredOnly) : string {
        $modifiers = "";
        if($getRegisteredOnly) $modifiers = "checked disabled hidden";
        $string = "";
        $string .= "<p>Use as many filters as necessary.</p>";
        if($getRegisteredOnly) $string .= "<p>Note: registered users only.</p>";
        $string .= "<div id='search-filters'>";
        $string .= "<label for='name_first'>First Name: </label>";
        $string .= "<input type='text'  id='name_first' autocomplete='off'>";
        $string .= "<label for='name_last'>Last Name: </label>";
        $string .= "<input type='text'  id='name_last' autocomplete='off'>";
        $string .= "<label for='email'>Email: </label>";
        $string .= "<input type='email' id='email'  autocomplete='off'>";
        $string .= "<label for='phone_number'>Phone Number: </label>";
        $string .= "<input type='text' id='phone_number' autocomplete='off'>";
        if(!$getRegisteredOnly) $string .= "<label for='registered-only'>Registered Users Only:</label>";
        $string .= "<input type='checkbox' id='registered-only' " . $modifiers . ">";
        $string .= "</div>";
        $string .= "<input type='submit' id='user-search-button' value='Search'>";
        $string .= "<div id='user-table' class='orders-container'>";
        $string .= "</div>";
        return $string;
    }
    
}

?>
