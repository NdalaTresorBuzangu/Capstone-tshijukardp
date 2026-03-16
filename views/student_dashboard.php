<?php
include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core.php';
if (!isset($L)) require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
isLogin();

if ($_SESSION['user_role'] !== 'Document Seeker') {
    echo "Access denied.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
<meta charset="UTF-8">
<title>Student Dashboard - Tshijuka RDP</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/nav.css">
<style>
    body {
        background: url('<?php echo htmlspecialchars(isset($bgImage) ? $bgImage : '../assets/nature-7047433_1280.jpg'); ?>') no-repeat center center fixed;
        background-size: cover;
        color: #333;
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
    }
    .dashboard-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        color: #333;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        padding: 2rem;
        transition: all 0.3s ease-in-out;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.25);
    }
    .list-group-item {
        border: none;
        border-radius: 10px;
        margin-bottom: 10px;
        transition: 0.3s;
    }
    .list-group-item:hover {
        background-color: #0d6efd;
        color: #fff;
    }
    .header-section {
        text-align: center;
        margin-bottom: 0.75rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 0.6rem 1rem;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        color: #333;
    }
    .header-section h1 {
        font-weight: 600;
        font-size: 1.1rem;
        color: #dc3545;
        margin: 0 0 0.2rem 0;
    }
    .tagline {
        font-size: 0.8rem;
        color: #666;
        margin: 0;
    }
    /* Recovery Tools – 2x2 grid so all 4 boxes visible without scrolling */
    .recovery-tools {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin-top: 0.5rem;
    }
    
    .tool-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 0.85rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: all 0.25s ease;
        border: 2px solid transparent;
        text-decoration: none;
        color: inherit;
        display: block;
        position: relative;
        overflow: hidden;
    }
    
    .tool-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #dc3545, #007bff);
        transform: scaleX(0);
        transition: transform 0.25s ease;
    }
    
    .tool-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.18);
        border-color: #dc3545;
    }
    
    .tool-card:hover::before {
        transform: scaleX(1);
    }
    
    .tool-icon {
        font-size: 1.75rem;
        margin-bottom: 0.35rem;
        display: block;
        text-align: center;
        transition: transform 0.25s ease;
    }
    
    .tool-card:hover .tool-icon {
        transform: scale(1.08);
    }
    
    .tool-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #dc3545;
        margin-bottom: 0.35rem;
        text-align: center;
        line-height: 1.25;
    }
    
    .tool-card:hover .tool-title {
        color: #c82333;
    }
    
    .tool-description {
        color: #555;
        line-height: 1.4;
        margin-bottom: 0.5rem;
        text-align: center;
        font-size: 0.7rem;
    }
    
    .tool-card.tool-card-upload-protect .tool-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #c82333;
    }
    .tool-card.tool-card-upload-protect .tool-description {
        font-size: 0.7rem;
        color: #444;
        font-weight: 500;
        line-height: 1.4;
    }
    .tool-card.tool-card-upload-protect .tool-features li {
        color: #444;
        font-size: 0.68rem;
    }
    .tool-card.tool-card-upload-protect .tool-button {
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .tool-features {
        list-style: none;
        padding: 0;
        margin-bottom: 0.5rem;
    }
    
    .tool-features li {
        padding: 0.15rem 0;
        color: #555;
        font-size: 0.68rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    
    .tool-features li::before {
        content: '✓';
        color: #28a745;
        font-weight: bold;
        font-size: 0.7rem;
    }
    
    .tool-button {
        width: 100%;
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.75rem;
        text-align: center;
        transition: all 0.25s ease;
        text-decoration: none;
        display: block;
    }
    
    .tool-button:hover {
        background: linear-gradient(135deg, #c82333, #a71e2a);
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(220, 53, 69, 0.35);
        color: white;
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #dc3545;
        text-align: center;
        margin-bottom: 0.25rem;
    }
    
    .section-subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 0.5rem;
        font-size: 0.78rem;
    }
    
    .dashboard-card.seeker-tools {
        padding: 0.85rem 1rem;
    }
    
    /* Responsive: stack to 1 column on small screens */
    @media (max-width: 576px) {
        .recovery-tools {
            grid-template-columns: 1fr;
            gap: 0.6rem;
        }
        
        .tool-card {
            padding: 0.75rem;
        }
        
        .section-title { font-size: 1rem; }
        .section-subtitle { font-size: 0.75rem; }
    }
    
    @media (max-width: 480px) {
        .container { padding: 0.5rem; }
        .header-section { padding: 0.5rem 0.75rem; }
        .header-section h1 { font-size: 1rem; }
        .tagline { font-size: 0.75rem; }
    }
    
    .doc-id-item {
        background: rgba(255, 255, 255, 0.9);
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 0.75rem;
        border-left: 4px solid #dc3545;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .doc-id-item code {
        font-size: 1.1rem;
        font-weight: bold;
        color: #dc3545;
        background: #f8f9fa;
        padding: 0.5rem;
        border-radius: 5px;
        flex: 1;
        min-width: 200px;
    }
    
    .doc-id-actions {
        display: flex;
        gap: 0.5rem;
    }
</style>
<script>
// Load and display saved document IDs
function loadMyDocumentIDs() {
    const savedIDs = JSON.parse(localStorage.getItem('documentIDs') || '[]');
    const myDocumentIDs = document.getElementById('myDocumentIDs');
    const documentIDsList = document.getElementById('documentIDsList');
    
    if (savedIDs.length > 0) {
        myDocumentIDs.style.display = 'block';
        documentIDsList.innerHTML = savedIDs.map((item, index) => {
            const date = new Date(item.date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            return `
                <div class="doc-id-item">
                    <div style="flex: 1;">
                        <code>${item.id}</code>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                            ${date} - ${item.description || 'No description'}
                        </div>
                    </div>
                    <div class="doc-id-actions">
                        <button onclick="copyDocID('${item.id}')" class="btn btn-sm btn-primary">📋 Copy</button>
                        <a href="index.php?controller=Seeker&action=progress" class="btn btn-sm btn-success">📊 Track</a>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        myDocumentIDs.style.display = 'none';
    }
}

// Copy document ID
function copyDocID(docID) {
    navigator.clipboard.writeText(docID).then(function() {
        alert('Document ID copied: ' + docID);
    }, function() {
        const textArea = document.createElement('textarea');
        textArea.value = docID;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Document ID copied: ' + docID);
    });
}

// Clear all document IDs
function clearAllDocumentIDs() {
    if (confirm('Are you sure you want to clear all saved document IDs? This will not delete your documents, only the local list.')) {
        localStorage.removeItem('documentIDs');
        loadMyDocumentIDs();
    }
}

// Load on page load
document.addEventListener('DOMContentLoaded', function() {
    loadMyDocumentIDs();
});
</script>
</head>
<body>

<!-- Navigation Bar -->
<?php $showLogout = true; include 'nav.php'; ?>

<div class="container py-2" style="margin-top: 72px;">
    <div class="header-section">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h1>
        <p class="tagline">Empowering you to recover and protect your documents.</p>
    </div>

    <!-- My Document IDs Section -->
    <div class="dashboard-card mb-4" id="myDocumentIDs" style="display: none;">
        <h4 class="text-center mb-3" style="color: #dc3545;">📋 My Document IDs</h4>
        <p class="text-center text-muted mb-3">All your submitted document IDs for easy tracking</p>
        <div id="documentIDsList"></div>
        <div class="text-center mt-3">
            <button onclick="clearAllDocumentIDs()" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars($L['clear_all'] ?? 'Clear all') ?></button>
        </div>
    </div>

    <div class="dashboard-card seeker-tools">
        <h2 class="section-title"><?= htmlspecialchars($L['recovery_tools'] ?? 'Recovery tools') ?></h2>
        <p class="section-subtitle"><?= htmlspecialchars($L['recovery_subtitle'] ?? 'Submit requests, view your Tshijuka Pack, and track progress.') ?></p>
        
        <div class="recovery-tools">
            <a href="index.php?controller=Seeker&action=submit_form" class="tool-card">
                <span class="tool-icon">✍️</span>
                <h3 class="tool-title"><?= htmlspecialchars($L['submit_request'] ?? 'Submit a request') ?></h3>
                <p class="tool-description"><?= htmlspecialchars($L['submit_desc'] ?? 'Request documents from issuers.') ?></p>
                <ul class="tool-features">
                    <li><?= htmlspecialchars($L['submit_feat1'] ?? 'Submit new document requests') ?></li>
                    <li><?= htmlspecialchars($L['submit_feat2'] ?? 'Track status of requests') ?></li>
                    <li><?= htmlspecialchars($L['submit_feat3'] ?? 'Receive updates from issuers') ?></li>
                    <li><?= htmlspecialchars($L['submit_feat4'] ?? 'Secure and private') ?></li>
                </ul>
                <span class="tool-button"><?= htmlspecialchars($L['submit_new'] ?? 'Submit new request') ?> →</span>
            </a>
            
            <a href="index.php?controller=Seeker&action=pack" class="tool-card">
                <span class="tool-icon">📦</span>
                <h3 class="tool-title"><?= htmlspecialchars($L['tshijuka_pack'] ?? 'Tshijuka Pack') ?></h3>
                <p class="tool-description"><?= htmlspecialchars($L['tshijuka_desc'] ?? 'Your collected documents in one place.') ?></p>
                <ul class="tool-features">
                    <li><?= htmlspecialchars($L['tshijuka_feat1'] ?? 'View all your documents') ?></li>
                    <li><?= htmlspecialchars($L['tshijuka_feat2'] ?? 'Download when needed') ?></li>
                    <li><?= htmlspecialchars($L['tshijuka_feat3'] ?? 'Share with consent') ?></li>
                    <li><?= htmlspecialchars($L['tshijuka_feat4'] ?? 'Always available') ?></li>
                </ul>
                <span class="tool-button"><?= htmlspecialchars($L['view_docs'] ?? 'View documents') ?> →</span>
            </a>
            
            <a href="index.php?controller=Seeker&action=progress" class="tool-card">
                <span class="tool-icon">📊</span>
                <h3 class="tool-title"><?= htmlspecialchars($L['track_progress'] ?? 'Track progress') ?></h3>
                <p class="tool-description"><?= htmlspecialchars($L['track_desc'] ?? 'Check the status of your document requests.') ?></p>
                <ul class="tool-features">
                    <li><?= htmlspecialchars($L['track_feat1'] ?? 'Enter your document ID') ?></li>
                    <li><?= htmlspecialchars($L['track_feat2'] ?? 'See current status') ?></li>
                    <li><?= htmlspecialchars($L['track_feat3'] ?? 'View issuer updates') ?></li>
                    <li><?= htmlspecialchars($L['track_feat4'] ?? 'Get notified when ready') ?></li>
                </ul>
                <span class="tool-button"><?= htmlspecialchars($L['track_btn'] ?? 'Check status') ?> →</span>
            </a>

            <a href="index.php?controller=Seeker&action=preloss" class="tool-card tool-card-upload-protect">
                <span class="tool-icon">📁</span>
                <h3 class="tool-title"><?= htmlspecialchars($L['preloss_title'] ?? 'Upload & protect your documents') ?></h3>
                <p class="tool-description"><?= htmlspecialchars($L['preloss_desc'] ?? 'Upload and store copies of your important documents here. If you ever lose the originals, you will have a secure backup.') ?></p>
                <ul class="tool-features">
                    <li><?= htmlspecialchars($L['preloss_feat1'] ?? 'Upload certificates, IDs, diplomas and other documents') ?></li>
                    <li><?= htmlspecialchars($L['preloss_feat2'] ?? 'Keep a secure backup before any loss or damage') ?></li>
                    <li><?= htmlspecialchars($L['preloss_feat3'] ?? 'Download your documents whenever you need them') ?></li>
                    <li><?= htmlspecialchars($L['preloss_feat4'] ?? 'Your files are stored securely and privately') ?></li>
                </ul>
                <span class="tool-button"><?= htmlspecialchars($L['preloss_btn'] ?? 'Upload & protect my documents') ?> →</span>
            </a>
        </div>
    </div>

    <footer class="text-center mt-3" style="color: #333; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); padding: 0.5rem; border-radius: 8px; font-size: 0.75rem;">
        <small>© <?= date('Y') ?> Tshijuka Document Recovery Platform • Built for Hope & Resilience</small>
    </footer>
</div>

</body>
</html>








