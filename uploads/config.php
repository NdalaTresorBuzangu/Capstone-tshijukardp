<?php

// Database connection settings
$servername = "localhost";
$username = "tresor.ndala"; 
$password = "Ndala1950@@"; 
$dbname = "document";

// Create connection
$con = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

?>


