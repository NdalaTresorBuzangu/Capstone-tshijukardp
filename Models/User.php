<?php
/**
 * User Model
 * 
 * Handles all database operations related to users.
 * Pure data access layer - no business logic.
 */

namespace App\Models;

class User
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT userID, userName, userEmail, userPassword, userRole, userContact 
             FROM User WHERE userEmail = ?"
        );
        if (!$stmt) return null;

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT userID, userName, userEmail, userRole, userContact 
             FROM User WHERE userID = ?"
        );
        if (!$stmt) return null;

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    /**
     * Get all users
     */
    public function getAll(): array
    {
        $result = $this->db->query(
            "SELECT userID, userName, userEmail, userRole FROM User ORDER BY userName"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role): array
    {
        $stmt = $this->db->prepare(
            "SELECT userID, userName, userEmail, userRole FROM User WHERE userRole = ?"
        );
        if (!$stmt) return [];

        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $users;
    }

    /**
     * Create a new user (with optional consent timestamps for GDPR/Ghana compliance)
     */
    public function create(array $data): ?int
    {
        $hasConsent = isset($data['terms_accepted_at']) && isset($data['privacy_accepted_at']);
        if ($hasConsent) {
            $stmt = $this->db->prepare(
                "INSERT INTO User (userName, userEmail, userPassword, userRole, userContact, terms_accepted_at, privacy_accepted_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO User (userName, userEmail, userPassword, userRole, userContact) 
                 VALUES (?, ?, ?, ?, ?)"
            );
        }
        if (!$stmt) return null;

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $contact = $data['contact'] ?? '';

        if ($hasConsent) {
            $termsAt = $data['terms_accepted_at'];
            $privacyAt = $data['privacy_accepted_at'];
            $stmt->bind_param(
                'sssssss',
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role'],
                $contact,
                $termsAt,
                $privacyAt
            );
        } else {
            $stmt->bind_param(
                'sssss',
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role'],
                $contact
            );
        }

        $success = $stmt->execute();
        $insertId = $success ? $stmt->insert_id : null;
        $stmt->close();

        return $insertId;
    }

    /**
     * Update consent timestamps (e.g. after user accepts updated terms)
     */
    public function updateConsent(int $userId, string $termsAcceptedAt, string $privacyAcceptedAt): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE User SET terms_accepted_at = ?, privacy_accepted_at = ? WHERE userID = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ssi', $termsAcceptedAt, $privacyAcceptedAt, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $types = '';
        $values = [];

        if (isset($data['name'])) {
            $fields[] = 'userName = ?';
            $types .= 's';
            $values[] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'userEmail = ?';
            $types .= 's';
            $values[] = $data['email'];
        }
        if (isset($data['role'])) {
            $fields[] = 'userRole = ?';
            $types .= 's';
            $values[] = $data['role'];
        }
        if (isset($data['contact'])) {
            $fields[] = 'userContact = ?';
            $types .= 's';
            $values[] = $data['contact'];
        }
        if (isset($data['password'])) {
            $fields[] = 'userPassword = ?';
            $types .= 's';
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) return false;

        $types .= 'i';
        $values[] = $id;

        $sql = "UPDATE User SET " . implode(', ', $fields) . " WHERE userID = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Delete user
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM User WHERE userID = ?");
        if (!$stmt) return false;

        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT userID FROM User WHERE userEmail = ?";
        if ($excludeId) {
            $sql .= " AND userID != ?";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        if ($excludeId) {
            $stmt->bind_param('si', $email, $excludeId);
        } else {
            $stmt->bind_param('s', $email);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user) return null;

        $storedPassword = $user['userPassword'] ?? '';
        if (password_verify($password, $storedPassword)) {
            unset($user['userPassword']); // Don't return password hash
            return $user;
        }

        return null;
    }
}
