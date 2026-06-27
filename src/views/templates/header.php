<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Camagru Studio</title>
	<link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<header class="global-header">
	<div class="logo">
		<a href="/gallery">📷 Camagru</a>
	</div>
	<nav class="navigation-bar">
		<a href="/gallery">Public Gallery</a>
		<?php if (isset($_SESSION['user_id'])): ?>
			<a href="/studio">Workspace Studio</a>
			<a href="/preferences">Settings</a>
			<a href="/logout" class="btn-auth">Logout</a>
		<?php else: ?>
			<a href="/login">Sign In</a>
			<a href="/register" class="btn-auth">Register</a>
		<?php endif; ?>
	</nav>
</header>
<main class="main-content-wrapper">
