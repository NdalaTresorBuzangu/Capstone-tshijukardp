<?php
if (!isset($L)) require_once __DIR__ . '/../config/lang.php';
$pageTitle = isset($L['api']) ? $L['api'] : 'API';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – Tshijuka RDP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/site.css">
    <style>
        .result { margin-top: 1.5rem; padding: 1rem; border-radius: 12px; text-align: left; font-family: monospace; font-size: 0.9rem; white-space: pre-wrap; word-break: break-all; display: none; background: var(--blue-50); border: 1px solid var(--blue-200); }
        .result.success { display: block; background: #f0fdf4; border-color: #22c55e; }
        .result.error { display: block; background: #fef2f2; border-color: #dc2626; }
        .result-label { font-size: 0.85rem; color: var(--slate-600); margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <section class="hero">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= isset($L['api_success_desc']) ? htmlspecialchars($L['api_success_desc']) : 'The API is available. Use the button below to test the connection.' ?></p>
    </section>

    <div class="api-page">
        <div class="api-success-box">
            <h1>API success</h1>
            <p><?= isset($L['api_success_desc']) ? htmlspecialchars($L['api_success_desc']) : 'The API is available. Use the button below to test the connection.' ?></p>
            <button type="button" class="btn-test" id="btnTest"><?= isset($L['api_test']) ? htmlspecialchars($L['api_test']) : 'Test API' ?></button>
        </div>
        <div id="result" class="result" role="status" aria-live="polite"></div>
    </div>

    <footer class="footer">
        <p><?php echo htmlspecialchars($L['footer_contact'] ?? 'Contact Tshijuka RDP'); ?>: <a href="tel:+233591429017"><?php echo htmlspecialchars($L['contact_phone']); ?></a></p>
    </footer>

    <script>
        (function() {
            var btn = document.getElementById('btnTest');
            var resultEl = document.getElementById('result');
            var base = document.querySelector('base') ? document.querySelector('base').href : (window.location.pathname.replace(/\/views\/.*$/, '') || '/');
            var apiUrl = (base.replace(/\/$/, '') + '/api/v1/ping.php').replace(/^\/\//, '//');

            function showResult(success, text) {
                resultEl.className = 'result ' + (success ? 'success' : 'error');
                resultEl.innerHTML = '<span class="result-label">' + (success ? 'API success' : 'Request failed') + '</span>\n' + text;
                resultEl.style.display = 'block';
            }

            btn.addEventListener('click', function() {
                resultEl.style.display = 'none';
                btn.disabled = true;
                btn.textContent = '…';

                fetch(apiUrl, { method: 'GET', credentials: 'same-origin' })
                    .then(function(r) { return r.json().then(function(j) { return { ok: r.ok, json: j }; }); })
                    .then(function(o) {
                        showResult(o.ok && o.json && o.json.success, JSON.stringify(o.json, null, 2));
                    })
                    .catch(function(e) {
                        showResult(false, e.message || 'Network or server error.');
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.textContent = '<?= isset($L["api_test"]) ? addslashes($L["api_test"]) : "Test API"; ?>';
                    });
            });
        })();
    </script>
</body>
</html>
