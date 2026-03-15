<?php
/**
 * Front controller – single entry point for MVC.
 * Routes: index.php?controller=Home&action=index (default) | controller=Auth&action=login_form | etc.
 */

// No controller/action → show home via MVC
$controller = trim($_GET['controller'] ?? $_POST['controller'] ?? '');
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

if ($controller === '' && $action === '') {
    $_GET['controller'] = 'Home';
    $_GET['action'] = 'index';
}

$controller = trim($_GET['controller'] ?? $_POST['controller'] ?? '');
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

if ($controller !== '' && $action !== '') {
    require_once __DIR__ . '/config/bootstrap.php';
    $db = getDB();
    $router = new App\Core\Router($db);
    $router->dispatch();
    exit;
}

// Legacy: no router params – render home directly (same as HomeController::index)
require_once __DIR__ . '/config/lang.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/';
$loggedIn = !empty($_SESSION['user_id']);
require __DIR__ . '/views/home.php';
exit;
