<?php
/**
 * Application Bootstrap
 * 
 * Initializes the MVC framework.
 * Include this file to get access to Models, Controllers, and Services.
 * 
 * Location: config/bootstrap.php (MVC standard - config holds initialization)
 */

// Error reporting (set to 0 in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define base path (project root)
define('APP_BASE_PATH', dirname(__DIR__));

// Single DB connection: load config.php so $conn is available; getDB() returns it
$configPath = APP_BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// Debug mode for OTP (set to false in production)
if (!defined('OTP_DEBUG_SHOW_CODE')) {
    define('OTP_DEBUG_SHOW_CODE', true);
}

// Autoloader for App namespace
// Maps: App\Models -> Models/, App\Controllers -> controllers/, App\Services -> Services/, App\Core -> core/
spl_autoload_register(function ($class) {
    // Only handle App namespace
    if (strpos($class, 'App\\') !== 0) {
        return;
    }

    // Convert namespace to path (base path is project root)
    $relativePath = str_replace('App\\', '', $class);
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
    // Models -> Models/, Controllers -> controllers/, Services -> Services/, Core -> core/
    $relativePath = str_replace('Controllers', 'controllers', $relativePath);
    $relativePath = str_replace('Core', 'core', $relativePath);
    $filePath = APP_BASE_PATH . DIRECTORY_SEPARATOR . $relativePath . '.php';

    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection (uses $conn from config.php when bootstrap loaded, else creates from env)
 * @return mysqli
 */
function getDB(): \mysqli
{
    global $conn;
    if (isset($conn) && $conn instanceof \mysqli) {
        return $conn;
    }
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $name = getenv('DB_NAME') ?: 'document';
    $conn = new \mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        throw new \Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

/**
 * JSON response helper for API
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get JSON request body
 */
function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

/**
 * Check if request is authenticated (has valid session)
 */
function requireAuth(): array
{
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'user_role' => $_SESSION['user_role'] ?? '',
        'username' => $_SESSION['username'] ?? ''
    ];
}

/**
 * Check if user has required role
 */
function requireRole(string ...$roles): array
{
    $auth = requireAuth();

    if (!in_array($auth['user_role'], $roles)) {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }

    return $auth;
}
