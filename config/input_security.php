<?php
/**
 * Input security helpers – detect suspicious/SQL-injection–like input.
 * Used for login and signup to reject invalid input and return clear feedback.
 */

/**
 * Patterns that suggest SQL injection or invalid input for auth fields.
 * Case-insensitive.
 */
function getSuspiciousPatterns(): array
{
    return [
        '/[\'"]\s*(OR|AND)\s*[\'"]/i',
        '/\bOR\s+1\s*=\s*1\b/i',
        '/\bAND\s+1\s*=\s*1\b/i',
        '/--\s*$/m',
        '/;\s*$/m',
        '/\bUNION\s+SELECT\b/i',
        '/\bSELECT\s+.*\s+FROM\b/i',
        '/\bINSERT\s+INTO\b/i',
        '/\bUPDATE\s+.*\s+SET\b/i',
        '/\bDELETE\s+FROM\b/i',
        '/\bDROP\s+(TABLE|DATABASE)\b/i',
        '/\/\*/',
        '/\*\//',
        '/\bEXEC\b/i',
        '/\bEXECUTE\b/i',
        '/\bSCRIPT\b/i',
        '/<\s*script/i',
        '/\bCHAR\s*\(/i',
        '/\bCONCAT\s*\(/i',
        '/\bSLEEP\s*\(/i',
        '/\bBENCHMARK\s*\(/i',
        '/\'\s*;\s*--/i',
        '/"\s*;\s*--/i',
    ];
}

/**
 * Returns true if the string contains suspicious/SQL-injection–like content.
 */
function containsSuspiciousInput(string $value): bool
{
    $value = trim($value);
    if ($value === '') return false;

    foreach (getSuspiciousPatterns() as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }
    return false;
}

/**
 * Validate auth-related fields; returns error message if any field is suspicious, null otherwise.
 */
function validateAuthInput(array $fields): ?string
{
    foreach ($fields as $name => $value) {
        if (!is_string($value)) continue;
        if (containsSuspiciousInput($value)) {
            return 'Invalid or suspicious characters detected. Please use only letters, numbers, and allowed symbols.';
        }
    }
    return null;
}
