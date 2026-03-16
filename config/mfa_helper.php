<?php
/**
 * Simple email OTP helper for Document Seekers.
 * No app, no QR code — just a 6-digit code sent to email (or phone later).
 */

/**
 * Generate a 6-digit OTP.
 */
function otp_generate() {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Verify user-entered code against stored hash. Expiry in seconds.
 */
function otp_verify($storedHash, $userCode, $expiresAt) {
    if (time() > $expiresAt) return false;
    $code = preg_replace('/\D/', '', trim($userCode));
    if (strlen($code) !== 6) return false;
    return hash_equals($storedHash, hash('sha256', $code));
}
