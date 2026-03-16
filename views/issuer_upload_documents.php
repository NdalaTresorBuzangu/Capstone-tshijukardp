<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
if (!isset($L)) require_once __DIR__ . '/../config/lang.php';
isLogin();

if ($_SESSION['user_role'] !== 'Document Issuer') {
    echo 'Access denied. Only document issuing institutions can access this page.';
    exit;
}

$userID = (int) $_SESSION['user_id'];

// Issuer profile (optional for display)
$issuerName = '';
$stmt = $conn->prepare('SELECT documentIssuerName FROM Subscribe WHERE userID = ?');
if ($stmt) {
    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r) $issuerName = $r['documentIssuerName'];
    $stmt->close();
}

// Document types for dropdown
$documentTypes = [];
$res = $conn->query('SELECT documentTypeID, typeName FROM DocumentType ORDER BY typeName');
if ($res) {
    while ($row = $res->fetch_assoc()) $documentTypes[] = $row;
}

// List of already uploaded documents (from IssuerStoredDocuments)
$storedList = [];
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'IssuerStoredDocuments'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
    $stmt = $conn->prepare('SELECT id, title, documentTypeID, description, filePath, uploadedOn FROM IssuerStoredDocuments WHERE userID = ? ORDER BY uploadedOn DESC');
    if ($stmt) {
        $stmt->bind_param('i', $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $storedList[] = $row;
        $stmt->close();
    }
}

$typeNames = [];
foreach ($documentTypes as $dt) $typeNames[$dt['documentTypeID']] = $dt['typeName'];
?>
<!DOCTYPE html>
<html lang="<?php echo (isset($lang) && $lang === 'fr') ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload documents to platform - Tshijuka RDP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/nav.css">
    <style>
        body { background: #f5f5f5; padding-top: 80px; }
        .issuer-upload-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1rem; }
        .issuer-upload-item { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; padding: 1rem; border: 1px solid #eee; border-radius: 8px; margin-bottom: 0.75rem; }
        .issuer-upload-item:last-child { margin-bottom: 0; }
        .issuer-upload-lead { font-size: 1rem; color: #333; margin-top: 0.5rem; margin-bottom: 1rem; line-height: 1.6; }
        .issuer-upload-card-title { font-size: 1.1rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem; }
        .issuer-upload-card-desc { font-size: 0.9rem; color: #555; margin-bottom: 1rem; }
    </style>
</head>
<body>

<?php $showLogout = true; include 'nav.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <div>
            <h1 class="h3 mb-0">Upload documents to platform</h1>
            <p class="issuer-upload-lead">Upload and store documents on the platform without waiting for a request from document seekers. Useful when you are in unstable environments—digitize and protect records in advance.</p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl ?? ''); ?>index.php?controller=Institution&action=panel" class="btn btn-outline-secondary">← Back to dashboard</a>
    </div>

    <!-- Upload form: same pattern as request box / preloss -->
    <div class="issuer-upload-card mb-4">
        <h2 class="issuer-upload-card-title">Add documents</h2>
        <p class="issuer-upload-card-desc">Add one or more documents. You can upload several files at once or take a picture. Each row can have a title, type, and optional description.</p>
        <form id="issuerUploadForm" enctype="multipart/form-data">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 small text-secondary">Documents to upload</h5>
                <button type="button" class="btn btn-outline-primary btn-sm" id="issuerAddRow">+ Add another document</button>
            </div>
            <div id="issuerRows">
                <div class="issuer-row card card-body mb-3 border">
                    <div class="row align-items-end g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Title</label>
                            <input type="text" name="title[0]" class="form-control form-control-sm" placeholder="e.g. Birth certificate, Diploma" maxlength="255" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Type</label>
                            <select name="documentType[0]" class="form-select form-select-sm">
                                <option value="">— Optional —</option>
                                <?php foreach ($documentTypes as $dt): ?>
                                    <option value="<?= (int)$dt['documentTypeID'] ?>"><?= htmlspecialchars($dt['typeName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Description</label>
                            <input type="text" name="description[0]" class="form-control form-control-sm" placeholder="Optional" maxlength="500">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Choose file(s)</label>
                            <input type="file" name="file[0][]" class="form-control form-control-sm issuer-file-multi" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" multiple>
                            <small class="text-muted">Select one or more</small>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Take picture</label>
                            <input type="file" name="camera[0]" class="form-control form-control-sm issuer-camera" accept="image/*" capture="environment">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm issuer-remove-row" title="Remove" style="visibility: hidden;">×</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" id="issuerUploadBtn">Upload to platform</button>
                <span id="issuerUploadStatus" class="ms-2 text-muted small"></span>
            </div>
        </form>
    </div>

    <!-- List of stored documents -->
    <div class="issuer-upload-card">
        <h2 class="h5 mb-3">Your uploaded documents (on platform)</h2>
        <div id="issuerStoredList">
            <?php if (!$tableExists || empty($storedList)): ?>
                <p class="text-muted mb-0">No documents uploaded yet. Use the form above to upload documents to the platform without waiting for a request.</p>
            <?php else: ?>
                <?php foreach ($storedList as $doc): ?>
                    <div class="issuer-upload-item" data-issuer-stored-id="<?= (int)$doc['id'] ?>">
                        <div>
                            <strong><?= htmlspecialchars($doc['title']) ?></strong>
                            <?php if (!empty($doc['documentTypeID']) && isset($typeNames[$doc['documentTypeID']])): ?>
                                <span class="badge bg-secondary ms-1"><?= htmlspecialchars($typeNames[$doc['documentTypeID']]) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($doc['description'])): ?>
                                <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($doc['description'], 0, 60, '…')) ?></div>
                            <?php endif; ?>
                            <div class="small text-muted">Uploaded <?= date('M j, Y', strtotime($doc['uploadedOn'])) ?></div>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="index.php?controller=Document&action=view_image_page&path=<?php echo urlencode('issuer/' . basename($doc['filePath'])); ?>&type=issuer&id=<?= (int)$doc['id'] ?>" class="btn btn-sm btn-success" target="_blank" rel="noopener">View</a>
                            <a href="index.php?controller=Document&action=view_image&path=<?php echo urlencode('issuer/' . basename($doc['filePath'])); ?>&download=1" class="btn btn-sm btn-outline-secondary" download>Download</a>
                            <button type="button" class="btn btn-sm btn-outline-danger issuer-delete-stored" data-id="<?= (int)$doc['id'] ?>">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
var BASE_URL = <?= json_encode($baseUrl ?? (function_exists('getBaseUrl') ? getBaseUrl() : '')) ?>;
(function() {
    const form = document.getElementById('issuerUploadForm');
    const status = document.getElementById('issuerUploadStatus');
    const uploadBtn = document.getElementById('issuerUploadBtn');
    const container = document.getElementById('issuerRows');

    function reindexIssuerRows() {
        const rows = container.querySelectorAll('.issuer-row');
        rows.forEach(function(row, idx) {
            var t = row.querySelector('input[name^="title"]'); if (t) t.setAttribute('name', 'title[' + idx + ']');
            var d = row.querySelector('select[name^="documentType"]'); if (d) d.setAttribute('name', 'documentType[' + idx + ']');
            var desc = row.querySelector('input[name^="description"]'); if (desc) desc.setAttribute('name', 'description[' + idx + ']');
            var f = row.querySelector('input.issuer-file-multi'); if (f) f.setAttribute('name', 'file[' + idx + '][]');
            var c = row.querySelector('input.issuer-camera'); if (c) c.setAttribute('name', 'camera[' + idx + ']');
        });
    }

    document.getElementById('issuerAddRow').addEventListener('click', function() {
        const firstRow = container.querySelector('.issuer-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelectorAll('input[type="text"], input[type="file"], select').forEach(function(el) { el.value = ''; });
        clone.querySelector('.issuer-remove-row').style.visibility = 'visible';
        container.appendChild(clone);
        reindexIssuerRows();
    });

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('issuer-remove-row')) {
            const rows = container.querySelectorAll('.issuer-row');
            if (rows.length > 1) {
                e.target.closest('.issuer-row').remove();
                reindexIssuerRows();
            }
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        status.textContent = '';
        uploadBtn.disabled = true;
        const fd = new FormData(this);
        try {
            const res = await fetch(BASE_URL + 'actions/issuer_upload_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                status.textContent = data.message;
                status.className = 'ms-2 small text-success';
                var rows = container.querySelectorAll('.issuer-row');
                for (var i = rows.length - 1; i > 0; i--) rows[i].remove();
                reindexIssuerRows();
                form.reset();
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                status.textContent = data.message || 'Upload failed.';
                status.className = 'ms-2 small text-danger';
                uploadBtn.disabled = false;
            }
        } catch (err) {
            status.textContent = 'Network error.';
            status.className = 'ms-2 small text-danger';
            uploadBtn.disabled = false;
        }
    });

    document.querySelectorAll('.issuer-delete-stored').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            if (!confirm('Remove this document from the platform?')) return;
            const fd = new FormData();
            fd.append('id', id);
            try {
                const res = await fetch(BASE_URL + 'actions/issuer_delete_stored_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const row = document.querySelector('.issuer-upload-item[data-issuer-stored-id="' + id + '"]');
                    if (row) row.remove();
                } else {
                    alert(data.message || 'Delete failed.');
                }
            } catch (err) {
                alert('Network error.');
            }
        });
    });
})();
</script>

</body>
</html>
