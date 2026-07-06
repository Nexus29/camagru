<?php

require_once dirname(__DIR__) . '/config/database.php';

class AuthController {
    
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

        $db = Database::getInstance();

        try {
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
            $checkStmt->execute([':email' => $email, ':username' => $username]);
            if ($checkStmt->fetch()) {
                $this->sendJson(['error' => 'Username or email string token already exists.'], 409);
                return;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $verificationToken = bin2hex(random_bytes(32));

            $insertSql = "INSERT INTO users (email, username, password, verification_token, is_verified) 
                          VALUES (:email, :username, :password, :token, FALSE)";
            
            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                ':email'    => $email,
                ':username' => $username,
                ':password' => $hashedPassword,
                ':token'    => $verificationToken
            ]);

            $this->dispatchEmail($email, $username, $verificationToken);

            $this->sendJson([
                'success' => true,
                'message' => 'Profile compiled successfully! Check your email to verify account activation.'
            ], 201);

        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database execution layer encountered an error: ' . $e->getMessage()], 500);
        }
    }

    public function verify() {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->sendJson(['error' => 'Verification token is missing.'], 400);
            return;
        }

        $db = Database::getInstance();

        try {
            // Verify if a database profile maps directly to this unique string identifier
            $stmt = $db->prepare("SELECT id, is_verified FROM users WHERE verification_token = :token");
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->sendJson(['error' => 'Invalid or expired verification token.'], 400);
                return;
            }

            if ($user['is_verified']) {
                $this->sendJson(['message' => 'Account is already verified. You can sign in.'], 200);
                return;
            }

            // Flip database activation parameters and strip the validation string token
            $updateStmt = $db->prepare("UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE id = :id");
            $updateStmt->execute([':id' => $user['id']]);

            // Clean API feedback enabling single-page routers to complete travel milestones safely
            $this->sendJson([
                'success' => true,
                'message' => 'Account verified successfully! You can now log in.'
            ], 200);

        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database execution error during verification: ' . $e->getMessage()], 500);
        }
    }

    public function login() {
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

        $db = Database::getInstance();

        try {
            $stmt = $db->prepare("SELECT id, username, password, is_verified FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $this->sendJson(['error' => 'Invalid username or password credentials.'], 401);
                return;
            }

            if (!$user['is_verified']) {
                $this->sendJson(['error' => 'Your email verification is incomplete. Check your inbox.'], 403);
                return;
            }

            $sessionToken = bin2hex(random_bytes(32));

            $this->sendJson([
                'success'  => true,
                'token'    => $sessionToken,
                'username' => $user['username']
            ], 200);

        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database layer login exception: ' . $e->getMessage()], 500);
        }
    }

    private function dispatchEmail($email, $username, $token) {
        $activationLink = "http://localhost:8080/api/verify?token=" . urlencode($token);
        $subject = "Confirm your Camagru Account";
        $headers = "MIME-Version: 1.0" . "\r\n" . "Content-type:text/html;charset=UTF-8" . "\r\n" . "From: Camagru Team <segreteria.camagru@gmail.com>" . "\r\n";
        
        $message = "<html><body><h2>Welcome, " . htmlspecialchars($username) . "!</h2><p>Click <a href='{$activationLink}'>here</a> to verify.</p></body></html>";

		@mail($email, $subject, $message, $headers);
    }

    private function sendJson($data, $statusCode = 200) {
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}
