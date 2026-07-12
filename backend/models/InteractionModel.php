<?php

require_once dirname(__DIR__) . '/config/database.php';

class InteractionModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getDbInstance() {
        return $this->db;
    }

    public function checkLikeExists($userId, $postId) {
        $stmt = $this->db->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");
        $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
        return $stmt->fetch();
    }

    public function addLike($userId, $postId) {
        $stmt = $this->db->prepare("INSERT INTO likes (user_id, post_id) VALUES (:user_id, :post_id)");
        return $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
    }

    public function removeLike($userId, $postId) {
        $stmt = $this->db->prepare("DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id");
        return $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
    }

    public function addComment($userId, $postId, $content) {
        $stmt = $this->db->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (:user_id, :post_id, :content)");
        return $stmt->execute([
            ':user_id' => $userId,
            ':post_id' => $postId,
            ':content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
        ]);
    }

    public function getCommentsForPost($postId) {
        $stmt = $this->db->prepare("
            SELECT c.id, c.content as text, u.username 
            FROM comments c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.post_id = :post_id
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([':post_id' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addNotification($ownerId, $senderId, $postId, $type) {
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, sender_id, post_id, type) VALUES (:user_id, :sender_id, :post_id, :type)");
        return $stmt->execute([
            ':user_id'   => $ownerId,
            ':sender_id' => $senderId,
            ':post_id'   => $postId,
            ':type'      => $type
        ]);
    }

    public function removeLikeNotification($senderId, $postId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE sender_id = :sender_id AND post_id = :post_id AND type = 'LIKE'");
        return $stmt->execute([':sender_id' => $senderId, ':post_id' => $postId]);
    }
}
