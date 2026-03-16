<?php 
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Base URL for assets (CSS, JS, images). Leave empty to auto-detect from request.
// On local XAMPP in subfolder use e.g. '/aa/' so assets load correctly.
// if (!defined('BASE_URL')) { define('BASE_URL', '/aa/'); }

if (!function_exists('getBaseUrl')) {
    function getBaseUrl(): string {
        if (defined('BASE_URL') && BASE_URL !== '') {
            return rtrim(BASE_URL, '/') . '/';
        }
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(dirname($script), '/');
        // If we are in a subdirectory like /aa/views, strip the trailing /views
        if (substr($dir, -6) === '/views') {
            $dir = substr($dir, 0, -6);
        }
        return ($dir === '' ? '/' : $dir . '/');
    }
}

// Database connection settings (Hostinger database)
// Database connection settings
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "document"; 


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Do not close the connection here; let other files use it
// $conn->close(); // Remove this line

/**
 * All uploads (documents, issuer stored, preloss, chat) are stored under uploads/images/.
 * Returns the correct relative URL for the current page (views/ vs root).
 */
if (!function_exists('upload_url')) {
    function upload_url($path) {
        if (empty($path)) return '';
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        if (strpos($path, 'uploads/') !== 0) return $path;
        $prefix = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/views/') !== false) ? '../' : '';
        return $prefix . $path;
    }
}
