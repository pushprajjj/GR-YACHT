<?php

// Set timezone for consistent date/time handling
date_default_timezone_set('Asia/Dubai');

// JWT Configuration
define('JWT_SECRET_KEY', 'GRYachts2024!@#SecretKeyForJWT$%^AdminDashboard789');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed (adjust origin as per your requirements)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}