<?php
// backend/controllers/InteractionController.php

require_once dirname(__FILE__, 2) . '/config/database.php';

class InteractionController {
    
    public function toggleLike($userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = $input['post_id'] ?? null;
        
        if (!$postId) {
            $this->sendJson(['error' => 'Missing parameter: post_id.'], 400);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $postQuery = $db->prepare("SELECT user_id FROM posts WHERE id = :post_id");
            $postQuery->execute([':post_id' => $postId]);
            $post = $postQuery->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                $db->rollBack();
                $this->sendJson(['error' => 'Target image post entity not found.'], 404);
            }

            $postOwnerId = $post['user_id'];

            $stmt = $db->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");
            $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
            $like = $stmt->fetch();
            
            if ($like) {
                $delete = $db->prepare("DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id");
                $delete->execute([':user_id' => $userId, ':post_id' => $postId]);
                
                $delNotify = $db->prepare("DELETE FROM notifications WHERE sender_id = :sender_id AND post_id = :post_id AND type = 'LIKE'");
                $delNotify->execute([':sender_id' => $userId, ':post_id' => $postId]);

                $db->commit();
                $this->sendJson(['liked' => false]);
            } else {
                $insert = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (:user_id, :post_id)");
                $insert->execute([':user_id' => $userId, ':post_id' => $postId]);

                if ($userId !== $postOwnerId) {
                    $notify = $db->prepare("INSERT INTO notifications (user_id, sender_id, post_id, type) VALUES (:user_id, :sender_id, :post_id, 'LIKE')");
                    $notify->execute([
                        ':user_id' => $postOwnerId,
                        ':sender_id' => $userId,
                        ':post_id' => $postId
                    ]);
                }

                $db->commit();
                $this->sendJson(['liked' => true]);
            }
        } catch (Exception $e) {
            $db->rollBack();
            $this->sendJson(['error' => 'Database operation transaction execution crash: ' . $e->getMessage()], 500);
        }
    }

    public function addComment($userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = $input['post_id'] ?? null;
        $content = trim($input['text'] ?? '');

        if (!$postId || empty($content)) {
            $this->sendJson(['error' => 'Comment text length schema boundaries violated.'], 400);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Pull author details and notify preference status dynamically[cite: 4]
            $postQuery = $db->prepare("
                SELECT p.user_id, u.email, u.username, u.notify_on_comment 
                FROM posts p
                INNER JOIN users u ON p.user_id = u.id
                WHERE p.id = :post_id
            ");
            $postQuery->execute([':post_id' => $postId]);
            $postData = $postQuery->fetch(PDO::FETCH_ASSOC);

            if (!$postData) {
                $db->rollBack();
                $this->sendJson(['error' => 'Target image post entity not found.'], 404);
            }

            $postOwnerId = $postData['user_id'];

            // Fetch commenting user identity name string for the email body content mapping
            $commenterQuery = $db->prepare("SELECT username FROM users WHERE id = :user_id");
            $commenterQuery->execute([':user_id' => $userId]);
            $commenterName = $commenterQuery->fetchColumn() ?: 'Anonymous';

            // Insert matching schema 'content' column name specifically[cite: 4]
            $stmt = $db->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (:user_id, :post_id, :content)");
            $stmt->execute([
                ':user_id' => $userId,
                ':post_id' => $postId,
                ':content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
            ]);

            // Real-time transactional tracking pipeline entry creation[cite: 4]
            if ($userId !== $postOwnerId) {
                $notify = $db->prepare("INSERT INTO notifications (user_id, sender_id, post_id, type) VALUES (:user_id, :sender_id, :post_id, 'COMMENT')");
                $notify->execute([
                    ':user_id' => $postOwnerId,
                    ':sender_id' => $userId,
                    ':post_id' => $postId
                ]);

                // 🚀 MATCHING DISPATCH EMAIL LAYOUT: Check preference and dispatch with requested syntax style[cite: 4]
                if (!empty($postData['notify_on_comment']) && $postData['notify_on_comment'] == true) {
                    $email = $postData['email'];
                    $username = $postData['username'];
                    
                    $subject = "New Comment on your Camagru Post";
                    $headers = "MIME-Version: 1.0" . "\r\n" . "Content-type:text/html;charset=UTF-8" . "\r\n" . "From: Camagru Team <segreteria.camagru@gmail.com>" . "\r\n";
                    
                    $message = "<html><body><h2>Hello, " . htmlspecialchars($username) . "!</h2><p>Your post (ID: " . htmlspecialchars($postId) . ") received a new comment from <strong>" . htmlspecialchars($commenterName) . "</strong>.</p></body></html>";

                    @mail($email, $subject, $message, $headers);
                }
            }

            $db->commit();
            $this->sendJson(['success' => true], 201);
        } catch (Exception $e) {
            $db->rollBack();
            $this->sendJson(['error' => 'Database operation execution crash: ' . $e->getMessage()], 500);
        }
    }

    private function sendJson($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}
