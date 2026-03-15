<?php
/**
 * Add a new document request – API action (links to database).
 * Request Data: userId, issuerId, typeId, description, location (form or JSON).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Use POST with userId, issuerId, typeId, description, location.']);
    exit;
}

// Accept form (x-www-form-urlencoded) or JSON body
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$userId = (int) ($input['userId'] ?? $_SESSION['user_id'] ?? 0);
$issuerId = (int) ($input['issuerId'] ?? $input['documentIssuerID'] ?? 0);
$typeId = (int) ($input['typeId'] ?? $input['documentTypeID'] ?? 0);
$description = trim((string) ($input['description'] ?? ''));
$location = trim((string) ($input['location'] ?? ''));

if (!$userId || !$issuerId || !$typeId || $description === '' || $location === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid: userId, issuerId, typeId, description, location (all required).'
    ]);
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
    $file = $_FILES['image'] ?? $_FILES['document'] ?? null;
    $result = $controller->submit([
        'userId' => $userId,
        'issuerId' => $issuerId,
        'typeId' => $typeId,
        'description' => $description,
        'location' => $location,
    ], $file);

    if ($result['success']) {
        $docId = $result['documentId'] ?? $result['documentID'] ?? null;
        echo json_encode([
            'success' => true,
            'message' => 'success',
            'documentId' => $docId,
            'documentID' => $docId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
