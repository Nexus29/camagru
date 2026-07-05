<?php
require_once __DIR__ . '/controllers/AuthController.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Core RESTful routing table handling data operations
if ($requestMethod === 'POST' && $requestUri === '/api/register') {
    $auth = new AuthController();
    $auth->register();
    exit;
}

// Fallback error bucket for unregistered operations
header('Content-Type: application/json', true, 404);
echo json_encode(['error' => 'Requested API route target interface resource not found.']);
