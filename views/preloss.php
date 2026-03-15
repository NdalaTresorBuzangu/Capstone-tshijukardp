<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
if (!isset($L)) require_once __DIR__ . '/../config/lang.php';
isLogin();

if ($_SESSION['user_role'] !== 'Document Seeker') {
    echo 'Access denied.';
    exit;
}

$userID = (int) $_SESSION['user_id'];
$prelossList = [];
$stmt = $conn->prepare('SELECT prelossID, title, filePath, uploadedOn FROM PrelossDocuments WHERE userID = ? ORDER BY uploadedOn DESC');
if ($stmt) {
    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $prelossList[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($lang) && $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($L['preloss_page_title']) ?> - Tshijuka RDP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/nav.css">
    <style>
        body { background: #f5f5f5; padding-top: 80px; }
        .preloss-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1rem; }
        .preloss-item { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; padding: 1rem; border: 1px solid #eee; border-radius: 8px; margin-bottom: 0.75rem; }
        .preloss-item:last-child { margin-bottom: 0; }
        .preloss-actions { display: flex; gap: 0.5rem; }
        .preloss-lead { font-size: 1.05rem; color: #333; margin-top: 0.5rem; margin-bottom: 1.5rem; line-height: 1.6; max-width: 42rem; }
        .preloss-card .preloss-card-title { font-size: 1.1rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem; }
        .preloss-card .preloss-card-desc { font-size: 0.95rem; color: #555; margin-bottom: 1rem; }
    </style>
</head>
<body>

<?php $showLogout = true; include 'nav.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <div>
            <h1 class="h3 mb-0"><?= htmlspecialchars($L['preloss_page_title'] ?? 'Store documents (pre-loss)') ?></h1>
            <p class="preloss-lead"><?= htmlspecialchars($L['preloss_lead'] ?? $L['preloss_desc'] ?? 'Upload and store copies of your important documents here. If you ever lose the originals, you will have a secure backup.') ?></p>
        </div>
        <a href="student_dashboard.php" class="btn btn-outline-secondary">← <?= htmlspecialchars($L['back_dashboard'] ?? 'Back to Dashboard') ?></a>
    </div>

    <!-- Upload form: same pattern as request box – multiple rows, multi-file + take picture -->
    <div class="preloss-card mb-4">
        <h2 class="preloss-card-title"><?= htmlspecialchars($L['preloss_upload'] ?? 'Upload a document') ?></h2>
        <p class="preloss-card-desc"><?= htmlspecialchars($L['preloss_desc'] ?? 'Upload and store copies of your important documents. Your files are kept securely so you can download them anytime.') ?></p>
        <form id="prelossForm" enctype="multipart/form-data">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 small text-secondary"><?= htmlspecialchars($L['preloss_docs_to_upload'] ?? 'Documents to upload') ?></h5>
                <button type="button" class="btn btn-outline-primary btn-sm" id="prelossAddRow">+ <?= htmlspecialchars($L['preloss_add_another'] ?? 'Add another document') ?></button>
            </div>
            <p class="text-muted small mb-3"><?= htmlspecialchars($L['preloss_multi_hint'] ?? 'Add one or more documents. You can upload several files at once or take a picture.') ?></p>
            <div id="prelossRows">
                <div class="preloss-row card card-body mb-3 border">
                    <div class="row align-items-end g-2">
                        <div class="col-md-4">
                            <label class="form-label small"><?= htmlspecialchars($L['preloss_doc_title']) ?></label>
                            <input type="text" name="title[0]" class="form-control form-control-sm" placeholder="<?= htmlspecialchars($L['preloss_doc_title_placeholder']) ?>" maxlength="255" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small"><?= htmlspecialchars($L['preloss_choose_file']) ?></label>
                            <input type="file" name="file[0][]" class="form-control form-control-sm preloss-file-multi" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" multiple>
                            <small class="text-muted"><?= htmlspecialchars($L['preloss_multiple_hint'] ?? 'Select one or more files') ?></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small"><?= htmlspecialchars($L['preloss_take_picture'] ?? 'Take picture') ?></label>
                            <input type="file" name="camera[0]" class="form-control form-control-sm preloss-camera" accept="image/*" capture="environment" title="<?= htmlspecialchars($L['preloss_take_picture'] ?? 'Take picture') ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm preloss-remove-row" title="<?= htmlspecialchars($L['preloss_remove'] ?? 'Remove') ?>" style="visibility: hidden;">×</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" id="uploadBtn"><?= htmlspecialchars($L['preloss_upload_btn']) ?></button>
                <span id="uploadStatus" class="ms-2 text-muted small"></span>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="preloss-card">
        <h2 class="h5 mb-3"><?= htmlspecialchars($L['preloss_list']) ?></h2>
        <div id="prelossList">
            <?php if (empty($prelossList)): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars($L['preloss_empty']) ?></p>
            <?php else: ?>
                <?php foreach ($prelossList as $doc): ?>
                    <div class="preloss-item" data-preloss-id="<?= (int) $doc['prelossID'] ?>">
                        <div>
                            <strong><?= htmlspecialchars($doc['title']) ?></strong>
                            <div class="small text-muted"><?= htmlspecialchars($L['preloss_uploaded_on']) ?> <?= date('M j, Y', strtotime($doc['uploadedOn'])) ?></div>
                        </div>
                        <div class="preloss-actions">
                            <a href="index.php?controller=Document&action=view_image_page&path=<?php echo urlencode('preloss/' . basename($doc['filePath'])); ?>&type=preloss&prelossID=<?= (int) $doc['prelossID'] ?>" class="btn btn-sm btn-success" target="_blank" rel="noopener">View</a>
                            <a href="index.php?controller=Document&action=view_image&path=<?php echo urlencode('preloss/' . basename($doc['filePath'])); ?>&download=1" class="btn btn-sm btn-outline-secondary" download>Download</a>
                            <button type="button" class="btn btn-sm btn-outline-danger preloss-delete" data-id="<?= (int) $doc['prelossID'] ?>"><?= htmlspecialchars($L['preloss_delete']) ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('prelossForm');
    const status = document.getElementById('uploadStatus');
    const uploadBtn = document.getElementById('uploadBtn');
    const container = document.getElementById('prelossRows');

    function reindexPrelossRows() {
        const rows = container.querySelectorAll('.preloss-row');
        rows.forEach(function(row, idx) {
            const titleInput = row.querySelector('input[name^="title"]');
            const fileInput = row.querySelector('input.preloss-file-multi');
            const cameraInput = row.querySelector('input.preloss-camera');
            if (titleInput) titleInput.setAttribute('name', 'title[' + idx + ']');
            if (fileInput) fileInput.setAttribute('name', 'file[' + idx + '][]');
            if (cameraInput) cameraInput.setAttribute('name', 'camera[' + idx + ']');
        });
    }

    document.getElementById('prelossAddRow').addEventListener('click', function() {
        const firstRow = container.querySelector('.preloss-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelectorAll('input[type="text"], input[type="file"]').forEach(function(el) { el.value = ''; });
        clone.querySelector('.preloss-remove-row').style.visibility = 'visible';
        container.appendChild(clone);
        reindexPrelossRows();
    });

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('preloss-remove-row')) {
            const rows = container.querySelectorAll('.preloss-row');
            if (rows.length > 1) {
                e.target.closest('.preloss-row').remove();
                reindexPrelossRows();
            }
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        status.textContent = '';
        uploadBtn.disabled = true;

        const fd = new FormData(this);
        try {
            const res = await fetch('../actions/preloss_upload_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                status.textContent = data.message;
                status.className = 'ms-2 small text-success';
                var rows = container.querySelectorAll('.preloss-row');
                for (var i = rows.length - 1; i > 0; i--) rows[i].remove();
                reindexPrelossRows();
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

    document.querySelectorAll('.preloss-delete').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            if (!confirm('<?= htmlspecialchars($L['preloss_delete_confirm'], ENT_QUOTES, 'UTF-8') ?>')) return;

            const fd = new FormData();
            fd.append('prelossID', id);
            try {
                const res = await fetch('../actions/preloss_delete_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const row = document.querySelector('.preloss-item[data-preloss-id="' + id + '"]');
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
