<?php
namespace App\Services;

class EmailService {
    
    private static function logEmail($type, $toEmail, $data) {
        $logFile = __DIR__ . '/../../logs/email.log';
        $message = date('Y-m-d H:i:s') . " - {$type} to {$toEmail}: {$data}\n";
        file_put_contents($logFile, $message, FILE_APPEND);
        return true;
    }
    
    public static function sendVerificationEmail($toEmail, $verificationUrl) {
        self::logEmail('VERIFICATION', $toEmail, $verificationUrl);
        return true;
    }
    
    public static function sendPasswordResetEmail($toEmail, $resetUrl) {
        self::logEmail('PASSWORD_RESET', $toEmail, $resetUrl);
        return true;
    }
    
    public static function send2FACodeEmail($toEmail, $code) {
        self::logEmail('2FA_CODE', $toEmail, $code);
        return true;
    }
}
