<?php
// backend/config/Database.php

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $host = getenv('DB_HOST');
        $db   = getenv('POSTGRES_DB');
        $user = getenv('POSTGRES_USER');
        $pass = getenv('POSTGRES_PASSWORD');
        $port = 5432;

        $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
        
        try {
            $this->conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['error' => 'Database connection failed securely: ' . $e->getMessage()]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->getConnection();
    }

    public function getConnection() {
        return $this->conn;
    }
}
