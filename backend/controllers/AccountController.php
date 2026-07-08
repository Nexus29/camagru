<?php
// backend/controllers/AccountController.php

require_once dirname(__DIR__) . '/config/database.php';

class AccountController {

    public function updateProfile() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Robust Token Extraction (Checks headers case-insensitively)
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $authHeader = $headers['authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        // Fallback to checking session data directly if headers are dropped by Nginx proxy layers
        if (empty($token) && isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
        }

        if (!isset($_SESSION['user_id']) || empty($token) || !isset($_SESSION['token']) || $_SESSION['token'] !== $token) {
            $this->sendJson(['error' => 'Authentication validation failed. Please sign in again.'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);

        // Extract individual inputs safely
        $newUsername = isset($input['username']) ? trim($input['username']) : '';
        $newEmail = isset($input['email']) ? filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL) : '';
        $newPassword = isset($input['password']) ? $input['password'] : '';

        $db = Database::getInstance();

        try {
            // Get current attributes to evaluate what actually changed
            $stmt = $db->prepare("SELECT username, email FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $currentUser = $stmt->fetch();

            if (!$currentUser) {
                $this->sendJson(['error' => 'User profile target entity not found.'], 404);
                return;
            }

            $updateFields = [];
            $params = [':id' => $userId];

            // A. Update Username ONLY if it's provided and different
            if (!empty($newUsername) && $newUsername !== $currentUser['username']) {
                if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
                    $this->sendJson(['error' => 'Username must be between 3 and 20 characters.'], 400);
                    return;
                }

                $uniqCheck = $db->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
                $uniqCheck->execute([':username' => $newUsername, ':id' => $userId]);
                if ($uniqCheck->fetch()) {
                    $this->sendJson(['error' => 'Username is already taken by another user.'], 409);
                    return;
                }

                $updateFields[] = "username = :username";
                $params[':username'] = $newUsername;
            }

            // B. Update Email ONLY if it's provided and different
            if (isset($input['email']) && !empty(trim($input['email'])) && $newEmail !== $currentUser['email']) {
                if (!$newEmail) {
                    $this->sendJson(['error' => 'The provided email string format is invalid.'], 400);
                    return;
                }

                $uniqCheck = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $uniqCheck->execute([':email' => $newEmail, ':id' => $userId]);
                if ($uniqCheck->fetch()) {
                    $this->sendJson(['error' => 'This email address is already registered.'], 409);
                    return;
                }

                $verificationToken = bin2hex(random_bytes(32));
                $updateFields[] = "email = :email";
                $updateFields[] = "is_verified = FALSE";
                $updateFields[] = "verification_token = :v_token";
                
                $params[':email'] = $newEmail;
                $params[':v_token'] = $verificationToken;

                $this->dispatchReverificationEmail($newEmail, $newUsername ?: $currentUser['username'], $verificationToken);
            }

            // C. Update Password ONLY if it's provided
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 8) {
                    $this->sendJson(['error' => 'New password must be at least 8 characters long.'], 400);
                    return;
                }
                $updateFields[] = "password = :password";
                $params[':password'] = password_hash($newPassword, PASSWORD_BCRYPT);
            }

            // If absolutely nothing was altered, stop and notify user gracefully
            if (empty($updateFields)) {
                $this->sendJson(['error' => 'No modification details were changed.'], 400);
                return;
            }

            // Execute the dynamic parameters query update
            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = :id";
            $updateStmt = $db->prepare($sql);
            $updateStmt->execute($params);

            // Log user out ONLY if their email address changed
            $emailRevoked = in_array("is_verified = FALSE", $updateFields);
            if ($emailRevoked) {
                session_destroy();
            }

            $this->sendJson([
                'success' => true,
                'email_changed' => $emailRevoked,
                'message' => $emailRevoked 
                    ? 'Email changed! Please re-verify your account via your new mailbox before logging back in.' 
                    : 'Your account configuration parameters have been updated successfully!'
            ]);

        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database update transaction failed: ' . $e->getMessage()], 500);
        }
    }

	public function getProfile() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $authHeader = $headers['authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (empty($token) && isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
        }

        if (!isset($_SESSION['user_id']) || empty($token) || !isset($_SESSION['token']) || $_SESSION['token'] !== $token) {
            $this->sendJson(['error' => 'Authentication validation failed.'], 401);
            return;
        }

        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT username, email FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->sendJson(['error' => 'User not found.'], 404);
                return;
            }

            $this->sendJson([
                'success' => true,
                'username' => $user['username'],
                'email' => $user['email']
            ]);
        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database failure: ' . $e->getMessage()], 500);
        }
    }

    private function dispatchReverificationEmail($email, $username, $token) {
        $activationLink = "https://localhost/api/verify?token=" . urlencode($token);
        $subject = "Verify Your New Camagru Email Address";
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Camagru Team <segreteria.camagru@gmail.com>\r\n";
        
        $message = "<html><body><h2>Hello " . htmlspecialchars($username) . ",</h2>"
                 . "<p>You updated your profile email. Click <a href='{$activationLink}'>here</a> to securely re-verify your account access.</p></body></html>";
        
        mail($email, $subject, $message, $headers);
    }

    private function sendJson($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}
