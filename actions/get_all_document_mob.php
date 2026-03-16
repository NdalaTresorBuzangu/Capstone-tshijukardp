<?php
/**
 * Get all documents – API action (links to database).
 * Like: get_all_contact_mob for contacts.
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

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/config.php';

use App\Controllers\DocumentController;

try {
    if (!isset($conn) || !$conn) {
        echo json_encode(['success' => false, 'message' => 'Database not available.']);
        exit;
    }
    $controller = new DocumentController($conn);
    $result = $controller->getAll();

    if (empty($result['documents'])) {
        echo json_encode(['success' => true, 'documents' => []]);
        exit;
    }

    // Expose documentIssuerID as issuerId for API consistency
    $documents = $result['documents'];
    foreach ($documents as &$doc) {
        $doc['issuerId'] = $doc['documentIssuerID'] ?? null;
    }
    unset($doc);

    echo json_encode([
        'success' => true,
        'documents' => $documents
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching documents.', 'error' => $e->getMessage()]);
}
