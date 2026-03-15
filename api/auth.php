<?php
/**
 * API Auth – programmatic login (for integration partners or admin tools).
 * POST api/auth.php with email, password → JSON with user info (no password).
 * Session is set so subsequent API calls can use the same session.
 */

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJson(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
}

$input = getJsonInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    apiJson(['success' => false, 'message' => 'Email and password are required.'], 400);
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'input_security.php';
if (validateAuthInput(['email' => $email, 'password' => $password]) !== null) {
    apiJson(['success' => false, 'message' => 'Invalid or suspicious input.'], 400);
}

$db = getDB();
$authService = new \App\Services\AuthService($db);
$result = $authService->login($email, $password);

if (!$result['success']) {
    apiJson($result, 401);
}

$user = $result['user'];
$_SESSION['user_id'] = $user['userID'];
$_SESSION['user_role'] = $user['userRole'];
$_SESSION['username'] = $user['userName'];

apiJson([
    'success' => true,
    'user' => [
        'userID' => (int) $user['userID'],
        'userName' => $user['userName'],
        'userEmail' => $user['userEmail'],
        'userRole' => $user['userRole']
    ],
    'redirect' => $result['redirect'] ?? null
]);
