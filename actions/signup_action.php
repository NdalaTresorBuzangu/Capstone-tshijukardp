<?php
/**
 * Signup Action
 * 
 * Web handler for user registration.
 * Uses MVC Controllers for business logic and site DB config.
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/config.php';

use App\Controllers\AuthController;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $db = $conn;
    $authController = new AuthController($db);

    $result = $authController->register([
        'name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirmPassword' => $_POST['confirm_password'] ?? '',
        'role' => $_POST['userRole'] ?? '',
        'contact' => $_POST['contact'] ?? ''
    ]);

    if ($result['success']) {
        $result['redirect'] = 'login.php';
        $result['message'] = 'Signup successful!';
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}

exit;
