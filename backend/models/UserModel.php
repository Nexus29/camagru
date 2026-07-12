<?php

require_once dirname(__DIR__) . '/config/database.php';

class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, username, email, password, verification_token, is_verified, notify_on_comment FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT id, username, password, email, is_verified FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByVerificationToken($token) {
        $stmt = $this->db->prepare("SELECT id, is_verified FROM users WHERE verification_token = :token");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isUniqueConflict($username, $email, $excludeUserId = null) {
        if ($excludeUserId) {
            $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE (username = :username OR email = :email) AND id != :id");
            $stmt->execute([':username' => $username, ':email' => $email, ':id' => $excludeUserId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
            $stmt->execute([':email' => $email, ':username' => $username]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function register($username, $email, $hashedPassword, $token) {
        $sql = "INSERT INTO users (email, username, password, verification_token, is_verified) 
                VALUES (:email, :username, :password, :token, FALSE)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':email'    => $email,
            ':username' => $username,
            ':password' => $hashedPassword,
            ':token'    => $token
        ]);
    }

    public function verifyAccount($id) {
        $stmt = $this->db->prepare("UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateProfileFields($id, $fields, $params) {
        $params[':id'] = $id;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

	public function findByEmail($email) {
		$stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE email = :email");
		$stmt->execute([':email' => $email]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function setPasswordResetToken($id, $token, $expiresAt) {
		$stmt = $this->db->prepare("
			UPDATE users 
			SET reset_token = :token, 
				reset_expires_at = :expires_at 
			WHERE id = :id
		");
		return $stmt->execute([
			':token' => $token,
			':expires_at' => $expiresAt,
			':id' => $id
		]);
	}

    public function findByResetToken($token) {
        $stmt = $this->db->prepare("SELECT id, reset_expires_at FROM users WHERE reset_token = :token");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function resetUserPassword($id, $hashedPassword) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password = :password, 
                reset_token = NULL, 
                reset_expires_at = NULL 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $id
        ]);
    }
}
