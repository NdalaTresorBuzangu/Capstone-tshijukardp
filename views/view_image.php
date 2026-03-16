<?php
/**
 * View image/file from uploads/images/ (issuer, preloss, chat attachments).
 * path= is relative to uploads/images/ (e.g. issuer/file.jpg, preloss/file.jpg, chat/file.jpg).
 * Output buffering prevents stray output from corrupting the binary file.
 */
ob_start();
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
isLogin();

$path = isset($_GET['path']) ? trim($_GET['path']) : '';
$path = str_replace(['../', '..\\', '\\'], ['', '', '/'], $path);
$path = trim($path, '/');
if ($path === '' || strpos($path, '..') !== false) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid path.');
}

$projectRoot = dirname(__DIR__);
$baseDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
$fullPath = $baseDir . str_replace('/', DIRECTORY_SEPARATOR, $path);
$realBase = realpath($baseDir);
$realFull = realpath($fullPath);

if ($realBase === false || $realFull === false || strpos($realFull, $realBase) !== 0 || !is_file($realFull) || !is_readable($realFull)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found.');
}

$ext = strtolower(pathinfo($realFull, PATHINFO_EXTENSION));
$mimes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

ob_end_clean();
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realFull));
header('Content-Transfer-Encoding: binary');
$disposition = (isset($_GET['download']) && $_GET['download'] === '1') ? 'attachment' : 'inline';
header('Content-Disposition: ' . $disposition . '; filename="' . basename($realFull) . '"');
readfile($realFull);
exit;
