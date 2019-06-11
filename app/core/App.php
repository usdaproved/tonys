<?php

class App {

    private $controller = "Home";
    private $method = "index";
    private $params = [];

    public function __construct() {
        $url = $this->parseUrl();
        $url = $this->controllerHandler($url);
        $url = $this->methodHandler($url);
        $url = $this->paramsHandler($url);

        call_user_func_array([$this->controller, $this->method], $this->params);
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
        $controllerUrl = APP_ROOT . "/controllers/$passedController.php";

        if(file_exists($controllerUrl)) {
            $this->controller = $passedController;
            unset($newUrl[0]);
        }

        require_once APP_ROOT . "/controllers/$this->controller.php";
        $this->controller = new $this->controller;

        return $newUrl;
    }

    public function methodHandler($url){
        if(!isset($url[1])){
            return;
        }

        $newUrl = $url;
        $passedMethod = $url[1];

        if(method_exists($this->controller, $passedMethod)){
            $this->method = $passedMethod;
            unset($newUrl[1]);
        }

        return $newUrl;
    }

    public function paramsHandler($url){
        $this->params = !empty($url) ? array_values($url) : [];
    }
}

?>
