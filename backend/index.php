<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AccountController.php';
require_once __DIR__ . '/controllers/PostController.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$requestUri = rtrim($requestUri, '/');

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
	
if ($requestMethod === 'POST' && $requestUri === '/api/forgot-password') {
	$auth = new AccountController();
	$auth->forgotPassword();
	exit;
}

if ($requestMethod === 'POST' && $requestUri === '/api/reset-password') {
    $auth = new AccountController();
    $auth->resetPassword();
    exit;
}

if ($requestMethod === 'POST' && $requestUri === '/api/update-profile') {
    $auth = new AccountController();
    $auth->updateProfile();
    exit;
}

if ($requestMethod === 'GET' && $requestUri === '/api/get-profile') {
    $account = new AccountController();
    $account->getProfile();
    exit;
}

if ($requestMethod === 'GET' && $requestUri === '/api/posts') {
    $auth = new PostController();
    $auth->getPosts();
    exit;
}

if ($requestMethod === 'GET' && $requestUri === '/api/overlays') {
    $dirPath = __DIR__ . '/../frontend/images/overlays/';
    $files = [];
    if (is_dir($dirPath)) {
        $scan = scandir($dirPath);
        foreach ($scan as $file) {
            if ($file !== '.' && $file !== '..' && preg_match('/\.(png|jpg|jpeg|webp)$/i', $file)) {
                $files[] = [
                    'filename' => $file,
                    'web_path' => '/images/overlays/' . $file
                ];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($files);
    exit;
}

header('Content-Type: application/json', true, 404);
echo json_encode(['error' => 'Requested API route target interface resource not found.']);
