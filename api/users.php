<?php
/**
 * Users API – list users (Admin only). Use for integration or reporting.
 * GET api/users.php – list all users (admin only)
 * GET api/users.php?role=Document Seeker – filter by role
 */

require_once __DIR__ . '/bootstrap.php';

requireApiAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiJson(['success' => false, 'message' => 'Method not allowed. Use GET.'], 405);
}

$db = getDB();
$userModel = new \App\Models\User($db);
$role = trim($_GET['role'] ?? '');

if ($role !== '') {
    $list = $userModel->getByRole($role);
} else {
    $list = $userModel->getAll();
}

apiJson(['success' => true, 'users' => $list, 'count' => count($list)]);
