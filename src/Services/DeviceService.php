<?php

namespace GlobalLogin\Services;

use GlobalLogin\Models\Database;
use PDO;

class DeviceService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generiere einen eindeutigen Device-Hash
     */
    public function generateDeviceHash($ipAddress, $userAgent): string
    {
        $deviceString = $ipAddress . '|' . $userAgent;
        return hash('sha256', $deviceString);
    }

    /**
     * Prüfe ob Device bekannt ist
     */
    public function isDeviceKnown($userId, $ipAddress, $userAgent): bool
    {
        $deviceHash = $this->generateDeviceHash($ipAddress, $userAgent);
        
        $sql = "SELECT COUNT(*) FROM known_devices 
                WHERE user_id = :user_id AND device_hash = :device_hash";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':device_hash' => $deviceHash
        ]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Füge neues Device hinzu
     */
    public function addDevice($userId, $ipAddress, $userAgent, $deviceName = null): bool
    {
        $deviceHash = $this->generateDeviceHash($ipAddress, $userAgent);
        
        // Prüfe ob bereits existiert
        if ($this->isDeviceKnown($userId, $ipAddress, $userAgent)) {
            $this->updateLastUsed($userId, $deviceHash);
            return true;
        }
        
        $sql = "INSERT INTO known_devices 
                (user_id, device_hash, device_name, ip_address, user_agent) 
                VALUES (:user_id, :device_hash, :device_name, :ip_address, :user_agent)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':device_hash' => $deviceHash,
            ':device_name' => $deviceName,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent
        ]);
    }

    /**
     * Aktualisiere last_used timestamp
     */
    public function updateLastUsed($userId, $deviceHash): bool
    {
        $sql = "UPDATE known_devices 
                SET last_used = CURRENT_TIMESTAMP 
                WHERE user_id = :user_id AND device_hash = :device_hash";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':device_hash' => $deviceHash
        ]);
    }

    /**
     * Hole alle bekannten Devices eines Users
     */
    public function getUserDevices($userId): array
    {
        $sql = "SELECT * FROM known_devices 
                WHERE user_id = :user_id 
                ORDER BY last_used DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Entferne ein Device
     */
    public function removeDevice($userId, $deviceId): bool
    {
        $sql = "DELETE FROM known_devices 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $deviceId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Prüfe ob Device innerhalb der Grace Period ist
     */
    public function isWithinGracePeriod($userId, $ipAddress, $userAgent, $graceHours = 24): bool
    {
        $deviceHash = $this->generateDeviceHash($ipAddress, $userAgent);
        
        $sql = "SELECT TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours_ago 
                FROM known_devices 
                WHERE user_id = :user_id AND device_hash = :device_hash";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':device_hash' => $deviceHash
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return true; // Neues Device → innerhalb Grace Period
        }
        
        return $result['hours_ago'] <= $graceHours;
    }
}
