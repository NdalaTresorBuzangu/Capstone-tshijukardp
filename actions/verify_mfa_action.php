<?php
/**
 * Verify MFA Action
 * 
 * Web handler for OTP verification.
 * Uses MVC Services for business logic.
 */

require_once __DIR__ . '/../config/bootstrap.php';

use App\Services\OTPService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['mfa_pending_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

$code = trim($_POST['mfa_code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit code from your email.']);
    exit;
}

// Verify OTP using service
$otpService = new OTPService();

if (!$otpService->verifyFromSession($code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Please try again.']);
    exit;
}

// Complete login
$_SESSION['user_id'] = (int) $_SESSION['mfa_pending_user_id'];
$_SESSION['user_role'] = $_SESSION['mfa_pending_role'];
$_SESSION['username'] = $_SESSION['mfa_pending_username'];

// Determine redirect based on role (use base URL so redirect works in subfolder e.g. /aa/)
$base = function_exists('getBaseUrl') ? getBaseUrl() : '';
$redirect = match ($_SESSION['user_role']) {
    'Document Seeker' => $base . 'index.php?controller=Seeker&action=dashboard',
    'Document Issuer' => $base . 'index.php?controller=Institution&action=subscribe',
    'Admin' => $base . 'index.php?controller=Admin&action=dashboard',
    default => $base . 'index.php?controller=Auth&action=login_form'
};

// Clear MFA session data
$otpService->clearSession();
unset(
    $_SESSION['mfa_pending_user_id'],
    $_SESSION['mfa_pending_username'],
    $_SESSION['mfa_pending_role'],
    $_SESSION['mfa_pending_email']
);

echo json_encode(['success' => true, 'redirect' => $redirect]);
exit;
