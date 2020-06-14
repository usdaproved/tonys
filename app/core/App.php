<?php

class App {

    // The url follows this format:
    // /controllerFoo/functionBar/.../NFunctionSegmentBaz
    //
    // Example: /Dashboard/menu/item
    //
    // Which ends up getting parsed to DashboardController->menu_item_get()
    // Where DashboardController->menu_get() is also a valid function.

    public function __construct() {
        $url = $this->parseUrl();

        $pageFound = true;
        
        $controller = $this->getController($url);
        $method = $this->getMethod($controller, $url, $pageFound);

        if($pageFound){
            $controller->$method();
        } else {
            Controller::display404Page();
        }
    }

    private function parseUrl() : array {
        $url = $_SERVER["REQUEST_URI"];

        $paramStartIndex = strpos($url, "?");
        if($paramStartIndex !== false) $url = substr($url, 0, $paramStartIndex);
        
        $url = rtrim($url, "/");
        $url = ltrim($url, "/");
        if(empty($url)) return array();
        // TODO(Trystan): ensure that this truly leaves no security vulnerabilities.
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $urlArray = explode("/", $url);

        return $urlArray;
    }

    private function getController(array &$url = NULL) : Controller {
        if(!empty($url)){
            $newUrl = $url;
            $passedController = ucwords($url[0]);
            $passedController = $passedController . "Controller";
            $controllerUrl = APP_ROOT . "/controllers/$passedController.php";

            if(file_exists($controllerUrl)) {
                unset($url[0]);

                return new $passedController();
            }
        }

        return new HomeController(); // An empty url equals the home page.  
    }

    private function getMethod(Controller $controller, array &$url = NULL, bool &$pageFound) : string {
        $requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);
        
        $passedMethod = $requestMethod;

        // The remaining url at this point refers to functions within some contoller.
        if(!empty($url)){
            $controllerFunction = "";
            foreach($url as $functionSegment){
                $controllerFunction = $controllerFunction . $functionSegment . "_";
            }
            $passedMethod = strtolower($controllerFunction) . $requestMethod;
        }
        
        if(!method_exists($controller, $passedMethod)){
            $pageFound = false;
            return strtolower($_SERVER["REQUEST_METHOD"]);
        }

        return $passedMethod;
    }
}

?>
