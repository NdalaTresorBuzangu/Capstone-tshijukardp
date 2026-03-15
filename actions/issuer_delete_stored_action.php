<?php
/**
 * Delete one issuer-stored document (Document Issuing Institution only).
 */
session_start();
include __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Issuer') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method.']);
    exit;
}

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document.']);
    exit;
}

$userID = (int) $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT filePath FROM IssuerStoredDocuments WHERE id = ? AND userID = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
$stmt->bind_param('ii', $id, $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Document not found or access denied.']);
    exit;
}

$fullPath = __DIR__ . '/../' . $row['filePath'];
if (file_exists($fullPath) && is_file($fullPath)) {
    @unlink($fullPath);
}

$stmt = $conn->prepare('DELETE FROM IssuerStoredDocuments WHERE id = ? AND userID = ?');
$stmt->bind_param('ii', $id, $userID);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Document removed.']);
