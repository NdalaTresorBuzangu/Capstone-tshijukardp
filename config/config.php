<?php 
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings (Hostinger database)
$servername = "localhost";
$username = "u628771162_nd";
$password = "Ndala1950@@";
$dbname = "u628771162_ndalab"; 

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
