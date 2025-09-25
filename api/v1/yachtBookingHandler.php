<?php
/**
 * Yacht Booking Handler API
 * Handles yacht booking form submissions from yacht-details page
 */

require_once 'config/_db.php';
require_once 'config/_config.php';


// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $preferred_date = isset($_POST['preferred_date']) ? trim($_POST['preferred_date']) : '';
    $preferred_time = isset($_POST['preferred_time']) ? trim($_POST['preferred_time']) : '';
    $charter_length = isset($_POST['charter_length']) ? trim($_POST['charter_length']) : '';
    $yacht_name = isset($_POST['yacht_name']) ? trim($_POST['yacht_name']) : 'BLACK PEARL 95';
    
    // Check for empty required fields
    if (empty($full_name) || empty($email) || empty($phone) || empty($preferred_date) || empty($preferred_time) || empty($charter_length)) {
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
    
    // Validate date format and ensure it's not in the past
    $date_obj = DateTime::createFromFormat('Y-m-d', $preferred_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $preferred_date) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format.'
        ]);
        exit(); 
    }
    
    // Check if date is not in the past
    $today = new DateTime();
    if ($date_obj < $today->setTime(0, 0, 0)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Booking date cannot be in the past.'
        ]);
        exit(); 
    }
    
    // Validate time format
    $time_obj = DateTime::createFromFormat('H:i', $preferred_time);
    if (!$time_obj || $time_obj->format('H:i') !== $preferred_time) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid time format.'
        ]);
        exit(); 
    }
    
    // Validate charter length
    $valid_charter_lengths = ['1 Hour', '2 Hours', '3 Hours', '4 Hours', '5 Hours', '6 Hours', 'Full Day'];
    if (!in_array($charter_length, $valid_charter_lengths)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid charter length selected.'
        ]);
        exit(); 
    }
    
    // Rate limiting check
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check IP rate limiting (max 3 requests per hour)
    $stmt = $conn->prepare("SELECT request_count, window_start FROM yacht_booking_rate_limits WHERE ip_address = ? AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['request_count'] >= 3) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Too many booking requests. Please try again later.'
            ]);
            exit();
        }
        // Update existing rate limit record
        $stmt = $conn->prepare("UPDATE yacht_booking_rate_limits SET request_count = request_count + 1, last_request = CURRENT_TIMESTAMP() WHERE ip_address = ?");
        $stmt->bind_param("s", $ip_address);
    } else {
        // Create new rate limit record
        $stmt = $conn->prepare("INSERT INTO yacht_booking_rate_limits (ip_address, request_count) VALUES (?, 1)");
        $stmt->bind_param("s", $ip_address);
    }
    $stmt->execute();
    
    // Check for duplicate bookings (same email and date)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM yacht_bookings WHERE email = ? AND preferred_date = ? AND status != 'cancelled'");
    $stmt->bind_param("ss", $email, $preferred_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $duplicate_count = $result->fetch_assoc()['count'];
    
    if ($duplicate_count > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You already have a booking for this date. Please contact us to modify your existing booking.'
        ]);
        exit();
    }
    
    // Check email submission frequency (max 5 bookings per week per email)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM yacht_bookings WHERE email = ? AND submitted_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $email_count = $result->fetch_assoc()['count'];
    
    if ($email_count >= 5) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Maximum weekly booking requests reached for this email. Please contact us directly.'
        ]);
        exit();
    }
    
    // Calculate estimated price based on charter length
    $price_per_hour = 1200; // AED per hour
    $estimated_price = 0;
    
    switch ($charter_length) {
        case '1 Hour':
            $estimated_price = $price_per_hour * 1;
            break;
        case '2 Hours':
            $estimated_price = $price_per_hour * 2;
            break;
        case '3 Hours':
            $estimated_price = $price_per_hour * 3;
            break;
        case '4 Hours':
            $estimated_price = $price_per_hour * 4;
            break;
        case '5 Hours':
            $estimated_price = $price_per_hour * 5;
            break;
        case '6 Hours':
            $estimated_price = $price_per_hour * 6;
            break;
        case 'Full Day':
            $estimated_price = 14400; // As mentioned in the page
            break;
    }
    
    // Insert new yacht booking
    $stmt = $conn->prepare("INSERT INTO yacht_bookings (full_name, email, phone, preferred_date, preferred_time, charter_length, yacht_name, ip_address, user_agent, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("sssssssssd", $full_name, $email, $phone, $preferred_date, $preferred_time, $charter_length, $yacht_name, $ip_address, $user_agent, $estimated_price);
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking request submitted successfully! We will contact you within 24 hours to confirm availability.',
            'booking_id' => $booking_id,
            'estimated_price' => $estimated_price,
            'currency' => 'AED'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit booking request. Please try again later.'
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
?>
