<?php
/**
 * OTP Service
 * 
 * Handles One-Time Password generation and verification.
 * Independent of storage mechanism (session, database, etc.)
 */

namespace App\Services;

class OTPService
{
    private int $codeLength;
    private int $expirySeconds;

    public function __construct(int $codeLength = 6, int $expirySeconds = 600)
    {
        $this->codeLength = $codeLength;
        $this->expirySeconds = $expirySeconds;
    }

    /**
     * Generate a random OTP code
     */
    public function generate(): string
    {
        $max = pow(10, $this->codeLength) - 1;
        return str_pad((string) random_int(0, $max), $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Hash an OTP for secure storage
     */
    public function hash(string $otp): string
    {
        return hash('sha256', $otp);
    }

    /**
     * Verify an OTP against stored hash
     */
    public function verify(string $storedHash, string $userCode, int $expiresAt): bool
    {
        // Check expiry
        if (time() > $expiresAt) {
            return false;
        }

        // Clean and validate code
        $code = preg_replace('/\D/', '', trim($userCode));
        if (strlen($code) !== $this->codeLength) {
            return false;
        }

        // Constant-time comparison
        return hash_equals($storedHash, $this->hash($code));
    }

    /**
     * Get expiry timestamp
     */
    public function getExpiryTimestamp(): int
    {
        return time() + $this->expirySeconds;
    }

    /**
     * Store OTP in session
     */
    public function storeInSession(string $otp, string $prefix = 'mfa'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION[$prefix . '_otp_hash'] = $this->hash($otp);
        $_SESSION[$prefix . '_otp_expires'] = $this->getExpiryTimestamp();
    }

    /**
     * Verify OTP from session
     */
    public function verifyFromSession(string $userCode, string $prefix = 'mfa'): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $hash = $_SESSION[$prefix . '_otp_hash'] ?? '';
        $expires = $_SESSION[$prefix . '_otp_expires'] ?? 0;

        return $this->verify($hash, $userCode, $expires);
    }

    /**
     * Clear OTP from session
     */
    public function clearSession(string $prefix = 'mfa'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        unset($_SESSION[$prefix . '_otp_hash']);
        unset($_SESSION[$prefix . '_otp_expires']);
    }
}
