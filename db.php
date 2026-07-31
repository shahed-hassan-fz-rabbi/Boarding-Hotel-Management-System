<?php
/**
 * ==========================================
 * DATABASE CONNECTION FILE
 * ==========================================
 */

$servername = "localhost";
$username = "root";
$password = "";
$database = "boarding_school";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Base URL
define('BASE_URL', 'http://localhost/boarding-school/');

?>