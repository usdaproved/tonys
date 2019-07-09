<?php

class App {

    private $controller = "HomeController";
    private $method = "get";

    public function __construct() {
        $url = $this->parseUrl();
        $this->controllerHandler($url);
        

        call_user_func([$this->controller, $this->getMethod($url)]);
    }

    public function parseUrl(){
        if(!isset($_GET['url'])) {
            return NULL;
        }

        $url = $_GET['url'];
        $trimmedUrl = rtrim($url, '/');
        $sanitizedUrl = filter_var($trimmedUrl, FILTER_SANITIZE_URL);
        $splitUrl = explode('/', $sanitizedUrl);

        return $splitUrl;
    }

    public function controllerHandler($url){
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
        $this->controller = new $this->controller();
    }

    public function getMethod($url){
        $requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);
        
        $passedMethod = $requestMethod;
        
        if(isset($url[1])){
            $passedMethod = strtolower($url[1]) . "_" . $requestMethod;
        }
        
        if(!method_exists($this->controller, $passedMethod)){
            return strtolower($_SERVER['REQUEST_METHOD']);
        }

        return $passedMethod;
    }
}

?>
