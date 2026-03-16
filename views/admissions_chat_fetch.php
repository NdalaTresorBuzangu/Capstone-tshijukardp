<?php
session_start();
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
isLogin();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admissions Office') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$documentID = $_GET['documentID'] ?? '';
$documentIssuerID = (int) ($_GET['documentIssuerID'] ?? 0);
$officeID = (int) $_SESSION['user_id'];

if (empty($documentID) || $documentIssuerID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
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

// Fetch messages (support optional filePath column)
$stmt = $conn->prepare("
    SELECT c.chatID, c.message, c.timestamp, c.senderID, c.filePath, u.userName
    FROM Chat c
    JOIN User u ON c.senderID = u.userID
    WHERE c.documentID = ?
    ORDER BY c.timestamp ASC
");
$stmt->bind_param("s", $documentID);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'chatID'    => (int) $row['chatID'],
        'senderID'  => (int) $row['senderID'],
        'userName'  => $row['userName'],
        'message'   => $row['message'],
        'timestamp' => $row['timestamp'],
        'filePath'  => isset($row['filePath']) ? $row['filePath'] : null,
        'isMe'      => (int) $row['senderID'] === $officeID
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'messages' => $messages]);
?>
