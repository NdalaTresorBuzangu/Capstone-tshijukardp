<?php
/**
 * Preloss document upload – Document Seeker saves copies of documents.
 * Supports multiple rows and multiple files per row (same pattern as request box):
 * title[i], file[i][], and optional camera[i] (take picture).
 */
session_start();
include __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Seeker') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method.']);
    exit;
}

$titles = $_POST['title'] ?? [];
if (!is_array($titles)) {
    $titles = [$titles];
}
$indices = array_keys($titles);
sort($indices, SORT_NUMERIC);

$allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxSize = 10 * 1024 * 1024; // 10 MB
$uploadDir = __DIR__ . '/../uploads/images/preloss/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$userID = (int) $_SESSION['user_id'];
$saved = 0;
$errors = [];

foreach ($indices as $i) {
    $title = trim($titles[$i] ?? '');
    if ($title === '') {
        continue;
    }

    // Collect files from file[i][] (multiple) and camera[i] (single)
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

        $filePath = 'uploads/images/preloss/' . $safeName;
        $stmt = $conn->prepare('INSERT INTO PrelossDocuments (userID, title, filePath) VALUES (?, ?, ?)');
        if (!$stmt) {
            @unlink($targetPath);
            $errors[] = 'Database error.';
            continue;
        }
        $stmt->bind_param('iss', $userID, $title, $filePath);
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

$msg = $saved === 1 ? 'Document saved successfully.' : $saved . ' documents saved successfully.';
if (!empty($errors)) {
    $msg .= ' ' . implode(' ', array_slice($errors, 0, 3));
}
echo json_encode(['success' => true, 'message' => $msg]);
