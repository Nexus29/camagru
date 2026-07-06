<?php
// backend/controllers/PostController.php

require_once dirname(__DIR__) . '/config/database.php';

class PostController {
    

    public function getPosts() {
        $db = Database::getInstance();
        $filter = $_GET['filter'] ?? '';

        try {
            $sql = "SELECT p.id, p.image_path, u.username 
                    FROM posts p
                    INNER JOIN users u ON p.user_id = u.id 
                    ORDER BY p.created_at DESC";
                    
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->sendJson($posts ? $posts : [], 200);

        } catch (PDOException $e) {
            $this->sendJson(['error' => 'Database layer query exception: ' . $e->getMessage()], 500);
        }
    }

    private function sendJson($data, $statusCode = 200) {
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json; charset=utf-8', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}
