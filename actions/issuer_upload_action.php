<?php
/**
 * Issuer document upload – Document Issuing Institution uploads documents to the platform
 * without waiting for a request from seekers (e.g. pre-digitize in war zones).
 * Same pattern as preloss: title[i], documentType[i], description[i], file[i][], camera[i].
 */
session_start();
include __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Issuer') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method.']);
    exit;
}

$titles = $_POST['title'] ?? [];
$documentTypes = $_POST['documentType'] ?? [];
$descriptions = $_POST['description'] ?? [];
if (!is_array($titles)) $titles = [$titles];
if (!is_array($documentTypes)) $documentTypes = [$documentTypes];
if (!is_array($descriptions)) $descriptions = [$descriptions];

$indices = array_keys($titles);
sort($indices, SORT_NUMERIC);

$allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxSize = 10 * 1024 * 1024; // 10 MB
$uploadDir = __DIR__ . '/../uploads/images/issuer/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$userID = (int) $_SESSION['user_id'];
$saved = 0;
$errors = [];

foreach ($indices as $i) {
    $title = trim($titles[$i] ?? '');
    if ($title === '') continue;

    $documentTypeID = isset($documentTypes[$i]) ? (int) $documentTypes[$i] : null;
    if ($documentTypeID < 1) $documentTypeID = null;
    $description = isset($descriptions[$i]) ? trim($descriptions[$i]) : null;
    if ($description !== null && $description === '') $description = null;

    $filesToSave = [];
    $multiNames = $_FILES['file']['name'][$i] ?? null;
    $multiTmp = $_FILES['file']['tmp_name'][$i] ?? null;
    if (is_array($multiNames)) {
        foreach ($multiNames as $j => $name) {
            if ($name === '' || $name === null) continue;
            $tmp = $multiTmp[$j] ?? null;
            $err = $_FILES['file']['error'][$i][$j] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK && $tmp && is_uploaded_file($tmp)) {
                $filesToSave[] = ['name' => $name, 'tmp_name' => $tmp, 'size' => $_FILES['file']['size'][$i][$j] ?? 0];
            }
        }
    } elseif ($multiNames && $multiTmp && (($_FILES['file']['error'][$i] ?? 0) === UPLOAD_ERR_OK) && is_uploaded_file($multiTmp)) {
        $filesToSave[] = ['name' => $multiNames, 'tmp_name' => $multiTmp, 'size' => $_FILES['file']['size'][$i] ?? 0];
    }

    $camName = $_FILES['camera']['name'][$i] ?? null;
    $camTmp = $_FILES['camera']['tmp_name'][$i] ?? null;
    $camErr = $_FILES['camera']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
    if ($camName && $camErr === UPLOAD_ERR_OK && $camTmp && is_uploaded_file($camTmp)) {
        $filesToSave[] = ['name' => $camName, 'tmp_name' => $camTmp, 'size' => $_FILES['camera']['size'][$i] ?? 0];
    }

    foreach ($filesToSave as $file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = $file['name'] . ': only PDF and image files (JPG, PNG, GIF, WebP) allowed.';
            continue;
        }
        if ($file['size'] > $maxSize) {
            $errors[] = $file['name'] . ': file too large (max 10 MB).';
            continue;
        }

        $safeName = time() . '_' . $saved . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $targetPath = $uploadDir . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = $file['name'] . ': failed to save.';
            continue;
        }

        $filePath = 'uploads/images/issuer/' . $safeName;
        if ($documentTypeID !== null) {
            $stmt = $conn->prepare('INSERT INTO IssuerStoredDocuments (userID, title, documentTypeID, description, filePath) VALUES (?, ?, ?, ?, ?)');
            if ($stmt) $stmt->bind_param('isiss', $userID, $title, $documentTypeID, $description, $filePath);
        } else {
            $stmt = $conn->prepare('INSERT INTO IssuerStoredDocuments (userID, title, description, filePath) VALUES (?, ?, ?, ?)');
            if ($stmt) $stmt->bind_param('isss', $userID, $title, $description, $filePath);
        }
        if (!$stmt) {
            @unlink($targetPath);
            $errors[] = 'Database error.';
            continue;
        }
        if (!$stmt->execute()) {
            @unlink($targetPath);
            $stmt->close();
            $errors[] = 'Failed to save record.';
            continue;
        }
        $stmt->close();
        $saved++;
    }
}

if ($saved === 0) {
    $msg = !empty($errors) ? implode(' ', $errors) : 'Add at least one document with a title and at least one file (upload or take picture).';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$msg = $saved === 1 ? 'Document saved to platform.' : $saved . ' documents saved to platform.';
if (!empty($errors)) $msg .= ' ' . implode(' ', array_slice($errors, 0, 3));
echo json_encode(['success' => true, 'message' => $msg]);
