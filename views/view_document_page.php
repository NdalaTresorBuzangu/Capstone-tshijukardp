<?php
/**
 * View document page: shows the actual document with Download and Delete options.
 */
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
include __DIR__ . '/../controllers/Functions_users_documents.php';
isLogin();

$documentID = isset($_GET['documentID']) ? trim($_GET['documentID']) : '';
if ($documentID === '') {
    header('Location: index.php?controller=' . (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin' ? 'Admin&action=dashboard' : 'Seeker&action=pack'));
    exit;
}

$userID = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? '';

$stmt = $conn->prepare('SELECT documentID, imagePath, imageMime, imageData, userID, documentIssuerID FROM Document WHERE documentID = ?');
if (!$stmt) {
    $stmt = $conn->prepare('SELECT documentID, imagePath, userID, documentIssuerID FROM Document WHERE documentID = ?');
    if ($stmt) {
        $stmt->bind_param('s', $documentID);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $doc['imageMime'] = null;
        $doc['imageData'] = null;
    } else {
        $doc = null;
    }
} else {
    $stmt->bind_param('s', $documentID);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if ($doc) {
    $doc['imageData'] = $doc['imageData'] ?? null;
    $doc['imageMime'] = $doc['imageMime'] ?? null;
}
if (!$doc) {
    $_SESSION['message'] = 'Document not found.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php?controller=' . ($userRole === 'Admin' ? 'Admin&action=dashboard' : 'Seeker&action=pack'));
    exit;
}

// Build document content for display: prefer DB blob, else read from file
$docContent = null;
$docMime = !empty($doc['imageMime']) ? trim($doc['imageMime']) : '';
if (!empty($doc['imageData'])) {
    $docContent = $doc['imageData'];
    if ($docMime === '') {
        $docMime = 'application/octet-stream';
    }
}
if ($docContent === null && !empty($doc['imagePath'])) {
    $path = str_replace(['../', '..\\'], '', trim($doc['imagePath']));
    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/\\');
    if ($path !== '' && strpos($path, '..') === false) {
        $pathDs = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $projectRoot = dirname(__DIR__);
        $projectRootResolved = @realpath($projectRoot);
        if ($projectRootResolved !== false) {
            $projectRoot = $projectRootResolved;
        }
        $uploadDirRoot = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        $candidates = [];
        if (strpos($path, 'uploads/') === 0) {
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . $pathDs;
        }
        $candidates[] = $uploadDirRoot . basename($path);
        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . basename($path);
        if (strpos($path, 'uploads/') !== 0) {
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $pathDs;
        }
        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename($path);
        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . $pathDs;
        $candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . basename($path);
        $candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . $pathDs;
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $docRoot = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);
            $candidates[] = $docRoot . DIRECTORY_SEPARATOR . $pathDs;
            $candidates[] = $docRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . basename($path);
        }
        $baseReal = @realpath($uploadDirRoot) ?: @realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads');
        $projectRootReal = @realpath($projectRoot);
        if ($projectRootReal === false) {
            $projectRootReal = $projectRoot;
        }
        $docRootReal = !empty($_SERVER['DOCUMENT_ROOT']) ? @realpath(str_replace('/', DIRECTORY_SEPARATOR, rtrim($_SERVER['DOCUMENT_ROOT'], '/'))) : false;
        foreach ($candidates as $candidate) {
            if ($candidate === '' || !is_file($candidate) || !is_readable($candidate)) continue;
            $resolved = realpath($candidate);
            if ($resolved === false) continue;
            $safe = ($baseReal !== false && strpos($resolved, $baseReal) === 0)
                || ($projectRootReal !== false && strpos($resolved, $projectRootReal) === 0)
                || strpos($resolved, $projectRoot) === 0
                || ($docRootReal !== false && strpos($resolved, $docRootReal) === 0);
            if (!$safe) continue;
            $docContent = @file_get_contents($candidate);
            if ($docMime === '' || $docMime === 'application/octet-stream') {
                $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
                $docMime = $mimes[$ext] ?? 'application/octet-stream';
            }
            break;
        }
    }
}

$canView = false;
if ($userRole === 'Document Issuer' && (int) $doc['documentIssuerID'] === $userID) $canView = true;
if ($userRole === 'Document Seeker' && (int) $doc['userID'] === $userID) $canView = true;
if ($userRole === 'Admin') $canView = true;

if (!$canView) {
    $_SESSION['message'] = 'Access denied.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php?controller=' . ($userRole === 'Admin' ? 'Admin&action=dashboard' : 'Seeker&action=pack'));
    exit;
}

$canDelete = false;
if ($userRole === 'Admin') $canDelete = true;
if ($userRole === 'Document Seeker' && (int) $doc['userID'] === $userID) $canDelete = true;
if ($userRole === 'Document Issuer' && (int) $doc['documentIssuerID'] === $userID) $canDelete = true;

$mime = !empty($doc['imageMime']) ? trim($doc['imageMime']) : '';
$isPdf = (strpos($mime, 'pdf') !== false) || (strtolower(pathinfo($doc['imagePath'] ?? '', PATHINFO_EXTENSION)) === 'pdf');
if ($docContent !== null && $docMime !== '') {
    $isPdf = (strpos($docMime, 'pdf') !== false);
}
$viewUrl = 'view_document.php?documentID=' . urlencode($documentID);
$downloadUrl = 'view_document.php?documentID=' . urlencode($documentID) . '&download=1';
$dataUri = null;
if ($docContent !== null && $docMime !== '') {
    $dataUri = 'data:' . $docMime . ';base64,' . base64_encode($docContent);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document <?= htmlspecialchars($documentID) ?> - Tshijuka RDP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/nav.css">
    <style>
        .viewer-toolbar { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 0.75rem 1rem; }
        .viewer-content { padding: 1rem; min-height: 70vh; display: flex; align-items: center; justify-content: center; background: #2b2b2b; }
        .viewer-content img { max-width: 100%; max-height: 85vh; object-fit: contain; }
        .viewer-content embed, .viewer-content iframe { width: 100%; height: 85vh; border: none; }
    </style>
</head>
<body>
<?php $showLogout = true; include 'nav.php'; ?>

<div class="viewer-toolbar d-flex flex-wrap align-items-center gap-3">
    <strong class="text-secondary">Document: <?= htmlspecialchars($documentID) ?></strong>
    <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-primary btn-sm" download>
        <i class="fas fa-download"></i> Download
    </a>
    <?php if ($canDelete): ?>
        <form method="post" action="<?= (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/views/') !== false ? '../' : '') ?>index.php" class="d-inline" onsubmit="return confirm('Delete this document? This cannot be undone.');">
            <input type="hidden" name="controller" value="Document">
            <input type="hidden" name="action" value="delete_submit">
            <input type="hidden" name="documentID" value="<?= htmlspecialchars($documentID) ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i> Delete</button>
        </form>
    <?php endif; ?>
    <?php $backBase = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/views/') !== false ? '../' : ''); ?>
    <a href="<?= $backBase ?>index.php?controller=<?= $userRole === 'Admin' ? 'Admin&action=dashboard' : ($userRole === 'Document Issuer' ? 'Institution&action=panel' : 'Seeker&action=pack') ?>" class="btn btn-outline-secondary btn-sm ms-auto">← Back</a>
</div>

<div class="viewer-content">
    <?php if ($dataUri !== null): ?>
        <?php if ($isPdf): ?>
            <iframe src="<?= htmlspecialchars($dataUri) ?>#toolbar=1" title="Document PDF" class="w-100"></iframe>
        <?php else: ?>
            <img src="<?= htmlspecialchars($dataUri) ?>" alt="Document">
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center text-white py-5">
            <p class="mb-2">Document file could not be loaded.</p>
            <p class="small text-muted">The record exists but the file is missing or not accessible. You can still <a href="<?= htmlspecialchars($downloadUrl) ?>" class="text-white">try to download</a> or go back.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://kit.fontawesome.com/cb76afc7c2.js" crossorigin="anonymous"></script>
</body>
</html>
