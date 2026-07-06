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

if ($requestMethod === 'GET' && $requestUri === '/api/verify') {
    $auth = new AuthController();
    $auth->verify(); 
    exit;
}

if ($requestMethod === 'POST' && $requestUri === '/api/users') {
    $auth = new AuthController();
    $auth->login();
    exit;
}

header('Content-Type: application/json', true, 404);
echo json_encode(['error' => 'Requested API route target interface resource not found.']);
