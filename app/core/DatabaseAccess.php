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

    public function bindValueToStatement($parameter, $value){
        $type = PDO::PARAM_STR; // default type.
        switch(true){
        case is_int($value):
            $type = PDO::PARAM_INT;
            break;
        case is_bool($value):
            $type = PDO::PARAM_BOOL;
            break;
        case is_null($value):
            $type = PDO::PARAM_NULL;
            break;
        }

        $this->statement->bindValue($parameter, $value, $type);
    }

    public function executeStatement(){
        try {
            return $this->statement->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function getResult(){
        return $this->statement->fetch();
    }
    
    public function getResultSet(){
        return $this->statement->fetchAll();
    }

    public function lastInsertID(){
        return $this->handler->lastInsertId();
    }

    public function checkError(){
        return $this->error;
    }
}

?>
