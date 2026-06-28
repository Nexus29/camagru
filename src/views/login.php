<?php
if (!defined('CAMAGRU_RUNNING')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct script access is strictly prohibited.");
}

$message = "";
$messageClass = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_login'])) {
    // Calling your existing AuthController login verification pipeline
    $loginResult = $auth->login($_POST['username'], $_POST['password']);
    
    if ($loginResult['success']) {
        // Redirect to studio workspace upon successful login verification
        header("Location: /studio");
        exit();
    } else {
        $message = $loginResult['message'];
        $messageClass = "error";
    }
}
?>

<section class="card auth-container" style="max-width: 400px; margin: 40px auto; padding: 20px; background: #1e1e1e; border-radius: 8px; border: 1px solid #333;">
    <h1>Account Sign In</h1>
    
    <?php if (!empty($message)): ?>
        <p class="<?php echo $messageClass; ?>" style="padding: 10px; border-radius: 4px; font-weight: bold; color: #ff5555;">
            ❌ <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form action="/login" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <div>
            <label style="display:block; margin-bottom: 5px;">Username</label>
            <input type="text" name="username" required style="width: 100%; padding: 8px; background: #222; color: #fff; border: 1px solid #444; border-radius: 4px;">
        </div>
        
        <div>
            <label style="display:block; margin-bottom: 5px;">Password</label>
            <input type="password" name="password" required style="width: 100%; padding: 8px; background: #222; color: #fff; border: 1px solid #444; border-radius: 4px;">
        </div>

        <button type="submit" name="submit_login" style="background: #00adb5; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Log In
        </button>
    </form>
    
    <p style="margin-top: 15px; font-size: 0.9rem; text-align: center;">
        Don't have an account? <a href="/register" style="color: #00adb5; text-decoration: none;">Register here</a>
    </p>
</section>
