<?php

class Database{

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

    // Note(Trystan): There's no way to insert this id in a table and return it at the same time.
    // postgreSQL has a returning statement that would do it.
    // I could generate my own first, but I would prefer the database do it
    // for important things, users, orders, line items.
    public function generateUUID() : string {
        $sql = "SELECT uuid_to_bin(uuid(), TRUE) as uuid;";

        $this->beginStatement($sql);
        $this->executeStatement();
        
        return  $this->getResult()["uuid"];
    }

    public function beginStatement($sql) : void {
        $this->statement = $this->handler->prepare($sql);
    }

    public function bindValueToStatement($parameter, $value) : void {
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

    public function executeStatement() {
        try {
            return $this->statement->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    // Gets a single row.
    public function getResult() {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    // Gets all rows.
    public function getResultSet() {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rowCount() : int {
        return $this->statement->rowCount();
    }


    // NOTE(Trystan): php defines this as returning a string.
    // Though I only use it with index numbers.
    public function lastInsertID() : string {
        return $this->handler->lastInsertId();
    }

    public function checkError(){
        return $this->error;
    }
}

?>
