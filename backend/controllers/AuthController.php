<?php
// backend/controllers/AuthController.php

require_once dirname(__DIR__) . '/models/UserModel.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->sendJson(['error' => 'Invalid structural payload matrix.'], 400);
            return;
        }

        $email    = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $username = trim($input['username'] ?? '');
        $newPassword = $input['password'] ?? '';

        if (!$email || strlen($username) < 3 || strlen($username) > 20 || strlen($newPassword) < 8 || !preg_match("/[a-z]/", $newPassword) || !preg_match("/[0-9]/", $newPassword) || !preg_match("/[A-Z]/", $newPassword)) {
            $this->sendJson(['error' => 'Validation failed. Check constraint bounds.'], 400);
            return;
        }

        try {
            if ($this->userModel->isUniqueConflict($username, $email)) {
                $this->sendJson(['error' => 'Username or email string token already exists.'], 409);
                return;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $verificationToken = bin2hex(random_bytes(32));

            $this->userModel->register($username, $email, $hashedPassword, $verificationToken);

            $this->dispatchEmail($email, $username, $verificationToken, false); 

            $this->sendJson([
                'success' => true,
                'message' => 'Profile compiled successfully! Check your email to verify account activation.'
            ], 201);

        } catch (Exception $e) {
            $this->sendJson(['error' => 'Database execution layer encountered an error: ' . $e->getMessage()], 500);
        }
    }

    public function verify() {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->sendJson(['error' => 'Verification token is missing.'], 400);
            return;
        }

        try {
            $user = $this->userModel->findByVerificationToken($token);

            if (!$user) {
                $this->sendJson(['error' => 'Invalid or expired verification token.'], 400);
                return;
            }

            if ($user['is_verified']) {
                $this->sendJson(['message' => 'Account is already verified. You can sign in.'], 200);
                return;
            }

            $this->userModel->verifyAccount($user['id']);

            $this->sendJson([
                'success' => true,
                'message' => 'Account verified successfully! You can now log in.'
            ], 200);

        } catch (Exception $e) {
            $this->sendJson(['error' => 'Database execution error during verification: ' . $e->getMessage()], 500);
        }
    }

    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->sendJson(['error' => 'Invalid structural payload matrix.'], 400);
            return;
        }

        $username = trim($input['username'] ?? '');
        $newPassword = $input['password'] ?? '';

        if (empty($username) || empty($newPassword)) {
            $this->sendJson(['error' => 'Username and password fields are required.'], 400);
            return;
        }

        try {
            $user = $this->userModel->findByUsername($username);

            if (!$user || !password_verify($newPassword, $user['password'])) {
                $this->sendJson(['error' => 'Invalid username or password credentials.'], 401);
                return;
            }

            if (!$user['is_verified']) {
                $this->sendJson(['error' => 'Your email verification is incomplete. Check your inbox.'], 403);
                return;
            }

            $payload = base64_encode(json_encode(['id' => $user['id']]));
            $signature = bin2hex(random_bytes(16));
            $sessionToken = "token.{$payload}.{$signature}";

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['token'] = $sessionToken;

            $this->sendJson([
                'success'  => true,
                'token'    => $sessionToken,
                'username' => $user['username']
            ], 200);

        } catch (Exception $e) {
            $this->sendJson(['error' => 'Database layer login exception: ' . $e->getMessage()], 500);
        }
    }

    public function forgotPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $this->sendJson(['error' => 'Please provide a valid email address.'], 400);
            return;
        }

        try {
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                $this->sendJson([
                    'success' => true, 
                    'message' => 'If that matrix profile exists, an initialization link has been dispatched to your mailbox.'
                ], 200);
                return;
            }

            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s O', strtotime('+1 hour'));

            $this->userModel->setPasswordResetToken($user['id'], $resetToken, $expiresAt);

            $this->dispatchEmail($email, $user['username'], $resetToken, true);

            $this->sendJson([
                'success' => true, 
                'message' => 'An authentication initialization link has been safely dispatched to your mailbox!'
            ], 200);

        } catch (Exception $e) {
            $this->sendJson(['error' => 'Server execution error: ' . $e->getMessage()], 500);
        }
    }

	public function showResetForm() {
    // 🛡️ Sanitize the inbound query parameter token
		$token = htmlspecialchars($_GET['token'] ?? '');

		// Set the proper content header for rendering regular HTML markup pages
		header('Content-Type: text/html; charset=utf-8');

		echo <<<HTML
				<!DOCTYPE html>
				<html lang="en">
				<head>
					<meta charset="UTF-8">
					<title>Reset Password - Camagru</title>
					<style>
						body { font-family: sans-serif; background: #121214; color: #e1e1e6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
						.card { background: #202024; padding: 2rem; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
						h2 { margin-top: 0; color: #fff; }
						input { width: 100%; padding: 0.75rem; margin: 1rem 0; border: 1px solid #323238; background: #121214; color: #fff; border-radius: 4px; box-sizing: border-box; }
						button { width: 100%; padding: 0.75rem; background: #8257e5; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer; }
						button:hover { background: #9466ff; }
						.banner { padding: 0.75rem; margin-bottom: 1rem; border-radius: 4px; display: none; }
						.error { background: #22c55e22; border: 1px solid #e96379; color: #e96379; }
						.success { background: #22c55e22; border: 1px solid #22c55e; color: #4ade80; }
					</style>
				</head>
				<body>
					<div class="card">
						<h2>Create New Password</h2>
						<p style="color: #8d8d99; font-size: 0.9rem;">Enter a new secure password structure for your account profile matrix.</p>
						
						<div id="banner" class="banner"></div>

						<form id="resetForm">
							<input type="hidden" id="token" value="{$token}">
							<input type="password" id="password" placeholder="Minimum 8 characters" required minlength="8">
							<button type="submit">Update Password</button>
						</form>
					</div>

					<script>
						document.getElementById('resetForm').addEventListener('submit', async (e) => {
							e.preventDefault();
							const token = document.getElementById('token').value;
							const password = document.getElementById('password').value;
							const banner = document.getElementById('banner');
							
							banner.style.display = 'none';

							try {
								const response = await fetch('/api/reset-password', {
									method: 'POST',
									headers: { 'Content-Type': 'application/json' },
									body: JSON.stringify({ token, password })
								});

								const data = await response.json();

								if (!response.ok) {
									throw new Error(data.error || 'Failed to securely compile request matrix.');
								}

								banner.className = 'banner success';
								banner.textContent = data.message;
								banner.style.display = 'block';
								document.getElementById('resetForm').style.display = 'none';

							} catch (err) {
								banner.className = 'banner error';
								banner.textContent = err.message;
								banner.style.display = 'block';
							}
						});
					</script>
				</body>
				</html>
			HTML;
		exit;
	}

    public function resetPassword() {
		$input = json_decode(file_get_contents('php://input'), true);
		
		$token = trim($input['token'] ?? '');
		$newPassword = $input['password'] ?? '';

		if (empty($token) || strlen($newPassword) < 8 || !preg_match("/[a-z]/", $newPassword) || !preg_match("/[0-9]/", $newPassword) || !preg_match("/[A-Z]/", $newPassword)) {
			$this->sendJson(['error' => 'Validation failed. Token is missing or password is too short.'], 400);
			return;
		}

		try {
			$user = $this->userModel->findByResetToken($token);

			if (!$user) {
				$this->sendJson(['error' => 'Invalid or expired password reset token.'], 400);
				return;
			}

			// ⏰ Verify token lifespan matrix constraints against the current time context
			$expiryTime = strtotime($user['reset_expires_at']);
			if (time() > $expiryTime) {
				$this->sendJson(['error' => 'This reset link window has expired. Please request a new one.'], 400);
				return;
			}

			$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
			$this->userModel->resetUserPassword($user['id'], $hashedPassword);

			// Uses your private function to return success directly to the frontend script
			$this->sendJson([
				'success' => true,
				'message' => 'Your password has been securely updated! You can now log in.'
			], 200);

		} catch (Exception $e) {
			$this->sendJson(['error' => 'Database operation execution crash: ' . $e->getMessage()], 500);
		}
	}

    private function dispatchEmail($email, $username, $token, $isReset = false) {
        $headers = "MIME-Version: 1.0" . "\r\n" . "Content-type:text/html;charset=UTF-8" . "\r\n" . "From: Camagru Team <segreteria.camagru@gmail.com>" . "\r\n";
        
        if ($isReset) {
            $resetLink = "http://localhost:8080/api/reset-password?token=" . urlencode($token);
            $subject = "Reset Your Camagru Password Matrix Access";
            $message = "<html><body>"
                     . "<h2>Hello " . htmlspecialchars($username) . ",</h2>"
                     . "<p>We received a password reset request. Click <a href='{$resetLink}'>here</a> to safely choose a new password block.</p>"
                     . "<p>This link window will expire in 1 hour.</p>"
                     . "</body></html>";
        } else {
            $activationLink = "http://localhost:8080/api/verify?token=" . urlencode($token);
            $subject = "Confirm your Camagru Account";
            $message = "<html><body><h2>Welcome, " . htmlspecialchars($username) . "!</h2><p>Click <a href='{$activationLink}'>here</a> to verify.</p></body></html>";
        }

        @mail($email, $subject, $message, $headers);
    }

    private function sendJson($data, $statusCode = 200) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}