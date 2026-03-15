<?php if (!isset($L)) require_once __DIR__ . '/../config/lang.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($L['about']) ?> – Tshijuka RDP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body>
    <?php include 'nav.php'; ?>

    <section class="hero">
        <h1><?= htmlspecialchars($L['about']) ?> Tshijuka RDP</h1>
        <p><?= htmlspecialchars($L['about_tagline'] ?? $L['about_lead'] ?? $L['what_content']); ?></p>
    </section>

    <div class="page-content">
        <div class="card">
            <h2><?= htmlspecialchars($L['what_we_do']) ?></h2>
            <p><?= htmlspecialchars($L['about_overview'] ?? $L['what_content']) ?></p>
        </div>

        <div class="card">
            <h2><?= htmlspecialchars($L['about_who_we_are_heading'] ?? 'Who we are') ?></h2>
            <p><?= htmlspecialchars($L['about_who_we_are'] ?? $L['what_content']) ?></p>
        </div>

        <div class="card">
            <h2><?= htmlspecialchars($L['our_vision']) ?></h2>
            <p><?= htmlspecialchars($L['vision_content']) ?></p>
        </div>

        <div class="card">
            <h2><?= htmlspecialchars($L['our_mission']) ?></h2>
            <p><?= htmlspecialchars($L['mission_content']) ?></p>
        </div>

        <div class="card">
            <h2><?= htmlspecialchars($L['about_what_we_provide_heading'] ?? 'What we provide') ?></h2>
            <p><?= htmlspecialchars($L['about_what_we_do_full'] ?? $L['what_content']) ?></p>
        </div>

        <div class="card">
            <h2><?= htmlspecialchars($L['about_who_we_serve_heading'] ?? 'Who we serve') ?></h2>
            <p><?= htmlspecialchars($L['about_who_we_serve'] ?? $L['services_users']) ?></p>
        </div>

        <div class="card">
            <h2><?= htmlspecialchars($L['about_platform_highlights'] ?? 'Platform highlights') ?></h2>
            <ul>
                <?php for ($i = 1; $i <= 5; $i++) { $k = 'about_highlight_' . $i; if (!empty($L[$k])) { ?>
                <li><?= htmlspecialchars($L[$k]) ?></li>
                <?php } } ?>
            </ul>
        </div>

        <div class="card">
            <h2><?= htmlspecialchars($L['services_users']) ?></h2>

            <h3><?= htmlspecialchars($L['for_issuers']) ?></h3>
            <p><?= htmlspecialchars($L['for_issuers_content']) ?></p>
            <h3><?= htmlspecialchars($L['secure_guidance']) ?></h3>
            <p><?= htmlspecialchars($L['secure_guidance_content']) ?></p>
            <h4><?= htmlspecialchars($L['benefits_digital']) ?></h4>
            <ul>
                <li><?= htmlspecialchars($L['benefit1']) ?></li>
                <li><?= htmlspecialchars($L['benefit2']) ?></li>
                <li><?= htmlspecialchars($L['benefit3']) ?></li>
                <li><?= htmlspecialchars($L['benefit4']) ?></li>
                <li><?= htmlspecialchars($L['benefit5']) ?></li>
            </ul>
            <p><strong><?= htmlspecialchars($L['issuer_guide_intro'] ?? 'Our team will guide you through:') ?></strong></p>
            <ul>
                <li>Secure system setup and migration</li>
                <li>Document scanning and digitization</li>
                <li>Data security and encryption</li>
                <li>Training for your staff</li>
                <li>Ongoing support and maintenance</li>
            </ul>
            <div class="contact-info">
                <p><strong><?= htmlspecialchars($L['contact_digital']) ?></strong></p>
                <p><strong>Email:</strong> <a href="mailto:digitalization@tshijuka.org">digitalization@tshijuka.org</a></p>
                <p><strong>Phone:</strong> <a href="tel:+233591429017"><?= htmlspecialchars($L['contact_phone']) ?></a></p>
            </div>
        </div>

        <div class="card">
            <h3><?= htmlspecialchars($L['for_seekers']) ?></h3>
            <h4><?= htmlspecialchars($L['doc_recovery_services']) ?></h4>
            <p><?= htmlspecialchars($L['doc_recovery_content']) ?></p>
            <h4><?= htmlspecialchars($L['services_for_seekers']) ?></h4>
            <ul>
                <li>Direct connection to document-issuing institutions</li>
                <li>Secure payment processing for document requests</li>
                <li>Real-time tracking of document retrieval progress</li>
                <li>Multilingual support and guidance</li>
                <li>Digital document delivery and storage</li>
            </ul>
            <p><strong><?= htmlspecialchars($L['seeker_help_intro'] ?? 'How we help you:') ?></strong></p>
            <ul>
                <li>Identify the correct issuing institution</li>
                <li>Submit your document request securely</li>
                <li>Facilitate communication with institutions</li>
                <li>Process payments safely and transparently</li>
                <li>Deliver documents digitally when ready</li>
            </ul>
            <div class="contact-info">
                <p><strong><?= htmlspecialchars($L['contact_recovery']) ?></strong></p>
                <p><strong>Email:</strong> <a href="mailto:support@tshijuka.org">support@tshijuka.org</a></p>
                <p><strong>Phone:</strong> <a href="tel:+233591429017"><?= htmlspecialchars($L['contact_phone']) ?></a></p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p><?= htmlspecialchars($L['about_contact_professional'] ?? $L['footer_connecting']) ?></p>
        <p><strong><?= htmlspecialchars($L['footer_contact']) ?>:</strong> <a href="tel:+233591429017"><?= htmlspecialchars($L['contact_phone']) ?></a> · <a href="mailto:support@tshijuka.org">support@tshijuka.org</a></p>
    </footer>
</body>
</html>
