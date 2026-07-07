<?php
// controllers/AccountController.php

require_once dirname(__DIR__) . '/config/database.php';

class AccountController {

    /**
     * POST /api/forgot-password
     * Generates a temporary reset token and emails it out
     */
    public function forgotPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $this->sendJson(['error' => 'Please provide a valid email address.'], 400);
            return;
        }

        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT id, username FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $updateStmt = $db->prepare("UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE id = :id");
                $updateStmt->execute([
                    ':token' => $token,
                    ':expires' => $expires,
                    ':id' => $user['id']
                ]);

                $resetLink = "http://localhost:8080/reset-password?token=" . urlencode($token);
                
                // Safety net: Log to terminal so you can copy/paste it on firewalled public Wi-Fi
                error_log("PASSWORD RESET LINK FOR " . $user['username'] . " >>>: " . $resetLink);

                $subject = "Reset Your Camagru Password";
                $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Camagru Team <segreteria.camagru@gmail.com>\r\n";
                $message = "<html><body><h3>Hello " . htmlspecialchars($user['username']) . ",</h3><p>Click <a href='{$resetLink}'>here</a> to safely reset your password.</p></body></html>";
                
                mail($email, $subject, $message, $headers);
            }

            $this->sendJson(['success' => true, 'message' => 'If this email address exists in our database, a initialization token link has been sent.']);
        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database operation failure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/reset-password
     * Consumes token and overwrites with a fresh password hash
     */
    public function resetPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = trim($input['token'] ?? '');
        $newPassword = $input['password'] ?? '';

        if (empty($token) || strlen($newPassword) < 8) {
            $this->sendJson(['error' => 'Invalid structural inputs. Passwords must be at least 8 characters.'], 400);
            return;
        }

        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_expires_at > NOW()");
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->sendJson(['error' => 'The link is either invalid or expired.'], 400);
                return;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $updateStmt = $db->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_expires_at = NULL WHERE id = :id");
            $updateStmt->execute([
                ':password' => $hashedPassword,
                ':id' => $user['id']
            ]);

            $this->sendJson(['success' => true, 'message' => 'Password reset successfully! You can now log in.']);
        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database exception: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/update-profile (Protected route)
     * Handles updating authenticated settings profiles
     */
    public function updateProfile() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['token']) || $_SESSION['token'] !== $token) {
            $this->sendJson(['error' => 'Unauthorized entry parameters.'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);

        $newUsername = trim($input['username'] ?? '');
        $newEmail = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $newPassword = $input['password'] ?? '';

        $db = Database::getInstance();

        try {
            $stmt = $db->prepare("SELECT username, email FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $currentUser = $stmt->fetch();

            $updateFields = [];
            $params = [':id' => $userId];

            if (!empty($newUsername) && $newUsername !== $currentUser['username']) {
                if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
                    $this->sendJson(['error' => 'Username must be 3-20 characters.'], 400); return;
                }
                $updateFields[] = "username = :username";
                $params[':username'] = $newUsername;
            }

            if (!empty($input['email']) && $newEmail !== $currentUser['email']) {
                if (!$newEmail) { $this->sendJson(['error' => 'Invalid email address.'], 400); return; }
                
                $verificationToken = bin2hex(random_bytes(32));
                $updateFields[] = "email = :email";
                $updateFields[] = "is_verified = FALSE";
                $updateFields[] = "verification_token = :v_token";
                
                $params[':email'] = $newEmail;
                $params[':v_token'] = $verificationToken;
                
                // Log verification string link to terminal for local safety testing checks
                error_log("RE-VERIFY NEW EMAIL LINK >>> http://localhost:8080/api/verify?token=" . $verificationToken);
            }

            if (!empty($newPassword)) {
                if (strlen($newPassword) < 8) { $this->sendJson(['error' => 'Password must be 8+ characters.'], 400); return; }
                $updateFields[] = "password = :password";
                $params[':password'] = password_hash($newPassword, PASSWORD_BCRYPT);
            }

            if (empty($updateFields)) {
                $this->sendJson(['error' => 'No fields were changed.'], 400);
                return;
            }

            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = :id";
            $updateStmt = $db->prepare($sql);
            $updateStmt->execute($params);

            $emailRevoked = in_array("is_verified = FALSE", $updateFields);
            if ($emailRevoked) {
                session_destroy();
            }

            $this->sendJson([
                'success' => true,
                'email_changed' => $emailRevoked,
                'message' => $emailRevoked 
                    ? 'Profile updated! Please re-verify your new email address before logging in again.' 
                    : 'Profile updated successfully!'
            ]);

        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database operational error: ' . $e->getMessage()], 500);
        }
    }

    private function sendJson($data, $statusCode = 200) {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}
