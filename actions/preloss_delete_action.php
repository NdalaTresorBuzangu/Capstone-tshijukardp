<?php
/**
 * Preloss document delete – Document Seeker removes a saved document.
 */
session_start();
include __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Seeker') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method.']);
    exit;
}

$prelossID = (int) ($_POST['prelossID'] ?? $_GET['prelossID'] ?? 0);
if ($prelossID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document.']);
    exit;
}

$userID = (int) $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT filePath FROM PrelossDocuments WHERE prelossID = ? AND userID = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
$stmt->bind_param('ii', $prelossID, $userID);
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

$stmt = $conn->prepare('DELETE FROM PrelossDocuments WHERE prelossID = ? AND userID = ?');
$stmt->bind_param('ii', $prelossID, $userID);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Document removed.']);
