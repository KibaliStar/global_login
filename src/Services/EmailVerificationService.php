<?php
namespace App\Services;

use App\Models\Database;

class EmailVerificationService {
    
    public static function createVerification($userId, $email) {
        $code = bin2hex(random_bytes(16)); // 32-char hex code
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO email_verifications (user_id, verification_code, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $code, $expires]);
        
        return $code;
    }
    
    public static function verifyCode($code) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ev.*, u.email 
            FROM email_verifications ev
            JOIN users u ON ev.user_id = u.id
            WHERE ev.verification_code = ? 
            AND ev.expires_at > NOW() 
            AND ev.is_used = FALSE
        ");
        $stmt->execute([$code]);
        $verification = $stmt->fetch();
        
        if (!$verification) {
            return false;
        }
        
        // Mark as used
        $stmt = $db->prepare("
            UPDATE email_verifications 
            SET is_used = TRUE 
            WHERE id = ?
        ");
        $stmt->execute([$verification['id']]);
        
        // Activate user
        $stmt = $db->prepare("
            UPDATE users 
            SET is_active = TRUE, email_verified = TRUE 
            WHERE id = ?
        ");
        $stmt->execute([$verification['user_id']]);
        
        return $verification;
    }
}
?>