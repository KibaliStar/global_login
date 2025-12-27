<?php
namespace App\Services;

use App\Models\User;
use App\Models\Database;
use App\Utils\JWTHandler;

class AuthService {
    
    public static function login($email, $password) {
        // 1. User finden
        $user = User::findByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // 2. Passwort prüfen
        if (!User::verifyPassword($password, $user['password_hash'])) {
            self::logFailedAttempt($user['id']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // 3. Account aktiv?
        if (!$user['is_active'] || !$user['email_verified']) {
            return ['success' => false, 'message' => 'Please verify your email first'];
        }
        
        // 4. 2FA benötigt? (basierend auf Config)
        $requires2FA = self::requiresTwoFactorAuth($user['id']);
        
        if ($requires2FA) {
            // 2FA Code senden
            $codeResult = TwoFactorService::generateAndSendCode($user['id'], $user['email']);
            
            return [
                'success' => true,
                'requires_2fa' => true,
                'user_id' => $user['id'],
                'message' => $codeResult['message']
            ];
        }
        
        // 5. Normales Login
        $token = JWTHandler::generateToken($user['id'], $user['email'], $user['username']);
        
        return [
            'success' => true,
            'requires_2fa' => false,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ];
    }
    
    /**
     * Entscheidet basierend auf Config, ob 2FA benötigt wird
     */
    private static function requiresTwoFactorAuth($userId) {
        $mode = defined('TFA_MODE') ? constant('TFA_MODE') : 'optional';
        
        switch ($mode) {
            case 'disabled':
                return false;
                
            case 'always':
                return true;
                
            case 'suspicious':
                return self::hasSuspiciousActivity($userId);
                
            case 'optional':
            default:
                // User hat 2FA selbst aktiviert?
                if (TwoFactorService::is2FAEnabled($userId)) {
                    return true;
                }
                // Verdächtige Aktivität?
                return self::hasSuspiciousActivity($userId);
        }
    }
    
    /**
     * Prüft auf verdächtige Aktivität (für 'suspicious' Mode)
     */
    private static function hasSuspiciousActivity($userId) {
        // 1. Zu viele fehlgeschlagene Versuche?
        $failedAttempts = self::getRecentFailedAttempts($userId);
        $threshold = defined('TFA_FAILED_ATTEMPTS_THRESHOLD') ? constant('TFA_FAILED_ATTEMPTS_THRESHOLD') : 3;
        
        if ($failedAttempts >= $threshold) {
            return true;
        }
        
        // 2. Neues Gerät? (vereinfacht - prüfe nur IP)
        // In Produktion: User-Agent + Device-Fingerprinting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $isNewDevice = self::isNewDevice($userId, $ip);
        
        return $isNewDevice;
    }
    
    /**
     * Fehlgeschlagene Login-Versuche zählen
     */
    private static function getRecentFailedAttempts($userId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE user_id = ? 
            AND successful = FALSE
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['attempts'];
    }
    
    /**
     * Fehlgeschlagenen Login protokollieren
     */
    private static function logFailedAttempt($userId) {
        $db = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $db->prepare("
            INSERT INTO login_attempts (user_id, ip_address, user_agent, successful) 
            VALUES (?, ?, ?, FALSE)
        ");
        $stmt->execute([$userId, $ip, $userAgent]);
    }
    
    /**
     * Prüft ob Gerät/IP neu ist (vereinfacht)
     */
private static function isNewDevice($userId, $ip) {
    // Erweiterte Device-Erkennung
    try {
        require_once __DIR__ . '/DeviceService.php';
        $deviceService = new \App\Services\DeviceService();
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return !$deviceService->isDeviceKnown($userId, $ip, $userAgent);
        
    } catch (Exception $e) {
        error_log("DeviceService error: " . $e->getMessage());
        return false;
    }
}
    
    // Registrierung bleibt gleich
    public static function register($username, $email, $password) {
        if (User::findByEmail($email)) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        if (User::create($username, $email, $password)) {
            $user = User::findByEmail($email);
            
            $db = Database::getInstance();
            $code = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            
            $stmt = $db->prepare("
                INSERT INTO email_verifications (user_id, verification_code, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $code, $expires]);
            
            $verificationUrl = APP_URL . "/verify-email?code=" . $code;
            
            $emailSent = EmailService::sendVerificationEmail($email, $verificationUrl);
            
            return [
                'success' => true,
                'message' => $emailSent 
                    ? 'Registration successful. Please check your email.' 
                    : 'Registration successful but email sending failed.',
                'verification_url' => $emailSent ? null : $verificationUrl
            ];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
}