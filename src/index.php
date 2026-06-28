<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	define('CAMAGRU_RUNNING', true);

	if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
		header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		exit();
	}

	// 2. Load Core Application Architecture Dependencies
	require_once __DIR__ . '/config/database.php';

	// Models
	require_once __DIR__ . '/models/UserModel.php';
	require_once __DIR__ . '/models/SnapshotModel.php';
	require_once __DIR__ . '/models/InteractionModel.php';

	// Controllers
	require_once __DIR__ . '/controllers/AuthController.php';
	require_once __DIR__ . '/controllers/StudioController.php';
	require_once __DIR__ . '/controllers/GalleryController.php';

	// 3. Instantiate Core Domain Engine Layers
	$userModel        = new UserModel($pdo);
	$snapshotModel    = new SnapshotModel($pdo);
	$interactionModel = new InteractionModel($pdo);

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

	// 4. Parse Environment URI Routing Path Parameters
	$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$request_uri = rtrim($request_uri, '/');
	$request_method = $_SERVER['REQUEST_METHOD'];


	// ========================================================
	// API LAYER INTERCEPTION (Only for Asynchronous JSON Calls)
	// ========================================================
	if ($request_method === 'POST' && ($request_uri === '/studio' || $request_uri === '/gallery')) {
		header('Content-Type: application/json');
		
		// AJAX Webcam Studio Composite Route
		if ($request_uri === '/studio' && ($_POST['action'] ?? '') === 'snap') {
			$res = $studio->saveSnapshot($_SESSION['user_id'], $_POST['image'], $_POST['overlay']);
			echo json_encode($res);
			exit();
		}
		
		// AJAX Gallery Interactions (Likes & Comments) Routes
		if ($request_uri === '/gallery') {
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
					echo json_encode(['success' => false, 'message' => 'Comment body content empty.']);
				}
				exit();
			}
		}
		
		echo json_encode(['success' => false, 'message' => 'Invalid Endpoint Route Context.']);
		exit();
	}

	// ========================================================
	// VISUAL & FORM SUBMISSION LAYER (Traditional Page Views)
	// ========================================================
	require_once __DIR__ . '/views/templates/header.php';

	switch ($request_uri) {
		case '':
		case '/gallery':
			$cards = $snapshotModel->getPaginated(12, 0);
			require_once __DIR__ . '/views/gallery.php';
			break;

		case '/studio':
			if (!isset($_SESSION['user_id'])) {
				header("Location: /login");
				exit();
			}
			require_once __DIR__ . '/views/studio.php';
			break;

		case '/login':
			require_once __DIR__ . '/views/login.php';
			break;

		case '/register':
			require_once __DIR__ . '/views/register.php';
			break;

		default:
			header("HTTP/1.0 404 Not Found");
			echo '<section class="card"><h1>404 — Page Not Found</h1><p>Target endpoint context trace unregistered.</p></section>';
			break;
	}

	require_once __DIR__ . '/views/templates/footer.php';
?>
