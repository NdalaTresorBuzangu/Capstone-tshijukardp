<?php
/**
 * View document file (image/PDF). Used by both document seekers and issuing institutions.
 * Serves the file from disk so the same path resolution works for both; no direct URL redirect.
 * Output buffering ensures no stray output (whitespace, BOM, notices) corrupts the binary download.
 */
ob_start();
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
isLogin();

$documentID = isset($_GET['documentID']) ? trim($_GET['documentID']) : '';
if ($documentID === '') {
    header('HTTP/1.0 400 Bad Request');
    exit('Missing document ID.');
}

$userID = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$userRole = $_SESSION['user_role'] ?? '';

// Prefer SELECT including imageData/imageMime; fallback to without if columns don't exist yet
$row = null;
$stmt = $conn->prepare('SELECT imagePath, imageData, imageMime, userID, documentIssuerID FROM Document WHERE documentID = ?');
if ($stmt) {
    $stmt->bind_param('s', $documentID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
}
if (!$row) {
    $stmt = $conn->prepare('SELECT imagePath, userID, documentIssuerID FROM Document WHERE documentID = ?');
    if ($stmt) {
        $stmt->bind_param('s', $documentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}
if (!$row) {
    header('HTTP/1.0 404 Not Found');
    exit('Document not found.');
}
// Normalize: older DB may not have imageData/imageMime keys
$row['imageData'] = $row['imageData'] ?? null;
$row['imageMime'] = $row['imageMime'] ?? null;
// Must have either image stored in DB or path to file
$hasDbImage = !empty($row['imageData']);
$hasPath = !empty($row['imagePath']);
if (!$hasDbImage && !$hasPath) {
    header('HTTP/1.0 404 Not Found');
    exit('Document or file not found.');
}

// Access: issuer can view their documents; seeker can view their own; admin can view any
$allowed = false;
if ($userRole === 'Document Issuer' && (int) $row['documentIssuerID'] === $userID) {
    $allowed = true;
}
if ($userRole === 'Document Seeker' && (int) $row['userID'] === $userID) {
    $allowed = true;
}
if ($userRole === 'Admin') {
    $allowed = true;
}
if (!$allowed) {
    header('HTTP/1.0 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    exit('Access denied.');
}

// Optional: force download instead of inline
$forceDownload = !empty($_GET['download']);

// 1) If image is stored in database, serve it directly (no file needed)
if ($hasDbImage) {
    ob_end_clean();
    $mime = !empty($row['imageMime']) ? trim($row['imageMime']) : 'application/octet-stream';
    $data = $row['imageData'];
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($data));
    header('Content-Transfer-Encoding: binary');
    $ext = pathinfo($row['imagePath'], PATHINFO_EXTENSION);
    $ext = $ext ?: 'jpg';
    header('Content-Disposition: ' . ($forceDownload ? 'attachment' : 'inline') . '; filename="' . $documentID . '.' . $ext . '"');
    echo $data;
    exit;
}

// 2) Fallback: serve from file on disk
$path = str_replace(['../', '..\\'], '', trim($row['imagePath']));
$path = str_replace('\\', '/', $path);
$path = ltrim($path, '/\\');
if ($path === '' || strpos($path, '..') !== false) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid path.');
}
$pathDs = str_replace('/', DIRECTORY_SEPARATOR, $path);
$filename = basename($path);

// Project root: prefer resolved path (handles symlinks, "..", spaces)
$projectRoot = dirname(__DIR__);
$projectRootResolved = @realpath($projectRoot);
if ($projectRootResolved !== false) {
    $projectRoot = $projectRootResolved;
}
$uploadDirRoot = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

// Build list of candidate file paths (order matters)
$candidates = [];
if (strpos($path, 'uploads/') === 0) {
    $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . $pathDs;
}
$candidates[] = $uploadDirRoot . $filename;
$candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $filename;
if (strpos($path, 'uploads/') !== 0) {
    $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $pathDs;
}
$candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
$candidates[] = $projectRoot . DIRECTORY_SEPARATOR . $pathDs;
// When script is under views/, same dir as controllers/ that does uploads
$candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $filename;
$candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . $pathDs;
// DOCUMENT_ROOT fallback (e.g. app at document root or subdir)
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);
    $candidates[] = $docRoot . DIRECTORY_SEPARATOR . $pathDs;
    $candidates[] = $docRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $filename;
}

$fullPath = null;
$baseReal = @realpath($uploadDirRoot);
if ($baseReal === false) {
    $baseReal = @realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads');
}
$projectRootReal = @realpath($projectRoot);
if ($projectRootReal === false) {
    $projectRootReal = $projectRoot;
}
$docRootReal = !empty($_SERVER['DOCUMENT_ROOT']) ? @realpath(str_replace('/', DIRECTORY_SEPARATOR, rtrim($_SERVER['DOCUMENT_ROOT'], '/'))) : false;

foreach ($candidates as $candidate) {
    if ($candidate === '' || !is_file($candidate) || !is_readable($candidate)) {
        continue;
    }
    $resolved = realpath($candidate);
    if ($resolved === false) {
        continue;
    }
    // Ensure file is under a known safe base (prevent path traversal)
    $safe = ($baseReal !== false && strpos($resolved, $baseReal) === 0)
        || ($projectRootReal !== false && strpos($resolved, $projectRootReal) === 0)
        || strpos($resolved, $projectRoot) === 0
        || ($docRootReal !== false && strpos($resolved, $docRootReal) === 0);
    if ($safe) {
        $fullPath = $resolved;
        break;
    }
}

if ($fullPath === null) {
    header('HTTP/1.0 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    exit('File not found on server. The document record exists but the image file is missing. Ensure uploads/images/ exists and contains the file.');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'
];
$mime = $mimes[$ext] ?? 'application/octet-stream';
$disposition = (!empty($_GET['download'])) ? 'attachment' : 'inline';

ob_end_clean();
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Content-Transfer-Encoding: binary');
header('Content-Disposition: ' . $disposition . '; filename="' . basename($fullPath) . '"');
readfile($fullPath);
exit;
