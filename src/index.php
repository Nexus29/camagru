<?php
// src/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Global Setup Dependencies
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/StudioController.php';
require_once __DIR__ . '/controllers/GalleryController.php';

// Fast track: mock active login session if none exists to simplify testing
if (!isset($_SESSION['user_id'])) {
    $auth = new AuthController($pdo);
    $auth->register("camagru_tester", "tester@example.com", "password123");
    $pdo->exec("UPDATE users SET is_active = TRUE WHERE username = 'camagru_tester';");
    $auth->login("camagru_tester", "password123");
}

// 2. Parse Environment Request Clean Routes
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// ==========================================
// API ENDPOINT INTERCEPTIONS (POST requests)
// ==========================================
if ($requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if ($requestUri === '/studio' && ($_POST['action'] ?? '') === 'snap') {
        $studio = new StudioController($pdo);
        $res = $studio->saveSnapshot($_SESSION['user_id'], $_POST['image'], $_POST['overlay']);
        echo json_encode($res);
        exit();
    }
    
    if ($requestUri === '/gallery') {
        $gallery = new GalleryController($pdo);
        if (($_POST['action'] ?? '') === 'like') {
            echo json_encode($gallery->toggleLike($_SESSION['user_id'], (int)$_POST['snapshot_id']));
            exit();
        }
        if (($_POST['action'] ?? '') === 'comment') {
            echo json_encode($gallery->addComment($_SESSION['user_id'], (int)$_POST['snapshot_id'], $_POST['comment_text']));
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
    <title>Camagru Unified View Matrix</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        body { font-family: sans-serif; background: #121212; color: #e0e0e0; margin: 0; padding: 20px; }
        nav { background: #1f1f1f; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        nav a { color: #00adb5; margin-right: 20px; text-decoration: none; font-weight: bold; }
        .view-box { background: #1e1e1e; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .flex { display: flex; gap: 20px; }
        video, canvas { background: #000; border-radius: 8px; width: 320px; height: 240px; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: #2d2d2d; border-radius: 8px; padding: 15px; text-align: center; }
        .card img { max-width: 100%; border-radius: 6px; }
        button { background: #00adb5; border: none; color: white; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #007a80; }
        input[type="text"] { width: 80%; padding: 8px; border-radius: 4px; border: 1px solid #444; background: #333; color: #fff; }
    </style>
</head>
<body>

<nav>
    <a href="/gallery">🖼 View Public Gallery Feed</a>
    <a href="/studio">📸 Open Creative Studio</a>
</nav>

<div class="view-box">
    <?php if ($requestUri === '/studio'): ?>
        <h2>📸 Creative Capture Studio</h2>
        <p>Logged in user context mapping tracking label: <strong><?php echo $_SESSION['username']; ?></strong></p>
        
        <div class="flex">
            <div>
                <h4>Webcam Input Feed</h4>
                <video id="video-feed" autoplay playsinline></video>
                <br>
                <label for="overlay-select">Choose Overlay Layer: </label>
                <select id="overlay-select">
                    <option value="frame1.png">Alpha Transparency Frame 1</option>
                </select>
                <br>
                <button id="snap-btn">Capture & Combine Snapshot</button>
            </div>
            <div>
                <h4>Processed Pipeline Output Preview</h4>
                <canvas id="capture-canvas" style="display:none;"></canvas>
                <div id="studio-log"><p>Camera driver initialized. Ready to record.</p></div>
            </div>
        </div>
        <script src="/js/camera.js"></script>

    <?php else: ?>
        <h2>🖼 Public Community Gallery</h2>
        <?php
            $gallery = new GalleryController($pdo);
            $cards = $gallery->fetchGalleryPage(12, 0);
        ?>
        
        <?php if (empty($cards)): ?>
            <p style="color: #bbb;">No public posts saved yet. Head to the Studio to generate the first photo!</p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($cards as $item): ?>
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($item['storage_path']); ?>" alt="Snapshot">
                        <p>Uploaded by: <strong><?php echo htmlspecialchars($item['username']); ?></strong></p>
                        
                        <button onclick="toggleLike(<?php echo $item['id']; ?>, this)">
                            ❤️ Likes (<span class="like-count"><?php echo $item['like_count']; ?></span>)
                        </button>
                        
                        <hr style="border-color: #444; margin: 15px 0;">
                        <ul id="comments-list-<?php echo $item['id']; ?>" style="text-align: left; list-style: none; padding: 0; font-size: 0.9rem;">
                            </ul>
                        <form onsubmit="submitComment(event, <?php echo $item['id']; ?>)">
                            <input type="text" name="comment_text" placeholder="Add a comment..." required>
                            <button type="submit">Post</button>
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