<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
if (!isset($L)) require_once __DIR__ . '/../config/lang.php';
isLogin();

if ($_SESSION['user_role'] !== 'Document Seeker') {
    header('Location: student_dashboard.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$mfa_already_enabled = false;
$mfa_stmt = $conn->prepare('SELECT mfaEnabled FROM UserMfa WHERE userID = ? AND mfaEnabled = 1');
if ($mfa_stmt) {
    $mfa_stmt->bind_param('i', $user_id);
    $mfa_stmt->execute();
    $mfa_row = $mfa_stmt->get_result()->fetch_assoc();
    $mfa_stmt->close();
    $mfa_already_enabled = (bool) $mfa_row;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email verification - Tshijuka RDP</title>
    <link rel="stylesheet" href="../assets/nav.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:url('<?php echo htmlspecialchars($bgImage ?? '../assets/nature-7047433_1280.jpg'); ?>') no-repeat center center fixed;background-size:cover;}</style>
    <style>
        .mfa-setup-card { max-width: 500px; margin: 2rem auto; }
    </style>
</head>
<body>
<?php $showLogout = true; include 'nav.php'; ?>

<div class="container py-5" style="margin-top: 80px;">
    <div class="mfa-setup-card card shadow">
        <div class="card-body p-4">
            <h2 class="card-title text-center mb-2"><?= htmlspecialchars($L['email_verification_title']) ?></h2>
            <p class="text-center text-muted"><?= htmlspecialchars($L['email_verification_desc']) ?></p>

            <?php if ($mfa_already_enabled): ?>
                <div class="alert alert-success">
                    <strong><?= htmlspecialchars($L['email_on']) ?></strong> <?= htmlspecialchars($L['email_on_desc']) ?>
                </div>
                <div class="text-center mt-3">
                    <a href="student_dashboard.php" class="btn btn-primary"><?= htmlspecialchars($L['back_dashboard']) ?></a>
                </div>
            <?php else: ?>
                <div id="step1">
                    <p class="mb-3"><?= htmlspecialchars($L['when_enabled']) ?></p>
                    <button type="button" class="btn btn-primary" id="btnSendCode"><?= htmlspecialchars($L['send_code']) ?></button>
                </div>

                <div id="step2" style="display: none;">
                    <p class="mb-2 small text-muted"><?= htmlspecialchars($L['enter_code_sent']) ?> <strong id="emailDisplay"></strong></p>
                    <form id="confirmForm">
                        <div class="mb-3">
                            <label for="mfa_code" class="form-label"><?= htmlspecialchars($L['code']) ?></label>
                            <input type="text" id="mfa_code" name="mfa_code" class="form-control form-control-lg text-center" placeholder="000000" maxlength="6" pattern="[0-9]*" inputmode="numeric" autocomplete="one-time-code" required>
                        </div>
                        <div class="alert alert-danger small" id="confirmError" style="display: none;"></div>
                        <button type="submit" class="btn btn-success me-2"><?= htmlspecialchars($L['turn_on']) ?></button>
                        <button type="button" class="btn btn-outline-secondary" id="btnResend"><?= htmlspecialchars($L['send_new']) ?></button>
                    </form>
                </div>

                <div id="step3" style="display: none;">
                    <div class="alert alert-success">
                        <strong><?= htmlspecialchars($L['done']) ?></strong> <?= htmlspecialchars($L['done_desc']) ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="student_dashboard.php" class="btn btn-primary"><?= htmlspecialchars($L['back_dashboard']) ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$mfa_already_enabled): ?>
<script>
const btnSend = document.getElementById('btnSendCode');
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');
const emailDisplay = document.getElementById('emailDisplay');
const confirmForm = document.getElementById('confirmForm');
const confirmError = document.getElementById('confirmError');
const btnResend = document.getElementById('btnResend');

function maskEmail(email) {
    if (!email) return '';
    const at = email.indexOf('@');
    if (at === -1) return email.slice(0, 2) + '***';
    return email.slice(0, 2) + '***' + email.slice(at);
}

async function sendCode() {
    btnSend.disabled = true;
    try {
        const r = await fetch('../actions/mfa_setup_action.php');
        const d = await r.json();
        if (d.success) {
            emailDisplay.textContent = maskEmail(d.email || 'your email');
            step1.style.display = 'none';
            step2.style.display = 'block';
        } else {
            alert(d.message || 'Could not send code.');
        }
    } catch (e) {
        alert('Network error. Please try again.');
    }
    btnSend.disabled = false;
}

btnSend.addEventListener('click', sendCode);
btnResend.addEventListener('click', sendCode);

confirmForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const code = document.getElementById('mfa_code').value.trim();
    confirmError.style.display = 'none';
    const formData = new FormData();
    formData.append('mfa_code', code);
    try {
        const r = await fetch('../actions/mfa_enable_action.php', { method: 'POST', body: formData });
        const d = await r.json();
        if (d.success) {
            step2.style.display = 'none';
            step3.style.display = 'block';
        } else {
            confirmError.textContent = d.message || 'Invalid code.';
            confirmError.style.display = 'block';
        }
    } catch (e) {
        confirmError.textContent = 'Network error. Please try again.';
        confirmError.style.display = 'block';
    }
});
</script>
<?php endif; ?>
</body>
</html>
