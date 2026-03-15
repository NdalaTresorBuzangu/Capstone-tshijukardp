<?php
/**
 * Document Controller (MVC)
 * Handles document CRUD; used by web router and API.
 */

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Document;
use App\Models\User;

class DocumentController extends BaseController
{
    private Document $documentModel;
    private User $userModel;

    public function __construct(\mysqli $db)
    {
        parent::__construct($db);
        $this->documentModel = new Document($db);
        $this->userModel = new User($db);
    }

    /** Web: GET documentID → show document view page */
    public function viewPage(): void
    {
        $this->requireLogin();
        $this->render('view_document_page.php', []);
    }

    /** Web: GET path, type, prelossID|id → show image/file view page (preloss or issuer stored) */
    public function viewImagePage(): void
    {
        $this->requireLogin();
        $this->render('view_image_page.php', []);
    }

    /** Stream image/file from uploads/images/ (preloss, issuer). GET path=, download=1 optional. */
    public function viewImage(): void
    {
        $this->requireLogin();
        $path = isset($_GET['path']) ? trim($_GET['path']) : '';
        $path = str_replace(['../', '..\\', '\\'], ['', '', '/'], $path);
        $path = trim($path, '/');
        if ($path === '' || strpos($path, '..') !== false) {
            header('HTTP/1.0 400 Bad Request');
            exit('Invalid path.');
        }
        $projectRoot = dirname(__DIR__);
        $baseDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        $fullPath = $baseDir . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $realBase = realpath($baseDir);
        $realFull = $fullPath && is_file($fullPath) ? realpath($fullPath) : false;
        if ($realBase === false || $realFull === false || strpos($realFull, $realBase) !== 0 || !is_readable($realFull)) {
            header('HTTP/1.0 404 Not Found');
            exit('File not found.');
        }
        $ext = strtolower(pathinfo($realFull, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($realFull));
        header('Content-Transfer-Encoding: binary');
        $disposition = (isset($_GET['download']) && $_GET['download'] === '1') ? 'attachment' : 'inline';
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($realFull) . '"');
        readfile($realFull);
        exit;
    }

    /** Web: POST documentID → delete and redirect by role */
    public function deleteSubmit(): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['documentID'])) {
            $_SESSION['message'] = 'Invalid request.';
            $_SESSION['message_type'] = 'error';
            $this->redirect($this->backUrlByRole($_SESSION['user_role'] ?? ''));
        }
        $documentID = trim($_POST['documentID']);
        $userID = (int) ($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? '';
        $result = $this->delete($documentID, $userID, $userRole);
        if (!$result['success']) {
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = 'error';
            $this->redirect('index.php?controller=Document&action=view_page&documentID=' . urlencode($documentID));
        }
        $_SESSION['message'] = 'Document deleted.';
        $_SESSION['message_type'] = 'success';
        $this->redirect($this->backUrlByRole($userRole));
    }

    private function backUrlByRole(string $role): string
    {
        return match ($role) {
            'Admin' => 'index.php?controller=Admin&action=dashboard',
            'Document Issuer' => 'index.php?controller=Institution&action=panel',
            default => 'index.php?controller=Seeker&action=pack'
        };
    }

    /**
     * Get all documents (admin view)
     */
    public function getAll(): array
    {
        return [
            'success' => true,
            'documents' => $this->documentModel->getAll()
        ];
    }

    /**
     * Get documents for a seeker (by user ID)
     */
    public function getBySeeker(int $userId): array
    {
        return [
            'success' => true,
            'documents' => $this->documentModel->getBySeekerId($userId)
        ];
    }

    /**
     * Get documents for an issuer
     */
    public function getByIssuer(int $issuerId): array
    {
        return [
            'success' => true,
            'documents' => $this->documentModel->getByIssuerId($issuerId)
        ];
    }

    /**
     * Get single document by ID
     */
    public function getById(string $documentId): array
    {
        $doc = $this->documentModel->findById($documentId);

        if (!$doc) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        return ['success' => true, 'document' => $doc];
    }

    /**
     * Submit a new document request
     */
    public function submit(array $data, ?array $file = null): array
    {
        // Validate required fields
        $required = ['userId', 'issuerId', 'typeId', 'description', 'location'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }

        // Handle file upload
        $imagePath = null;
        if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleFileUpload($file, 'preloss');
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['path'];
            }
        }

        $documentId = $this->documentModel->create([
            'userId' => (int) $data['userId'],
            'issuerId' => (int) $data['issuerId'],
            'typeId' => (int) $data['typeId'],
            'description' => $data['description'],
            'location' => $data['location'],
            'imagePath' => $imagePath,
            'statusId' => 1 // Pending
        ]);

        if (!$documentId) {
            return ['success' => false, 'message' => 'Failed to create document request.'];
        }

        return [
            'success' => true,
            'message' => 'Document request submitted successfully.',
            'documentId' => $documentId,
            'documentID' => $documentId
        ];
    }

    /**
     * Update document status (for issuers)
     */
    public function updateStatus(string $documentId, int $statusId, ?int $issuerId = null): array
    {
        // Verify document exists
        $doc = $this->documentModel->findById($documentId);
        if (!$doc) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        // If issuer ID provided, verify ownership
        if ($issuerId && (int) $doc['documentIssuerID'] !== $issuerId) {
            return ['success' => false, 'message' => 'Not authorized to update this document.'];
        }

        // Update status
        $success = $this->documentModel->updateStatus($documentId, $statusId);

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to update status.'];
        }

        // If completed, you could trigger email notification here
        if ($statusId === 3) {
            $this->notifyCompletion($doc);
        }

        return [
            'success' => true,
            'message' => 'Document status updated.'
        ];
    }

    /**
     * Delete document
     */
    public function delete(string $documentId, ?int $userId = null, ?string $userRole = null): array
    {
        $doc = $this->documentModel->findById($documentId);
        if (!$doc) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        // Check authorization
        if ($userId && $userRole !== 'Admin') {
            $isOwner = (int) $doc['userID'] === $userId;
            $isIssuer = (int) $doc['documentIssuerID'] === $userId;
            if (!$isOwner && !$isIssuer) {
                return ['success' => false, 'message' => 'Not authorized to delete this document.'];
            }
        }

        $success = $this->documentModel->delete($documentId);

        return $success
            ? ['success' => true, 'message' => 'Document deleted.']
            : ['success' => false, 'message' => 'Failed to delete document.'];
    }

    /**
     * Get document statistics
     */
    public function getStats(?int $issuerId = null): array
    {
        return [
            'success' => true,
            'stats' => $this->documentModel->getStats($issuerId)
        ];
    }

    /**
     * Get document types
     */
    public function getTypes(): array
    {
        return [
            'success' => true,
            'types' => $this->documentModel->getTypes()
        ];
    }

    /**
     * Get status list
     */
    public function getStatuses(): array
    {
        return [
            'success' => true,
            'statuses' => $this->documentModel->getStatuses()
        ];
    }

    /**
     * Get all document issuing institutions
     */
    public function getIssuers(): array
    {
        return [
            'success' => true,
            'issuers' => $this->userModel->getByRole('Document Issuer')
        ];
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload(array $file, string $subfolder = ''): array
    {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return ['success' => false, 'message' => 'File type not allowed. Allowed: PDF, JPG, PNG, GIF, WebP.'];
        }

        $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        if ($subfolder) {
            $uploadDir .= $subfolder . DIRECTORY_SEPARATOR;
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $relativePath = 'uploads/images/' . ($subfolder ? $subfolder . '/' : '') . $fileName;
            return ['success' => true, 'path' => $relativePath];
        }

        return ['success' => false, 'message' => 'File upload failed.'];
    }

    /**
     * Notify user of document completion
     */
    private function notifyCompletion(array $document): void
    {
        $seekerEmail = $document['seekerEmail'] ?? null;
        if ($seekerEmail) {
            // Email notification would go here
        }
    }

    /**
     * Get document model (for direct access)
     */
    public function getDocumentModel(): Document
    {
        return $this->documentModel;
    }
}
