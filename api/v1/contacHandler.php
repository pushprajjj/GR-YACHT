<?php

/**
 * Contact Form Handler API
 * Handles form submissions from the contact page and stores them in database
 */

require_once 'config/_db.php';
require_once 'config/_config.php';




// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

//chekk for empty fields
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required.'
    ]);
    exit(); 
}   
// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    exit(); 
}
    //check for rate limiting
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check IP rate limiting
    $stmt = $conn->prepare("SELECT request_count, window_start FROM rate_limits WHERE ip_address = ? AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['request_count'] >= 5) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.'
            ]);
            exit();
        }
        // Update existing rate limit record
        $stmt = $conn->prepare("UPDATE rate_limits SET request_count = request_count + 1, last_request = CURRENT_TIMESTAMP() WHERE ip_address = ?");
        $stmt->bind_param("s", $ip_address);
    } else {
        // Create new rate limit record
        $stmt = $conn->prepare("INSERT INTO rate_limits (ip_address, request_count) VALUES (?, 1)");
        $stmt->bind_param("s", $ip_address);
    }
    $stmt->execute();

    // Check email submission frequency
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contacts WHERE email = ? AND submitted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $email_count = $result->fetch_assoc()['count'];

    if ($email_count >= 3) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Maximum daily contact attempts reached for this email.'
        ]);
        exit();
    }

    // Insert new contact submission
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("sssss", $name, $email, $message, $ip_address, $user_agent);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send message. Please try again later.'
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ]);
    exit();
}
