<?php
/**
 * Get one document by id – API action (links to database).
 * Like: get_a_contact_mob for contacts.
 * Request Data: contid or id (document ID).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use GET.']);
    exit;
}

$documentId = trim((string) ($_GET['contid'] ?? $_GET['id'] ?? ''));
if ($documentId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing document id (contid or id).']);
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
    $result = $controller->getById($documentId);

    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }

    // Expose documentIssuerID as issuerId for API consistency
    $doc = $result['document'];
    $doc['issuerId'] = $doc['documentIssuerID'] ?? null;

    echo json_encode($doc, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching document.', 'error' => $e->getMessage()]);
}
