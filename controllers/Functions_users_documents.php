<?php
/**
 * LEGACY FILE - For backward compatibility only
 * 
 * New code should use the MVC architecture:
 *   - App\Models\User (in Models/ folder)
 *   - App\Models\Document (in Models/ folder)
 *   - App\Controllers\* (in controllers/ folder)
 * 
 * Example:
 *   require_once __DIR__ . '/../config/bootstrap.php';
 *   use App\Controllers\DocumentController;
 *   $docController = new DocumentController(getDB());
 *   $docs = $docController->getAll();
 */

include __DIR__ . "/../config/config.php";  // Include the database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ------------------ USER FUNCTIONS ------------------

// Get all users (normalize keys so userRole is always present regardless of DB driver casing)
function getAllUsers() {
    global $conn;
    $sql = "SELECT userID, userName, userEmail, userRole FROM User ORDER BY userName";
    $result = $conn->query($sql);
    $users = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Normalize: some MySQL/configs return lowercase column names
            $row['userID'] = $row['userID'] ?? $row['userid'] ?? null;
            $row['userName'] = $row['userName'] ?? $row['username'] ?? '';
            $row['userEmail'] = $row['userEmail'] ?? $row['useremail'] ?? '';
            $row['userRole'] = $row['userRole'] ?? $row['userrole'] ?? '';
            $users[] = $row;
        }
    }
    return $users;
}

// Add a user
function addUser($userName, $userEmail, $userPassword, $userRole) {
    global $conn;
    $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO User (userName, userEmail, userPassword, userRole) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $userName, $userEmail, $hashedPassword, $userRole);
    $stmt->execute();
    $stmt->close();
}

// Delete a user
function deleteUser($userID) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM User WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->close();
}

// Update user role
function updateUserRole($userID, $newRole) {
    global $conn;
    $stmt = $conn->prepare("UPDATE User SET userRole = ? WHERE userID = ?");
    $stmt->bind_param("si", $newRole, $userID);
    $stmt->execute();
    $stmt->close();
}

// ------------------ DOCUMENT FUNCTIONS ------------------

// Get all documents with user and maintenance info
function getAllDocuments() {
    global $conn;
    $sql = "SELECT 
                r.documentID, 
                r.userID,
                r.documentIssuerID,
                u.userName AS userName, 
                s.userName AS issuerName, 
                r.documentTypeID, 
                m.typeName AS documentType, 
                r.statusID, 
                st.statusName AS statusName, 
                r.description, 
                r.location, 
                r.imagePath,
                r.submissionDate, 
                r.completionDate
            FROM Document r
            LEFT JOIN User u ON r.userID = u.userID
            LEFT JOIN User s ON r.documentIssuerID = s.userID
            LEFT JOIN DocumentType m ON r.documentTypeID = m.documentTypeID
            LEFT JOIN Status st ON r.statusID = st.statusID
            ORDER BY r.submissionDate DESC";
    $result = $conn->query($sql);
    $documents = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
    }
    return $documents;
}

// Add a document with optional image
function addDocument($userID, $documentIssuerID, $documentTypeID, $statusID, $description, $location, $file = null) {
    global $conn;

    $documentID = uniqid('document_');
    $imagePath = null;
    $imageData = null;
    $imageMime = null;

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
    if ($file && isset($file['error']) && $file['error'] == 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExtensions)) {
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = uniqid('img_') . "_" . basename($file['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/images/' . $fileName;
                $bin = @file_get_contents($targetFile);
                if ($bin !== false) {
                    $imageData = $bin;
                    $imageMime = $allowedMimes[$ext] ?? 'application/octet-stream';
                }
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO Document (documentID, userID, documentIssuerID, documentTypeID, statusID, description, location, imagePath, imageData, imageMime, submissionDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("siiiisssss", $documentID, $userID, $documentIssuerID, $documentTypeID, $statusID, $description, $location, $imagePath, $imageData, $imageMime);
    $stmt->execute();
    $stmt->close();

    return $documentID;
}

// Delete a document
function deleteDocument($documentID) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM Document WHERE documentID = ?");
    $stmt->bind_param("s", $documentID);
    $stmt->execute();
    $stmt->close();
}

// Update document status with optional image
function updateDocumentStatus($documentID, $newStatusID, $file = null) {
    global $conn;
    $imagePath = null;
    $imageData = null;
    $imageMime = null;

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
    if ($file && isset($file['error']) && $file['error'] == 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExtensions)) {
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = uniqid('img_') . "_" . basename($file['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/images/' . $fileName;
                $bin = @file_get_contents($targetFile);
                if ($bin !== false) {
                    $imageData = $bin;
                    $imageMime = $allowedMimes[$ext] ?? 'application/octet-stream';
                }
            }
        }
    }

    if ($imagePath) {
        $stmt = $conn->prepare("UPDATE Document SET statusID = ?, imagePath = ?, imageData = ?, imageMime = ? WHERE documentID = ?");
        $stmt->bind_param("issss", $newStatusID, $imagePath, $imageData, $imageMime, $documentID);
    } else {
        $stmt = $conn->prepare("UPDATE Document SET statusID = ? WHERE documentID = ?");
        $stmt->bind_param("is", $newStatusID, $documentID);
    }
    $stmt->execute();
    $stmt->close();

    // Send email if completed
    if ($newStatusID == 3) { // Completed
        $stmt = $conn->prepare("SELECT u.userEmail, u.userName FROM Document r JOIN User u ON r.userID = u.userID WHERE r.documentID = ?");
        $stmt->bind_param("s", $documentID);
        $stmt->execute();
        $stmt->bind_result($userEmail, $userName);
        $stmt->fetch();
        $stmt->close();

        if (!empty($userEmail)) sendCompletionEmail($userEmail, $userName, $documentID);
    }
}

// Get status name by ID
function getStatusById($statusID) {
    global $conn;
    $stmt = $conn->prepare("SELECT statusName FROM Status WHERE statusID = ?");
    $stmt->bind_param("i", $statusID);
    $stmt->execute();
    $stmt->bind_result($statusName);
    $status = null;
    if ($stmt->fetch()) $status = $statusName;
    $stmt->close();
    return $status;
}

// Send email when document completed
function sendCompletionEmail($userEmail, $userName, $documentID) {
    $subject = "Maintenance Document Completed";
    $message = "
    <html>
    <head><title>Maintenance Document Completed</title></head>
    <body>
        <p>Dear {$userName},</p>
        <p>Your maintenance document (ID: {$documentID}) has been marked as <strong>Completed</strong>.</p>
        <p>Thank you for using our service!</p>
    </body>
    </html>
    ";
    mail($userEmail, $subject, $message, "Content-type:text/html;charset=UTF-8");
}

// ------------------ DOCUMENT STATISTICS ------------------
function getTotalDocuments() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Document");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
function getCompletedDocuments() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Document WHERE statusID = 3");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
function getPendingDocuments() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Document WHERE statusID = 1");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
function getInProgressDocuments() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Document WHERE statusID = 2");
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getCancelledDocuments() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Document WHERE statusID = 4");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
?>
