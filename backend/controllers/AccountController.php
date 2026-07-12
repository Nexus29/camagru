<?php

require_once dirname(__DIR__) . '/models/UserModel.php';

class AccountController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function updateProfile($userId) {
        $input = json_decode(file_get_contents('php://input'), true);

        $newUsername = isset($input['username']) ? trim($input['username']) : '';
        $newEmail = isset($input['email']) ? filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL) : '';
        $newPassword = isset($input['password']) ? $input['password'] : '';

        try {
            $currentUser = $this->userModel->findById($userId);

            if (!$currentUser) {
                $this->sendJson(['error' => 'User profile target entity not found.'], 404);
                return;
            }

            $updateFields = [];
            $params = [];

            // A. Update Username ONLY if it's provided and different
            if (!empty($newUsername) && $newUsername !== $currentUser['username']) {
                if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
                    $this->sendJson(['error' => 'Username must be between 3 and 20 characters.'], 400);
                    return;
                }

                if ($this->userModel->isUniqueConflict($newUsername, '', $userId)) {
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

                if ($this->userModel->isUniqueConflict('', $newEmail, $userId)) {
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

            // D. Intercept and Update Notification preferences
            if (isset($input['notify_on_comment'])) {
                $newNotifyState = $input['notify_on_comment'] ? 1 : 0;
                if ($newNotifyState !== (int)$currentUser['notify_on_comment']) {
                    $updateFields[] = "notify_on_comment = :notify_on_comment";
                    $params[':notify_on_comment'] = $newNotifyState;
                }
            }

            if (empty($updateFields)) {
                $this->sendJson(['error' => 'No modification details were changed.'], 400);
                return;
            }

            $this->userModel->updateProfileFields($userId, $updateFields, $params);

            $emailRevoked = in_array("is_verified = FALSE", $updateFields);

            $this->sendJson([
                'success' => true,
                'email_changed' => $emailRevoked,
                'message' => $emailRevoked
                    ? 'Email changed! Please re-verify your account via your new mailbox before logging back in.'
                    : 'Your account configuration parameters have been updated successfully!'
            ]);

        } catch (Exception $e) {
            $this->sendJson(['error' => 'Database update transaction failed: ' . $e->getMessage()], 500);
        }
    }

    public function getProfile($userId) {
        try {
            $user = $this->userModel->findById($userId);

            if (!$user) {
                $this->sendJson(['error' => 'User not found.'], 404);
                return;
            }

            $this->sendJson([
                'success' => true,
                'username' => $user['username'],
                'email' => $user['email'],
                'notify_on_comment' => (bool)$user['notify_on_comment']
            ]);
        } catch (Exception $e) {
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
