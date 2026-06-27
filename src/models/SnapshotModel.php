<?php
// src/models/SnapshotModel.php

class SnapshotModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function save($userId, $storagePath) {
        $stmt = $this->pdo->prepare("INSERT INTO snapshots (user_id, storage_path, created_at) VALUES (?, ?, NOW())");
        return $stmt->execute([$userId, $storagePath]);
    }

    public function findById($snapshotId) {
        $stmt = $this->pdo->prepare("SELECT * FROM snapshots WHERE id = ?");
        $stmt->execute([$snapshotId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($snapshotId) {
        $stmt = $this->pdo->prepare("DELETE FROM snapshots WHERE id = ?");
        return $stmt->execute([$snapshotId]);
    }

    public function getPaginated($limit, $offset) {
        $sql = "SELECT s.*, u.username 
                FROM snapshots s 
                JOIN users u ON s.user_id = u.id 
                ORDER BY s.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>