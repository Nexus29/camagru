<?php
// src/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Core Structural Dependencies
require_once __DIR__ . '/config/database.php';

// Load Models
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/SnapshotModel.php';
require_once __DIR__ . '/models/InteractionModel.php';

// Load Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/StudioController.php';
require_once __DIR__ . '/controllers/GalleryController.php';

// 2. Initialize Models
$userModel        = new UserModel($pdo);
$snapshotModel    = new SnapshotModel($pdo);
$interactionModel = new InteractionModel($pdo);

// 3. Initialize Controllers
$auth    = new AuthController($pdo);
$studio  = new StudioController($pdo);
$gallery = new GalleryController($pdo);

// --- Testing Session Mocking Layer ---
// If not logged in, dynamically provision a test user matching your application schema
if (!isset($_SESSION['user_id'])) {
    $testUsername = "model_tester";
    $testEmail = "tester@camagru.local";
    
    $existing = $userModel->findByUsername($testUsername);
    if (!$existing) {
        $userModel->create($testUsername, $testEmail, password_hash("password123", PASSWORD_BCRYPT), null);
        $pdo->exec("UPDATE users SET is_active = TRUE WHERE username = 'model_tester';");
    }
    
    $auth->login($testUsername, "password123");
}

// 4. Request URI and Method Parsing
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// ==========================================
// API ENDPOINT INTERCEPTIONS (POST requests)
// ==========================================
if ($requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    // Asynchronous Snapshot Upload Route
    if ($requestUri === '/studio' && ($_POST['action'] ?? '') === 'snap') {
        $res = $studio->saveSnapshot($_SESSION['user_id'], $_POST['image'], $_POST['overlay']);
        echo json_encode($res);
        exit();
    }
    
    // Asynchronous Gallery Interaction Routes
    if ($requestUri === '/gallery') {
        $action = $_POST['action'] ?? '';
        $snapshotId = (int)($_POST['snapshot_id'] ?? 0);
        
        if ($action === 'like') {
            $result = $interactionModel->toggleLike($_SESSION['user_id'], $snapshotId);
            $result['like_count'] = $interactionModel->getLikeCount($snapshotId);
            $result['success'] = true;
            echo json_encode($result);
            exit();
        }
        
        if ($action === 'comment') {
            $commentText = trim($_POST['comment_text'] ?? '');
            if (!empty($commentText)) {
                $inserted = $interactionModel->addComment($_SESSION['user_id'], $snapshotId, $commentText);
                echo json_encode(['success' => $inserted]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Comment string cannot be empty.']);
            }
            exit();
        }
    }
}

// ==========================================
// USER VISUAL INTERACTION MARKUP (GET views)
// ==========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Camagru Model-Controller Testing Environment</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #121212; color: #e0e0e0; margin: 0; padding: 20px; }
        nav { background: #1f1f1f; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #333; }
        nav a { color: #00adb5; margin-right: 20px; text-decoration: none; font-weight: bold; }
        nav a:hover { text-decoration: underline; }
        .view-box { background: #1e1e1e; padding: 25px; border-radius: 12px; border: 1px solid #2d2d2d; }
        .flex { display: flex; gap: 24px; flex-wrap: wrap; }
        video, canvas { background: #000; border-radius: 8px; width: 320px; height: 240px; border: 2px solid #333; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: #2d2d2d; border-radius: 8px; padding: 15px; text-align: center; border: 1px solid #444; }
        .card img { max-width: 100%; border-radius: 6px; height: auto; display: block; margin: 0 auto 10px; }
        button { background: #00adb5; border: none; color: white; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #007a80; }
        input[type="text"] { width: 75%; padding: 8px; border-radius: 4px; border: 1px solid #444; background: #222; color: #fff; }
        ul { padding: 0; list-style: none; text-align: left; max-height: 120px; overflow-y: auto; background: #222; padding: 8px; border-radius: 4px; }
        li { margin-bottom: 6px; font-size: 0.85rem; border-bottom: 1px solid #333; padding-bottom: 4px; }
        .error { color: #ff5555; font-weight: bold; }
        .success { color: #50fa7b; font-weight: bold; }
    </style>
</head>
<body>

<nav>
    <a href="/gallery">🖼 View Community Gallery</a>
    <a href="/studio">📸 Open Creative Studio</a>
</nav>

<div class="view-box">
    <?php if ($requestUri === '/studio'): ?>
        <h2>📸 Creative Capture Studio</h2>
        <p>Active Model Session Identity: <strong style="color: #00adb5;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
        
        <div class="flex">
            <div>
                <h4>Hardware Video Stream Capture</h4>
                <video id="video-feed" autoplay playsinline></video>
                <br><br>
                <label for="overlay-select">Choose Overlay Graphics Frame: </label>
                <select id="overlay-select" style="padding: 6px; background: #333; color: #fff; border-radius: 4px;">
                    <option value="frame1.png">Alpha Transparency Frame 1 (Teal Border)</option>
                </select>
                <br><br>
                <button id="snap-btn">Capture & Process Composite</button>
            </div>
            <div>
                <h4>Rendering Operations Pipeline Console</h4>
                <canvas id="capture-canvas" style="display:none;"></canvas>
                <div id="studio-log"><p style="color: #aaa;">Awaiting camera hardware mounting validation...</p></div>
            </div>
        </div>
        <script src="/js/camera.js"></script>

    <?php else: ?>
        <h2>🖼 Public Community Gallery (Driven by SnapshotModel)</h2>
        <?php
            // Pull files using the model pagination calculation pipeline
            $cards = $snapshotModel->getPaginated(12, 0);
        ?>
        
        <?php if (empty($cards)): ?>
            <p style="color: #bbb;">No data records located inside the snapshots relational matrix. Visit the studio to post an image!</p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($cards as $item): ?>
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($item['storage_path']); ?>" alt="Camagru Snapshot">
                        <p style="font-size: 0.9rem; color: #aaa;">Author: <strong style="color: #fff;"><?php echo htmlspecialchars($item['username']); ?></strong></p>
                        
                        <?php $likesCount = $interactionModel->getLikeCount($item['id']); ?>
                        <button onclick="toggleLike(<?php echo $item['id']; ?>, this)">
                            ❤️ Likes (<span class="like-count"><?php echo $likesCount; ?></span>)
                        </button>
                        
                        <hr style="border: 0; border-top: 1px solid #444; margin: 15px 0;">
                        
                        <ul id="comments-list-<?php echo $item['id']; ?>">
                            <?php 
                            $comments = $interactionModel->getCommentsForSnapshot($item['id']); 
                            foreach ($comments as $comment): 
                            ?>
                                <li>
                                    <strong style="color: #00adb5;"><?php echo htmlspecialchars($comment['username']); ?>:</strong> 
                                    <?php echo htmlspecialchars($comment['comment_text']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <form onsubmit="submitComment(event, <?php echo $item['id']; ?>)">
                            <input type="text" name="comment_text" placeholder="Write a comment..." required>
                            <button type="submit" style="padding: 8px 12px; margin-top: 0; font-size: 0.85rem;">Post</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <script src="/js/gallery.js"></script>
    <?php endif; ?>
</div>

</body>
</html>
