<?php
/**
 * Update a document – API action (links to database).
 * Request Data: cid (document id), statusId (1=Pending, 2=In Progress, 3=Completed). Form or JSON.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Use POST with cid and statusId (1, 2, or 3).']);
    exit;
}

// Accept form or JSON body
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$documentId = trim((string) ($input['cid'] ?? $input['id'] ?? ''));
$statusId = (int) ($input['statusId'] ?? $input['cnum'] ?? 0);

if ($documentId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing cid (document ID).']);
    exit;
}

$validStatusIds = [1, 2, 3]; // 1=Pending, 2=In Progress, 3=Completed
if (!in_array($statusId, $validStatusIds, true)) {
    $statusId = 1; // default to Pending if invalid
}

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/config.php';

use App\Controllers\DocumentController;

try {
    if (!isset($conn) || !$conn) {
        echo json_encode(['success' => false, 'message' => 'Database not available.']);
        exit;
    }
    $controller = new DocumentController($conn);
    $issuerId = isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'Document Issuer'
        ? (int) $_SESSION['user_id'] : null;

    $result = $controller->updateStatus($documentId, $statusId, $issuerId);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'success']);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
