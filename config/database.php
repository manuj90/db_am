<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'db_multimedia_agency';
    private $username = 'root';
    private $password = '';
    private $pdo;
    
    public function connect() {
        try {
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname}", 
                                $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("SET NAMES utf8mb4");
            return $this->pdo;
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}
?>