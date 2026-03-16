<?php
session_start();
include '../config/core.php';
include '../config/config.php';
header('Content-Type: application/json; charset=utf-8');

isLogin();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admissions Office') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$documentID = trim($_POST['documentID'] ?? '');
$message = trim($_POST['message'] ?? '');
$documentIssuerID = (int) ($_POST['documentIssuerID'] ?? 0);
$officeID = (int) $_SESSION['user_id'];
$filePath = null;

if (empty($documentID) || $documentIssuerID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Document ID and institution required.']);
    exit;
}

// Optional file upload – all under uploads/images/chat/
if (!empty($_FILES['attachment']['name'])) {
    $uploadDir = __DIR__ . '/../uploads/images/chat/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $safeName = time() . '_' . basename($_FILES['attachment']['name']);
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $safeName);
    $targetFile = $uploadDir . $safeName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (in_array($fileType, $allowed) && move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
        $filePath = 'uploads/images/chat/' . $safeName;
    }
}

if (empty($message) && empty($filePath)) {
    echo json_encode(['success' => false, 'message' => 'Message or attachment required.']);
    exit;
}

// Verify document belongs to this institution
$stmt = $conn->prepare("SELECT 1 FROM Document WHERE documentID = ? AND documentIssuerID = ?");
$stmt->bind_param("si", $documentID, $documentIssuerID);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Document not found.']);
    exit;
}
$stmt->close();

// Insert: try with receiverID and filePath if columns exist
$hasReceiver = false;
$cols = $conn->query("SHOW COLUMNS FROM Chat LIKE 'receiverID'");
if ($cols && $cols->num_rows > 0) $hasReceiver = true;
$hasFilePath = false;
$cols2 = $conn->query("SHOW COLUMNS FROM Chat LIKE 'filePath'");
if ($cols2 && $cols2->num_rows > 0) $hasFilePath = true;

if ($hasReceiver && $hasFilePath) {
    $msg = $message !== '' ? $message : '(Attachment)';
    $stmt = $conn->prepare("INSERT INTO Chat (documentID, senderID, receiverID, message, filePath) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiss", $documentID, $officeID, $documentIssuerID, $msg, $filePath);
} elseif ($hasReceiver) {
    $stmt = $conn->prepare("INSERT INTO Chat (documentID, senderID, receiverID, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siis", $documentID, $officeID, $documentIssuerID, $message);
} else {
    $stmt = $conn->prepare("INSERT INTO Chat (documentID, senderID, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $documentID, $officeID, $message);
}
$ok = $stmt->execute();
$stmt->close();

echo $ok ? json_encode(['success' => true]) : json_encode(['success' => false, 'message' => 'Failed to send.']);
?>
