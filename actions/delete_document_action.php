<?php
/**
 * Delete a document. Allowed: Admin (any), Document Seeker (own), Document Issuer (docs for their institution).
 */
session_start();
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
include __DIR__ . '/../controllers/Functions_users_documents.php';
isLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['documentID'])) {
    $_SESSION['message'] = 'Invalid request.';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . ($_SESSION['user_role'] === 'Admin' ? '../views/admin_landing.php' : '../views/tshijuka_pack.php'));
    exit;
}

$documentID = trim($_POST['documentID']);
$userID = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? '';

$stmt = $conn->prepare('SELECT userID, documentIssuerID FROM Document WHERE documentID = ?');
$stmt->bind_param('s', $documentID);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    $_SESSION['message'] = 'Document not found.';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . ($userRole === 'Admin' ? '../views/admin_landing.php' : '../views/tshijuka_pack.php'));
    exit;
}

$canDelete = false;
if ($userRole === 'Admin') $canDelete = true;
if ($userRole === 'Document Seeker' && (int) $row['userID'] === $userID) $canDelete = true;
if ($userRole === 'Document Issuer' && (int) $row['documentIssuerID'] === $userID) $canDelete = true;

if (!$canDelete) {
    $_SESSION['message'] = 'You are not allowed to delete this document.';
    $_SESSION['message_type'] = 'error';
    header('Location: ../views/view_document_page.php?documentID=' . urlencode($documentID));
    exit;
}

deleteDocument($documentID);
$_SESSION['message'] = 'Document deleted.';
$_SESSION['message_type'] = 'success';

$back = $userRole === 'Admin' ? '../views/admin_landing.php' : ($userRole === 'Document Issuer' ? '../views/institutionpanel.php' : '../views/tshijuka_pack.php');
header('Location: ' . $back);
exit;
