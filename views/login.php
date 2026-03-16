<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($L)) require_once __DIR__ . '/../config/lang.php';
$resetSuccess = !empty($_SESSION['reset_success']);
if ($resetSuccess) unset($_SESSION['reset_success']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tshijuka RDP – <?php echo htmlspecialchars($L['login']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/site.css">
</head>
<body class="form-page">
    <?php include 'nav.php'; ?>

    <section class="hero">
        <h1><?php echo htmlspecialchars($L['login']); ?></h1>
        <p><?php echo htmlspecialchars($L['login_title']); ?></p>
    </section>

    <div class="form-card">
        <?php if ($resetSuccess): ?>
            <div class="success-message" style="margin-bottom: 1rem; color: #0a0; font-weight: 500;"><?php echo htmlspecialchars($L['reset_success'] ?? 'Password updated. You can now sign in.'); ?></div>
        <?php endif; ?>
        <div class="error-message" id="error-message" style="display: none;"></div>
        <form id="loginForm" novalidate>
            <div class="input-space">
                <label for="email"><?php echo htmlspecialchars($L['email']); ?></label>
                <input type="email" id="email" name="email" placeholder="<?php echo htmlspecialchars($L['enter_email']); ?>" required>
                <span class="field-feedback invalid-feedback" id="email-feedback" aria-live="polite"></span>
            </div>
            <div class="input-space">
                <label for="password"><?php echo htmlspecialchars($L['password']); ?></label>
                <input type="password" id="password" name="password" placeholder="<?php echo htmlspecialchars($L['enter_password']); ?>" required>
                <span class="field-feedback invalid-feedback" id="password-feedback" aria-live="polite"></span>
            </div>
            <div class="remember-forgot">
                <label for="remember-me">
                    <input type="checkbox" id="remember-me" name="remember"> <?php echo htmlspecialchars($L['remember_me']); ?>
                </label>
                <a href="index.php?controller=Auth&action=password_recovery_form"><?php echo htmlspecialchars($L['forgot_password']); ?></a>
            </div>
            <p class="consent-notice"><?php echo $L['login_consent'] ?? 'By logging in you agree to our'; ?> <a href="index.php?controller=Page&action=terms"><?php echo htmlspecialchars($L['terms_short'] ?? 'Terms'); ?></a> <?php echo $L['and'] ?? 'and'; ?> <a href="index.php?controller=Page&action=privacy"><?php echo htmlspecialchars($L['privacy_short'] ?? 'Privacy Policy'); ?></a>.</p>
            <button type="submit" class="login-button"><?php echo htmlspecialchars($L['login']); ?></button>
        </form>
        <div class="register">
            <p><?php echo htmlspecialchars($L['dont_have_account']); ?> <a href="index.php?controller=Auth&action=signup_form"><?php echo htmlspecialchars($L['signup']); ?></a></p>
        </div>
    </div>

    <footer class="footer">
        <p><?php echo htmlspecialchars($L['footer_contact'] ?? 'Contact Tshijuka RDP'); ?>: <a href="tel:+233591429017"><?php echo htmlspecialchars($L['contact_phone']); ?></a></p>
        <p><a href="index.php?controller=Page&action=terms"><?php echo htmlspecialchars($L['terms_short'] ?? 'Terms'); ?></a> &middot; <a href="index.php?controller=Page&action=privacy"><?php echo htmlspecialchars($L['privacy_short'] ?? 'Privacy'); ?></a></p>
    </footer>

    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('error-message');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailFeedback = document.getElementById('email-feedback');
        const passwordFeedback = document.getElementById('password-feedback');

        // Real-time: detect suspicious/SQL-injection–like input (mirrors server check)
        function isSuspicious(value) {
            if (!value || typeof value !== 'string') return false;
            var v = value.trim();
            var patterns = [
                /['"]\s*(OR|AND)\s*['"]/i,
                /\bOR\s+1\s*=\s*1\b/i,
                /\bAND\s+1\s*=\s*1\b/i,
                /--\s*$/m,
                /;\s*$/m,
                /\bUNION\s+SELECT\b/i,
                /\bSELECT\s+.*\s+FROM\b/i,
                /\bINSERT\s+INTO\b/i,
                /\bUPDATE\s+.*\s+SET\b/i,
                /\bDELETE\s+FROM\b/i,
                /\bDROP\s+(TABLE|DATABASE)\b/i,
                /\/\*/,
                /\*\//,
                /\bEXEC(UTE)?\b/i,
                /\bSCRIPT\b/i,
                /<\s*script/i,
                /\bCHAR\s*\(/i,
                /\bCONCAT\s*\(/i,
                /\bSLEEP\s*\(/i,
                /\bBENCHMARK\s*\(/i,
                /'\s*;\s*--/i,
                /"\s*;\s*--/i
            ];
            for (var i = 0; i < patterns.length; i++) {
                if (patterns[i].test(v)) return true;
            }
            return false;
        }

        function showFieldFeedback(inputEl, feedbackEl, value) {
            if (isSuspicious(value)) {
                inputEl.setAttribute('aria-invalid', 'true');
                inputEl.classList.add('field-invalid');
                feedbackEl.textContent = 'Invalid or suspicious characters detected. Please remove them.';
                feedbackEl.style.display = 'block';
                return true;
            } else {
                inputEl.removeAttribute('aria-invalid');
                inputEl.classList.remove('field-invalid');
                feedbackEl.textContent = '';
                feedbackEl.style.display = 'none';
                return false;
            }
        }

        function checkLoginForm() {
            var emailBad = showFieldFeedback(emailInput, emailFeedback, emailInput.value);
            var passwordBad = showFieldFeedback(passwordInput, passwordFeedback, passwordInput.value);
            loginForm.querySelector('button[type="submit"]').disabled = emailBad || passwordBad;
        }

        emailInput.addEventListener('input', checkLoginForm);
        emailInput.addEventListener('blur', checkLoginForm);
        passwordInput.addEventListener('input', checkLoginForm);
        passwordInput.addEventListener('blur', checkLoginForm);
        checkLoginForm();

        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            errorMessage.style.display = 'none';
            if (isSuspicious(emailInput.value) || isSuspicious(passwordInput.value)) {
                errorMessage.style.display = 'block';
                errorMessage.innerText = 'Invalid or suspicious characters detected. Please use only letters, numbers, and allowed symbols.';
                return;
            }

            const formData = new FormData(this);
            try {
                formData.append('controller', 'Auth');
                formData.append('action', 'login_submit');
                const response = await fetch('index.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Network error');
                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect || 'index.php?controller=Seeker&action=dashboard';
                    return;
                }
                errorMessage.style.display = 'block';
                errorMessage.innerText = result.message || 'Login failed.';
            } catch (err) {
                errorMessage.style.display = 'block';
                errorMessage.innerText = 'Something went wrong. Please try again.';
            }
        });
    </script>
</body>
</html>
