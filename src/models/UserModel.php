<?php
class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($username, $email, $passwordHash, $token) {
        $sql = "INSERT INTO users (username, email, password_hash, activation_token) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$username, $email, $passwordHash, $token]);
    }

    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function activateAccount($token) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = TRUE, activation_token = NULL WHERE activation_token = ?");
        return $stmt->execute([$token]);
    }
}
?>
