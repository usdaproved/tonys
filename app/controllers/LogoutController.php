<?php

class LogoutController extends Controller
{
    public function get()
    {
        $this->sessionManager->logout();
        
        $this->redirect("/");
    }
}

?>
