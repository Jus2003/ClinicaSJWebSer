<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $database = 'sistema_clinica';
    private $username = 'root';
    private $password = ''; 
    private $connection;

    public function getConnection() {
        $this->connection = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->database . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            throw new \Exception("Error de conexión: " . $e->getMessage());
        }
        
        return $this->connection;
    }
}
?>