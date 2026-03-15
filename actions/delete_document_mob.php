<?php
/**
 * Delete one document – API action (links to database).
 * Like: delete_contact for contacts.
 * Request Data: cid (document ID).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Use POST with form/data: cid = document ID. Response is true/false (Boolean).'
    ]);
    exit;
}

// Accept cid from form or JSON body
$documentId = trim($_POST['cid'] ?? $_POST['id'] ?? '');
if ($documentId === '') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $documentId = trim($input['cid'] ?? $input['id'] ?? '');
    }
}
if ($documentId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing cid (document ID).']);
    exit;
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
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $userRole = $_SESSION['user_role'] ?? null;
    $result = $controller->delete($documentId, $userId, $userRole);

    echo json_encode([
        'success' => $result['success'],
        'message' => $result['message'] ?? ($result['success'] ? 'Deleted.' : 'Failed.')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
