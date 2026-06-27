<?php
// src/test_controllers.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/StudioController.php';
require_once __DIR__ . '/controllers/GalleryController.php';

echo "<h1>🧪 Camagru Controller Testing Suite</h1>";

// Initialize Controllers
$auth = new AuthController($pdo);
$studio = new StudioController($pdo);
$gallery = new GalleryController($pdo);

// ==========================================
// TEST 1: User Registration
// ==========================================
echo "<h3>Test 1: Registration</h3>";
$regResult = $auth->register("testuser_" . time(), "test_" . time() . "@example.com", "securePassword123");
echo "Result: " . ($regResult['success'] ? "✅ " : "❌ ") . $regResult['message'] . "<br>";

// ==========================================
// TEST 2: User Login (Using standard credentials)
// ==========================================
echo "<h3>Test 2: Login</h3>";
// Force activate a test user directly in the database for login evaluation
$pdo->exec("UPDATE users SET is_active = TRUE WHERE username = 'camagru_user';");
// Note: If 'camagru_user' doesn't exist yet, let's create a known test user
$auth->register("demo_user", "demo@example.com", "demo_password_123");
$pdo->exec("UPDATE users SET is_active = TRUE WHERE username = 'demo_user';");

$loginResult = $auth->login("demo_user", "demo_password_123");
echo "Result: " . ($loginResult['success'] ? "✅ " : "❌ ") . $loginResult['message'] . "<br>";

// ==========================================
// TEST 3: Studio Snapshot Handling (Mocking a 1x1 PNG pixel Base64)
// ==========================================
echo "<h3>Test 3: Studio Composite Composition</h3>";
// Mock clear 1x1 Base64 image data string
$mockBase64 = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";

// Ensure an overlay image exists to avoid immediate errors
$overlayDir = __DIR__ . '/uploads/overlays/';
if (!file_exists($overlayDir)) {
    mkdir($overlayDir, 0755, true);
}
// Create an empty dummy framework overlay PNG
if (!file_exists($overlayDir . 'frame1.png')) {
    $img = imagecreatetruecolor(100, 100);
    imagesavealpha($img, true);
    $trans_colour = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $trans_colour);
    imagepng($img, $overlayDir . 'frame1.png');
    imagedestroy($img);
}

// Fetch a valid user ID from the database to link the snapshot to
$userCheck = $pdo->query("SELECT id FROM users LIMIT 1")->fetch();
if ($userCheck) {
    $snapResult = $studio->saveSnapshot($userCheck['id'], $mockBase64, "frame1.png");
    echo "Result: " . ($snapResult['success'] ? "✅ " : "❌ ") . $snapResult['message'] . "<br>";
} else {
    echo "❌ Skipped snapshot test: No users found in database.<br>";
}

// ==========================================
// TEST 4: Fetch Gallery Feed
// ==========================================
echo "<h3>Test 4: Gallery Stream</h3>";
$galleryFeed = $gallery->fetchGalleryPage(5, 0);
echo "Successfully fetched " . count($galleryFeed) . " public records from database rows.<br>";
if (count($galleryFeed) > 0) {
    echo "Newest post path: " . htmlspecialchars($galleryFeed[0]['storage_path']) . " by " . htmlspecialchars($galleryFeed[0]['username']) . "<br>";
}
