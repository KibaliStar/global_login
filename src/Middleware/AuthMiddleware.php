<?php
namespace App\Middleware;

use App\Utils\JWTHandler;

class AuthMiddleware {
    public static function validate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'No token provided']);
            return false;
        }
        
        $decoded = JWTHandler::validateToken($matches[1]);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            return false;
        }
        
        return $decoded;
    }
}