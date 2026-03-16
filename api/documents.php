<?php
/**
 * Documents API – list, get one, create (admin or document issuer).
 * GET api/documents.php – list (admin: all; issuer: own)
 * GET api/documents.php?id=DOC-xxx – one document
 * POST api/documents.php – create request (admin or issuer; body: userId, issuerId, typeId, description, location)
 */

require_once __DIR__ . '/bootstrap.php';

$auth = requireApiAdminOrIssuer();
$db = getDB();
$documentModel = new \App\Models\Document($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = trim($_GET['id'] ?? '');
    if ($id !== '') {
        $doc = $documentModel->findById($id);
        if (!$doc) {
            apiJson(['success' => false, 'message' => 'Document not found.'], 404);
        }
        if ($auth['user_role'] === 'Document Issuer' && (int) $doc['documentIssuerID'] !== $auth['user_id']) {
            apiJson(['success' => false, 'message' => 'Access denied.'], 403);
        }
        apiJson(['success' => true, 'document' => $doc]);
    }

    if ($auth['user_role'] === 'Admin') {
        $list = $documentModel->getAll();
    } else {
        $list = $documentModel->getByIssuerId($auth['user_id']);
    }
    apiJson(['success' => true, 'documents' => $list, 'count' => count($list)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $userId = (int) ($input['userId'] ?? 0);
    $issuerId = (int) ($input['issuerId'] ?? 0);
    $typeId = (int) ($input['typeId'] ?? 0);
    $description = trim($input['description'] ?? '');
    $location = trim($input['location'] ?? '');

    if (!$userId || !$typeId || $description === '') {
        apiJson(['success' => false, 'message' => 'Required: userId, typeId, description.'], 400);
    }
    if ($auth['user_role'] === 'Document Issuer' && $issuerId !== $auth['user_id']) {
        $issuerId = $auth['user_id'];
    } elseif (!$issuerId) {
        apiJson(['success' => false, 'message' => 'issuerId required (or inferred for issuer).'], 400);
    }

    $documentId = $documentModel->create([
        'userId' => $userId,
        'issuerId' => $issuerId,
        'typeId' => $typeId,
        'description' => $description,
        'location' => $location,
        'imagePath' => null,
        'statusId' => 1
    ]);

    if (!$documentId) {
        apiJson(['success' => false, 'message' => 'Failed to create document request.'], 500);
    }
    apiJson([
        'success' => true,
        'message' => 'Document request created.',
        'documentId' => $documentId,
        'documentID' => $documentId
    ], 201);
}

apiJson(['success' => false, 'message' => 'Method not allowed.'], 405);
