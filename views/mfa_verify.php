<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($L)) require_once __DIR__ . '/../config/lang.php';
if (!isset($_SESSION['mfa_pending_user_id']) || $_SESSION['mfa_pending_role'] !== 'Document Seeker') {
    header('Location: login.php');
    exit;
}
$masked_email = '';
if (!empty($_SESSION['mfa_pending_email'])) {
    $e = $_SESSION['mfa_pending_email'];
    $at = strpos($e, '@');
    if ($at !== false) {
        $masked_email = substr($e, 0, 2) . '***' . substr($e, $at);
    } else {
        $masked_email = substr($e, 0, 2) . '***';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter your code - Tshijuka RDP</title>
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/nav.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>body{background:url('<?php echo htmlspecialchars($bgImage ?? '../assets/nature-7047433_1280.jpg'); ?>') no-repeat center center fixed;background-size:cover;}</style>
    <style>
        .mfa-container { max-width: 400px; margin: 100px auto 2rem; }
        .mfa-container h2 { text-align: center; margin-bottom: 0.5rem; }
        .mfa-container .subtitle { text-align: center; color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 1.5rem; }
        .input-space input[name="mfa_code"] { letter-spacing: 0.3em; font-size: 1.25rem; text-align: center; }
    </style>
</head>
<body class="login-page">
    <?php $showLogout = true; include 'nav.php'; ?>

    <div class="container mfa-container">
        <h2><?= htmlspecialchars($L['check_email']) ?></h2>
        <p class="otp-intro" style="text-align: center; color: rgba(255,255,255,0.9); font-size: 14px; margin-bottom: 1rem;"><?= htmlspecialchars($L['otp_sent']) ?></p>
        <?php if ($masked_email): ?><p id="otp-email-hint" class="subtitle"><?= htmlspecialchars($L['code_sent_prefix']) ?> <?= htmlspecialchars($masked_email) ?></p><?php endif; ?>
        <div class="error-message" id="error-message" style="display: none; color: #f28b82;"></div>

        <form id="mfaForm" novalidate>
            <div class="input-space">
                <label for="mfa_code"><?= htmlspecialchars($L['verification_code']) ?></label>
                <input type="text" id="mfa_code" name="mfa_code" placeholder="000000" maxlength="6" pattern="[0-9]*" inputmode="numeric" autocomplete="one-time-code" required>
                <i class='bx bxs-key'></i>
            </div>
            <div style="margin-top: 1.25rem;">
                <button type="submit" class="login-button"><?= htmlspecialchars($L['verify']) ?></button>
            </div>
        </form>
        <p style="text-align: center; margin-top: 1rem; font-size: 13px;">
            <a href="logout.php" style="color: rgba(255,255,255,0.7);"><?= htmlspecialchars($L['cancel_signin']) ?></a>
        </p>
    </div>

    <script>
        var BASE_URL = <?= json_encode($baseUrl ?? (function_exists('getBaseUrl') ? getBaseUrl() : '')) ?>;
        document.getElementById('mfaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const code = document.getElementById('mfa_code').value.trim();
            const errEl = document.getElementById('error-message');
            errEl.style.display = 'none';

            const formData = new FormData();
            formData.append('mfa_code', code);

            try {
                const response = await fetch(BASE_URL + 'actions/verify_mfa_action.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    window.location.href = result.redirect || (BASE_URL + 'index.php?controller=Seeker&action=dashboard');
                } else {
                    errEl.style.display = 'block';
                    errEl.innerText = result.message || 'Invalid code. Please try again.';
                }
            } catch (err) {
                errEl.style.display = 'block';
                errEl.innerText = 'An error occurred. Please try again.';
            }
        });
    </script>
</body>
</html>
