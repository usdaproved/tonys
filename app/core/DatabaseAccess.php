<?php

class DatabaseAccess{

    private $handler;
    private $statement;

    private $error;

    
    public function __construct(){
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        try {
            $this->handler = new PDO($dsn, DB_USER, DB_PWD, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function beginStatement($sql){
        $this->statement = $this->handler->prepare($sql);
    }

    public function executeStatement(){
        try {
            return $this->statement->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    
    public function getResultSet(){
        return $this->statement->fetchAll();
    }

    public function checkError(){
        return $this->error;
    }
}

?>
