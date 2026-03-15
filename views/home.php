<?php
/** Home view – receives $L, $lang, $baseUrl, $loggedIn from controller */
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tshijuka RDP – Document Loss & Recovery</title>
    <base href="<?php echo htmlspecialchars($baseUrl); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/site.css">
    <link rel="stylesheet" href="assets/nav.css">
</head>
<body>
    <nav class="navbar navbar-index">
        <a href="index.php" class="nav-brand">
            <img src="assets/logo.jpg" alt="Tshijuka RDP" class="logo">
        </a>
        <ul class="nav-links">
            <li><a href="index.php?controller=Page&action=about"><?php echo htmlspecialchars($L['about']); ?></a></li>
            <li><a href="index.php?controller=Auth&action=login_form"><?php echo htmlspecialchars($L['login']); ?></a></li>
            <li><a href="index.php?controller=Auth&action=signup_form"><?php echo htmlspecialchars($L['signup']); ?></a></li>
        </ul>
    </nav>

    <section class="hero">
        <h1>Tshijuka RDP</h1>
        <p><?php echo htmlspecialchars($L['doc_retrieval']); ?> <?php echo htmlspecialchars($L['connect_issuers']); ?></p>
    </section>

    <section class="loss-recovery">
        <div class="loss-recovery-card loss-card">
            <div class="img-wrap">
                <img src="assets/nature-7047433_1280.jpg" alt="Document loss – lost or inaccessible documents">
            </div>
            <div class="content">
                <h3><?php echo htmlspecialchars($L['problem']); ?></h3>
                <div class="index-problem-text"><?php echo nl2br(htmlspecialchars($L['index_problem_short'] ?? $L['doc_loss'])); ?></div>
            </div>
        </div>
        <div class="loss-recovery-card recovery-card">
            <div class="img-wrap">
                <img src="assets/Gemini_Generated_Image_ydzuxjydzuxjydzu.jpg" alt="Document recovery – secure platform">
            </div>
            <div class="content">
                <h3><?php echo htmlspecialchars($L['solution']); ?></h3>
                <div class="index-solution-text"><?php echo nl2br(htmlspecialchars($L['index_solution_short'] ?? $L['indep_digital'])); ?></div>
            </div>
        </div>
    </section>

    <section class="users-header">
        <h2><?php echo htmlspecialchars($L['platform_users']); ?></h2>
        <p class="users-count"><?php echo htmlspecialchars($L['users_count']); ?></p>
    </section>

    <section class="features">
        <div class="feature">
            <div class="feature-icon">🏢</div>
            <h4><?php echo htmlspecialchars($L['doc_issuers']); ?></h4>
            <div class="feature-details">
                <?php echo htmlspecialchars($L['gov_inst']); ?><br>
                <?php echo htmlspecialchars($L['embassies']); ?><br>
                <?php echo htmlspecialchars($L['schools']); ?><br>
                <?php echo htmlspecialchars($L['training']); ?><br>
                <?php echo htmlspecialchars($L['hospitals']); ?>
            </div>
        </div>
        <div class="feature">
            <div class="feature-icon">👤</div>
            <h4><?php echo htmlspecialchars($L['doc_seekers']); ?></h4>
            <div class="feature-details"><?php echo htmlspecialchars($L['displaced']); ?></div>
        </div>
    </section>

    <section class="action-section">
        <p class="subtitle"><?php echo htmlspecialchars($L['footer']); ?></p>
    </section>

    <footer class="footer">
        <p><?php echo htmlspecialchars($L['footer']); ?></p>
        <p><strong><?php echo htmlspecialchars($L['footer_contact'] ?? 'Contact Tshijuka RDP'); ?>:</strong> <a href="tel:+233591429017"><?php echo htmlspecialchars($L['contact_phone']); ?></a></p>
        <p><a href="index.php?controller=Page&action=terms"><?php echo htmlspecialchars($L['terms_title'] ?? 'Terms of Service'); ?></a> &middot; <a href="index.php?controller=Page&action=privacy"><?php echo htmlspecialchars($L['privacy_title'] ?? 'Privacy Policy'); ?></a></p>
    </footer>
</body>
</html>
