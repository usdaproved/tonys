<?php

require_once APP_ROOT . "/controllers/Controller.php";

class LogoutController extends Controller
{
    public function get()
    {
        $this->sessionManager->logout();
        
        header("Location: /");
        exit;
    }
}

?>
