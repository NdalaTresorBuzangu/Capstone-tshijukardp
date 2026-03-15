<?php
/**
 * Auth Controller (Application Tier – thin)
 * Handles HTTP for auth: forms, POST login/signup, logout.
 * Business logic is in AuthService so the same logic can be used by web and future mobile API.
 */

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(\mysqli $db)
    {
        parent::__construct($db);
        $this->authService = new AuthService($db);
    }

    /** Show login form (GET) */
    public function loginForm(): void
    {
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $resetSuccess = !empty($_SESSION['reset_success']);
        if ($resetSuccess) unset($_SESSION['reset_success']);
        $this->render('login.php', ['L' => $L, 'lang' => $lang, 'resetSuccess' => $resetSuccess]);
    }

    /** Process login (POST) – validate input, call AuthService, set session, return JSON */
    public function loginSubmit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method.']);
        }
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'input_security.php';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $validationError = validateAuthInput(['email' => $email, 'password' => $password]);
        if ($validationError !== null) {
            $this->json(['success' => false, 'message' => $validationError]);
        }
        $result = $this->authService->login($email, $password);
        if (!$result['success']) {
            $this->json($result);
        }
        $user = $result['user'];
        $_SESSION['user_id'] = $user['userID'];
        $_SESSION['user_role'] = $user['userRole'];
        $_SESSION['username'] = $user['userName'];
        $this->json(['success' => true, 'redirect' => $result['redirect']]);
    }

    /** Show signup form (GET) */
    public function signupForm(): void
    {
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('signup.php', ['L' => $L, 'lang' => $lang]);
    }

    /** Process signup (POST) – validate input, call AuthService, return JSON */
    public function signupSubmit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method.']);
        }
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'input_security.php';
        $data = [
            'name' => $_POST['name'] ?? $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirmPassword' => $_POST['confirmPassword'] ?? $_POST['confirm_password'] ?? '',
            'role' => $_POST['role'] ?? $_POST['userRole'] ?? '',
            'contact' => $_POST['contact'] ?? '',
            'accept_terms' => isset($_POST['accept_terms']) && $_POST['accept_terms'] === '1',
            'accept_privacy' => isset($_POST['accept_privacy']) && $_POST['accept_privacy'] === '1',
        ];
        $validationError = validateAuthInput($data);
        if ($validationError !== null) {
            $this->json(['success' => false, 'message' => $validationError]);
        }
        $result = $this->authService->register($data);
        $this->json($result);
    }

    /** Logout and redirect to login form */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirect('index.php?controller=Auth&action=login_form');
    }

    /** Password recovery form (GET) */
    public function passwordRecoveryForm(): void
    {
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('password-recovery.php', ['L' => $L, 'lang' => $lang]);
    }

    /** Process password recovery (POST) – send OTP and redirect to reset form */
    public function passwordRecoverySubmit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=Auth&action=password_recovery_form');
        }
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'mfa_helper.php';
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['recovery_error'] = 'Please enter a valid email address.';
            $this->redirect('index.php?controller=Auth&action=password_recovery_form');
        }
        $conn = $this->db;
        $stmt = $conn->prepare('SELECT userID, userName, userEmail FROM User WHERE userEmail = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $redirectEmail = urlencode($email);
        if (!$user) {
            $_SESSION['recovery_error'] = 'No account found for this email.';
            $this->redirect('index.php?controller=Auth&action=reset_password_verify&email=' . $redirectEmail);
        }
        $del = $conn->prepare('DELETE FROM PasswordResetOtp WHERE email = ?');
        $del->bind_param('s', $email);
        $del->execute();
        $del->close();
        $otp = otp_generate();
        $otpHash = hash('sha256', $otp);
        $expiresAt = date('Y-m-d H:i:s', time() + 900);
        $ins = $conn->prepare('INSERT INTO PasswordResetOtp (email, otpHash, expiresAt) VALUES (?, ?, ?)');
        $ins->bind_param('sss', $email, $otpHash, $expiresAt);
        if (!$ins->execute()) {
            $ins->close();
            $_SESSION['recovery_error'] = 'Unable to create reset code. Try again.';
            $this->redirect('index.php?controller=Auth&action=reset_password_verify&email=' . $redirectEmail);
        }
        $ins->close();
        $subject = 'Password reset code - Tshijuka RDP';
        $body = "Hello " . ($user['userName'] ?: 'there') . ",\n\nYour password reset code is: " . $otp . "\n\nThis code expires in 15 minutes. Do not share it.\n\nIf you did not request this, ignore this email.\n\n— Tshijuka RDP";
        $headers = "From: Tshijuka RDP <noreply@tshijuka.org>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        @mail($user['userEmail'], $subject, $body, $headers);
        if (defined('OTP_DEBUG_SHOW_CODE') && OTP_DEBUG_SHOW_CODE) {
            $_SESSION['recovery_dev_code'] = $otp;
        }
        $_SESSION['recovery_success'] = true;
        $this->redirect('index.php?controller=Auth&action=reset_password_verify&email=' . $redirectEmail);
    }

    /** Reset password verify form (GET) – enter OTP and new password */
    public function resetPasswordVerify(): void
    {
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('reset-password-verify.php', ['L' => $L ?? [], 'lang' => $lang ?? 'en']);
    }

    /** Process reset password (POST) – verify OTP and update password */
    public function resetPasswordSubmit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=Auth&action=login_form');
        }
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'mfa_helper.php';
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $otp = preg_replace('/\D/', '', trim($_POST['otp'] ?? ''));
        $newPassword = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $redirectUrl = 'index.php?controller=Auth&action=reset_password_verify&email=' . urlencode($email);
        if (empty($email) || strlen($otp) !== 6 || empty($newPassword) || $newPassword !== $confirm) {
            $_SESSION['recovery_error'] = 'Invalid input or passwords do not match.';
            $this->redirect($redirectUrl);
        }
        if (strlen($newPassword) < 6) {
            $_SESSION['recovery_error'] = 'Password must be at least 6 characters.';
            $this->redirect($redirectUrl);
        }
        $conn = $this->db;
        $stmt = $conn->prepare('SELECT id, email, otpHash, expiresAt FROM PasswordResetOtp WHERE email = ? ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || !hash_equals($row['otpHash'], hash('sha256', $otp))) {
            $_SESSION['recovery_error'] = 'Invalid or expired code. Request a new one.';
            $this->redirect($redirectUrl);
        }
        if (strtotime($row['expiresAt']) < time()) {
            $del = $conn->prepare('DELETE FROM PasswordResetOtp WHERE email = ?');
            $del->bind_param('s', $email);
            $del->execute();
            $del->close();
            $_SESSION['recovery_error'] = $L['invalid_otp'] ?? 'Code has expired. Request a new one.';
            $this->redirect($redirectUrl);
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
        $this->redirect('index.php?controller=Auth&action=login_form');
    }

    /** Redirect URL by role (delegate to AuthService – for MFA/verify actions that need it) */
    public function getRedirectForRole(string $role): string
    {
        return $this->authService->getRedirectForRole($role);
    }

    public function generateOTP(): string
    {
        return $this->authService->generateOTP();
    }

    public function hashOTP(string $otp): string
    {
        return $this->authService->hashOTP($otp);
    }

    public function verifyOTP(string $storedHash, string $userCode, int $expiresAt): bool
    {
        return $this->authService->verifyOTP($storedHash, $userCode, $expiresAt);
    }

    public function maskEmail(string $email): string
    {
        return $this->authService->maskEmail($email);
    }

    /** For legacy code that expects User model from controller */
    public function getUserModel(): \App\Models\User
    {
        return $this->authService->getUserModel();
    }

    /** For legacy code that expects MFA model from controller */
    public function getMfaModel(): \App\Models\MFA
    {
        return $this->authService->getMfaModel();
    }
}
