<?php
/**
 * Legacy entry – forwards to MVC router.
 * POST login is handled by AuthController::loginSubmit().
 */
$_POST['controller'] = $_POST['controller'] ?? 'Auth';
$_POST['action'] = $_POST['action'] ?? 'login_submit';
require_once __DIR__ . '/../index.php';
