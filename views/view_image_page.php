<?php
/**
 * View image/file on a page with Download and Delete. For issuer-stored and preloss documents.
 * Query: path= (e.g. issuer/xxx.jpg), type=issuer|preloss, id= (record id for delete).
 */
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
isLogin();

$path = isset($_GET['path']) ? trim($_GET['path']) : '';
$path = str_replace(['../', '..\\', '\\'], ['', '', '/'], $path);
$path = trim($path, '/');
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$prelossID = isset($_GET['prelossID']) ? (int) $_GET['prelossID'] : $id;

if ($path === '' || strpos($path, '..') !== false || !in_array($type, ['issuer', 'preloss'], true)) {
    $_SESSION['message'] = 'Invalid request.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php?controller=' . ($type === 'issuer' ? 'Institution&action=upload_documents' : 'Seeker&action=preloss'));
    exit;
}

$viewUrl = 'index.php?controller=Document&action=view_image&path=' . urlencode($path);
$downloadUrl = 'index.php?controller=Document&action=view_image&path=' . urlencode($path) . '&download=1';
$canDelete = false;
$backUrl = 'index.php?controller=Seeker&action=preloss';
if ($type === 'issuer') {
    $backUrl = 'index.php?controller=Institution&action=upload_documents';
    $canDelete = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Document Issuer' && $id > 0;
} elseif ($type === 'preloss') {
    $canDelete = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Document Seeker' && $prelossID > 0;
}

$isPdf = (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - Tshijuka RDP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/nav.css">
    <style>
        .viewer-toolbar { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 0.75rem 1rem; }
        .viewer-content { padding: 1rem; min-height: 70vh; display: flex; align-items: center; justify-content: center; background: #2b2b2b; }
        .viewer-content img { max-width: 100%; max-height: 85vh; object-fit: contain; }
        .viewer-content iframe { width: 100%; height: 85vh; border: none; }
    </style>
</head>
<body>
<?php $showLogout = true; include 'nav.php'; ?>

<div class="viewer-toolbar d-flex flex-wrap align-items-center gap-3">
    <strong class="text-secondary">Document</strong>
    <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-primary btn-sm" download><i class="fas fa-download"></i> Download</a>
    <?php if ($canDelete): ?>
        <?php if ($type === 'issuer'): ?>
            <form method="post" action="../actions/issuer_delete_stored_action.php" class="d-inline" onsubmit="return confirm('Delete this document?');">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i> Delete</button>
            </form>
        <?php else: ?>
            <button type="button" class="btn btn-outline-danger btn-sm preloss-delete-btn" data-preloss-id="<?= $prelossID ?>"><i class="fas fa-trash-alt"></i> Delete</button>
        <?php endif; ?>
    <?php endif; ?>
    <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary btn-sm ms-auto">← Back</a>
</div>

<div class="viewer-content">
    <?php if ($isPdf): ?>
        <iframe src="<?= htmlspecialchars($viewUrl) ?>#toolbar=1" title="Document PDF" class="w-100"></iframe>
    <?php else: ?>
        <img src="<?= htmlspecialchars($viewUrl) ?>" alt="Document">
    <?php endif; ?>
</div>

<?php if ($type === 'preloss' && $canDelete): ?>
<script>
document.querySelector('.preloss-delete-btn')?.addEventListener('click', function() {
    if (!confirm('Delete this document? This cannot be undone.')) return;
    var prelossID = this.getAttribute('data-preloss-id');
    var fd = new FormData();
    fd.append('prelossID', prelossID);
    fetch('../actions/preloss_delete_action.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) window.location.href = 'index.php?controller=Seeker&action=preloss';
            else alert(data.message || 'Failed to delete.');
        })
        .catch(function() { alert('Error.'); });
});
</script>
<?php endif; ?>
<script src="https://kit.fontawesome.com/cb76afc7c2.js" crossorigin="anonymous"></script>
</body>
</html>
