<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../controllers/Functions_users_documents.php";
include __DIR__ . "/../config/core.php";
include __DIR__ . "/../controllers/CrossBorderPayment.php";
include __DIR__ . "/../config/payment_config.php";
isLogin();
isAdmin();

// Handle form submissions for adding, updating, and deleting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['addUser'])) {
            $userName = filter_input(INPUT_POST, 'userName', FILTER_SANITIZE_STRING);
            $userEmail = filter_input(INPUT_POST, 'userEmail', FILTER_VALIDATE_EMAIL);
            $userPassword = filter_input(INPUT_POST, 'userPassword', FILTER_SANITIZE_STRING);
            $userRole = filter_input(INPUT_POST, 'userRole', FILTER_SANITIZE_STRING);

            if ($userName && $userEmail && $userPassword && $userRole) {
                addUser($userName, $userEmail, $userPassword, $userRole);
                $_SESSION['message'] = "User added successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                throw new Exception("Invalid user data provided.");
            }
        } elseif (isset($_POST['deleteUser'])) {
            $userID = filter_input(INPUT_POST, 'deleteUser', FILTER_VALIDATE_INT);
            deleteUser($userID);
            $_SESSION['message'] = "User deleted successfully.";
            $_SESSION['message_type'] = "success";
        } elseif (isset($_POST['updateUserRole'])) {
            $userID = filter_input(INPUT_POST, 'updateUserRole', FILTER_VALIDATE_INT);
            $newRole = filter_input(INPUT_POST, 'newRole', FILTER_SANITIZE_STRING);
            updateUserRole($userID, $newRole);
            $_SESSION['message'] = "User role updated successfully.";
            $_SESSION['message_type'] = "success";
        }

        if (isset($_POST['addDocument'])) {
            $userID = filter_input(INPUT_POST, 'userID', FILTER_VALIDATE_INT);
            $documentIssuerID = filter_input(INPUT_POST, 'documentIssuerID', FILTER_VALIDATE_INT);
            $documentTypeID = filter_input(INPUT_POST, 'documentTypeID', FILTER_VALIDATE_INT);
            $statusID = filter_input(INPUT_POST, 'statusID', FILTER_VALIDATE_INT);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
            $file = $_FILES['documentImage'];

            if ($userID && $documentIssuerID && $documentTypeID && $statusID && $description && $location) {
                $documentID = addDocument($userID, $documentIssuerID, $documentTypeID, $statusID, $description, $location, $file);
                $_SESSION['message'] = "Document added successfully. Document ID: {$documentID}";
                $_SESSION['message_type'] = "success";
            } else {
                throw new Exception("Invalid document data provided.");
            }
        } elseif (isset($_POST['deleteDocument'])) {
            $documentID = filter_input(INPUT_POST, 'deleteDocument', FILTER_SANITIZE_STRING);
            deleteDocument($documentID);
            $_SESSION['message'] = "Document deleted successfully.";
            $_SESSION['message_type'] = "success";
        } elseif (isset($_POST['updateDocumentStatus'])) {
            $documentID = filter_input(INPUT_POST, 'updateDocumentStatus', FILTER_SANITIZE_STRING);
            $newStatusID = filter_input(INPUT_POST, 'newStatusID', FILTER_VALIDATE_INT);
            $file = isset($_FILES['updateDocumentImage']) ? $_FILES['updateDocumentImage'] : null;
            updateDocumentStatus($documentID, $newStatusID, $file);
            $_SESSION['message'] = "Document status updated successfully.";
            $_SESSION['message_type'] = "success";
        }

        // Cross-border: Country agents
        if (isset($_POST['addCountryAgent'])) {
            $countryCode = trim($_POST['agent_country_code'] ?? '');
            $agentName = trim($_POST['agent_name'] ?? '');
            $contactPhone = trim($_POST['agent_contact_phone'] ?? '');
            $contactEmail = trim($_POST['agent_contact_email'] ?? '');
            $momoNumber = trim($_POST['agent_momo_number'] ?? '');
            $momoProvider = trim($_POST['agent_momo_provider'] ?? '');
            $bankName = trim($_POST['agent_bank_name'] ?? '');
            $bankAccountNumber = trim($_POST['agent_bank_account_number'] ?? '');
            $bankAccountName = trim($_POST['agent_bank_account_name'] ?? '');
            if ($countryCode && $agentName && $momoNumber) {
                addCountryAgent($countryCode, $agentName, $contactPhone, $contactEmail, $momoNumber, $momoProvider, $bankName, $bankAccountNumber, $bankAccountName);
                $_SESSION['message'] = "Country agent added successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Country, agent name and Momo number are required.";
                $_SESSION['message_type'] = "error";
            }
        } elseif (isset($_POST['deleteCountryAgent'])) {
            $agentId = filter_input(INPUT_POST, 'deleteCountryAgent', FILTER_VALIDATE_INT);
            if ($agentId) {
                deleteCountryAgent($agentId);
                $_SESSION['message'] = "Agent removed.";
                $_SESSION['message_type'] = "success";
            }
        }

        // Cross-border: Assign agent to payment
        if (isset($_POST['assignAgentToPayment'])) {
            $paymentId = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
            $agentId = filter_input(INPUT_POST, 'agent_id', FILTER_VALIDATE_INT);
            $institutionCountry = trim($_POST['institution_country'] ?? '');
            $amountLocal = trim($_POST['amount_local'] ?? '') ?: null;
            $notes = trim($_POST['flow_notes'] ?? '') ?: null;
            if ($paymentId && $agentId) {
                assignAgentToPayment($paymentId, $agentId, $institutionCountry, $amountLocal, $notes);
                $_SESSION['message'] = "Agent assigned. Communicate to the agent to pay the institution via Momo.";
                $_SESSION['message_type'] = "success";
            }
        } elseif (isset($_POST['markAgentPaidMomo'])) {
            $flowId = filter_input(INPUT_POST, 'flow_id', FILTER_VALIDATE_INT);
            if ($flowId) {
                markAgentPaidMomo($flowId);
                $_SESSION['message'] = "Marked: Agent paid institution via Momo.";
                $_SESSION['message_type'] = "success";
            }
        } elseif (isset($_POST['markCompensationSent'])) {
            $flowId = filter_input(INPUT_POST, 'flow_id', FILTER_VALIDATE_INT);
            if ($flowId) {
                markCompensationSent($flowId);
                $_SESSION['message'] = "Marked: Compensation sent to agent (e.g. to bank account).";
                $_SESSION['message_type'] = "success";
            }
        }

        header("Location: " . (isset($baseUrl) ? $baseUrl : (function_exists('getBaseUrl') ? getBaseUrl() : '')) . "index.php?controller=Admin&action=dashboard");
        exit;
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: " . (isset($baseUrl) ? $baseUrl : (function_exists('getBaseUrl') ? getBaseUrl() : '')) . "index.php?controller=Admin&action=dashboard");
        exit;
    }
}

$users = getAllUsers();
$documents = getAllDocuments();
$totalUsers = is_array($users) ? count($users) : 0;
$totalDocs = is_array($documents) ? count($documents) : 0;

$countryAgents = [];
$paymentsReceived = [];
try {
    $countryAgents = getCountryAgents();
    $paymentsReceived = getPaymentsReceivedForAdmin();
} catch (Throwable $e) {
    $paymentsReceived = [];
    $countryAgents = [];
}

function statusBadgeClass($statusName) {
    if (empty($statusName)) return 'admin-badge-pending';
    $s = strtolower($statusName);
    if (strpos($s, 'pending') !== false) return 'admin-badge-pending';
    if (strpos($s, 'progress') !== false) return 'admin-badge-progress';
    if (strpos($s, 'completed') !== false) return 'admin-badge-completed';
    if (strpos($s, 'cancelled') !== false) return 'admin-badge-cancelled';
    return 'admin-badge-pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/nav.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? ''; ?>assets/admin.css">
    <script src="https://kit.fontawesome.com/cb76afc7c2.js" crossorigin="anonymous"></script>
    <title>Admin Dashboard - Tshijuka RDP</title>
</head>
<body class="admin-dashboard">
<?php $showLogout = true; include 'nav.php'; ?>

<div class="admin-wrap">
    <header class="admin-header">
        <h1 class="admin-title">Admin Dashboard</h1>
        <div class="admin-actions">
            <a href="views/generate_analytics.php" class="btn btn-primary">
                <i class="fas fa-chart-bar"></i> View Analytics
            </a>
        </div>
    </header>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert admin-alert alert-<?php echo $_SESSION['message_type'] === 'error' ? 'danger' : 'success'; ?>">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="admin-stats">
        <div class="admin-stat-card stat-users">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?php echo (int) $totalUsers; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="admin-stat-card stat-docs">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-value"><?php echo (int) $totalDocs; ?></div>
            <div class="stat-label">Total Documents</div>
        </div>
    </div>

    <!-- Manage Users -->
    <section class="admin-section">
        <div class="admin-section-header">
            <h2 class="admin-section-title"><i class="fas fa-user-cog"></i> Manage Users</h2>
        </div>
        <div class="admin-form">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="userName" class="form-control" placeholder="Full name" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="userEmail" class="form-control" placeholder="email@example.com" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Password</label>
                    <input type="password" name="userPassword" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select name="userRole" class="form-select" required>
                        <option value="Document Seeker">Document Seeker</option>
                        <option value="Document Issuer">Document Issuing Institution</option>
                        <option value="Admin">Admin</option>
                        <option value="Admissions Office">Admissions Office</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="addUser" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> Add User</button>
                </div>
            </form>
        </div>
        <div class="admin-table-wrap">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user):
                        $uid = (int) ($user['userID'] ?? $user['userid'] ?? 0);
                        $role = (string) ($user['userRole'] ?? $user['userrole'] ?? '');
                    ?>
                        <tr>
                            <td><?php echo $uid; ?></td>
                            <td><?php echo htmlspecialchars($user['userName'] ?? $user['username'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['userEmail'] ?? $user['useremail'] ?? ''); ?></td>
                            <td><span class="admin-badge admin-badge-progress"><?php echo htmlspecialchars($role ?: '—'); ?></span></td>
                            <td>
                                <form action="" method="POST" class="admin-inline-form">
                                    <select name="newRole" class="form-select" required>
                                        <option value="Document Seeker" <?php echo ($role === 'Document Seeker') ? ' selected' : ''; ?>>Document Seeker</option>
                                        <option value="Document Issuer" <?php echo ($role === 'Document Issuer') ? ' selected' : ''; ?>>Document Issuing Institution</option>
                                        <option value="Admin" <?php echo ($role === 'Admin') ? ' selected' : ''; ?>>Admin</option>
                                        <option value="Admissions Office" <?php echo ($role === 'Admissions Office') ? ' selected' : ''; ?>>Admissions Office</option>
                                    </select>
                                    <button type="submit" name="updateUserRole" value="<?php echo $uid; ?>" class="btn btn-admin-warning btn-sm">Update</button>
                                </form>
                                <form action="" method="POST" class="admin-inline-form ms-1">
                                    <button type="submit" name="deleteUser" value="<?php echo $uid; ?>" class="btn btn-admin-danger btn-sm" onclick="return confirm('Delete this user?');"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Manage Documents -->
    <section class="admin-section">
        <div class="admin-section-header">
            <h2 class="admin-section-title"><i class="fas fa-file-medical"></i> Manage Documents</h2>
        </div>
        <div class="admin-form">
            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select name="userID" class="form-select" required>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo (int) $u['userID']; ?>"><?php echo htmlspecialchars($u['userName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Issuer</label>
                    <select name="documentIssuerID" class="form-select" required>
                        <?php foreach ($users as $u): if ($u['userRole'] !== 'Document Issuer') continue; ?>
                            <option value="<?php echo (int) $u['userID']; ?>"><?php echo htmlspecialchars($u['userName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="documentTypeID" class="form-select" required>
                        <option value="1">Identity</option>
                        <option value="2">Educational</option>
                        <option value="3">History</option>
                        <option value="4">Contract</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="statusID" class="form-select" required>
                        <option value="1">Pending</option>
                        <option value="2">In Progress</option>
                        <option value="3">Completed</option>
                        <option value="4">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Description" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" placeholder="Location" required>
                </div>
                <div class="col-12 row g-2">
                    <div class="col-md-4">
                        <label class="form-label">File (image)</label>
                        <input type="file" name="documentImage" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="addDocument" class="btn btn-primary"><i class="fas fa-upload me-1"></i> Add Document</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="admin-table-wrap">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Location</th>
                        <th>Image</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc):
                        $imagePath = !empty($doc['imagePath']) ? $doc['imagePath'] : 'uploads/images/placeholder.jpg';
                        $imgSrc = upload_url($imagePath);
                        $statusName = isset($doc['statusName']) ? $doc['statusName'] : getStatusById($doc['statusID']);
                    ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($doc['documentID']); ?></code></td>
                            <td><?php echo htmlspecialchars($doc['description']); ?></td>
                            <td><?php echo htmlspecialchars($doc['location']); ?></td>
                            <td>
                                <?php if (!empty($doc['imagePath']) || !empty($doc['imageData'])): ?>
                                    <div class="admin-doc-thumb-wrap">
                                        <a href="<?= htmlspecialchars($baseUrl ?? ''); ?>index.php?controller=Document&action=view_page&documentID=<?php echo urlencode($doc['documentID']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary me-1">View</a>
                                        <a href="<?= htmlspecialchars($baseUrl ?? ''); ?>views/view_document.php?documentID=<?php echo urlencode($doc['documentID']); ?>&download=1" class="btn btn-sm btn-success me-1">Download</a>
                                        <img src="<?= htmlspecialchars($baseUrl ?? ''); ?>views/view_document.php?documentID=<?php echo urlencode($doc['documentID']); ?>" alt="Doc" class="admin-doc-thumb" style="max-height:40px" onerror="this.style.display='none'">
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($doc['submissionDate'] ?? ''); ?></td>
                            <td><span class="admin-badge <?php echo statusBadgeClass($statusName); ?>"><?php echo htmlspecialchars($statusName); ?></span></td>
                            <td>
                                <form action="" method="POST" enctype="multipart/form-data" class="admin-inline-form">
                                    <input type="file" name="updateDocumentImage" class="form-control form-control-sm d-inline-block" style="max-width:100px" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" title="Optional new image">
                                    <select name="newStatusID" class="form-select">
                                        <option value="1" <?php echo ((int)($doc['statusID']) === 1) ? 'selected' : ''; ?>>Pending</option>
                                        <option value="2" <?php echo ((int)($doc['statusID']) === 2) ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="3" <?php echo ((int)($doc['statusID']) === 3) ? 'selected' : ''; ?>>Completed</option>
                                        <option value="4" <?php echo ((int)($doc['statusID']) === 4) ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="updateDocumentStatus" value="<?php echo htmlspecialchars($doc['documentID']); ?>" class="btn btn-admin-warning btn-sm">Update</button>
                                </form>
                                <form action="" method="POST" class="admin-inline-form ms-1">
                                    <button type="submit" name="deleteDocument" value="<?php echo htmlspecialchars($doc['documentID']); ?>" class="btn btn-admin-danger btn-sm" onclick="return confirm('Delete this document?');"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Cross-border: Country Agents (pay institutions via Momo; you compensate via bank) -->
    <section class="admin-section">
        <div class="admin-section-header">
            <h2 class="admin-section-title"><i class="fas fa-hand-holding-usd"></i> Country Agents (Cross-border)</h2>
            <p class="text-muted small mb-0">Agents in each country pay institutions via Momo; you compensate them by sending to their bank account.</p>
        </div>
        <div class="admin-form">
            <form method="POST" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Country</label>
                    <select name="agent_country_code" class="form-select" required>
                        <?php foreach (getSupportedCurrencies() as $code => $info): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($info['country'] . ' (' . $code . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Agent name</label>
                    <input type="text" name="agent_name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phone</label>
                    <input type="text" name="agent_contact_phone" class="form-control" placeholder="Contact">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Momo number</label>
                    <input type="text" name="agent_momo_number" class="form-control" placeholder="For paying institutions" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Momo provider</label>
                    <input type="text" name="agent_momo_provider" class="form-control" placeholder="e.g. MTN, M-Pesa">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bank name (compensation)</label>
                    <input type="text" name="agent_bank_name" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bank account number</label>
                    <input type="text" name="agent_bank_account_number" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bank account name</label>
                    <input type="text" name="agent_bank_account_name" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="addCountryAgent" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> Add Agent</button>
                </div>
            </form>
        </div>
        <div class="admin-table-wrap">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Momo</th>
                        <th>Bank (for compensation)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($countryAgents as $a): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($a['country_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($a['agent_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['contact_phone'] ?: $a['contact_email'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($a['momo_number']); ?> <?php if (!empty($a['momo_provider'])) echo '<span class="text-muted small">(' . htmlspecialchars($a['momo_provider']) . ')</span>'; ?></td>
                            <td><?php echo htmlspecialchars($a['bank_name'] ?: '—'); ?> <?php if (!empty($a['bank_account_number'])) echo '<br><code class="small">' . htmlspecialchars($a['bank_account_number']) . '</code>'; ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="deleteCountryAgent" value="<?php echo (int)$a['agent_id']; ?>" class="btn btn-admin-danger btn-sm" onclick="return confirm('Remove this agent?');"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($countryAgents)): ?>
                        <tr><td colspan="6" class="text-muted">No agents yet. Add agents for each country where you need Momo payments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Cross-border: Payments received → Assign agent → Agent pays Momo → Compensation sent -->
    <section class="admin-section">
        <div class="admin-section-header">
            <h2 class="admin-section-title"><i class="fas fa-money-bill-wave"></i> Payments Received (Cross-border flow)</h2>
            <p class="text-muted small mb-0">When a document seeker pays, assign an agent. The agent pays the institution via Momo; then you mark compensation sent to the agent's bank.</p>
        </div>
        <div class="admin-table-wrap">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Payment / Ref</th>
                        <th>Amount</th>
                        <th>Seeker / Document</th>
                        <th>Institution</th>
                        <th>Flow</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paymentsReceived as $pay): ?>
                        <?php $flow = $pay['flow'] ?? null; ?>
                        <tr>
                            <td>
                                <code><?php echo htmlspecialchars($pay['reference']); ?></code>
                                <br><span class="text-muted small"><?php echo htmlspecialchars($pay['verified_at'] ?? ''); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($pay['currency'] ?? 'GHS'); ?> <?php echo number_format((float)($pay['amount'] ?? 0), 2); ?></td>
                            <td><?php echo htmlspecialchars($pay['seeker_name'] ?? '—'); ?>
                                <?php if (!empty($pay['document_id'])): ?><br><span class="text-muted small">Doc: <?php echo htmlspecialchars($pay['document_id']); ?></span><?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($pay['issuer_name'] ?? '—'); ?></td>
                            <td>
                                <?php if ($flow): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($flow['agent_name']); ?></span> (<?php echo htmlspecialchars($flow['country_code']); ?>)
                                    <br><span class="badge <?php
                                        echo $flow['status'] === 'compensation_sent' ? 'bg-success' : ($flow['status'] === 'agent_paid_momo' ? 'bg-primary' : 'bg-warning text-dark');
                                    ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $flow['status'])); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">No agent assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$flow): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="payment_id" value="<?php echo (int)$pay['payment_id']; ?>">
                                        <select name="agent_id" class="form-select form-select-sm d-inline-block" style="width:auto" required>
                                            <option value="">Select agent</option>
                                            <?php
                                            $agentsForSelect = getCountryAgents(true);
                                            foreach ($agentsForSelect as $ag): ?>
                                                <option value="<?php echo (int)$ag['agent_id']; ?>"><?php echo htmlspecialchars($ag['agent_name'] . ' (' . $ag['country_code'] . ')'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="amount_local" class="form-control form-control-sm d-inline-block" style="width:80px" placeholder="Local amt">
                                        <button type="submit" name="assignAgentToPayment" class="btn btn-primary btn-sm">Assign</button>
                                    </form>
                                <?php elseif ($flow['status'] === 'agent_notified'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="flow_id" value="<?php echo (int)$flow['id']; ?>">
                                        <button type="submit" name="markAgentPaidMomo" class="btn btn-success btn-sm">Mark: Agent paid Momo</button>
                                    </form>
                                <?php elseif ($flow['status'] === 'agent_paid_momo'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="flow_id" value="<?php echo (int)$flow['id']; ?>">
                                        <button type="submit" name="markCompensationSent" class="btn btn-success btn-sm">Mark: Compensation sent</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-success small"><i class="fas fa-check"></i> Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paymentsReceived)): ?>
                        <tr><td colspan="6" class="text-muted">No successful payments yet. Payments from document seekers will appear here.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- API & Integration (Admin only – not visible to regular users) -->
    <section class="admin-section">
        <div class="admin-section-header">
            <h2 class="admin-section-title"><i class="fas fa-plug"></i> API &amp; Integration</h2>
            <p class="text-muted small mb-0">Use these APIs to integrate with external systems, offer document services to partners, or monetize access. <strong>Only admins see this section;</strong> it is not linked on the public site.</p>
        </div>
        <div class="card border-info mb-3">
            <div class="card-header bg-info text-white">
                <strong>How to use the API to get money or services from requesters</strong>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li><strong>Charge for document requests:</strong> Expose the Documents API to partners (e.g. schools, agencies). They send requests via API; you fulfill and can charge per request or a subscription fee.</li>
                    <li><strong>White-label or integration:</strong> Let third-party apps (mobile apps, other platforms) create document requests on behalf of their users. You receive the requests and get paid for processing.</li>
                    <li><strong>Bulk or reporting:</strong> Use the Users and Documents APIs to pull data for billing, analytics, or reporting tools (admin credentials only).</li>
                    <li><strong>Authentication:</strong> Use <code>POST api/auth.php</code> with an admin or issuer account to get a session; then call other endpoints. Keep credentials secure and share only with trusted integrators.</li>
                </ul>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Endpoint</th>
                        <th>Method</th>
                        <th>Auth</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>api/ping.php</code></td>
                        <td>GET</td>
                        <td>None</td>
                        <td>Health check – verify API is reachable.</td>
                    </tr>
                    <tr>
                        <td><code>api/auth.php</code></td>
                        <td>POST</td>
                        <td>None (sends email + password)</td>
                        <td>Programmatic login. Body: <code>{"email":"...","password":"..."}</code>. Returns user info and sets session for subsequent calls.</td>
                    </tr>
                    <tr>
                        <td><code>api/documents.php</code></td>
                        <td>GET</td>
                        <td>Admin or Document Issuer (session)</td>
                        <td>List documents. Admin: all; Issuer: own. Optional <code>?id=DOC-xxx</code> for one document.</td>
                    </tr>
                    <tr>
                        <td><code>api/documents.php</code></td>
                        <td>POST</td>
                        <td>Admin or Document Issuer (session)</td>
                        <td>Create document request. Body: <code>userId</code>, <code>issuerId</code>, <code>typeId</code>, <code>description</code>, <code>location</code>.</td>
                    </tr>
                    <tr>
                        <td><code>api/users.php</code></td>
                        <td>GET</td>
                        <td>Admin only (session)</td>
                        <td>List users. Optional <code>?role=Document Seeker</code> (or Document Issuer, Admin) to filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="small text-muted mb-0">
            <strong>Base URL:</strong> <code><?php
$host = $_SERVER['HTTP_HOST'] ?? 'yoursite.com';
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
echo htmlspecialchars($proto . '://' . $host . '/api/');
?></code>
            &nbsp;| Send <code>Content-Type: application/json</code> for POST. Use the same session cookie (or login via auth.php first) for protected endpoints.
        </p>
    </section>
</div>
</body>
</html>
