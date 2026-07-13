<?php
// backend/controllers/PostController.php

require_once dirname(__FILE__, 2) . '/models/PostModel.php';
require_once dirname(__FILE__, 2) . '/models/InteractionModel.php';

class PostController {
    private $postModel;
    private $interactionModel;

    public function __construct() {
        $this->postModel = new PostModel();
        $this->interactionModel = new InteractionModel();
    }

    public function createPost($userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $base64Image = $input['image'] ?? null;
        $overlayUrl = $input['overlay'] ?? null;

        if (!$base64Image || !$overlayUrl) {
            $this->sendJson(['error' => 'Missing essential raw payload matrix properties (image/overlay).'], 400);
        }

        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    $this->sendJson(['error' => 'Invalid data payload representation layer format.'], 400);
                }
            } else {
                $this->sendJson(['error' => 'Data format structural anomaly matched inside transmission payload.'], 400);
            }

            $decodedData = base64_decode($base64Image);
            if ($decodedData === false) {
                $this->sendJson(['error' => 'Base64 stream byte decryption failed.'], 400);
            }

            $outputDir = '/var/www/html/uploads_shared/posts/';
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $filename = uniqid('snap_', true) . '.png';
            $targetFilePath = $outputDir . $filename;
            $publicWebPath = '/uploads/posts/' . $filename;

            $userSnapshot = imagecreatefromstring($decodedData);
            if (!$userSnapshot) {
                $this->sendJson(['error' => 'Failed to initialize snapshot layer mapping.'], 500);
            }

            $canvas = imagecreatetruecolor(640, 480);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagecopyresampled($canvas, $userSnapshot, 0, 0, 0, 0, 640, 480, imagesx($userSnapshot), imagesy($userSnapshot));

            $overlayFilename = basename($overlayUrl);
            $localOverlayPath = '/var/www/html/uploads_shared/overlays/' . $overlayFilename;

            if (!file_exists($localOverlayPath)) {
                imagedestroy($canvas);
                imagedestroy($userSnapshot);
                $this->sendJson(['error' => 'Selected target interface overlay resource not found on disk: ' . $overlayFilename], 404);
            }

            $overlayImage = imagecreatefrompng($localOverlayPath);
            if (!$overlayImage) {
                imagedestroy($canvas);
                imagedestroy($userSnapshot);
                $this->sendJson(['error' => 'Failed to read structural overlay image stream.'], 500);
            }

            imagecopyresampled($canvas, $overlayImage, 0, 0, 0, 0, 640, 480, imagesx($overlayImage), imagesy($overlayImage));

            if (!imagepng($canvas, $targetFilePath)) {
                $this->sendJson(['error' => 'Failed to serialize image file binary to destination drive partition.'], 500);
            }

            imagedestroy($canvas);
            imagedestroy($userSnapshot);
            imagedestroy($overlayImage);

            $this->postModel->create($userId, $publicWebPath);

            $this->sendJson(['success' => true, 'image_path' => $publicWebPath], 201);

        } catch (Exception $e) {
            $this->sendJson(['error' => 'Internal compilation layer processing fault: ' . $e->getMessage()], 500);
        }
    }

    public function getPosts($userId = null) {
        $currentUserId = $userId ? (int)$userId : 0;
        
        try {
            $posts = $this->postModel->getAllPostsWithLikes($currentUserId, $userId);

            foreach ($posts as &$post) {
                $post['user_liked'] = $post['user_liked'] > 0;
                $post['comments'] = $this->interactionModel->getCommentsForPost($post['id']);
            }
            
            $this->sendJson($posts ? $posts : [], 200);
        } catch (Exception $e) {
            $this->sendJson(['error' => 'Database layer query exception: ' . $e->getMessage()], 500);
        }
    }

    public function deletePost($userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = $input['id'] ?? null;

        if (!$postId) {
            $this->sendJson(['error' => 'Missing explicit target identity property.'], 400);
        }

        try {
            $post = $this->postModel->findByIdAndUser($postId, $userId);

            if (!$post) {
                $this->sendJson(['error' => 'Asset not found or unauthorized operation request match.'], 403);
            }

            $filename = basename($post['image_path']);
            $localDiskPath = '/var/www/html/uploads_shared/posts/' . $filename;
            
            if (file_exists($localDiskPath)) {
                @unlink($localDiskPath);
            }

            $this->postModel->delete($postId, $userId);

            $this->sendJson(['success' => true, 'message' => 'Asset permanently removed from cluster.'], 200);
        } catch (Exception $e) {
            $this->sendJson(['error' => 'Database operation failure during target purge: ' . $e->getMessage()], 500);
        }
    }

    private function sendJson($data, $statusCode = 200) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}
