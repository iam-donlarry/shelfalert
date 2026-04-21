<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // In production, these would be environment variables
        $this->host = getenv('DB_HOST') ?: 'db.pxxl.pro';
        $this->port = getenv('DB_PORT') ?: '40587';
        $this->db_name = getenv('DB_NAME') ?: 'pxxldb_mlb03asdd75aa85';
        $this->username = getenv('DB_USER') ?: 'pxxluser_mlb03asdd02e80d';
        $this->password = getenv('DB_PASS') ?: 'd7135e91ab336aab6ef59e5f63dd4597a731ef0b55106016e35e748f3069f56f';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed: " . $exception->getMessage());
        }
        return $this->conn;
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollBack() {
        return $this->conn->rollBack();
    }
}
?>