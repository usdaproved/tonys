<?php

class App {

    // The url follows this format:
    // /controllerFoo/functionBar/.../NFunctionSegmentBaz
    //
    // Example: /Dashboard/menu/item
    //
    // Which ends up getting parsed to DashboardController->menu_item_get()
    // Where DashboardController->menu_get() is also a valid function.

    private $controller = "HomeController";

    public function __construct() {
        $url = $this->parseUrl();

        $this->controller = $this->getController($url);
        // Unsetting the controller allows us to iterate over the function segments
        unset($url[0]);

        call_user_func([$this->controller, $this->getMethod($url)]);
    }

    private function parseUrl() : ?array {
        if(!isset($_GET["url"])) {
            return NULL;
        }

        $url = $_GET["url"];
        $trimmedUrl = rtrim($url, "/");
        $sanitizedUrl = filter_var($trimmedUrl, FILTER_SANITIZE_URL);
        $splitUrl = explode("/", $sanitizedUrl);

        return $splitUrl;
    }

    private function getController(array $url = NULL) : object {
        if(!is_null($url)){
            $newUrl = $url;
            $passedController = ucwords($url[0]);
            $passedController = $passedController . "Controller";
            $controllerUrl = APP_ROOT . "/controllers/$passedController.php";

            if(file_exists($controllerUrl)) {
                $this->controller = $passedController;
            }
        }

        require_once APP_ROOT . "/controllers/$this->controller.php";
        return new $this->controller();
    }

    private function getMethod(array $url = NULL) : string {
        $requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);
        
        $passedMethod = $requestMethod;

        // The remaining url at this point refers to functions within the contoller.
        if(isset($url)){
            $controllerFunction = "";
            foreach($url as $functionSegment){
                $controllerFunction = $controllerFunction . $functionSegment . "_";
            }
            $passedMethod = strtolower($controllerFunction) . $requestMethod;
        }
        
        if(!method_exists($this->controller, $passedMethod)){
            return strtolower($_SERVER["REQUEST_METHOD"]);
        }

        return $passedMethod;
    }
}

?>
