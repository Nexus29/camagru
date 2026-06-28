<?php
// src/controllers/StudioController.php

class StudioController {
    private $pdo;
    private $uploadDir;
    private $overlayDir;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Define paths relative to src/controllers/
        $this->uploadDir  = __DIR__ . '/../uploads/';
        $this->overlayDir = __DIR__ . '/../uploads/overlays/';

        // Dynamically initialize directories if they don't exist on disk yet
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        if (!file_exists($this->overlayDir)) {
            mkdir($this->overlayDir, 0777, true);
        }
    }

    public function saveSnapshot($userId, $base64Data, $overlayFilename) {
        // Resolve path to the overlay directory inside uploads/
        $overlayPath = $this->overlayDir . basename($overlayFilename);
        
        if (empty($base64Data)) {
            return ['success' => false, 'message' => 'Webcam camera feed stream data is completely empty.'];
        }
        
        if (!file_exists($overlayPath)) {
            return ['success' => false, 'message' => 'The selected overlay graphics frame asset could not be found at: ' . $overlayPath];
        }

        if (strpos($base64Data, ',') !== false) {
            @list(, $base64Data) = explode(',', $base64Data);
        }
        
        $imgData = str_replace(' ', '+', $base64Data);
        $rawBinaryImage = base64_decode($imgData);

        if ($rawBinaryImage === false) {
            return ['success' => false, 'message' => 'Base64 image decoding processing failed.'];
        }

        $cameraImage = @imagecreatefromstring($rawBinaryImage);
        $overlayLayer = @imagecreatefrompng($overlayPath);

        if (!$cameraImage) {
            return ['success' => false, 'message' => 'System error: Camera data could not transform into a GD resource image.'];
        }
        if (!$overlayLayer) {
            imagedestroy($cameraImage);
            return ['success' => false, 'message' => 'System error: Failed to parse overlay asset into a GD resource image.'];
        }

        imagealphablending($cameraImage, true);
        imagesavealpha($cameraImage, true);
        imagealphablending($overlayLayer, true);

        $camWidth   = imagesx($cameraImage);
        $camHeight  = imagesy($cameraImage);
        $frameWidth = imagesx($overlayLayer);
        $frameHeight = imagesy($overlayLayer);

        $composited = imagecopyresampled(
            $cameraImage, $overlayLayer,
            0, 0, 0, 0,
            $camWidth, $camHeight, $frameWidth, $frameHeight
        );

        if (!$composited) {
            imagedestroy($cameraImage);
            imagedestroy($overlayLayer);
            return ['success' => false, 'message' => 'GD layers blending operation failed.'];
        }

        $filename = 'snapshot_' . $userId . '_' . time() . '.png';
        $finalDestination = $this->uploadDir . $filename;

        if (imagepng($cameraImage, $finalDestination)) {
            imagedestroy($cameraImage);
            imagedestroy($overlayLayer);

            $storagePath = '/uploads/' . $filename;
            try {
                $stmt = $this->pdo->prepare("INSERT INTO snapshots (user_id, storage_path, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$userId, $storagePath]);

                return [
                    'success' => true, 
                    'path' => $storagePath, 
                    'message' => 'Snapshot saved successfully.'
                ];
            } catch (PDOException $e) {
                if (file_exists($finalDestination)) {
                    unlink($finalDestination);
                }
                return ['success' => false, 'message' => 'Database persistence query failure: ' . $e->getMessage()];
            }
        }

        imagedestroy($cameraImage);
        imagedestroy($overlayLayer);
        return ['success' => false, 'message' => 'Persistent storage write failure. check permissions.'];
    }

    public function deleteSnapshot($userId, $snapshotId) {
        $stmt = $this->pdo->prepare("SELECT * FROM snapshots WHERE id = ? AND user_id = ?");
        $stmt->execute([$snapshotId, $userId]);
        $snapshot = $snapshotId ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

        if (!$snapshot) {
            return ['success' => false, 'message' => 'Snapshot record not found or access privilege denied.'];
        }

        $filePath = __DIR__ . '/../' . ltrim($snapshot['storage_path'], '/');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $this->pdo->prepare("DELETE FROM snapshots WHERE id = ?");
        $stmt->execute([$snapshotId]);

        return ['success' => true, 'message' => 'Snapshot deleted successfully.'];
    }
}
