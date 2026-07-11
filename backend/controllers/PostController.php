<?php
// backend/controllers/PostController.php

require_once dirname(__FILE__, 2) . '/config/database.php';

class PostController {

    public function createPost($userId) {
        // 1. Parse Raw Inbound JSON Payload
        $input = json_decode(file_get_contents('php://input'), true);
        $base64Image = $input['image'] ?? null;
        $overlayUrl = $input['overlay'] ?? null;

        if (!$base64Image || !$overlayUrl) {
            $this->sendJson(['error' => 'Missing essential raw payload matrix properties (image/overlay).'], 400);
        }

        try {
            // 2. Clean up the base64 raw string
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    $this->sendJson(['error' => 'Invalid data payload representation layer format.'], 400);
                }
            } else {
                $this->sendJson(['error' => 'Data format structural anomaly matched inside transmission payload.'], 400);
            }

            $decodedData = base64_decode($base64Image);
            if ($decodedData === false) {
                $this->sendJson(['error' => 'Base64 stream byte decryption failed.'], 400);
            }

            // 3. Set up target canvas directories
            // 🟢 UPDATED: Aligned with your real backend container directory structure
            $outputDir = '/var/www/html/uploads_shared/posts/';
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $filename = uniqid('snap_', true) . '.png';
            $targetFilePath = $outputDir . $filename;
            $publicWebPath = '/uploads/posts/' . $filename;

            // 4. Initialize GD image frameworks from raw input data stream
            $userSnapshot = imagecreatefromstring($decodedData);
            if (!$userSnapshot) {
                $this->sendJson(['error' => 'Failed to initialize snapshot layer mapping.'], 500);
            }

            $canvas = imagecreatetruecolor(640, 480);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagecopyresampled($canvas, $userSnapshot, 0, 0, 0, 0, 640, 480, imagesx($userSnapshot), imagesy($userSnapshot));

            // 5. Layer the Selected Overlay on Top
            $overlayFilename = basename($overlayUrl);
            // 🟢 UPDATED: Points directly to your confirmed active overlays mount path
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

            // 6. Store the Public Web Path into the Database
            $db = Database::getInstance();
            $sql = "INSERT INTO posts (user_id, image_path, created_at) VALUES (:user_id, :image_path, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id'    => $userId,
                ':image_path' => $publicWebPath
            ]);

            $this->sendJson(['success' => true, 'image_path' => $publicWebPath], 201);

        } catch (Exception $e) {
            $this->sendJson(['error' => 'Internal compilation layer processing fault: ' . $e->getMessage()], 500);
        }
    }

    public function getPosts($userId = null) {
        $db = Database::getInstance();
        try {
            if ($userId !== null) {
                $sql = "SELECT p.id, p.image_path, u.username 
                        FROM posts p
                        INNER JOIN users u ON p.user_id = u.id 
                        WHERE p.user_id = :user_id
                        ORDER BY p.created_at DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute([':user_id' => $userId]);
            } else {
                $sql = "SELECT p.id, p.image_path, u.username 
                        FROM posts p
                        INNER JOIN users u ON p.user_id = u.id 
                        ORDER BY p.created_at DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute();
            }
            
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->sendJson($posts ? $posts : [], 200);
        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database layer query exception: ' . $e->getMessage()], 500);
        }
    }

	public function deletePost($userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = $input['id'] ?? null;

        if (!$postId) {
            $this->sendJson(['error' => 'Missing explicit target identity property.'], 400);
        }

        $db = Database::getInstance();
        try {
            // 1. Fetch post metadata to verify user ownership context and obtain the disk path
            $stmt = $db->prepare("SELECT image_path FROM posts WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $postId, ':user_id' => $userId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                $this->sendJson(['error' => 'Asset not found or unauthorized operation request match.'], 403);
            }

            // 2. Erase the physical binary file map from the shared volume disk sector
            $filename = basename($post['image_path']);
            $localDiskPath = '/var/www/html/uploads_shared/posts/' . $filename;
            
            if (file_exists($localDiskPath)) {
                @unlink($localDiskPath);
            }

            // 3. Purge the matching row registration from the database cluster
            $deleteStmt = $db->prepare("DELETE FROM posts WHERE id = :id AND user_id = :user_id");
            $deleteStmt->execute([':id' => $postId, ':user_id' => $userId]);

            $this->sendJson(['success' => true, 'message' => 'Asset permanently removed from cluster.'], 200);
        } catch (PDOException $e) {
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
