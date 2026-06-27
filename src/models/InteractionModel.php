<?php
// src/models/InteractionModel.php

class InteractionModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // --- Like Management ---
    public function toggleLike($userId, $snapshotId) {
        // Check if like exists
        $stmt = $this->pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND snapshot_id = ?");
        $stmt->execute([$userId, $snapshotId]);
        
        if ($stmt->fetch()) {
            // Remove Like
            $del = $this->pdo->prepare("DELETE FROM likes WHERE user_id = ? AND snapshot_id = ?");
            $del->execute([$userId, $snapshotId]);
            return ['action' => 'unliked'];
        } else {
            // Add Like
            $ins = $this->pdo->prepare("INSERT INTO likes (user_id, snapshot_id) VALUES (?, ?)");
            $ins->execute([$userId, $snapshotId]);
            return ['action' => 'liked'];
        }
    }

    public function getLikeCount($snapshotId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM likes WHERE snapshot_id = ?");
        $stmt->execute([$snapshotId]);
        return (int)$stmt->fetchColumn();
    }

    // --- Comment Management ---
    public function addComment($userId, $snapshotId, $commentText) {
        $stmt = $this->pdo->prepare("INSERT INTO comments (user_id, snapshot_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$userId, $snapshotId, $commentText]);
    }

    public function getCommentsForSnapshot($snapshotId) {
        $sql = "SELECT c.*, u.username 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.snapshot_id = ? 
                ORDER BY c.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$snapshotId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>