<?php
// backend/controllers/InteractionController.php

require_once dirname(__FILE__, 2) . '/models/PostModel.php';
require_once dirname(__FILE__, 2) . '/models/InteractionModel.php';
require_once dirname(__FILE__, 2) . '/models/UserModel.php';

class InteractionController {
    private $postModel;
    private $interactionModel;
    private $userModel;

    public function __construct() {
        $this->postModel = new PostModel();
        $this->interactionModel = new InteractionModel();
        $this->userModel = new UserModel();
    }
    
    public function toggleLike($userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = $input['post_id'] ?? null;
        
        if (!$postId) {
            $this->sendJson(['error' => 'Missing parameter: post_id.'], 400);
        }

        $db = $this->interactionModel->getDbInstance();
        $db->beginTransaction();

        try {
            $post = $this->postModel->getPostWithOwner($postId);

            if (!$post) {
                $db->rollBack();
                $this->sendJson(['error' => 'Target image post entity not found.'], 404);
            }

            $postOwnerId = $post['user_id'];

            if ($this->interactionModel->checkLikeExists($userId, $postId)) {
                $this->interactionModel->removeLike($userId, $postId);
                $this->interactionModel->removeLikeNotification($userId, $postId);

                $db->commit();
                $this->sendJson(['liked' => false]);
            } else {
                $this->interactionModel->addLike($userId, $postId);

                if ($userId !== $postOwnerId) {
                    $this->interactionModel->addNotification($postOwnerId, $userId, $postId, 'LIKE');
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

        $db = $this->interactionModel->getDbInstance();
        $db->beginTransaction();

        try {
            $postData = $this->postModel->getPostWithOwner($postId);

            if (!$postData) {
                $db->rollBack();
                $this->sendJson(['error' => 'Target image post entity not found.'], 404);
            }

            $postOwnerId = $postData['user_id'];

            $commenter = $this->userModel->findById($userId);
            $commenterName = $commenter['username'] ?? 'Anonymous';

            $this->interactionModel->addComment($userId, $postId, $content);

            if ($userId !== $postOwnerId) {
                $this->interactionModel->addNotification($postOwnerId, $userId, $postId, 'COMMENT');

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
