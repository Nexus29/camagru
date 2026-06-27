<?php
// src/controllers/GalleryController.php

class GalleryController {
	private $pdo;

	public function __construct($pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * Fetches public snapshots ordered by newest first with cursor-based pagination parameters
	 */
	public function fetchGalleryPage($limit = 6, $offset = 0) {
		$stmt = $this->pdo->prepare("
			SELECT s.*, u.username,
				   (SELECT COUNT(*) FROM likes WHERE snapshot_id = s.id) as like_count,
				   (SELECT COUNT(*) FROM comments WHERE snapshot_id = s.id) as comment_count
			FROM snapshots s
			JOIN users u ON s.user_id = u.id
			ORDER BY s.created_at DESC
			LIMIT :limit OFFSET :offset
		");
		
		// Explicit data binding required due to SQL structure properties
		$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Handles adding comments and updates the snapshot author via notification hooks if requested
	 */
	public function addComment($userId, $snapshotId, $commentText) {
		$commentText = trim(htmlspecialchars($commentText));
		if (empty($commentText)) {
			return ['success' => false, 'message' => 'Comment text cannot be left empty.'];
		}

		$stmt = $this->pdo->prepare("INSERT INTO comments (snapshot_id, user_id, comment_text) VALUES (?, ?, ?)");
		$stmt->execute([$snapshotId, $userId, $commentText]);

		// Social automation layer hook: query snapshot owner notifications criteria
		$stmt = $this->pdo->prepare("
			SELECT u.email, u.notify_on_comment 
			FROM users u 
			JOIN snapshots s ON s.user_id = u.id 
			WHERE s.id = ?
		");
		$stmt->execute([$snapshotId]);
		$owner = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($owner && $owner['notify_on_comment']) {
			// TODO: Trigger mail() configuration alert script asynchronously here
		}

		return ['success' => true, 'message' => 'Comment posted successfully.'];
	}

	/**
	 * Toggles interaction state variables (Like / Unlike) for any given target media entry
	 */
	public function toggleLike($userId, $snapshotId) {
		$stmt = $this->pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND snapshot_id = ?");
		$stmt->execute([$userId, $snapshotId]);
		$like = $stmt->fetch();

		if ($like) {
			// Unlike interaction layer sequence
			$stmt = $this->pdo->prepare("DELETE FROM likes WHERE user_id = ? AND snapshot_id = ?");
			$stmt->execute([$userId, $snapshotId]);
			return ['success' => true, 'action' => 'unliked'];
		} else {
			// Like interaction layer sequence
			$stmt = $this->pdo->prepare("INSERT INTO likes (user_id, snapshot_id) VALUES (?, ?)");
			$stmt->execute([$userId, $snapshotId]);
			return ['success' => true, 'action' => 'liked'];
		}
	}
}
