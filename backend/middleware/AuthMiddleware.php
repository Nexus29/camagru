<?php
// backend/middleware/AuthMiddleware.php

class AuthMiddleware {
    public static function authenticate() {
        $authHeader = null;

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }

        if (!$authHeader) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        }

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $tokenParts = explode('.', $token);
            
            if (isset($tokenParts[1])) {
                $payload = json_decode(base64_decode($tokenParts[1]), true);
                $userId = $payload['id'] ?? $payload['user_id'] ?? null;
                
                if ($userId) {
                    return (int)$userId; // 🟢 Clean match!
                }
            }
        }

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8', true, 401);
        echo json_encode(['error' => 'Unauthorized user session token context interface missing.']);
        exit;
    }
}
