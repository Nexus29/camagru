<?php
$host = 'database';
$port = '5432';
$db   = 'camagru_db';
$user = 'camagru_user';
$pass = 'camagru_pass';

try {
	$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Database Connection Error: " . $e->getMessage());
}