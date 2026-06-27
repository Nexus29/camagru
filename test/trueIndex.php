<?php
session_start();

define('CAMAGRU_RUNNING', true);

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
	header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit();
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = rtrim($request_uri, '/');

require_once __DIR__ . '/views/templates/header.php';


switch ($request_uri) {
	case '':
	case '/gallery':
		require_once __DIR__ . '/views/gallery.php';
		break;

	case '/studio':
		require_once __DIR__ . '/views/studio.php';
		break;

	case '/login':
		echo '<section class="card"><h1>Account Sign In</h1><p>Authentication layout panel placeholder.</p></section>';
		break;

	case '/register':
		echo '<section class="card"><h1>Create Account</h1><p>Validation configuration metrics panel placeholder.</p></section>';
		break;

	default:
		header("HTTP/1.0 404 Not Found");
		echo '<section class="card"><h1>404 — Page Not Found</h1><p>Target endpoint context trace unregistered.</p></section>';
		break;
}

require_once __DIR__ . '/views/templates/footer.php';
?>
