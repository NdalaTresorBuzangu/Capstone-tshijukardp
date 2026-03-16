<?php
if (!isset($L) || !is_array($L)) require_once __DIR__ . '/../config/lang.php';
$L = $L ?? [];
?>
<!DOCTYPE html>
<html lang="<?php echo isset($lang) && $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($L['privacy_title'] ?? 'Privacy Policy'); ?> – Tshijuka RDP</title>
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/site.css">
</head>
<body class="form-page">
    <?php include 'nav.php'; ?>
    <section class="hero">
        <h1><?php echo htmlspecialchars($L['privacy_title'] ?? 'Privacy Policy'); ?></h1>
        <p><?php echo htmlspecialchars($L['privacy_intro'] ?? 'How we collect, use and protect your data.'); ?></p>
    </section>
    <div class="form-card" style="max-width: 800px; text-align: left;">
        <div class="legal-content">
            <p><strong>Last updated:</strong> <?php echo date('F j, Y'); ?></p>
            <p>Tshijuka RDP (“we”, “us”) is committed to protecting your personal data in line with the EU General Data Protection Regulation (GDPR), the Ghana Data Protection Act, 2012 (Act 843), and other applicable African and international data protection standards. This Privacy Policy explains what data we collect, why we collect it, how we use and protect it, and your rights.</p>
            <h2>1. Data controller</h2>
            <p>Tshijuka RDP operates the platform. For data protection purposes we are the data controller. Contact: see footer and “Contact” section below.</p>
            <h2>2. Legal basis (GDPR / Ghana / Africa)</h2>
            <p>We process your data on the following bases, as applicable: (a) <strong>Contract</strong> – to provide the platform and fulfil document requests; (b) <strong>Consent</strong> – where you have given clear consent (e.g. by accepting this Privacy Policy and our Terms of Service); (c) <strong>Legal obligation</strong> – where we must comply with law; (d) <strong>Legitimate interests</strong> – for security, fraud prevention, and improving the service, where not overridden by your rights. Under Ghana’s Data Protection Act we ensure lawful and fair processing and only collect data necessary for stated purposes.</p>
            <h2>3. Data we collect</h2>
            <p>We may collect: (a) <strong>Account data</strong> – name, email, contact (e.g. phone), role, password (stored hashed); (b) <strong>Document and request data</strong> – document types, descriptions, locations, uploaded files, institution selections, status and tracking information; (c) <strong>Payment data</strong> – payment references and transaction metadata (we do not store full card details; payment processing is handled by third parties such as Paystack); (d) <strong>Technical and usage data</strong> – IP address, browser type, device information, and logs necessary for security and operation; (e) <strong>Communications</strong> – messages sent through the platform (e.g. chat). We do not collect more than is necessary for the purposes described.</p>
            <h2>4. How we use your data</h2>
            <p>We use your data to: create and manage your account; process document requests and deliver services; communicate with you (e.g. MFA codes, status updates); process payments; improve security and prevent fraud; comply with legal obligations; and, where you have consented, send relevant service or marketing communications. We do not sell your personal data to third parties.</p>
            <h2>5. Sharing and transfers</h2>
            <p>We may share data with: (a) document-issuing institutions and admissions offices (as needed to fulfil requests); (b) payment providers (e.g. Paystack); (c) hosting and technical service providers (under strict agreements); (d) authorities when required by law. Data may be stored or processed in Ghana, the EU, or other jurisdictions with adequate safeguards where required (e.g. GDPR Art. 44–50; Ghana Data Protection Act).</p>
            <h2>6. Retention</h2>
            <p>We retain your data only as long as necessary for the purposes above, including legal, tax, or regulatory requirements. Account and document data are retained while your account is active and for a period after closure as needed for disputes, legal compliance, or legitimate business purposes. You may request erasure where the law gives you that right.</p>
            <h2>7. Security</h2>
            <p>We implement appropriate technical and organisational measures (e.g. encryption, access controls, secure storage) to protect your data against unauthorised access, loss, or alteration, in line with GDPR and Ghana data protection requirements.</p>
            <h2>8. Your rights (GDPR, Ghana, Africa)</h2>
            <p>Depending on your location, you may have the right to: <strong>Access</strong> – obtain a copy of your personal data; <strong>Rectification</strong> – correct inaccurate data; <strong>Erasure</strong> – request deletion in certain cases; <strong>Restriction</strong> – limit processing in certain cases; <strong>Portability</strong> – receive your data in a structured format (where applicable); <strong>Object</strong> – object to processing based on legitimate interests or for direct marketing; <strong>Withdraw consent</strong> – where processing is based on consent; <strong>Lodge a complaint</strong> – with a supervisory authority (e.g. in the EU or Ghana Data Protection Commission). To exercise these rights, contact us using the details below. We will respond within the timeframes required by applicable law.</p>
            <h2>9. Cookies and similar technologies</h2>
            <p>We use session and essential cookies (and similar technologies where applicable) necessary for authentication, security, and basic operation. You can control cookies through your browser settings; disabling essential cookies may affect platform functionality.</p>
            <h2>10. Children</h2>
            <p>The service is not directed at persons under the age of 18 (or the age of majority in your country). We do not knowingly collect data from children; if we become aware of such data we will delete it.</p>
            <h2>11. Changes to this policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of material changes where required by law (e.g. by email or a notice on the platform). Continued use after the effective date constitutes acceptance. We encourage you to review this policy periodically.</p>
            <h2>12. Contact</h2>
            <p>For privacy-related requests or questions: contact us at the phone number or contact details provided in the platform footer. For Ghana: you may also contact the Data Protection Commission where applicable. For the EU/EEA: you may lodge a complaint with your local data protection supervisory authority.</p>
        </div>
        <p class="mt-4"><a href="javascript:history.back()" class="btn-submit" style="display: inline-block;"><?php echo htmlspecialchars($L['back'] ?? 'Back'); ?></a></p>
    </div>
    <?php include __DIR__ . '/footer_legal.php'; ?>
</body>
</html>
