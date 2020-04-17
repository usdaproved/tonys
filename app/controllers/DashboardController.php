<?php

class DashboardController extends Controller{

    private Order $orderManager;
    private Menu $menuManager;

    public $menuStorage;
    public $orderStorage;
    public $employeeStorage;
    public $userStorage;

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

    // Tentative, not sure if we need this.
    // But it would be a way to go to a specific customer and view all their actvity.
    // It would show total spent and list all orders made by customer.
    // Maybe even something like amount spent in the last 30 days.
    public function customers_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }

        if(!isset($_GET["id"])){
            $this->redirect("/Dashboard/orders/search");
        }

        $this->userStorage = $this->userManager->getUserInfoByID($_GET["id"]);
        $this->orderStorage = $this->orderManager->getAllOrdersByUserID($_GET["id"]);

        require_once APP_ROOT . "/views/dashboard/dashboard-customers-page.php";
    }

    public function customers_search_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }

        require_once APP_ROOT . "/views/dashboard/dashboard-customers-search-page.php";
    }

    public function orders_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }

        if(!isset($_GET["id"])){
            $this->redirect("/Dashboard/orders/search");
        }

        $this->orderStorage = $this->orderManager->getOrderByID($_GET["id"]);
        $this->orderStorage["user_info"] = $this->userManager->getUserInfoByID($this->orderStorage["user_id"]);
        // TODO(Trystan): We can grab any specifics from the respective processors if data is necessary.
        $this->orderStorage["payments"] = $this->orderManager->getPayments($_GET["id"]);
        $cost = $this->orderManager->getCost($_GET["id"]);
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $payment = $this->orderManager->getPaymentByID($postData["payment_id"]);
        $refundTotal = $this->orderManager->getRefundTotal($postData["payment_id"]);

        if(($payment["amount"] - $refundTotal) < $postData["amount"]){
            echo json_encode(NULL);
            exit;
        }

        // TODO(Trystan): Paypal refunds. And then whenever we add Apple Pay.
        switch($payment["method"]){
        case PAYMENT_STRIPE:
            \Stripe\Stripe::setApiKey(STRIPE_PRIVATE_KEY);
            $paymentIntentID = $this->getStripeToken($postData["order_id"]);
            \Stripe\Refund::create(['amount' => $postData["amount"], 'payment_intent' => $paymentIntentID]);
            $this->orderManager->submitRefund($postData["payment_id"], $postData["amount"]);
            break;
        case PAYMENT_CASH:
            $this->orderManager->submitRefund($postData["payment_id"], $postData["amount"]);
            break;
        }

        echo json_encode("success");
    }

    public function orders_active_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-active-page.php";
    }

    public function orders_search_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-search-page.php";
    }
    
    public function orders_submit_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            $this->redirect("/");
        }

        $cartID = $this->orderManager->getCartID($userID);
        $this->orderStorage = $this->orderManager->getOrderByID($cartID);
        if($this->orderStorage["order_type"] != IN_RESTAURANT){
            $this->redirect("/Order/submit");
        }
        if(count($this->orderStorage["line_items"]) === 0){
            $this->redirect("/Order");
        }

        require_once APP_ROOT . "/views/dashboard/dashboard-orders-submit-page.php";
    }

    // Technically called by javascript. Just wanted to keep it close to the other.
    public function orders_submit_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo "fail";
            exit;
        }

        $cartID = $this->orderManager->getCartID($userID);
        $this->orderStorage = $this->orderManager->getOrderByID($cartID);
        if($cartID === NULL || $this->orderStorage["order_type"] != IN_RESTAURANT){
            echo "fail";
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);

        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo "fail";
            exit;
        }

        $customerID = $this->userManager->getUserIDByEmail($postData["customer_email"]);

        $this->orderManager->assignUserToOrder($cartID, $customerID);
        $this->orderManager->submitOrder($cartID);
        
        $this->sessionManager->pushOneTimeMessage(USER_ALERT, "Order successfully submitted.");
        echo "submitted";
    }

    public function menu_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }
        
        $this->menuStorage = $this->menuManager->getEntireMenu();

        require_once APP_ROOT . "/views/dashboard/dashboard-menu-item-select-page.php";
    }

    public function menu_categories_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
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

    public function menu_additions_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }

        $this->menuStorage = $this->menuManager->getAllAdditions();

        require_once APP_ROOT . "/views/dashboard/dashboard-menu-additions-edit-page.php";
    }

    public function menu_additions_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }
        if(!$this->sessionManager->validateCSRFToken($_POST["CSRFToken"])){
            $this->redirect("/");
        }

        $this->menuManager->createAddition($_POST["name"], $_POST["price"]);

        // TODO(trystan): Probably should push a message here.
        $this->redirect("/Dashboard/menu/additions");
    }

    public function menu_item_get() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            $this->redirect("/Dashboard");
        }

        if(isset($_GET["id"])){
            $this->menuStorage = $this->menuManager->getItemInfo($_GET["id"]);
            $this->menuStorage["categories"] = $this->menuManager->getCategories();
            $this->menuStorage["all_additions"] = $this->menuManager->getAllAdditions();

            // Cull out additions that are already associated with this item.
            foreach((array)$this->menuStorage["additions"] as $addition){
                $index = array_search($addition, $this->menuStorage["all_additions"]);

                if($index !== false){
                    unset($this->menuStorage["all_additions"][$index]);
                }
            }

            require_once APP_ROOT . "/views/dashboard/dashboard-menu-item-edit-page.php";
        } else {
            $this->redirect("/Dashboard/menu");
        }
    }

    // TODO(Trystan): Validate inputs. Especially ensure price is a number within range.
    // TODO(Trystan): Update all prices to be taken in 0.00 format, but then multiply by 100 to store in database.
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

    public function searchOrders_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
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

        $ids = $this->orderManager->getOrdersMatchingFilters($postData["start_date"], $postData["end_date"],
                                                                $postData["start_amount"], $postData["end_amount"],
                                                                $postData["order_type"], $postData["first_name"],
                                                                $postData["last_name"], $postData["email"],
                                                                $postData["phone_number"]);

        $orders = [];
        foreach($ids as $id){
            $order = $this->orderManager->getOrderByID($id["id"]);
            $order["user_info"] = $this->userManager->getUserInfoByID($id["user_id"]);

            $orders[] = $order;
        }
        
        echo json_encode($orders);
    }

    public function searchUsers_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
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
                                                             $postData["email"], $postData["phone_number"]);

        echo json_encode($users);
    }

    public function orders_active_getOrders_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $lastReceived = "NULL";
        if(isset($postData["last_received"])){
            $lastReceived = $postData["last_received"];
        }

        $orders = array();
        if($lastReceived === "NULL"){
            $orders = $this->orderManager->getAllActiveOrders();
        } else {
            $orders = $this->orderManager->getActiveOrdersAfterDate($lastReceived);
        }

        $numberOfOrders = count($orders);
        for($i = 0; $i < $numberOfOrders; $i++){
            if($orders[$i]["user_id"] != NULL){
                $orders[$i]["user_info"] = $this->userManager->getUserInfoByID($orders[$i]["user_id"]);
            }
            if($orders[$i]["order_type"] == DELIVERY){
                $orders[$i]["address"] = $this->orderManager->getDeliveryAddress($orders[$i]["id"]);
            }
            $orders[$i]["is_paid"] = $this->orderManager->isPaid($orders[$i]["id"]);
        }

        echo json_encode($orders);
    }

    public function orders_active_getStatus_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo json_encode(NULL);
            exit;
        }

        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        echo json_encode($this->orderManager->getActiveOrderStatus());
    }

    public function orders_active_updateStatus_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
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

        foreach($orders["status"] as $orderID){
            $order = $this->orderManager->getOrderByID($orderID);

            $index = array_search((int)$order["status"], ORDER_STATUS_FLOW[$order["order_type"]]);
            $updatedStatus = ORDER_STATUS_FLOW[$order["order_type"]][$index + 1];

            $this->orderManager->updateOrderStatus($orderID, $updatedStatus);
            $updatedInfo[$orderID] = $updatedStatus;
        }
        
        echo json_encode($updatedInfo);
    }

    public function orders_active_checkPayment_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
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
        
        foreach($orders["id"] as $orderID){
            $isPaid[$orderID] = $this->orderManager->isPaid($orderID);
        }

        echo json_encode($isPaid);
    }

    // Returns both the order_cost and order_payment
    public function orders_active_getPaymentInfo_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo json_encode(NULL);
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $orderID = $postData["id"];

        $result = [];
        $result["cost"] = $this->orderManager->getCost($orderID);
        $result["payments"] = $this->orderManager->getPayments($orderID);

        echo json_encode($result);
    }

    public function orders_active_submitPayment_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(EMPLOYEE, $userID)){
            echo json_encode(NULL);
            exit;
        }
        
        $json = file_get_contents("php://input");
        $postData = json_decode($json, true);
        
        if(!$this->sessionManager->validateCSRFToken($postData["CSRFToken"])){
            echo json_encode(NULL);
            exit;
        }

        $orderID = $postData["id"];
        $amount = (int)($postData["amount"] * 100);
        $method = $postData["method"];

        $this->orderManager->submitPayment($orderID, $amount, $method);
        $this->orderManager->updateOrderStatus($orderID, COMPLETE);
    }

    // TODO(Trystan): Update the c code to reflect the switch to orders_active
    // Leaving for now so as to not break anything.
    public function orders_printerStream_get(){
        $userID = $this->getUserID();
        if($this->userManager->getUserAuthorityLevelByID($userID) != PRINTER){
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
                echo "ORDER " . $order["id"] . PHP_EOL;
                $customer = $this->userManager->getUserInfoByID($order["user_id"]);
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
    
    /**
     * Updates the all the values and positions of the choice groups and options.
     */
    public function menu_item_updateChoices_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
                $choicePrice = $choice["price"];

                $this->menuManager->updateChoiceOption($choiceID, $choiceName, $choicePrice);
                $this->menuManager->updateChoiceOptionPosition($choiceID, $choicePosition);
                
                $choicePosition++;
            }
            $groupPosition++;
        }

        echo "success";
    }

    public function menu_item_addChoiceGroup_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
            echo "fail";
            exit;
        }

        $isLinked = $this->menuManager->isAdditionLinkedToItem($_GET["id"]);

        
        
        echo ($isLinked) ? "true" : "false";
    }

    public function menu_additions_updateAddition_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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
        $price = $postData["price"];

        $this->menuManager->updateAddition($additionID, $name, $price);

        echo "success";
    }

    public function menu_additions_removeAddition_post() : void {
        $userID = $this->getUserID();
        if(!$this->validateAuthority(ADMIN, $userID)){
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

    private function validateAuthority(int $requiredAuthority, int $userID = NULL) : bool {
        if(!$this->sessionManager->isUserLoggedIn()){
            return false;
        }

        $userAuthority = $this->userManager->getUserAuthorityLevelByID($userID);
        
        return $userAuthority >= $requiredAuthority;
    }
    
}

?>
