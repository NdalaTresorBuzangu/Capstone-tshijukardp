<?php
session_start();
include '../config/config.php';
require_once '../config/mfa_helper.php';
require_once '../config/otp_email.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Seeker') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT userEmail, userName FROM User WHERE userID = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$otp = otp_generate();
$_SESSION['mfa_setup_otp_hash'] = hash('sha256', $otp);
$_SESSION['mfa_setup_otp_expires'] = time() + 600; // 10 min

$sent = send_otp_to_email($user['userEmail'], $user['userName'], $otp);

echo json_encode([
    'success' => true,
    'email' => $user['userEmail'],
    'message' => $sent ? 'Code sent to your email.' : 'Code generated. If you don\'t receive an email, check your spam or try again.',
]);
exit;
