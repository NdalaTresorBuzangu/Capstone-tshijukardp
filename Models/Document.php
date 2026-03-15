<?php
/**
 * Document Model
 * 
 * Handles all database operations related to documents.
 * Pure data access layer - no business logic.
 */

namespace App\Models;

class Document
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Find document by ID
     */
    public function findById(string $documentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, 
                    u.userName AS seekerName, u.userEmail AS seekerEmail,
                    s.userName AS issuerName,
                    m.typeName AS documentType,
                    st.statusName
             FROM Document d
             LEFT JOIN User u ON d.userID = u.userID
             LEFT JOIN User s ON d.documentIssuerID = s.userID
             LEFT JOIN DocumentType m ON d.documentTypeID = m.documentTypeID
             LEFT JOIN Status st ON d.statusID = st.statusID
             WHERE d.documentID = ?"
        );
        if (!$stmt) return null;

        $stmt->bind_param('s', $documentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $doc = $result->fetch_assoc();
        $stmt->close();

        return $doc ?: null;
    }

    /**
     * Get all documents with related data
     */
    public function getAll(): array
    {
        $sql = "SELECT d.*, 
                       u.userName AS seekerName,
                       s.userName AS issuerName,
                       m.typeName AS documentType,
                       st.statusName
                FROM Document d
                LEFT JOIN User u ON d.userID = u.userID
                LEFT JOIN User s ON d.documentIssuerID = s.userID
                LEFT JOIN DocumentType m ON d.documentTypeID = m.documentTypeID
                LEFT JOIN Status st ON d.statusID = st.statusID
                ORDER BY d.submissionDate DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get documents by seeker (user) ID
     */
    public function getBySeekerId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, 
                    s.userName AS issuerName,
                    m.typeName AS documentType,
                    st.statusName
             FROM Document d
             LEFT JOIN User s ON d.documentIssuerID = s.userID
             LEFT JOIN DocumentType m ON d.documentTypeID = m.documentTypeID
             LEFT JOIN Status st ON d.statusID = st.statusID
             WHERE d.userID = ?
             ORDER BY d.submissionDate DESC"
        );
        if (!$stmt) return [];

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $docs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $docs;
    }

    /**
     * Get documents by issuer ID
     */
    public function getByIssuerId(int $issuerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, 
                    u.userName AS seekerName, u.userEmail AS seekerEmail,
                    m.typeName AS documentType,
                    st.statusName
             FROM Document d
             LEFT JOIN User u ON d.userID = u.userID
             LEFT JOIN DocumentType m ON d.documentTypeID = m.documentTypeID
             LEFT JOIN Status st ON d.statusID = st.statusID
             WHERE d.documentIssuerID = ?
             ORDER BY d.submissionDate DESC"
        );
        if (!$stmt) return [];

        $stmt->bind_param('i', $issuerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $docs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $docs;
    }

    /**
     * Create a new document
     */
    public function create(array $data): ?string
    {
        $documentId = uniqid('doc_');

        $stmt = $this->db->prepare(
            "INSERT INTO Document 
             (documentID, userID, documentIssuerID, documentTypeID, statusID, description, location, imagePath, submissionDate) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$stmt) return null;

        $statusId = $data['statusId'] ?? 1; // Default: Pending
        $imagePath = $data['imagePath'] ?? null;

        $stmt->bind_param(
            'siiissss',
            $documentId,
            $data['userId'],
            $data['issuerId'],
            $data['typeId'],
            $statusId,
            $data['description'],
            $data['location'],
            $imagePath
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success ? $documentId : null;
    }

    /**
     * Update document status
     */
    public function updateStatus(string $documentId, int $statusId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE Document SET statusID = ? WHERE documentID = ?"
        );
        if (!$stmt) return false;

        $stmt->bind_param('is', $statusId, $documentId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Update document
     */
    public function update(string $documentId, array $data): bool
    {
        $fields = [];
        $types = '';
        $values = [];

        if (isset($data['statusId'])) {
            $fields[] = 'statusID = ?';
            $types .= 'i';
            $values[] = $data['statusId'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $types .= 's';
            $values[] = $data['description'];
        }
        if (isset($data['imagePath'])) {
            $fields[] = 'imagePath = ?';
            $types .= 's';
            $values[] = $data['imagePath'];
        }
        if (isset($data['completionDate'])) {
            $fields[] = 'completionDate = ?';
            $types .= 's';
            $values[] = $data['completionDate'];
        }

        if (empty($fields)) return false;

        $types .= 's';
        $values[] = $documentId;

        $sql = "UPDATE Document SET " . implode(', ', $fields) . " WHERE documentID = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Delete document
     */
    public function delete(string $documentId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Document WHERE documentID = ?");
        if (!$stmt) return false;

        $stmt->bind_param('s', $documentId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get document statistics
     */
    public function getStats(?int $issuerId = null): array
    {
        $where = $issuerId ? " WHERE documentIssuerID = ?" : "";

        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statusID = 1 THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN statusID = 2 THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN statusID = 3 THEN 1 ELSE 0 END) as completed
                FROM Document" . $where;

        if ($issuerId) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $issuerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
        } else {
            $result = $this->db->query($sql);
            $stats = $result ? $result->fetch_assoc() : null;
        }

        return $stats ?: ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
    }

    /**
     * Get document types
     */
    public function getTypes(): array
    {
        $result = $this->db->query(
            "SELECT documentTypeID as id, typeName as name FROM DocumentType ORDER BY typeName"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get status list
     */
    public function getStatuses(): array
    {
        $result = $this->db->query(
            "SELECT statusID as id, statusName as name FROM Status ORDER BY statusID"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
