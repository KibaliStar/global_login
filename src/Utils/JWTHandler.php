<?php
namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    public static function generateToken($userId, $email, $username) {
        $issuedAt = time();
        $expire = $issuedAt + (JWT_EXPIRE_HOURS * 3600);
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId,
            'email' => $email,
            'username' => $username
        ];
        
        return JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
    }
    
    public static function validateToken($token) {
        try {
            return JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        } catch (\Exception $e) {
            return false;
        }
    }
}