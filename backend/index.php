<?php
/**
 * --- CAMAGRU SECURE MOCK BACKEND ENTRYPOINT ---
 * Intercepts incoming Nginx FastCGI parameters over HTTPS 
 * and simulates real database JSON responses.
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Extract the endpoint route path
$endpoint = str_replace('/api', '', parse_url($request_uri, PHP_URL_PATH));

// 🔐 ROUTE: Mock Login Authentication Matrix
if ($endpoint === '/users' && $request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!empty($input['username']) && !empty($input['password'])) {
        echo json_encode([
            "token" => "mock_secure_jwt_token_over_https",
            "username" => $input['username']
        ]);
        exit;
    }
    http_response_code(400);
    echo json_encode(["error" => "Invalid user credentials model structures."]);
    exit;
}

// 📸 ROUTE: Mock Gallery Stream Feed
if ($endpoint === '/posts' && $request_method === 'GET') {
    echo json_encode([
        ["id" => 201, "image_path" => "https://picsum.photos/640/480?random=11"],
        ["id" => 202, "image_path" => "https://picsum.photos/640/480?random=12"],
        ["id" => 203, "image_path" => "https://picsum.photos/640/480?random=13"]
    ]);
    exit;
}

// Default fallback for unhandled studio endpoints
echo json_encode([]);
