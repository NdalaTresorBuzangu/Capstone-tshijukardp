<?php
/**
 * Email Service
 * 
 * Handles email sending operations.
 * Uses PHP mail() by default, can be extended for PHPMailer/SMTP.
 */

namespace App\Services;

class EmailService
{
    private string $fromName;
    private string $fromEmail;
    private bool $debugMode;

    public function __construct(
        string $fromName = 'Tshijuka RDP',
        string $fromEmail = 'noreply@tshijuka.org',
        bool $debugMode = false
    ) {
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
        $this->debugMode = $debugMode;
    }

    /**
     * Send a plain text email
     */
    public function send(string $to, string $subject, string $body): bool
    {
        $headers = $this->buildHeaders('text/plain');

        if ($this->debugMode) {
            error_log("Email to: $to | Subject: $subject | Body: $body");
            return true; // Pretend success in debug mode
        }

        return @mail($to, $subject, $body, $headers);
    }

    /**
     * Send an HTML email
     */
    public function sendHtml(string $to, string $subject, string $htmlBody): bool
    {
        $headers = $this->buildHeaders('text/html');

        if ($this->debugMode) {
            error_log("HTML Email to: $to | Subject: $subject");
            return true;
        }

        return @mail($to, $subject, $htmlBody, $headers);
    }

    /**
     * Send OTP verification email
     */
    public function sendOTP(string $to, string $userName, string $otp): bool
    {
        $subject = 'Your login code - Tshijuka RDP';
        $body = "Hello " . ($userName ?: 'there') . ",\n\n"
            . "Your verification code is: " . $otp . "\n\n"
            . "This code expires in 10 minutes. Do not share it with anyone.\n\n"
            . "If you did not request this, you can ignore this email.\n\n"
            . "— Tshijuka Refugee Document Recovery Platform";

        return $this->send($to, $subject, $body);
    }

    /**
     * Send document completion notification
     */
    public function sendDocumentCompletion(string $to, string $userName, string $documentId): bool
    {
        $subject = 'Document Request Completed - Tshijuka RDP';
        $body = <<<HTML
        <html>
        <head><title>Document Request Completed</title></head>
        <body style="font-family: Arial, sans-serif; padding: 20px;">
            <h2 style="color: #2563eb;">Document Request Completed</h2>
            <p>Dear {$userName},</p>
            <p>Your document request (ID: <strong>{$documentId}</strong>) has been marked as <strong style="color: green;">Completed</strong>.</p>
            <p>You can now log in to your account to view and download your document.</p>
            <p>Thank you for using Tshijuka RDP!</p>
            <hr style="margin: 20px 0;">
            <p style="color: #666; font-size: 12px;">Tshijuka Refugee Document Recovery Platform</p>
        </body>
        </html>
        HTML;

        return $this->sendHtml($to, $subject, $body);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(string $to, string $userName, string $resetToken): bool
    {
        $subject = 'Password Reset - Tshijuka RDP';
        $resetLink = "https://tshijuka.org/reset-password?token=" . urlencode($resetToken);

        $body = "Hello " . ($userName ?: 'there') . ",\n\n"
            . "You requested a password reset for your Tshijuka RDP account.\n\n"
            . "Click the link below to reset your password:\n"
            . $resetLink . "\n\n"
            . "This link expires in 1 hour.\n\n"
            . "If you did not request this, please ignore this email.\n\n"
            . "— Tshijuka RDP";

        return $this->send($to, $subject, $body);
    }

    /**
     * Build email headers
     */
    private function buildHeaders(string $contentType = 'text/plain'): string
    {
        return "From: {$this->fromName} <{$this->fromEmail}>\r\n"
            . "Reply-To: {$this->fromEmail}\r\n"
            . "Content-Type: {$contentType}; charset=UTF-8\r\n"
            . "X-Mailer: PHP/" . phpversion();
    }

    /**
     * Set debug mode
     */
    public function setDebugMode(bool $debug): void
    {
        $this->debugMode = $debug;
    }

    /**
     * Check if in debug mode
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }
}
