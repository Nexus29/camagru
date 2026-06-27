<?php
// src/controllers/AuthController.php

class AuthController {
	private $pdo;

	public function __construct($pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * Handles programmatic registration requests
	 */
	public function register($username, $email, $password) {
		// Basic input normalization and sanitization
		$username = trim(htmlspecialchars($username));
		$email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

		if (empty($username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
			return ['success' => false, 'message' => 'Invalid registration parameters input. Password must be >= 8 chars.'];
		}

		// Securely create password mapping hashes using standard modern BCRYPT algorithms
		$passwordHash = password_hash($password, PASSWORD_BCRYPT);
		$activationToken = bin2hex(random_bytes(32));

		try {
			$stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, activation_token) VALUES (?, ?, ?, ?)");
			$stmt->execute([$username, $email, $passwordHash, $activationToken]);
			
			// TODO: In a production sequence, transmit an activation email containing $activationToken
			return ['success' => true, 'message' => 'Registration complete! Check email to activate account.'];
		} catch (PDOException $e) {
			// Intercept unique key collision conditions gracefully
			return ['success' => false, 'message' => 'Username or email identifier is already registered.'];
		}
	}

	/**
	 * Authenticates existing user profiles
	 */
	public function login($username, $password) {
		$username = trim($username);

		$stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
		$stmt->execute([$username, $username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$user || !password_verify($password, $user['password_hash'])) {
			return ['success' => false, 'message' => 'Invalid login credentials matching database records.'];
		}

		if (!$user['is_active']) {
			return ['success' => false, 'message' => 'Account inactive. Please complete the verification sequence.'];
		}

		// Establish secure cross-view state flags
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['username'] = $user['username'];
		return ['success' => true, 'message' => 'Welcome back! Login verified.'];
	}

	/**
	 * Terminates existing tracking scopes
	 */
	public function logout() {
		$_SESSION = [];
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		session_destroy();
		header("Location: /login");
		exit();
	}
}
