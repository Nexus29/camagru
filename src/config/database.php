<?php
// src/config/database.php
$host = 'database'; // Must match the service name in docker-compose.yml
$port = '5432';
$db   = 'camagru_db';
$user = 'camagru_user';
$pass = 'camagru_password';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the driver is installed but credentials fail, this shows us why
    die("Database Connection Error: " . $e->getMessage());
}