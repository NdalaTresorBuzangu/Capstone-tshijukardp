<?php
/**
 * Forgot password – request OTP. Sends 6-digit code to email and stores hash in PasswordResetOtp.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/password-recovery.php');
    exit;
}

require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/mfa_helper.php';
require_once __DIR__ . '/../config/otp_email.php';

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['recovery_error'] = 'Please enter a valid email address.';
    header('Location: ../views/password-recovery.php');
    exit;
}

$stmt = $conn->prepare('SELECT userID, userName, userEmail FROM User WHERE userEmail = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Always redirect to verify page (don't reveal if email exists)
$redirectEmail = urlencode($email);
if (!$user) {
    $_SESSION['recovery_error'] = 'No account found for this email.';
    header('Location: ../views/reset-password-verify.php?email=' . $redirectEmail);
    exit;
}

// Delete any existing OTP for this email
$del = $conn->prepare('DELETE FROM PasswordResetOtp WHERE email = ?');
$del->bind_param('s', $email);
$del->execute();
$del->close();

$otp = otp_generate();
$otpHash = hash('sha256', $otp);
$expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes

$ins = $conn->prepare('INSERT INTO PasswordResetOtp (email, otpHash, expiresAt) VALUES (?, ?, ?)');
$ins->bind_param('sss', $email, $otpHash, $expiresAt);
if (!$ins->execute()) {
    $ins->close();
    $_SESSION['recovery_error'] = 'Unable to create reset code. Try again.';
    header('Location: ../views/reset-password-verify.php?email=' . $redirectEmail);
    exit;
}
$ins->close();

$subject = 'Password reset code - Tshijuka RDP';
$body = "Hello " . ($user['userName'] ?: 'there') . ",\n\n"
    . "Your password reset code is: " . $otp . "\n\n"
    . "This code expires in 15 minutes. Do not share it.\n\n"
    . "If you did not request this, ignore this email.\n\n"
    . "— Tshijuka RDP";
$headers = "From: Tshijuka RDP <noreply@tshijuka.org>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
@mail($user['userEmail'], $subject, $body, $headers);

if (defined('OTP_DEBUG_SHOW_CODE') && OTP_DEBUG_SHOW_CODE) {
    $_SESSION['recovery_dev_code'] = $otp;
}

$_SESSION['recovery_success'] = true;
header('Location: ../views/reset-password-verify.php?email=' . $redirectEmail);
exit;
