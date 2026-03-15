<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
#isLogin();

// Ensure only students can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Document Seeker') {
    echo "Access denied. Only document seekers can access this page.";
    exit;
}

$studentID = $_SESSION['user_id'];

// Fetch document seeker info
$stmt = $conn->prepare("SELECT * FROM User WHERE userID = ?");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch documents submitted by this document seeker
$stmt = $conn->prepare("
    SELECT r.documentID, sub.documentIssuerName, r.description, r.location, r.submissionDate,
           st.statusName, r.statusID, r.imagePath, sub.userID AS issuerID
    FROM Document r
    JOIN Status st ON r.statusID = st.statusID
    JOIN Subscribe sub ON r.documentIssuerID = sub.userID
    WHERE r.userID = ?
    ORDER BY r.submissionDate DESC
");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$documents = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tshijuka Pack - Document Seeker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/nav.css">
</head>
<body>
<?php $showLogout = true; include 'nav.php'; ?>

<div class="container mt-4">
    <h1>Welcome, <?= htmlspecialchars((string)($student['userName'] ?? '')) ?></h1>
    <p class="lead">Here are your submitted document requests (Tshijuka Pack).</p>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Document ID</th>
                <th>Institution</th>
                <th>Description</th>
                <th>Location</th>
                <th>Status</th>
                <th>Submitted On</th>
                <th>Attachment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($documents->num_rows > 0): ?>
                <?php while ($row = $documents->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($row['documentID'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['documentIssuerName'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['description'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['location'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['statusName'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['submissionDate'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($row['imagePath'])): ?>
                                <a href="index.php?controller=Document&action=view_page&documentID=<?php echo urlencode($row['documentID']); ?>" target="_blank" rel="noopener">View</a>
                            <?php else: ?>
                                None
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Chat Button -->
                            <a href="index.php?controller=Chat&action=index&documentID=<?= urlencode($row['documentID']) ?>&documentIssuerID=<?= urlencode($row['issuerID']) ?>" 
                               class="btn btn-sm btn-success">Chat with Institution</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No documents submitted yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
