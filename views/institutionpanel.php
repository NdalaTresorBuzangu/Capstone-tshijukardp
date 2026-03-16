<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
include __DIR__ . '/../controllers/Functions_users_documents.php';
isLogin();

// Check if logged-in user is a document issuing institution (Document Issuer role)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Issuer') {
    echo "Access denied. Only document issuing institutions can access this page.";
    exit;
}

$documentIssuerID = $_SESSION['user_id'];

// Fetch document issuing institution info
$stmt = $conn->prepare("SELECT * FROM Subscribe WHERE userID = ?");
$stmt->bind_param("i", $documentIssuerID);
$stmt->execute();
$issuer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Document issuing institution must complete Subscribe profile first
if (!$issuer) {
    header('Location: index.php?controller=Institution&action=subscribe');
    exit;
}

// Handle form submissions for updating documents
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['updateDocumentStatus'])) {
            $documentID = filter_input(INPUT_POST, 'updateDocumentStatus', FILTER_SANITIZE_STRING);
            $newStatusID = filter_input(INPUT_POST, 'newStatusID', FILTER_VALIDATE_INT);
            $file = isset($_FILES['documentFile']) ? $_FILES['documentFile'] : null;

            updateDocumentStatus($documentID, $newStatusID, $file);

            $_SESSION['message'] = "Document updated successfully.";
            $_SESSION['message_type'] = "success";
            header("Location: index.php?controller=Institution&action=panel");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: index.php?controller=Institution&action=panel");
        exit;
    }
}

// Fetch documents assigned to this institution (include payment_confirmed_at for cross-border flow)
$documents = null;
$stmt = @$conn->prepare("
    SELECT r.documentID, u.userID AS personID, u.userName AS personName, u.userEmail AS personEmail,
           dt.typeName AS documentType, r.description, r.location, r.submissionDate,
           s.statusName, r.statusID, r.imagePath, r.payment_confirmed_at
    FROM Document r
    JOIN User u ON r.userID = u.userID
    JOIN DocumentType dt ON r.documentTypeID = dt.documentTypeID
    JOIN Status s ON r.statusID = s.statusID
    WHERE r.documentIssuerID = ?
    ORDER BY (r.payment_confirmed_at IS NOT NULL) DESC, r.submissionDate DESC
");
if ($stmt) {
    $stmt->bind_param("i", $documentIssuerID);
    $stmt->execute();
    $documents = $stmt->get_result();
    $stmt->close();
}
if (!$documents) {
    $stmt = $conn->prepare("
        SELECT r.documentID, u.userID AS personID, u.userName AS personName, u.userEmail AS personEmail,
               dt.typeName AS documentType, r.description, r.location, r.submissionDate,
               s.statusName, r.statusID, r.imagePath
        FROM Document r
        JOIN User u ON r.userID = u.userID
        JOIN DocumentType dt ON r.documentTypeID = dt.documentTypeID
        JOIN Status s ON r.statusID = s.statusID
        WHERE r.documentIssuerID = ?
        ORDER BY r.submissionDate DESC
    ");
    $stmt->bind_param("i", $documentIssuerID);
    $stmt->execute();
    $documents = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institution Dashboard — <?= htmlspecialchars($issuer['documentIssuerName']) ?> | Tshijuka RDP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/nav.css">
    <style>
        :root { --issuer-primary: #0d6efd; --issuer-dark: #0a58ca; --issuer-bg: #f8fafc; }
        body { background: var(--issuer-bg); padding-top: 76px; min-height: 100vh; }
        .issuer-header {
            background: linear-gradient(135deg, var(--issuer-primary) 0%, var(--issuer-dark) 100%);
            color: #fff;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(13, 110, 253, 0.25);
        }
        .issuer-header h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.35rem; }
        .issuer-header .subtitle { opacity: 0.95; font-size: 1rem; }
        .card-issuer { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .card-issuer .card-header { background: #fff; border-bottom: 1px solid #eee; font-weight: 600; padding: 1rem 1.25rem; border-radius: 12px 12px 0 0; }
        .guidance-card { border-left: 4px solid var(--issuer-primary); }
        .guidance-card .list-unstyled li { padding: 0.35rem 0; padding-left: 1.25rem; position: relative; }
        .guidance-card .list-unstyled li::before { content: "✓"; position: absolute; left: 0; color: #198754; font-weight: bold; }
        .contact-box { background: #f0f7ff; border-radius: 10px; padding: 1rem 1.25rem; }
        .contact-box a { color: var(--issuer-dark); font-weight: 500; }
        .table-issuer { font-size: 0.9rem; }
        .table-issuer thead th { background: #f1f5f9; font-weight: 600; color: #334155; border-bottom: 2px solid #e2e8f0; }
        .table-issuer tbody tr:hover { background: #f8fafc; }
        .section-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 600; margin-bottom: 0.5rem; }
        /* Mobile: stack table rows as cards so actions are visible */
        @media (max-width: 767px) {
            .table-issuer thead { display: none; }
            .table-issuer tbody tr { display: block; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 1rem; padding: 0.75rem; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
            .table-issuer tbody td { display: block; padding: 0.4rem 0; border: none; }
            .table-issuer tbody td::before { content: attr(data-label); font-weight: 600; color: #64748b; font-size: 0.75rem; display: block; margin-bottom: 0.15rem; }
            .table-issuer tbody td.issuer-actions-cell { border-top: 1px solid #e2e8f0; margin-top: 0.5rem; padding-top: 0.75rem; }
            .table-issuer tbody td.issuer-actions-cell::before { margin-bottom: 0.5rem; }
            .table-issuer .issuer-actions-wrap { display: flex; flex-wrap: wrap; gap: 0.5rem; }
            .table-issuer .issuer-actions-wrap .form-control.form-control-sm { max-width: none; }
            .table-issuer .issuer-actions-wrap form { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        }
    </style>
</head>
<body>
<?php $showLogout = true; include 'nav.php'; ?>

<div class="container py-4">
    <!-- Header for issuing institutions -->
    <div class="issuer-header">
        <p class="section-label mb-1">Document Issuing Institution Dashboard</p>
        <h1>Welcome, <?= htmlspecialchars($issuer['documentIssuerName']) ?></h1>
        <p class="subtitle mb-0">Manage document requests from seekers and keep track of status updates.</p>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- ========== COMPONENT 1: Digitization campaign ========== -->
    <div class="card card-issuer mb-4">
        <div class="card-header">1. Digitization campaign</div>
        <div class="card-body">
            <p class="small text-muted mb-3">We help issuing institutions protect and digitize records so document requests can be served quickly and securely.</p>
            <p class="small fw-semibold mb-2">Benefits:</p>
            <ul class="list-unstyled small mb-3">
                <li>Secure cloud-based document storage</li>
                <li>Fast retrieval when seekers submit requests</li>
                <li>Backup and disaster recovery</li>
                <li>Data protection compliance</li>
                <li>Streamlined document management</li>
            </ul>
            <p class="small fw-semibold mb-2">We support you with:</p>
            <ul class="list-unstyled small mb-3">
                <li>Secure setup and migration</li>
                <li>Scanning and digitization</li>
                <li>Data security and encryption</li>
                <li>Staff training and ongoing support</li>
            </ul>
            <div class="contact-box">
                <p class="small fw-semibold mb-2">Contact us for digitalization services</p>
                <p class="mb-1 small"><strong>Email:</strong> <a href="mailto:digitalization@tshijuka.org">digitalization@tshijuka.org</a></p>
                <p class="mb-0 small"><strong>Phone:</strong> <a href="tel:+233591429017">+233 59 142 9017</a></p>
            </div>
        </div>
    </div>

    <!-- ========== COMPONENT 2: Requests from document seekers ========== -->
    <div class="card card-issuer mb-4">
        <div class="card-header">2. Requests from document seekers</div>
        <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-issuer table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Seeker</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($documents->num_rows > 0): ?>
                                    <?php while ($row = $documents->fetch_assoc()): ?>
                                        <tr>
                                            <td data-label="ID"><code class="small"><?= htmlspecialchars($row['documentID']) ?></code></td>
                                            <td data-label="Seeker">
                                                <strong><?= htmlspecialchars($row['personName']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($row['personEmail']) ?></small>
                                            </td>
                                            <td data-label="Type"><?= htmlspecialchars($row['documentType']) ?></td>
                                            <td data-label="Description"><span title="<?= htmlspecialchars($row['description']) ?>"><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 35, '…')) ?></span></td>
                                            <td data-label="Status">
                                                <?php
                                                $sid = (int)$row['statusID'];
                                                $cls = $sid === 3 ? 'success' : ($sid === 2 ? 'primary' : ($sid === 4 ? 'secondary' : 'warning'));
                                                ?><span class="badge bg-<?= $cls ?> badge-status"><?= htmlspecialchars($row['statusName']) ?></span>
                                                <?php if (!empty($row['payment_confirmed_at'])): ?>
                                                <br><span class="badge bg-success mt-1" title="Admin has confirmed that the fee was paid to your institution. You can now send the document.">Payment confirmed – ready to send document</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Submitted"><small><?= date('M j, Y', strtotime($row['submissionDate'])) ?></small></td>
                                            <td data-label="Actions" class="issuer-actions-cell">
                                                <div class="d-flex flex-wrap gap-1 issuer-actions-wrap">
                                                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                                                        <input type="file" name="documentFile" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" style="max-width:140px" title="Upload file">
                                                        <select name="newStatusID" class="form-select form-select-sm" style="min-width:110px">
                                                            <option value="1" <?= $row['statusID']==1?'selected':'' ?>>Pending</option>
                                                            <option value="2" <?= $row['statusID']==2?'selected':'' ?>>In Progress</option>
                                                            <option value="3" <?= $row['statusID']==3?'selected':'' ?>>Completed</option>
                                                            <option value="4" <?= $row['statusID']==4?'selected':'' ?>>Cancelled</option>
                                                        </select>
                                                        <button type="submit" name="updateDocumentStatus" value="<?= htmlspecialchars($row['documentID']) ?>" class="btn btn-sm btn-primary">Update</button>
                                                    </form>
                                                    <?php if (!empty($row['imagePath'])): ?>
                                                        <a href="<?= htmlspecialchars($baseUrl ?? ''); ?>index.php?controller=Document&action=view_page&documentID=<?= urlencode($row['documentID']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                                    <?php endif; ?>
                                                    <a href="index.php?controller=Chat&action=index&documentID=<?= urlencode($row['documentID']) ?>&personID=<?= urlencode($row['personID']) ?>" class="btn btn-sm btn-success">Chat</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">No document requests have been submitted to your institution yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
        </div>
        </div>
    </div>

    <!-- ========== COMPONENT 3: Manage documents (upload to prevent loss) ========== -->
    <div class="card card-issuer mb-4 border-primary">
        <div class="card-header">3. Manage documents</div>
        <div class="card-body">
            <p class="mb-3">Upload and store documents on the platform to prevent loss even when a request has not yet come from document seekers. Use this when you are in unstable environments—digitize and protect records in advance.</p>
            <a href="<?= htmlspecialchars($baseUrl ?? ''); ?>index.php?controller=Institution&action=upload_documents" class="btn btn-primary">Upload documents to prevent loss</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
