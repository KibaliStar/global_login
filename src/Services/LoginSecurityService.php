<?php

namespace GlobalLogin\Services;

use GlobalLogin\Models\Database;
use PDO;

class LoginSecurityService
{
    private $db;
    private $deviceService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->deviceService = new DeviceService();
    }

    public function isSuspiciousLogin($userId, $ipAddress, $userAgent): bool
    {
        $isDeviceKnown = $this->deviceService->isDeviceKnown($userId, $ipAddress, $userAgent);
        $isWithinGrace = $this->deviceService->isWithinGracePeriod($userId, $ipAddress, $userAgent);
        $failedAttempts = $this->getRecentFailedAttempts($userId, $ipAddress);
        
        if (!$isDeviceKnown && !$isWithinGrace) return true;
        if ($failedAttempts >= 3) return true;
        if ($this->checkGeoAnomaly($userId, $ipAddress)) return true;
        
        return false;
    }

    private function getRecentFailedAttempts($userId, $ipAddress): int
    {
        $sql = "SELECT COUNT(*) FROM login_attempts 
                WHERE user_id = :user_id 
                AND ip_address = :ip_address 
                AND successful = 0 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':ip_address' => $ipAddress]);
        return (int) $stmt->fetchColumn();
    }

    private function checkGeoAnomaly($userId, $currentIp): bool
    {
        $sql = "SELECT ip_address FROM login_attempts 
                WHERE user_id = :user_id 
                AND successful = 1 
                ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $lastIp = $stmt->fetchColumn();
        
        if (!$lastIp) return false;
        
        $currentIpParts = explode('.', $currentIp);
        $lastIpParts = explode('.', $lastIp);
        
        if (count($currentIpParts) >= 2 && count($lastIpParts) >= 2) {
            if ($currentIpParts[0] !== $lastIpParts[0] || $currentIpParts[1] !== $lastIpParts[1]) {
                return true;
            }
        }
        
        return false;
    }

    public function logLoginAttempt($userId, $ipAddress, $userAgent, $successful): bool
    {
        $sql = "INSERT INTO login_attempts 
                (user_id, ip_address, user_agent, successful) 
                VALUES (:user_id, :ip_address, :user_agent, :successful)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
            ':successful' => $successful ? 1 : 0
        ]);
    }

    public function registerDeviceAfterLogin($userId, $ipAddress, $userAgent): void
    {
        $deviceName = $this->extractDeviceName($userAgent);
        $this->deviceService->addDevice($userId, $ipAddress, $userAgent, $deviceName);
    }

    private function extractDeviceName($userAgent): string
    {
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';
        
        if (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
        elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
        elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
        elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        
        if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
        elseif (strpos($userAgent, 'Mac') !== false) $os = 'macOS';
        elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
        elseif (strpos($userAgent, 'Android') !== false) $os = 'Android';
        elseif (strpos($userAgent, 'iPhone') !== false) $os = 'iOS';
        
        return $os . ' - ' . $browser;
    }
}
