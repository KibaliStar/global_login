<?php
namespace App\Models;

class User {
    private static $table = 'users';
    
    public static function create($username, $email, $password) {
        $db = Database::getInstance();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        
        $stmt = $db->prepare("INSERT INTO " . self::$table . " (username, email, password_hash) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $email, $hashedPassword]);
    }
    
    public static function findByEmail($email) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM " . self::$table . " WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM " . self::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public static function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }
    
    public static function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE " . self::$table . " SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    public static function activate($userId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE " . self::$table . " SET is_active = 1, email_verified = 1 WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}