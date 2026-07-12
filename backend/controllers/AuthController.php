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
        $password = $input['password'] ?? '';

        if (!$email || strlen($username) < 3 || strlen($username) > 20 || strlen($password) < 8) {
            $this->sendJson(['error' => 'Validation failed. Check constraint bounds.'], 400);
            return;
        }

        try {
            if ($this->userModel->isUniqueConflict($username, $email)) {
                $this->sendJson(['error' => 'Username or email string token already exists.'], 409);
                return;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
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
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->sendJson(['error' => 'Username and password fields are required.'], 400);
            return;
        }

        try {
            $user = $this->userModel->findByUsername($username);

            if (!$user || !password_verify($password, $user['password'])) {
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

    public function resetPassword() {
		$input = json_decode(file_get_contents('php://input'), true);
		
		$token = trim($input['token'] ?? '');
		$newPassword = $input['password'] ?? '';

		if (empty($token) || strlen($newPassword) < 8) {
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