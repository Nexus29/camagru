<?php
	session_start();

	if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
		header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		exit();
	}

	require_once __DIR__ . '/views/templates/header.php';

	echo '
	<section class="card">
		<h1>Welcome to Camagru Studio</h1>
		<p>Create, edit and share your photos with a modern experience.</p>
	</section>';

	require_once __DIR__ . '/views/templates/footer.php';
?>
