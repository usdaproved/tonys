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

        $controller = $this->getController($url);
        $method = $this->getMethod($controller, $url);

        // TODO(Trystan): If this function fails, like it will when you call a private function.
        // Then have it pull up the 404 not found page.
        call_user_func(array($controller, $method));
    }

    private function parseUrl() : array {
        if(!isset($_GET["url"])) {
            return array();
        }

        $url = $_GET["url"];
        $trimmedUrl = rtrim($url, "/");
        // TODO(Trystan): ensure that this truly leaves no security vulnerabilities.
        $sanitizedUrl = filter_var($trimmedUrl, FILTER_SANITIZE_URL);
        $splitUrl = explode("/", $sanitizedUrl);

        return $splitUrl;
    }

    private function getController(array &$url = NULL) : Controller {
        if(!empty($url)){
            $newUrl = $url;
            $passedController = ucwords($url[0]);
            $passedController = $passedController . "Controller";
            $controllerUrl = APP_ROOT . "/controllers/$passedController.php";

            if(file_exists($controllerUrl)) {
                $passedController;
                unset($url[0]);

                return new $passedController();
            }
        }

        return new HomeController(); // Default controller, the index page.
    }

    private function getMethod(Controller $controller, array &$url = NULL) : string {
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
            return strtolower($_SERVER["REQUEST_METHOD"]);
        }

        return $passedMethod;
    }
}

?>
