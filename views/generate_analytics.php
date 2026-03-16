<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/config.php';
include __DIR__ . '/../config/core.php';

isLogin();
if (!isAdmin()) {
    header('Location: ' . (isset($baseUrl) ? $baseUrl : (function_exists('getBaseUrl') ? getBaseUrl() : '')) . 'index.php?controller=Admin&action=dashboard');
    exit;
}

include_once '../controllers/Functions_users_documents.php';

if (!isset($conn) || !$conn) {
    die('Error: Database connection not available.');
}

try {
    $totalDocuments   = getTotalDocuments();
    $completedDocuments = getCompletedDocuments();
    $pendingDocuments = getPendingDocuments();
    $inProgressDocuments = getInProgressDocuments();
    $cancelledDocuments = getCancelledDocuments();

    $users = getAllUsers();
    $totalUsers = is_array($users) ? count($users) : 0;
} catch (Exception $e) {
    $analyticsError = 'Error fetching analytics: ' . $e->getMessage();
}

// Ensure base URL is available when this view is loaded directly (e.g. /aa/views/generate_analytics.php)
if (!isset($baseUrl)) {
    $baseUrl = function_exists('getBaseUrl')
        ? getBaseUrl()
        : (rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/nav.css">
    <title>Analytics - Tshijuka RDP</title>
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-4px); }
    </style>
</head>
<body>
<?php $showLogout = true; include 'nav.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Documents Analytics</h1>
        <a href="<?= htmlspecialchars($baseUrl ?? ''); ?>index.php?controller=Admin&action=dashboard" class="btn btn-outline-secondary">← Back to Admin Dashboard</a>
    </div>

    <?php if (!empty($analyticsError)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($analyticsError); ?></div>
    <?php else: ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white stat-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Documents</h5>
                    <p class="card-text display-6"><?php echo (int) $totalDocuments; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white stat-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <p class="card-text display-6"><?php echo (int) $completedDocuments; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark stat-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <p class="card-text display-6"><?php echo (int) $pendingDocuments; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white stat-card h-100">
                <div class="card-body">
                    <h5 class="card-title">In Progress</h5>
                    <p class="card-text display-6"><?php echo (int) $inProgressDocuments; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card text-center border-secondary stat-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Cancelled</h5>
                    <p class="card-text display-6"><?php echo (int) $cancelledDocuments; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-primary stat-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="card-text display-6"><?php echo (int) $totalUsers; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-success stat-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Completion Rate</h5>
                    <p class="card-text display-6">
                        <?php
                        $total = (int) $totalDocuments;
                        $done = (int) $completedDocuments;
                        echo $total > 0 ? round($done / $total * 100, 1) . '%' : '0%';
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>
</body>
</html>
