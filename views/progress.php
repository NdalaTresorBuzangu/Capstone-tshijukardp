<?php
// progress.php
include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
if (!isset($L) || !is_array($L)) require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
$L = $L ?? [];

$statusMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['documentId'])) {
        $document_id = $_POST['documentId'];

        // Query to fetch the document details including the image
        $stmt = $conn->prepare("SELECT r.documentID, r.description, r.imagePath, s.statusName 
                                FROM Document r 
                                JOIN Status s ON r.statusID = s.statusID 
                                WHERE r.documentID = ?");
        $stmt->bind_param("s", $document_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $document = $result->fetch_assoc();
            $statusMessage = "Document ID: " . htmlspecialchars($document['documentID']) . "<br>" .
                             "Description: " . htmlspecialchars($document['description']) . "<br>" .
                             "Status: " . htmlspecialchars($document['statusName']) . "<br>";

            // Use same viewer as issuing institution (view_document.php streams file from correct path)
            if (!empty($document['imagePath'])) {
                $viewUrl = 'view_document.php?documentID=' . urlencode($document['documentID']);
                $viewPageUrl = '/index.php?controller=Document&action=view_page&documentID=' . urlencode($document['documentID']);
                $statusMessage .= '<div class="document-image-wrap">';
                $statusMessage .= "<a href='" . htmlspecialchars($viewPageUrl) . "' target='_blank' rel='noopener'>View document (open / download / delete)</a>";
                $statusMessage .= "<img src='" . htmlspecialchars($viewUrl) . "' alt='Document' style='max-width: 800px; width: 100%; height: auto; max-height: 600px; display: block; margin-top: 8px;'>";
                $statusMessage .= '</div>';
            } else {
                $statusMessage .= "<p>No image submitted for this document.</p>";
            }
        } else {
            $statusMessage = "Document not found. Please check the Document ID.";
        }
        $stmt->close();
    } else {
        $statusMessage = "Please enter a valid Document ID.";
    }
}
// Don't close connection here - it's a global connection used by other files
// $conn->close();
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'fr' ? 'fr' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($L['track_progress'] ?? 'Track progress') ?> - Tshijuka RDP</title>
    <link rel="stylesheet" href="../assets/nav.css">
    <link rel="stylesheet" href="../assets/progress.css">
    <style>body{background:url('<?php echo htmlspecialchars($bgImage ?? '../assets/nature-7047433_1280.jpg'); ?>') no-repeat center center fixed;background-size:cover;}</style>
</head>
<body>
    <!-- Navigation Bar -->
    <?php $showLogout = true; include __DIR__ . DIRECTORY_SEPARATOR . 'nav.php'; ?>

    <!-- Main Content -->
    <main>
        <h2><?= htmlspecialchars($L['track_progress'] ?? 'Track progress') ?></h2>
        <p><?= htmlspecialchars($L['track_desc'] ?? 'Check the status of your document requests.') ?></p>

        <!-- Document ID Form -->
        <form method="POST" action="index.php?controller=Seeker&action=progress">
            <input type="text" name="documentId" placeholder="Document ID" required>
            <button type="submit"><?= htmlspecialchars($L['check_status'] ?? 'Check status') ?></button>
        </form>

        <!-- Display Status -->
        <div id="documentStatus" style="margin-top: 20px;">
            <?php echo $statusMessage; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; Tshijuka RDP</p>
    </footer>
</body>
</html>

