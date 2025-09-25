<?php
/**
 * Database Configuration for GR Yachts API
 */

// Database connection settings
//define('DB_HOST', 'localhost');
define('DB_HOST', 'srv1492.hstgr.io');

define('DB_NAME', 'u508105042_grYacht');
define('DB_USERNAME', 'u508105042_grYacht');
define('DB_PASSWORD', '7$r2qY1@Ux');
define('DB_CHARSET', 'utf8mb4');

// Database connection function
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}   

?>
