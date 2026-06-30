<?php
// src/controllers/AuthController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';

class AuthController {
	private $pdo;

	public function __construct($pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * Handles registration requests matching strict requirements
	 */
	public function register($username, $email, $password) {
		$username = trim(htmlspecialchars($username));
		$email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

		if (empty($username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return ['success' => false, 'message' => 'Invalid email format or empty username context.'];
		}

		if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
			return ['success' => false, 'message' => 'Password must be at least 8 characters long and contain at least one uppercase letter and one number.'];
		}

		$passwordHash = password_hash($password, PASSWORD_BCRYPT);
		$activationToken = bin2hex(random_bytes(32));

		try {
			// Accounts start explicitly INACTIVE (is_active = FALSE by default)
			$stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, activation_token, is_active) VALUES (?, ?, ?, ?, FALSE)");
			$stmt->execute([$username, $email, $passwordHash, $activationToken]);
			
			$activationLink = "https://localhost:8443/activate?token=" . $activationToken;

			// ========================================================
			// PHPMailer -> MAILHOG PIPELINE
			// ========================================================
			$mail = new PHPMailer(true);

			try {
				$mail->isSMTP();
				$mail->Host       = 'mailhog'; // Docker internal DNS route
				$mail->SMTPAuth   = false;     // Mailhog does not require security authentication tokens
				$mail->Username   = '';                
				$mail->Password   = '';                
				$mail->SMTPSecure = '';                
				$mail->Port       = 1025;      // Target Mailhog container port

				$mail->setFrom('no-reply@camagru.com', 'Camagru Studio Platform');
				$mail->addAddress($email, $username);

				$mail->isHTML(true);
				$mail->Subject = 'Camagru - Activate Your Account';
				$mail->Body    = "
				<html>
				<body style='font-family: Arial, sans-serif; background: #121212; color: #ffffff; padding: 20px;'>
					<h2 style='color: #00adb5;'>Welcome to Camagru, " . htmlspecialchars($username) . "!</h2>
					<p>Your registration profile context was processed successfully. To enable active web session authorizations, you must verify your address entry.</p>
					<p style='margin: 25px 0;'>
						<a href='" . $activationLink . "' style='background: #00adb5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Activate My Account Context</a>
					</p>
					<small style='color: #666;'>Alternatively, visit this path manually: <br>" . $activationLink . "</small>
				</body>
				</html>";

				$mail->send();
				
			} catch (Exception $e) {
				// Fallback safety log line if things crash unexpectedly
				$logFile = __DIR__ . '/../config/mail_logs.txt';
				@mkdir(dirname($logFile), 0777, true);
				@file_put_contents($logFile, "Mailhog down. Link: {$activationLink}\n", FILE_APPEND);
				
				return [
					'success' => true,
					'message' => 'Account created, but Mailhog transport timed out. Token logged into config/mail_logs.txt.'
				];
			}

			return [
				'success' => true, 
				'message' => 'Registration successful! Open http://localhost:8025 to click your verification link.'
			];
			
		} catch (PDOException $e) {
			return ['success' => false, 'message' => 'Username identifier or email address profile is already in use.'];
		}
	}

	/**
	 * Activates accounts via matching token parameters
	 */
	public function activateAccount($token) {
		if (empty($token)) {
			return ['success' => false, 'message' => 'Null or void confirmation token scope context.'];
		}

		$stmt = $this->pdo->prepare("SELECT id FROM users WHERE activation_token = ? AND is_active = FALSE");
		$stmt->execute([$token]);
		$user = $stmt->fetch();

		if (!$user) {
			return ['success' => false, 'message' => 'Invalid or expired profile token context mapping signature.'];
		}

		$update = $this->pdo->prepare("UPDATE users SET is_active = TRUE, activation_token = NULL WHERE id = ?");
		$update->execute([$user['id']]);

		return ['success' => true, 'message' => 'Account identity authenticated successfully! You may now access the studio.'];
	}

	public function login($username, $password) {
		$username = trim($username);

		$stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
		$stmt->execute([$username, $username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$user || !password_verify($password, $user['password_hash'])) {
			return ['success' => false, 'message' => 'Invalid login credentials matching database records.'];
		}

		if (!$user['is_active']) {
			return ['success' => false, 'message' => 'Account inactive. Please complete the verification sequence via email link. '];
		}

		$_SESSION['user_id'] = $user['id'];
		$_SESSION['username'] = $user['username'];
		return ['success' => true, 'message' => 'Welcome back! Login verified.'];
	}

	public function logout() {
		$_SESSION = [];
		session_destroy();
	}
}
