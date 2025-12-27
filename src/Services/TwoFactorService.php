<?php
namespace App\Services;

use App\Models\Database;
use App\Models\User;

class TwoFactorService {
    
    /**
     * 2FA Code generieren und per Email senden
     */
    public static function generateAndSendCode($userId, $email) {
        $db = Database::getInstance();
        
        // Alte Codes löschen
        $stmt = $db->prepare("DELETE FROM two_factor_codes WHERE user_id = ? AND expires_at < NOW()");
        $stmt->execute([$userId]);
        
        // 6-stelligen Code generieren
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 300); // 5 Minuten gültig
        
        // Code speichern
        $stmt = $db->prepare("
            INSERT INTO two_factor_codes (user_id, code, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $code, $expires]);
        
        // Email senden (im Testmodus loggen)
        $emailSent = EmailService::send2FACodeEmail($email, $code);
        
        return [
            'success' => true,
            'message' => $emailSent 
                ? '2FA code sent to your email.' 
                : '2FA code generated but email failed.',
            'code' => $emailSent ? null : $code // Nur anzeigen wenn Email fehlschlägt
        ];
    }
    
    /**
     * 2FA Code validieren
     */
    public static function verifyCode($userId, $code) {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT * FROM two_factor_codes 
            WHERE user_id = ? 
            AND code = ?
            AND expires_at > NOW() 
            AND is_used = FALSE
        ");
        $stmt->execute([$userId, $code]);
        $codeRecord = $stmt->fetch();
        
        if (!$codeRecord) {
            return false;
        }
        
        // Code als verwendet markieren
        $stmt = $db->prepare("UPDATE two_factor_codes SET is_used = TRUE WHERE id = ?");
        $stmt->execute([$codeRecord['id']]);
        
        return true;
    }
    
    /**
     * Prüfen ob User 2FA aktiviert hat
     */
    public static function is2FAEnabled($userId) {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT two_factor_enabled FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        return $user && $user['two_factor_enabled'] == 1;
    }
    
    /**
     * 2FA für User aktivieren/deaktivieren
     */
    public static function toggle2FA($userId, $enable = true) {
        $db = Database::getInstance();
        
        $value = $enable ? 1 : 0;
        $stmt = $db->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?");
        return $stmt->execute([$value, $userId]);
    }
}
