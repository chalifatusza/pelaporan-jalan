<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        define('DB_HOST', 'localhost');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'pelaporan_jalan_surabaya');
        
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>