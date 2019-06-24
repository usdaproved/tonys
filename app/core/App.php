<?php

class App {

    private $controller = "HomeController";
    private $method = "get";

    public function __construct() {
        $url = $this->parseUrl();
        $this->controllerHandler($url);
        

        call_user_func([$this->controller, $this->getMethod()]);
    }

    public function parseUrl(){
        if(!isset($_GET['url'])) {
            return;
        }

        $url = $_GET['url'];
        $trimmedUrl = rtrim($url, '/');
        // TODO: check how effective this is.
        $sanitizedUrl = filter_var($trimmedUrl, FILTER_SANITIZE_URL);
        $splitUrl = explode('/', $sanitizedUrl);

        return $splitUrl;
    }

    public function controllerHandler($url){
        $newUrl = $url;
        $passedController = ucwords($url[0]);
        $passedController = $passedController . "Controller";
        $controllerUrl = APP_ROOT . "/controllers/$passedController.php";

        if(file_exists($controllerUrl)) {
            $this->controller = $passedController;
        }

        require_once APP_ROOT . "/controllers/$this->controller.php";
        $this->controller = new $this->controller;
    }

    public function getMethod(){
        return strtolower($_SERVER['REQUEST_METHOD']);
    }
}

?>
