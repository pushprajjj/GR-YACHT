<?php
/**
 * Database Configuration for GR Yachts API
 */

// Database connection settings
//define('DB_HOST', 'localhost');
define('DB_HOST', '193.203.184.95');

define('DB_NAME', 'u508105042_ff');
define('DB_USERNAME', 'u508105042_ff');
define('DB_PASSWORD', '80LVd1phyoS+');
define('DB_CHARSET', 'utf8mb4');

// Database connection function
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}   

?>
