<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
isLogin();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['documentID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$documentID = $_GET['documentID'];
$userID = (int) $_SESSION['user_id'];

// Verify access
$stmt = $conn->prepare("SELECT * FROM Document WHERE documentID = ? AND (userID = ? OR documentIssuerID = ?)");
$stmt->bind_param("sii", $documentID, $userID, $userID);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

// Fetch chat messages
$stmt = $conn->prepare("
    SELECT c.chatID, c.message, c.timestamp, c.senderID, u.userName
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
        'isMe'      => (int) $row['senderID'] === $userID
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'messages' => $messages]);
?>
