<?php
/**
 * Auth Service (Application Tier – business logic)
 * Handles login and registration. Used by AuthController (web) and can be used by
 * a future mobile API so business rules stay in one place.
 * Data access is done via Models (Data layer) only.
 */

namespace App\Services;

use App\Models\User;
use App\Models\MFA;

class AuthService
{
    private User $userModel;
    private MFA $mfaModel;

    public function __construct(\mysqli $db)
    {
        $this->userModel = new User($db);
        $this->mfaModel = new MFA($db);
    }

    /**
     * Attempt login – validate credentials and return user or error.
     * Does not set session (controller or API does that).
     *
     * @return array{success: bool, user?: array, require_mfa?: bool, redirect?: string, message?: string}
     */
    public function login(string $email, string $password): array
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $password = trim($password);

        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password are required.'
            ];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'No user found with this email.'
            ];
        }

        $storedPassword = $user['userPassword'] ?? '';
        if (!password_verify($password, $storedPassword)) {
            return [
                'success' => false,
                'message' => 'Incorrect password.'
            ];
        }

        unset($user['userPassword']);
        $role = trim($user['userRole'] ?? '');
        $redirect = $this->getRedirectForRole($role);

        return [
            'success' => true,
            'require_mfa' => true,
            'user' => $user,
            'redirect' => $redirect
        ];
    }

    /**
     * Register a new user. Validation and create are in this layer.
     *
     * @return array{success: bool, message: string, userId?: int}
     */
    public function register(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirmPassword'] ?? '';
        $role = trim($data['role'] ?? '');
        $contact = trim($data['contact'] ?? '');

        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            return ['success' => false, 'message' => 'All required fields must be filled.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        if (!in_array($role, ['Document Seeker', 'Document Issuer'])) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }

        $acceptTerms = !empty($data['accept_terms']);
        $acceptPrivacy = !empty($data['accept_privacy']);
        if (!$acceptTerms || !$acceptPrivacy) {
            return [
                'success' => false,
                'message' => 'You must read and accept the Terms of Service and the Privacy Policy to create an account.'
            ];
        }

        if ($this->userModel->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }

        $consentTime = date('Y-m-d H:i:s');
        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'contact' => $contact,
            'terms_accepted_at' => $consentTime,
            'privacy_accepted_at' => $consentTime,
        ]);

        if (!$userId) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }

        return [
            'success' => true,
            'message' => 'Registration successful.',
            'userId' => $userId
        ];
    }

    /**
     * Redirect URL for web after login (by role). Mobile API can ignore or use for deep link.
     */
    public function getRedirectForRole(string $role): string
    {
        return match ($role) {
            'Document Seeker' => 'index.php?controller=Seeker&action=dashboard',
            'Document Issuer' => 'index.php?controller=Institution&action=subscribe',
            'Admin' => 'index.php?controller=Admin&action=dashboard',
            default => 'index.php?controller=Auth&action=login_form'
        };
    }

    public function generateOTP(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function hashOTP(string $otp): string
    {
        return hash('sha256', $otp);
    }

    public function verifyOTP(string $storedHash, string $userCode, int $expiresAt): bool
    {
        if (time() > $expiresAt) {
            return false;
        }
        $code = preg_replace('/\D/', '', trim($userCode));
        if (strlen($code) !== 6) {
            return false;
        }
        return hash_equals($storedHash, hash('sha256', $code));
    }

    public function maskEmail(string $email): string
    {
        if (strlen($email) > 4 && strpos($email, '@') !== false) {
            return substr($email, 0, 2) . '***' . substr($email, strpos($email, '@'));
        }
        return substr($email, 0, 2) . '***';
    }

    public function getUserModel(): User
    {
        return $this->userModel;
    }

    public function getMfaModel(): MFA
    {
        return $this->mfaModel;
    }
}
