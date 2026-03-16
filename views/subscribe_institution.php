<?php
/** Institution subscribe view – receives $L, $lang from controller. No logic. */
?>
<!DOCTYPE html>
<html lang="<?php echo isset($lang) ? $lang : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Issuing Institution Subscription</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>New Document Issuing Institution Subscription</h2>
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    <form method="POST" action="index.php">
        <input type="hidden" name="controller" value="Institution">
        <input type="hidden" name="action" value="subscribe_submit">
        <div class="mb-3">
            <label for="documentIssuerName" class="form-label">Document Issuing Institution Name</label>
            <input type="text" name="documentIssuerName" id="documentIssuerName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="documentIssuerContact" class="form-label">Contact</label>
            <input type="text" name="documentIssuerContact" id="documentIssuerContact" class="form-control">
        </div>
        <div class="mb-3">
            <label for="documentIssuerEmail" class="form-label">Email</label>
            <input type="email" name="documentIssuerEmail" id="documentIssuerEmail" class="form-control" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="accept_terms_subscribe" id="accept_terms_subscribe" value="1" required>
            <label class="form-check-label" for="accept_terms_subscribe">
                I have read and accept the <a href="index.php?controller=Page&action=terms" target="_blank" rel="noopener">Terms of Service</a> and <a href="index.php?controller=Page&action=privacy" target="_blank" rel="noopener">Privacy Policy</a>. I consent to the processing of my data in line with data protection (GDPR / Ghana).
            </label>
        </div>
        <button type="submit" class="btn btn-success">Subscribe</button>
    </form>
</body>
</html>
