<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private static function getMailer() {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'mail.your-server.de';       // GMX SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'authenticator@trinkbecherdepot.de';  // ÄNDERN!
            $mail->Password   = 'cJ90NH4rk35HfGyiheulsuse';      // ÄNDERN!
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Sender
            $mail->setFrom('authenticator@trinkbecherdepot.de', 'Global Login');
            $mail->addReplyTo('authenticator@trinkbecherdepot.de', 'Support');
            
            return $mail;
            
        } catch (Exception $e) {
            self::logError("Mailer creation failed: " . $e->getMessage());
            return null;
        }
    }
    
    private static function logEmail($type, $toEmail, $data, $success = true) {
        $logFile = __DIR__ . '/../../logs/email.log';
        $status = $success ? 'SUCCESS' : 'FAILED';
        $message = date('Y-m-d H:i:s') . " - {$type} to {$toEmail}: {$data} [{$status}]\n";
        file_put_contents($logFile, $message, FILE_APPEND);
    }
    
    private static function logError($message) {
        $logFile = __DIR__ . '/../../logs/email.log';
        $errorMsg = date('Y-m-d H:i:s') . " - ERROR: {$message}\n";
        file_put_contents($logFile, $errorMsg, FILE_APPEND);
    }
    
    public static function sendVerificationEmail($toEmail, $verificationUrl) {
        $mail = self::getMailer();
        if (!$mail) {
            self::logEmail('VERIFICATION', $toEmail, $verificationUrl, false);
            return false;
        }
        
        try {
            $mail->addAddress($toEmail);
            $mail->Subject = 'Verify Your Email - Global Login';
            
            $mail->Body = "
                <h2>Welcome to Global Login!</h2>
                <p>Please verify your email address by clicking the link below:</p>
                <p><a href='{$verificationUrl}' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
                <p>Or copy this link: {$verificationUrl}</p>
                <p>This link expires in 1 hour.</p>
                <hr>
                <small>If you didn't create an account, please ignore this email.</small>
            ";
            
            $mail->AltBody = "Verify your email: {$verificationUrl}\nThis link expires in 1 hour.";
            $mail->isHTML(true);
            
            $mail->send();
            self::logEmail('VERIFICATION', $toEmail, $verificationUrl, true);
            return true;
            
        } catch (Exception $e) {
            self::logError("Verification email failed: " . $e->getMessage());
            self::logEmail('VERIFICATION', $toEmail, $verificationUrl, false);
            return false;
        }
    }
    
    public static function sendPasswordResetEmail($toEmail, $resetUrl) {
        $mail = self::getMailer();
        if (!$mail) {
            self::logEmail('PASSWORD_RESET', $toEmail, $resetUrl, false);
            return false;
        }
        
        try {
            $mail->addAddress($toEmail);
            $mail->Subject = 'Password Reset Request - Global Login';
            
            $mail->Body = "
                <h2>Password Reset Request</h2>
                <p>You requested to reset your password. Click the link below:</p>
                <p><a href='{$resetUrl}' style='padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                <p>Or copy this link: {$resetUrl}</p>
                <p>This link expires in 1 hour.</p>
                <hr>
                <small>If you didn't request a password reset, please ignore this email.</small>
            ";
            
            $mail->AltBody = "Reset your password: {$resetUrl}\nThis link expires in 1 hour.";
            $mail->isHTML(true);
            
            $mail->send();
            self::logEmail('PASSWORD_RESET', $toEmail, $resetUrl, true);
            return true;
            
        } catch (Exception $e) {
            self::logError("Password reset email failed: " . $e->getMessage());
            self::logEmail('PASSWORD_RESET', $toEmail, $resetUrl, false);
            return false;
        }
    }
    
    public static function send2FACodeEmail($toEmail, $code) {
        $mail = self::getMailer();
        if (!$mail) {
            self::logEmail('2FA_CODE', $toEmail, $code, false);
            return false;
        }
        
        try {
            $mail->addAddress($toEmail);
            $mail->Subject = 'Your 2FA Code - Global Login';
            
            $mail->Body = "
                <h2>Your Two-Factor Authentication Code</h2>
                <p>Use the following code to complete your login:</p>
                <div style='font-size: 24px; font-weight: bold; letter-spacing: 5px; padding: 20px; background: #f5f5f5; text-align: center; margin: 20px 0;'>
                    {$code}
                </div>
                <p>This code expires in 5 minutes.</p>
                <hr>
                <small>If you didn't request this code, please secure your account.</small>
            ";
            
            $mail->AltBody = "Your 2FA code: {$code}\nThis code expires in 5 minutes.";
            $mail->isHTML(true);
            
            $mail->send();
            self::logEmail('2FA_CODE', $toEmail, $code, true);
            return true;
            
        } catch (Exception $e) {
            self::logError("2FA email failed: " . $e->getMessage());
            self::logEmail('2FA_CODE', $toEmail, $code, false);
            return false;
        }
    }
}