<?php
if (!isset($L) || !is_array($L)) require_once __DIR__ . '/../config/lang.php';
$L = $L ?? [];
?>
<!DOCTYPE html>
<html lang="<?php echo isset($lang) && $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tshijuka RDP – <?php echo htmlspecialchars($L['signup'] ?? 'Sign up'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/site.css">
</head>
<body class="form-page">
    <?php include 'nav.php'; ?>

    <section class="hero">
        <h1><?php echo htmlspecialchars($L['signup'] ?? 'Sign up'); ?></h1>
        <p><?php echo htmlspecialchars($L['signup_title'] ?? 'Create your account'); ?></p>
    </section>

    <div class="form-card">
        <div class="error-message" id="signup-error" style="display: none;"></div>
        <form id="signupForm">
            <div class="input-space">
                <label for="full_name"><?php echo htmlspecialchars($L['full_name'] ?? 'Full name'); ?></label>
                <input type="text" id="full_name" name="full_name" placeholder="<?php echo htmlspecialchars($L['full_name'] ?? 'Full name'); ?>" required>
                <span class="field-feedback invalid-feedback" id="full_name-feedback" aria-live="polite"></span>
            </div>
            <div class="input-space">
                <label for="contact"><?php echo htmlspecialchars($L['contact'] ?? 'Contact'); ?></label>
                <input type="text" id="contact" name="contact" placeholder="<?php echo htmlspecialchars($L['contact'] ?? 'Contact'); ?>" required>
                <span class="field-feedback invalid-feedback" id="contact-feedback" aria-live="polite"></span>
            </div>
            <div class="input-space">
                <label for="email"><?php echo htmlspecialchars($L['email'] ?? 'Email'); ?></label>
                <input type="email" id="email" name="email" placeholder="<?php echo htmlspecialchars($L['enter_email'] ?? 'Enter your email'); ?>" required>
                <span class="field-feedback invalid-feedback" id="email-feedback" aria-live="polite"></span>
            </div>
            <div class="input-space">
                <label for="password"><?php echo htmlspecialchars($L['password'] ?? 'Password'); ?></label>
                <input type="password" id="password" name="password" placeholder="<?php echo htmlspecialchars($L['password'] ?? 'Password'); ?>" required>
                <span class="field-feedback invalid-feedback" id="password-feedback" aria-live="polite"></span>
            </div>
            <div class="input-space">
                <label for="confirm_password"><?php echo htmlspecialchars($L['confirm_password'] ?? 'Confirm password'); ?></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo htmlspecialchars($L['confirm_password'] ?? 'Confirm password'); ?>" required>
                <span class="field-feedback invalid-feedback" id="confirm_password-feedback" aria-live="polite"></span>
            </div>
            <div class="input-space">
                <label for="userRole"><?php echo htmlspecialchars($L['select_role'] ?? 'Select your role'); ?></label>
                <select id="userRole" name="userRole" required>
                    <option value="" disabled selected><?php echo htmlspecialchars($L['select_role'] ?? 'Select your role'); ?></option>
                    <option value="Document Seeker"><?php echo htmlspecialchars($L['role_seeker'] ?? 'Document Seeker'); ?></option>
                    <option value="Document Issuer"><?php echo htmlspecialchars($L['role_issuer'] ?? 'Document Issuing Institution'); ?></option>
                </select>
            </div>
            <div class="input-space consent-block">
                <div class="consent-checkboxes">
                    <label class="consent-label">
                        <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required aria-required="true">
                        <?php echo $L['accept_terms'] ?? 'I have read and accept the'; ?> <a href="index.php?controller=Page&action=terms" target="_blank" rel="noopener"><?php echo htmlspecialchars($L['terms_title'] ?? 'Terms of Service'); ?></a>.
                    </label>
                    <label class="consent-label">
                        <input type="checkbox" id="accept_privacy" name="accept_privacy" value="1" required aria-required="true">
                        <?php echo $L['accept_privacy'] ?? 'I have read and accept the'; ?> <a href="index.php?controller=Page&action=privacy" target="_blank" rel="noopener"><?php echo htmlspecialchars($L['privacy_title'] ?? 'Privacy Policy'); ?></a> <?php echo htmlspecialchars($L['data_protection_note'] ?? '(data protection &amp; GDPR / Ghana compliant).'); ?>
                    </label>
                </div>
                <span class="field-feedback invalid-feedback" id="consent-feedback" aria-live="polite"></span>
            </div>
            <button type="submit" class="btn-submit"><?php echo htmlspecialchars($L['create_account'] ?? 'Create account'); ?></button>
        </form>
        <div class="signin-link">
            <p><?php echo htmlspecialchars($L['already_account'] ?? 'Already have an account?'); ?> <a href="index.php?controller=Auth&action=login_form"><?php echo htmlspecialchars($L['login'] ?? 'Log in'); ?></a></p>
        </div>
    </div>

    <footer class="footer">
        <p><?php echo htmlspecialchars($L['footer_contact'] ?? 'Contact Tshijuka RDP'); ?>: <a href="tel:+233591429017"><?php echo htmlspecialchars($L['contact_phone']); ?></a></p>
        <p><a href="index.php?controller=Page&action=terms"><?php echo htmlspecialchars($L['terms_title'] ?? 'Terms of Service'); ?></a> &middot; <a href="index.php?controller=Page&action=privacy"><?php echo htmlspecialchars($L['privacy_title'] ?? 'Privacy Policy'); ?></a></p>
    </footer>

    <script>
        var signupForm = document.getElementById('signupForm');
        var signupError = document.getElementById('signup-error');

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

        var fields = ['full_name', 'contact', 'email', 'password', 'confirm_password'];
        function showFieldFeedback(id, value) {
            var input = document.getElementById(id);
            var feedback = document.getElementById(id + '-feedback');
            if (!input || !feedback) return false;
            if (isSuspicious(value)) {
                input.setAttribute('aria-invalid', 'true');
                input.classList.add('field-invalid');
                feedback.textContent = 'Invalid or suspicious characters detected. Please remove them.';
                feedback.style.display = 'block';
                return true;
            } else {
                input.removeAttribute('aria-invalid');
                input.classList.remove('field-invalid');
                feedback.textContent = '';
                feedback.style.display = 'none';
                return false;
            }
        }

        function checkSignupForm() {
            var hasInvalid = false;
            fields.forEach(function (id) {
                var el = document.getElementById(id);
                if (el && showFieldFeedback(id, el.value)) hasInvalid = true;
            });
            signupForm.querySelector('button[type="submit"]').disabled = hasInvalid;
        }

        fields.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', checkSignupForm);
                el.addEventListener('blur', checkSignupForm);
            }
        });
        checkSignupForm();

        signupForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            signupError.style.display = 'none';
            var consentFeedback = document.getElementById('consent-feedback');
            if (consentFeedback) { consentFeedback.style.display = 'none'; consentFeedback.textContent = ''; }

            var termsOk = document.getElementById('accept_terms') && document.getElementById('accept_terms').checked;
            var privacyOk = document.getElementById('accept_privacy') && document.getElementById('accept_privacy').checked;
            if (!termsOk || !privacyOk) {
                signupError.style.display = 'block';
                signupError.textContent = 'You must read and accept the Terms of Service and the Privacy Policy to create an account.';
                if (consentFeedback) { consentFeedback.textContent = 'Please check both boxes above.'; consentFeedback.style.display = 'block'; }
                return;
            }

            var hasSuspicious = false;
            fields.forEach(function (id) {
                var el = document.getElementById(id);
                if (el && isSuspicious(el.value)) hasSuspicious = true;
            });
            if (hasSuspicious) {
                signupError.style.display = 'block';
                signupError.textContent = 'Invalid or suspicious characters detected. Please use only letters, numbers, and allowed symbols.';
                return;
            }

            var formData = new FormData(this);
            formData.append('controller', 'Auth');
            formData.append('action', 'signup_submit');

            try {
                var response = await fetch('index.php', { method: 'POST', body: formData });
                var result = await response.json();

                if (result.success) {
                    alert('Signup successful! Redirecting to login page...');
                    window.location.href = result.redirect || 'index.php?controller=Auth&action=login_form';
                } else {
                    signupError.style.display = 'block';
                    signupError.textContent = result.message || 'Registration failed.';
                }
            } catch (error) {
                console.error('Signup error:', error);
                signupError.style.display = 'block';
                signupError.textContent = 'An unexpected error occurred. Please try again later.';
            }
        });
    </script>
</body>
</html>
