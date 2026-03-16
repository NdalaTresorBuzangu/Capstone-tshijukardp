<?php
/**
 * Send OTP by email.
 * Uses PHP mail() by default. On XAMPP/Windows mail() often fails until sendmail/SMTP is configured.
 * When mail fails, login_action returns dev_code when OTP_DEBUG_SHOW_CODE is true (see config.php).
 * For production: configure sendmail in php.ini, or use an SMTP relay, or integrate PHPMailer.
 */

function send_otp_to_email($toEmail, $userName, $otp) {
    $subject = 'Your login code - Tshijuka RDP';
    $body = "Hello " . ($userName ?: 'there') . ",\n\n"
        . "Your verification code is: " . $otp . "\n\n"
        . "This code expires in 10 minutes. Do not share it with anyone.\n\n"
        . "If you did not request this, you can ignore this email.\n\n"
        . "— Tshijuka Refugee Document Recovery Platform";

    $headers = "From: Tshijuka RDP <noreply@tshijuka.org>\r\n"
        . "Reply-To: noreply@tshijuka.org\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "X-Mailer: PHP/" . phpversion();

    $ok = @mail($toEmail, $subject, $body, $headers);
    return (bool) $ok;
}
