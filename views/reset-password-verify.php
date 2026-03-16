<?php
if (!isset($L)) require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/core.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$successMsg = isset($_SESSION['recovery_success']) ? true : false;
$errorMsg = isset($_SESSION['recovery_error']) ? $_SESSION['recovery_error'] : '';
$devCode = isset($_SESSION['recovery_dev_code']) ? $_SESSION['recovery_dev_code'] : '';
unset($_SESSION['recovery_success'], $_SESSION['recovery_error'], $_SESSION['recovery_dev_code']);
?>
<!DOCTYPE html>
<html lang="<?php echo (isset($lang) && $lang === 'fr') ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($L['set_new_password'] ?? 'Set new password'); ?> – Tshijuka RDP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/site.css">
</head>
<body class="form-page">
    <?php include 'nav.php'; ?>

    <section class="hero">
        <h1><?php echo htmlspecialchars($L['set_new_password'] ?? 'Set new password'); ?></h1>
        <p><?php echo htmlspecialchars($successMsg ? ($L['recovery_otp_sent'] ?? 'Enter the code we sent and your new password.') : ($L['enter_email_recovery'] ?? 'Enter your email first.')); ?></p>
    </section>

    <div class="form-card">
        <?php if ($errorMsg): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        <?php if ($devCode): ?>
            <p class="text-muted small">Dev: Your code is <strong><?php echo htmlspecialchars($devCode); ?></strong></p>
        <?php endif; ?>
        <form action="index.php?controller=Auth&action=reset_password_submit" method="POST">
            <div class="input-space">
                <label for="email"><?php echo htmlspecialchars($L['email'] ?? 'Email'); ?></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="<?php echo htmlspecialchars($L['enter_email'] ?? 'Email'); ?>" required>
            </div>
            <div class="input-space">
                <label for="otp"><?php echo htmlspecialchars($L['enter_otp'] ?? 'Verification code'); ?></label>
                <input type="text" id="otp" name="otp" placeholder="000000" maxlength="6" pattern="[0-9]*" inputmode="numeric" required autocomplete="one-time-code">
            </div>
            <div class="input-space">
                <label for="new_password"><?php echo htmlspecialchars($L['new_password'] ?? 'New password'); ?></label>
                <input type="password" id="new_password" name="new_password" placeholder="<?php echo htmlspecialchars($L['enter_password'] ?? 'Password'); ?>" required minlength="6">
            </div>
            <div class="input-space">
                <label for="confirm_password"><?php echo htmlspecialchars($L['confirm_new_password'] ?? 'Confirm new password'); ?></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo htmlspecialchars($L['confirm_password'] ?? 'Confirm password'); ?>" required minlength="6">
            </div>
            <button type="submit" class="btn-submit"><?php echo htmlspecialchars($L['set_new_password'] ?? 'Set new password'); ?></button>
        </form>
        <div class="signin-link">
            <p><a href="index.php?controller=Auth&action=password_recovery_form"><?php echo htmlspecialchars($L['forgot_password'] ?? 'Forgot password?'); ?> — request new code</a></p>
            <p><a href="index.php?controller=Auth&action=login_form"><?php echo htmlspecialchars($L['login'] ?? 'Login'); ?></a></p>
        </div>
    </div>

    <footer class="footer">
        <p><?php echo htmlspecialchars($L['footer_contact'] ?? 'Contact Tshijuka RDP'); ?></p>
    </footer>
    <script>
    document.querySelector('form').addEventListener('submit', function(e) {
        var p = document.getElementById('new_password').value;
        var c = document.getElementById('confirm_password').value;
        if (p !== c) {
            e.preventDefault();
            alert('<?php echo addslashes($L['confirm_password'] ?? 'Passwords must match'); ?>');
        }
    });
    </script>
</body>
</html>
