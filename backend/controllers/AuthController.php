<?php
// backend/controllers/AuthController.php
require_once __DIR__ . '/../config/Database.php';

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

            $this->sendJson([
                'success' => true,
                'message' => 'Profile compiled successfully! Check your email to verify account activation.'
            ], 201);

        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database execution layer encountered an error.'], 500);
        }
    }

    private function sendJson($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}
