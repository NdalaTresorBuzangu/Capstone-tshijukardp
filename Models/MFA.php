<?php
/**
 * MFA Model
 * 
 * Handles multi-factor authentication database operations.
 */

namespace App\Models;

class MFA
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Check if MFA is enabled for user
     */
    public function isEnabled(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT mfaEnabled FROM UserMfa WHERE userID = ?"
        );
        if (!$stmt) return false;

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (bool) $row['mfaEnabled'] : false;
    }

    /**
     * Enable MFA for user
     */
    public function enable(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO UserMfa (userID, mfaEnabled, createdAt) 
             VALUES (?, 1, NOW()) 
             ON DUPLICATE KEY UPDATE mfaEnabled = 1"
        );
        if (!$stmt) return false;

        $stmt->bind_param('i', $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Disable MFA for user
     */
    public function disable(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE UserMfa SET mfaEnabled = 0 WHERE userID = ?"
        );
        if (!$stmt) return false;

        $stmt->bind_param('i', $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get MFA record for user
     */
    public function getByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM UserMfa WHERE userID = ?"
        );
        if (!$stmt) return null;

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }
}
