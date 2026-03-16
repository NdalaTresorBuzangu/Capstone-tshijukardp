<?php
/**
 * Reset password – verify OTP from PasswordResetOtp, update User password, then redirect to login.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login.php');
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mfa_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$otp = preg_replace('/\D/', '', trim($_POST['otp'] ?? ''));
$newPassword = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$redirectUrl = '../views/reset-password-verify.php?email=' . urlencode($email);

if (empty($email) || strlen($otp) !== 6 || empty($newPassword) || $newPassword !== $confirm) {
    $_SESSION['recovery_error'] = 'Invalid input or passwords do not match.';
    header('Location: ' . $redirectUrl);
    exit;
}

if (strlen($newPassword) < 6) {
    $_SESSION['recovery_error'] = 'Password must be at least 6 characters.';
    header('Location: ' . $redirectUrl);
    exit;
}

$stmt = $conn->prepare('SELECT id, email, otpHash, expiresAt FROM PasswordResetOtp WHERE email = ? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !hash_equals($row['otpHash'], hash('sha256', $otp))) {
    $_SESSION['recovery_error'] = 'Invalid or expired code. Request a new one.';
    header('Location: ' . $redirectUrl);
    exit;
}

if (strtotime($row['expiresAt']) < time()) {
    $del = $conn->prepare('DELETE FROM PasswordResetOtp WHERE email = ?');
    $del->bind_param('s', $email);
    $del->execute();
    $del->close();
    $_SESSION['recovery_error'] = isset($L['invalid_otp']) ? $L['invalid_otp'] : 'Code has expired. Request a new one.';
    header('Location: ' . $redirectUrl);
    exit;
}

$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
$upd = $conn->prepare('UPDATE User SET userPassword = ? WHERE userEmail = ?');
$upd->bind_param('ss', $passwordHash, $email);
$upd->execute();
$upd->close();

$del2 = $conn->prepare('DELETE FROM PasswordResetOtp WHERE email = ?');
$del2->bind_param('s', $email);
$del2->execute();
$del2->close();

$_SESSION['reset_success'] = true;
header('Location: ../views/login.php');
exit;
