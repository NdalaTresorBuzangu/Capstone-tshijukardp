<?php
if (!isset($L) || !is_array($L)) require_once __DIR__ . '/../config/lang.php';
$L = $L ?? [];
?>
<!DOCTYPE html>
<html lang="<?php echo isset($lang) && $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($L['terms_title'] ?? 'Terms of Service'); ?> – Tshijuka RDP</title>
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body class="form-page">
    <?php include 'nav.php'; ?>
    <section class="hero">
        <h1><?php echo htmlspecialchars($L['terms_title'] ?? 'Terms of Service'); ?></h1>
        <p><?php echo htmlspecialchars($L['terms_intro'] ?? 'Please read these terms before using the platform.'); ?></p>
    </section>
    <div class="form-card" style="max-width: 800px; text-align: left;">
        <div class="legal-content">
            <p><strong>Last updated:</strong> <?php echo date('F j, Y'); ?></p>
            <p>These Terms of Service (“Terms”) govern your use of the Tshijuka RDP platform and related services. By registering, logging in, or using the platform, you agree to these Terms. If you do not agree, do not use the service.</p>
            <h2>1. Acceptance and scope</h2>
            <p>By creating an account or using Tshijuka RDP, you confirm that you have read, understood, and agree to be bound by these Terms and our <a href="index.php?controller=Page&action=privacy">Privacy Policy</a>. These Terms apply to users in Ghana, other African jurisdictions, and the European Union (EU), in line with applicable data protection laws including the EU General Data Protection Regulation (GDPR) and the Ghana Data Protection Act, 2012 (Act 843), as well as other African data protection standards.</p>
            <h2>2. Description of the service</h2>
            <p>Tshijuka RDP is a document retrieval and verification platform that connects document seekers with document-issuing institutions. We provide account registration, document requests, status tracking, secure storage, payments, and related features. We may update or discontinue features with reasonable notice where required by law.</p>
            <h2>3. Eligibility and account</h2>
            <p>You must be at least 18 years old (or the age of majority in your jurisdiction) and provide accurate information when registering. You are responsible for keeping your password secure and for all activity under your account. You must notify us promptly of any unauthorized use.</p>
            <h2>4. Acceptable use</h2>
            <p>You agree to use the platform only for lawful purposes and in accordance with these Terms. You must not: (a) submit false or misleading information; (b) misuse, disrupt, or attempt to gain unauthorized access to the platform or others’ data; (c) use the service for fraud or illegal activity; or (d) violate any applicable law, including data protection and identity laws in Ghana, Africa, or the EU.</p>
            <h2>5. Data protection and consent</h2>
            <p>Your personal data is processed in accordance with our Privacy Policy and applicable data protection laws (including GDPR and Ghana’s Data Protection Act). By accepting these Terms, you consent to the collection, use, and storage of your data as described in the Privacy Policy. You may withdraw consent or exercise your rights (e.g. access, rectification, erasure, portability, objection) as set out in the Privacy Policy and in line with applicable law.</p>
            <h2>6. Intellectual property</h2>
            <p>Tshijuka RDP and its branding, design, and software are owned by us or our licensors. You may not copy, modify, or distribute our materials without permission. Documents you upload or request remain yours; you grant us the limited rights necessary to operate the service (e.g. storing and transmitting documents as part of the retrieval process).</p>
            <h2>7. Payments and fees</h2>
            <p>Where you choose to pay retrieval or other fees, payment terms and refunds are as displayed at the time of payment. We use third-party payment providers (e.g. Paystack); their terms also apply to payment processing.</p>
            <h2>8. Limitation of liability</h2>
            <p>To the fullest extent permitted by law, Tshijuka RDP and its operators shall not be liable for indirect, incidental, or consequential damages arising from your use of the service. Our total liability is limited to the amount you paid us in the twelve (12) months preceding the claim, where applicable. Nothing in these Terms excludes liability that cannot be excluded under applicable law (e.g. Ghana or EU consumer rights).</p>
            <h2>9. Termination</h2>
            <p>We may suspend or terminate your access if you breach these Terms or for other legitimate reasons. You may close your account at any time. Upon termination, your right to use the service ceases; provisions that by their nature should survive (e.g. liability, data retention) will remain in effect.</p>
            <h2>10. Changes to the Terms</h2>
            <p>We may update these Terms from time to time. We will notify you of material changes (e.g. by email or a notice on the platform) where required by law. Continued use after the effective date of changes constitutes acceptance. If you do not agree, you must stop using the service.</p>
            <h2>11. Governing law and disputes</h2>
            <p>These Terms are governed by the laws of Ghana, without prejudice to mandatory consumer or data protection laws in your country. Any disputes shall be resolved in the courts of Ghana, except where another jurisdiction is required by law (e.g. your place of residence in the EU).</p>
            <h2>12. Contact</h2>
            <p>For questions about these Terms, contact us at the details provided in the footer and in our <a href="index.php?controller=Page&action=privacy">Privacy Policy</a>.</p>
        </div>
        <p class="mt-4"><a href="javascript:history.back()" class="btn-submit" style="display: inline-block;"><?php echo htmlspecialchars($L['back'] ?? 'Back'); ?></a></p>
    </div>
    <?php include __DIR__ . '/footer_legal.php'; ?>
</body>
</html>
