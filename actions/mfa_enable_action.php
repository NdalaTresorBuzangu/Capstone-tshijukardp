<?php
session_start();
include '../config/config.php';
require_once '../config/mfa_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Seeker') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$hash = $_SESSION['mfa_setup_otp_hash'] ?? '';
$expires = (int) ($_SESSION['mfa_setup_otp_expires'] ?? 0);
$code = trim($_POST['mfa_code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit code from your email.']);
    exit;
}

if (!otp_verify($hash, $code, $expires)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Click "Send code" again to get a new one.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare('INSERT INTO UserMfa (userID, mfaEnabled) VALUES (?, 1) ON DUPLICATE KEY UPDATE mfaEnabled = 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();

unset($_SESSION['mfa_setup_otp_hash'], $_SESSION['mfa_setup_otp_expires']);

echo json_encode(['success' => true, 'message' => 'Email verification is now on. We\'ll send a code to your email each time you sign in.']);
exit;
