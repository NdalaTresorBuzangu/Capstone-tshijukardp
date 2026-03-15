<?php if (!isset($L)) require_once __DIR__ . '/../config/lang.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($L['password_recovery']); ?> – Tshijuka RDP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body class="form-page">
    <?php include 'nav.php'; ?>

    <section class="hero">
        <h1><?php echo htmlspecialchars($L['password_recovery']); ?></h1>
        <p><?php echo htmlspecialchars($L['enter_email_recovery']); ?></p>
    </section>

    <div class="form-card">
        <form action="index.php?controller=Auth&action=password_recovery_submit" method="POST">
            <div class="input-space">
                <label for="email"><?php echo htmlspecialchars($L['email']); ?></label>
                <input type="email" id="email" name="email" placeholder="<?php echo htmlspecialchars($L['enter_email']); ?>" required>
            </div>
            <button type="submit" class="btn-submit"><?php echo htmlspecialchars($L['submit']); ?></button>
        </form>
        <div class="signin-link">
            <p><a href="index.php?controller=Auth&action=login_form"><?php echo htmlspecialchars($L['login']); ?></a></p>
        </div>
    </div>

    <footer class="footer">
        <p><?php echo htmlspecialchars($L['footer_contact'] ?? 'Contact Tshijuka RDP'); ?>: <a href="tel:+233591429017"><?php echo htmlspecialchars($L['contact_phone']); ?></a></p>
    </footer>
</body>
</html>
