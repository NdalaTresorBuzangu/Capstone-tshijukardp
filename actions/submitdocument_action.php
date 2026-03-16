<?php
include '../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Data protection: require consent to process document request (GDPR / Ghana)
$acceptConsent = isset($_POST['accept_terms_doc']) && $_POST['accept_terms_doc'] === '1';
if (!$acceptConsent) {
    echo json_encode(['success' => false, 'message' => 'You must accept the Terms of Service and Privacy Policy to submit a document request.']);
    exit;
}

$userName = htmlspecialchars(trim($_POST['userName'] ?? ''));
$userEmail = filter_var(trim($_POST['userEmail'] ?? ''), FILTER_SANITIZE_EMAIL);
$documentIssuerID = (int) ($_POST['documentIssuerID'] ?? $_POST['institutionID'] ?? $_POST['schoolID'] ?? 0);
if ($documentIssuerID < 1 && isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Document Issuer') {
    $documentIssuerID = (int) $_SESSION['user_id'];
}
$paymentReference = isset($_POST['paymentReference']) ? htmlspecialchars(trim($_POST['paymentReference'])) : null;

// Support both single (legacy) and multiple documents; indexed names image[i][]
$documentTypes = $_POST['documentType'] ?? [];
$locations = $_POST['location'] ?? [];
$descriptions = $_POST['description'] ?? [];
if (!is_array($documentTypes)) $documentTypes = [$documentTypes];
if (!is_array($locations)) $locations = [$locations];
if (!is_array($descriptions)) $descriptions = [$descriptions];
// If associative (e.g. 0,1,2), sort by key so order is preserved
if (array_keys($documentTypes) !== range(0, count($documentTypes) - 1)) {
    ksort($documentTypes, SORT_NUMERIC);
    ksort($locations, SORT_NUMERIC);
    ksort($descriptions, SORT_NUMERIC);
}

$count = max(count($documentTypes), count($locations), count($descriptions));
if ($count < 1) {
    echo json_encode(['success' => false, 'message' => 'Add at least one document.']);
    exit;
}

if (empty($userName) || empty($userEmail) || $documentIssuerID < 1) {
    echo json_encode(['success' => false, 'message' => 'Name, email and institution are required.']);
    exit;
}
if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// Resolve user ID (session or lookup/create)
$userID = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $userID = (int) $_SESSION['user_id'];
} else {
    $stmt = $conn->prepare('SELECT userID FROM User WHERE userEmail = ?');
    if ($stmt) {
        $stmt->bind_param('s', $userEmail);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userID);
            $stmt->fetch();
        }
        $stmt->close();
    }
    if (!$userID) {
        $defaultHashed = password_hash('default123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO User (userName, userEmail, userRole, userPassword) VALUES (?, ?, "Document Seeker", ?)');
        if ($stmt) {
            $stmt->bind_param('sss', $userName, $userEmail, $defaultHashed);
            $stmt->execute();
            $userID = (int) $conn->insert_id;
            $stmt->close();
        }
    }
}
if (!$userID) {
    echo json_encode(['success' => false, 'message' => 'Could not resolve or create user.']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Allowed file types: PDF and images (PNG, JPG, JPEG, GIF, WebP)
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedMimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];

$conn->begin_transaction();
$documentIDs = [];
$statusID = 1; // Pending

try {
    $docIndex = 0;
    for ($i = 0; $i < $count; $i++) {
        $documentTypeID = (int) ($documentTypes[$i] ?? 0);
        $location = htmlspecialchars(trim($locations[$i] ?? ''));
        $description = htmlspecialchars(trim($descriptions[$i] ?? ''));
        if ($documentTypeID < 1 || $location === '' || $description === '') {
            continue;
        }

        // Multiple files per row: image[i][] gives $_FILES['image']['name'][$i] = array of names
        $names = $_FILES['image']['name'][$i] ?? null;
        $tmpNames = $_FILES['image']['tmp_name'][$i] ?? null;
        if (!is_array($names)) {
            $names = $names ? [$names] : [];
            $tmpNames = $tmpNames ? [$tmpNames] : [];
        }
        // If no files in this row, create one document with no image
        if (empty($names)) {
            $names = [null];
            $tmpNames = [null];
        }

        foreach ($names as $j => $name) {
            $docIndex++;
            $documentID = 'DOC-' . strtoupper(substr(uniqid(), -8)) . '-' . date('Ymd') . '-' . $docIndex;

            $imagePath = null;
            $imageData = null;
            $imageMime = null;
            $tmp = $tmpNames[$j] ?? null;
            if ($name && $tmp && is_uploaded_file($tmp)) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExtensions)) {
                    throw new Exception('File type not supported: "' . $ext . '". Allowed: PDF, JPG, JPEG, PNG, GIF, WebP.');
                }
                $fileName = time() . '_' . $i . '_' . $j . '_' . basename($name);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($tmp, $targetFile)) {
                    $imagePath = 'uploads/images/' . $fileName;
                    $bin = @file_get_contents($targetFile);
                    if ($bin !== false) {
                        $imageData = $bin;
                        $imageMime = $allowedMimes[$ext] ?? 'application/octet-stream';
                    }
                }
            }
            // Legacy: single file per row (image[] without [i][])
            if ($imagePath === null && $j === 0 && empty($names[0]) && !empty($_FILES['image']['name'][$i]) && !is_array($_FILES['image']['name'][$i])) {
                $tmp = $_FILES['image']['tmp_name'][$i] ?? null;
                if ($tmp && is_uploaded_file($tmp)) {
                    $ext = strtolower(pathinfo($_FILES['image']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExtensions)) {
                        throw new Exception('File type not supported: "' . $ext . '". Allowed: PDF, JPG, JPEG, PNG, GIF, WebP.');
                    }
                    $fileName = time() . '_' . $i . '_' . basename($_FILES['image']['name'][$i]);
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($tmp, $targetFile)) {
                        $imagePath = 'uploads/images/' . $fileName;
                        $bin = @file_get_contents($targetFile);
                        if ($bin !== false) {
                            $imageData = $bin;
                            $imageMime = $allowedMimes[$ext] ?? 'application/octet-stream';
                        }
                    }
                }
            }
            if ($imagePath === null && $count === 1 && $docIndex === 1 && !empty($_FILES['image']['name']) && !is_array($_FILES['image']['name'])) {
                $tmp = $_FILES['image']['tmp_name'] ?? null;
                if ($tmp && is_uploaded_file($tmp)) {
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExtensions)) {
                        throw new Exception('File type not supported: "' . $ext . '". Allowed: PDF, JPG, JPEG, PNG, GIF, WebP.');
                    }
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($tmp, $targetFile)) {
                        $imagePath = 'uploads/images/' . $fileName;
                        $bin = @file_get_contents($targetFile);
                        if ($bin !== false) {
                            $imageData = $bin;
                            $imageMime = $allowedMimes[$ext] ?? 'application/octet-stream';
                        }
                    }
                }
            }

            $stmt = $conn->prepare('
                INSERT INTO Document 
                (documentID, userID, documentIssuerID, documentTypeID, statusID, description, location, imagePath, imageData, imageMime, submissionDate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $stmt->bind_param('siiiisssss', $documentID, $userID, $documentIssuerID, $documentTypeID, $statusID, $description, $location, $imagePath, $imageData, $imageMime);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Error submitting document: ' . $conn->error);
            }
            $stmt->close();
            $documentIDs[] = $documentID;
        }
    }

    if (empty($documentIDs)) {
        throw new Exception('No valid document rows. Fill type, location and description for each.');
    }

    if ($paymentReference && count($documentIDs) > 0) {
        $firstID = $documentIDs[0];
        $updateStmt = $conn->prepare('UPDATE PaystackPayments SET document_id = ? WHERE reference = ?');
        if ($updateStmt) {
            $updateStmt->bind_param('ss', $firstID, $paymentReference);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'documentID' => $documentIDs[0],
        'documentIDs' => $documentIDs,
        'message' => count($documentIDs) > 1
            ? count($documentIDs) . ' documents submitted successfully.'
            : 'Document submitted successfully. Your Document ID: ' . $documentIDs[0]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
