<?php
/**
 * API Bootstrap – load app bootstrap, set JSON headers, optional auth.
 * API endpoints are for admin/integration use only; not linked on the public site.
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// CORS: allow requests from same origin; optionally allow admin-configured origins
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed = [$_SERVER['HTTP_HOST'] ?? ''];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && in_array(parse_url($origin, PHP_URL_HOST), $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(204);
    exit;
}

/**
 * Require admin session for API. Call at top of admin-only endpoints.
 */
function requireApiAdmin(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin access required.']);
        exit;
    }
    return [
        'user_id' => (int) $_SESSION['user_id'],
        'user_role' => $_SESSION['user_role'] ?? '',
        'username' => $_SESSION['username'] ?? ''
    ];
}

/**
 * Require admin or document issuer. Call at top of endpoints that allow both.
 */
function requireApiAdminOrIssuer(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, ['Admin', 'Document Issuer'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Admin or Document Issuer only.']);
        exit;
    }
    return [
        'user_id' => (int) $_SESSION['user_id'],
        'user_role' => $role,
        'username' => $_SESSION['username'] ?? ''
    ];
}

function apiJson(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
