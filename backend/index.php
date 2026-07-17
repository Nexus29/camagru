<?php
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($filePath)) {
        return false;
    }
}

require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AccountController.php';
require_once __DIR__ . '/controllers/PostController.php';
require_once __DIR__ . '/controllers/InteractionController.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = rtrim($requestUri, '/');

// backend/index.php

// 1. Send CORS response headers
header("Access-Control-Allow-Origin: *"); // For production, replace '*' with your specific Vercel URL
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// 2. Respond instantly to preflight OPTIONS requests before processing routes
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// 🟢 PUBLIC ROUTES (Unprotected)
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
	
if ($requestMethod === 'POST' && str_ends_with($requestUri, '/api/forgot-password')) {
    $auth = new AuthController();
    $auth->forgotPassword();
    exit;
}

// 2. Reset Password View/Route (When the user clicks the email link)
if ($requestMethod === 'GET' && str_ends_with($requestUri, '/api/reset-password')) {
    $auth = new AuthController();
    $auth->showResetForm();
    exit;
}

// 3. Reset Password Action Endpoint (When the user submits their new password form)
if ($requestMethod === 'POST' && str_ends_with($requestUri, '/api/reset-password')) {
    $auth = new AuthController();
    $auth->resetPassword();
    exit;
}

if ($requestMethod === 'GET' && $requestUri === '/api/posts') {
    $auth = new PostController();
    
    // Check if the user is explicitly requesting their own mini-gallery feed
    if (isset($_GET['filter']) && $_GET['filter'] === 'mine') {
        $userId = AuthMiddleware::authenticate(); // 🛡️ Run the active guard to get user context
        $auth->getPosts($userId); // Pass the validated ID context down
    } else {
        $auth->getPosts(null); // Return the global public stream to everyone
    }
    exit;
}

if ($requestMethod === 'GET' && $requestUri === '/api/overlays') {
    $dirPath = __DIR__ . 'uploads/overlays/';
    $files = [];
    if (is_dir($dirPath)) {
        $scan = scandir($dirPath);
        foreach ($scan as $file) {
            if ($file !== '.' && $file !== '..' && preg_match('/\.(png|jpg|jpeg|webp)$/i', $file)) {
                $files[] = [
                    'filename' => $file,
                    'web_path' => '/uploads/overlays/' . $file
                ];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($files);
    exit;
}


// 🔒 PROTECTED API ROUTES (Intercepted via Middleware Guard)

if ($requestMethod === 'POST' && $requestUri === '/api/posts') {
    $userId = AuthMiddleware::authenticate(); // 🛡️ Active Guard
    $posts = new PostController();
    $posts->createPost($userId);
    exit;
}

if ($requestMethod === 'POST' && $requestUri === '/api/posts/like') {
    $userId = AuthMiddleware::authenticate();
    $interaction = new InteractionController();
    $interaction->toggleLike($userId);
    exit;
}

if ($requestMethod === 'POST' && $requestUri === '/api/posts/comment') {
    $userId = AuthMiddleware::authenticate();
    $interaction = new InteractionController();
    $interaction->addComment($userId);
    exit;
}

if ($requestMethod === 'POST' && $requestUri === '/api/posts/delete') {
    $userId = AuthMiddleware::authenticate();
    $posts = new PostController();
    $posts->deletePost($userId);
    exit;
}

if ($requestMethod === 'POST' && $requestUri === '/api/update-profile') {
    $userId = AuthMiddleware::authenticate(); // 🛡️ Active Guard
    $account = new AccountController();
    $account->updateProfile($userId);
    exit;
}

if ($requestMethod === 'GET' && $requestUri === '/api/get-profile') {
    $userId = AuthMiddleware::authenticate(); // 🛡️ Active Guard
    $account = new AccountController();
    $account->getProfile($userId);
    exit;
}

header('Content-Type: application/json', true, 404);
echo json_encode(['error' => 'Requested API route target interface resource not found.']);
