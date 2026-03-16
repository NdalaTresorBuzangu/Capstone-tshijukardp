<?php
/**
 * Subscribe model – Document Issuer / Admissions Office subscription (MVC).
 */

namespace App\Models;

class Subscribe
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Subscribe WHERE userID = ? LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO Subscribe (userID, documentIssuerName, documentIssuerContact, documentIssuerEmail) VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) return false;
        $stmt->bind_param('isss',
            $data['userID'],
            $data['documentIssuerName'],
            $data['documentIssuerContact'],
            $data['documentIssuerEmail']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
