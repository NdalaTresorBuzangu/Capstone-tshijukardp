<?php
// Include the backend logic for fetching documents
include __DIR__ . '/../actions/document_action.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Documents - CampusFixIt</title>
    <link rel="stylesheet" href="../assets/nav.css"> <!-- Link the navigation bar styles -->
    <link rel="stylesheet" href="../assets/documents.css"> <!-- Link the page-specific styles -->
</head>
<body>
    <!-- Include the navigation bar -->
    <?php $showLogout = true; include 'nav.php'; ?>

    <header>
        <h1>Recent Documents</h1>
    </header>

    <main>
        <div class="document-grid">
            <?php
            // Fetch the recent documents from the database
            $documents = getDocuments($conn);
            if (count($documents) > 0):
                foreach ($documents as $document): ?>
                    <div class="document-card">
                        <span class="document-id">#FIX-<?php echo $document['document_id']; ?></span>
                        <h3><?php echo htmlspecialchars($document['description']); ?></h3>
                        <div class="document-details">
                            <div class="detail-item">
                                <span>Location: <?php echo htmlspecialchars($document['location']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Reported: <?php echo htmlspecialchars($document['date_reported']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Status: <?php echo htmlspecialchars($document['statusName']); ?></span>
                            </div>
                            <div class="detail-item">
                                <a href="progress.php?id=<?php echo $document['document_id']; ?>" class="status-badge">Track Progress</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach;
            else: ?>
                <p>No documents found.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; Tshijuka RDP</p>
    </footer>
</body>
</html>
