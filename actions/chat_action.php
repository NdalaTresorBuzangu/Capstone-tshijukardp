<?php
include '../config/core.php';
include '../config/config.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
isLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$documentID = trim($_POST['documentID'] ?? '');
$message = trim($_POST['message'] ?? '');
$senderID = (int) $_SESSION['user_id'];

if (empty($documentID) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Document ID and message are required.']);
    exit;
}

// Verify user is part of this document conversation
$stmt = $conn->prepare("SELECT 1 FROM Document WHERE documentID = ? AND (userID = ? OR documentIssuerID = ?)");
$stmt->bind_param("sii", $documentID, $senderID, $senderID);
$stmt->execute();
$allowed = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$allowed) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

// Insert (Chat table: documentID, senderID, message)
$stmt = $conn->prepare("INSERT INTO Chat (documentID, senderID, message) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $documentID, $senderID, $message);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Sent.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send.']);
}
