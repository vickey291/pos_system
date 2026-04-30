<?php
class Database {
    private $host = "localhost";
    private $db_name = "medical_store_pos";
    private $username = "root";
    private $password = "";
    private $port = 3306; // Default MySQL port
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Try with port
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            // Set PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            
            // Uncomment to test connection
            // echo "Connected successfully";
            
        } catch(PDOException $exception) {
            // Try without port specification
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->exec("set names utf8");
            } catch(PDOException $e) {
                // If both fail, show detailed error
                die("Connection error: " . $e->getMessage() . "<br>
                Please check:<br>
                1. MySQL is running in XAMPP Control Panel<br>
                2. Database name is correct: " . $this->db_name . "<br>
                3. Username/password is correct (root with no password)<br>
                4. MySQL is running on port 3306");
            }
        }
        
        return $this->conn;
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>