<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
$showLogout = true; include 'nav.php';
isLogin();
$issuerID = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - Document Issuing Institution | Tshijuka RDP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/submit-document.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/nav.css">
    <script src="https://kit.fontawesome.com/cb76afc7c2.js" crossorigin="anonymous"></script>
</head>
<body>
    <header class="container mt-3">
        <h1>Upload documents</h1>
        <p class="text-muted">Save multiple documents at once so you don't lose them. Add as many rows as you need, then submit.</p>
    </header>

    <main class="container mt-4">
        <form id="documentForm" enctype="multipart/form-data">
            <input type="hidden" name="documentIssuerID" value="<?= $issuerID ?>">
            <div class="mb-3">
                <label for="userName" class="form-label">Name (document owner or your name):</label>
                <input type="text" id="userName" name="userName" class="form-control" placeholder="Enter name" required>
            </div>
            <div class="mb-3">
                <label for="userEmail" class="form-label">Email (document owner or your email):</label>
                <input type="email" id="userEmail" name="userEmail" class="form-control" placeholder="Enter email" required>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Documents to save</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addDocumentRow">+ Add another document</button>
                </div>
                <p class="text-muted small mb-2">Add one or more documents. Each will get its own ID. Upload as many as you need in one go.</p>
                <div id="documentRows">
                    <div class="document-row card card-body mb-3 border">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label class="form-label small">Document Type</label>
                                <select name="documentType[0]" class="form-select form-select-sm" required>
                                    <option value="1">Identity</option>
                                    <option value="2">Educational</option>
                                    <option value="3">History</option>
                                    <option value="4">Contract</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small">Location</label>
                                <input type="text" name="location[0]" class="form-control form-control-sm" placeholder="Location" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Description</label>
                                <input type="text" name="description[0]" class="form-control form-control-sm" placeholder="Description" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label small">File(s) (optional)</label>
                                <input type="file" name="image[0][]" class="form-control form-control-sm file-multi" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" multiple>
                                <small class="text-muted">Shift+click to select many (like Google Drive).</small>
                            </div>
                            <div class="col-md-1 mb-2">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove this row" style="visibility: hidden;">×</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="fas fa-cloud-upload-alt"></i> Save all documents
                </button>
            </div>
        </form>

        <div id="successArea" class="mt-4" style="display: none;"></div>
    </main>

    <footer class="text-center mt-5">
        <p>&copy; Tshijuka RDP</p>
    </footer>

    <script>
    var BASE_URL = <?= json_encode($baseUrl ?? (function_exists('getBaseUrl') ? getBaseUrl() : '')) ?>;
    function reindexDocumentRows() {
        var rows = document.querySelectorAll('.document-row');
        rows.forEach(function(row, idx) {
            var fileInput = row.querySelector('input.file-multi');
            var sel = row.querySelector('select[name^="documentType"]');
            var loc = row.querySelector('input[name^="location"]');
            var desc = row.querySelector('input[name^="description"]');
            if (fileInput) fileInput.setAttribute('name', 'image[' + idx + '][]');
            if (sel) sel.setAttribute('name', 'documentType[' + idx + ']');
            if (loc) loc.setAttribute('name', 'location[' + idx + ']');
            if (desc) desc.setAttribute('name', 'description[' + idx + ']');
        });
    }
    document.getElementById('addDocumentRow').addEventListener('click', function() {
        var template = document.querySelector('.document-row').cloneNode(true);
        template.querySelectorAll('input[type="text"], input[type="file"], select').forEach(function(el) { el.value = ''; });
        template.querySelector('.remove-row').style.visibility = 'visible';
        document.getElementById('documentRows').appendChild(template);
        reindexDocumentRows();
    });
    document.getElementById('documentRows').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            var rows = document.querySelectorAll('.document-row');
            if (rows.length > 1) {
                e.target.closest('.document-row').remove();
                reindexDocumentRows();
            }
        }
    });

    document.getElementById('documentForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        var formData = new FormData(this);
        try {
            var response = await fetch(BASE_URL + 'actions/submitdocument_action.php', { method: 'POST', body: formData });
            var result = await response.json();
            if (result.success) {
                var ids = result.documentIDs && result.documentIDs.length ? result.documentIDs : (result.documentID || result.documentId ? [result.documentID || result.documentId] : []);
                var msg = ids.length > 1
                    ? ids.length + ' documents saved. IDs: ' + ids.join(', ')
                    : (ids[0] ? 'Document saved. ID: ' + ids[0] : 'Documents saved.');
                document.getElementById('successArea').style.display = 'block';
                document.getElementById('successArea').innerHTML = '<div class="alert alert-success"><strong>Success!</strong> ' + msg + '</div>';
                document.getElementById('documentForm').reset();
                document.querySelector('input[name="documentIssuerID"]').value = '<?= $issuerID ?>';
                var rows = document.querySelectorAll('.document-row');
                for (var i = rows.length - 1; i > 0; i--) rows[i].remove();
            } else {
                alert('Error: ' + (result.message || 'Submission failed'));
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred. Please try again.');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Save all documents';
    });
    </script>
</body>
</html>
