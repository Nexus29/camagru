<?php

require_once dirname(__DIR__) . '/config/database.php';

class PostModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($userId, $publicWebPath) {
        $sql = "INSERT INTO posts (user_id, image_path, created_at) VALUES (:user_id, :image_path, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id'    => $userId,
            ':image_path' => $publicWebPath
        ]);
    }

    public function findByIdAndUser($postId, $userId) {
        $stmt = $this->db->prepare("SELECT id, user_id, image_path FROM posts WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $postId, ':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPostWithOwner($postId) {
        $stmt = $this->db->prepare("
            SELECT p.user_id, u.email, u.username, u.notify_on_comment 
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            WHERE p.id = :post_id
        ");
        $stmt->execute([':post_id' => $postId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllPostsWithLikes($currentUserId, $targetUserId = null) {
        if ($targetUserId !== null) {
            $sql = "SELECT p.id, p.image_path, u.username,
                    (SELECT COUNT(*)::int FROM likes WHERE post_id = p.id) as likes_count,
                    (SELECT COUNT(*)::int FROM likes WHERE post_id = p.id AND user_id = :current_user) as user_liked
                    FROM posts p
                    INNER JOIN users u ON p.user_id = u.id 
                    WHERE p.user_id = :user_id
                    ORDER BY p.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $targetUserId,
                ':current_user' => $currentUserId
            ]);
        } else {
            $sql = "SELECT p.id, p.image_path, u.username,
                    (SELECT COUNT(*)::int FROM likes WHERE post_id = p.id) as likes_count,
                    (SELECT COUNT(*)::int FROM likes WHERE post_id = p.id AND user_id = :current_user) as user_liked
                    FROM posts p
                    INNER JOIN users u ON p.user_id = u.id 
                    ORDER BY p.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':current_user' => $currentUserId]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($postId, $userId) {
        $stmt = $this->db->prepare("DELETE FROM posts WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => $postId, ':user_id' => $userId]);
    }
}
