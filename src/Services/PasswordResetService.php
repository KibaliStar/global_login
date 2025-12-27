<?php
namespace App\Services;

use App\Models\Database;
use App\Models\User;

class PasswordResetService {
    
    public static function requestReset($email) {
        $db = Database::getInstance();
        
        $user = User::findByEmail($email);
        if (!$user) {
            return ['success' => true, 'message' => 'If the email exists, you will receive reset instructions.'];
        }
        
        $stmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ? AND expires_at < NOW()");
        $stmt->execute([$user['id']]);
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        
        $stmt = $db->prepare("
            INSERT INTO password_resets (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $token, $expires]);
        
        $resetUrl = APP_URL . "/reset-password?token=" . $token;
        
        $emailSent = EmailService::sendPasswordResetEmail($email, $resetUrl);
        
        return [
            'success' => true,
            'message' => $emailSent 
                ? 'Reset instructions sent to your email.' 
                : 'Reset request received but email sending failed. Contact support.',
            'reset_url' => $emailSent ? null : $resetUrl
        ];
    }
    
    public static function validateToken($token) {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT pr.*, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? 
            AND pr.expires_at > NOW() 
            AND pr.is_used = FALSE
        ");
        $stmt->execute([$token]);
        
        return $stmt->fetch();
    }
    
    public static function resetPassword($token, $newPassword) {
        $db = Database::getInstance();
        
        $resetRequest = self::validateToken($token);
        if (!$resetRequest) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
        $success = User::updatePassword($resetRequest['user_id'], $newPassword);
        
        if ($success) {
            $stmt = $db->prepare("UPDATE password_resets SET is_used = TRUE WHERE id = ?");
            $stmt->execute([$resetRequest['id']]);
            
            return ['success' => true, 'message' => 'Password has been reset successfully.'];
        }
        
        return ['success' => false, 'message' => 'Password reset failed'];
    }
}
