<?php
require_once 'config/_db.php';
require_once 'config/_config.php';
require_once 'jwt.php';

// JWT authentication check
$token = JWT::getValidToken();
if (empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$admin = JWT::verifyAdminToken($token);
if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit();
}

// Log admin activity
function logAdminActivity($adminId, $action, $details = null) {
    global $conn;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $adminId, $action, $details, $ipAddress, $userAgent);
    $stmt->execute();
}

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            // Get pagination parameters
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // Get filter parameters
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            $yachtName = $_GET['yacht_name'] ?? '';
            $charterLength = $_GET['charter_length'] ?? '';
            
            // Build WHERE clause
            $whereConditions = [];
            $params = [];
            $types = '';
            
            if (!empty($status)) {
                $whereConditions[] = "status = ?";
                $params[] = $status;
                $types .= 's';
            }
            
            if (!empty($search)) {
                $whereConditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'sss';
            }
            
            if (!empty($dateFrom)) {
                $whereConditions[] = "preferred_date >= ?";
                $params[] = $dateFrom;
                $types .= 's';
            }
            
            if (!empty($dateTo)) {
                $whereConditions[] = "preferred_date <= ?";
                $params[] = $dateTo;
                $types .= 's';
            }
            
            if (!empty($yachtName)) {
                $whereConditions[] = "yacht_name = ?";
                $params[] = $yachtName;
                $types .= 's';
            }
            
            if (!empty($charterLength)) {
                $whereConditions[] = "charter_length = ?";
                $params[] = $charterLength;
                $types .= 's';
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM yacht_bookings $whereClause";
            if (!empty($params)) {
                $countStmt = $conn->prepare($countQuery);
                $countStmt->bind_param($types, ...$params);
                $countStmt->execute();
                $countResult = $countStmt->get_result();
            } else {
                $countResult = $conn->query($countQuery);
            }
            $totalCount = $countResult->fetch_assoc()['total'];
            
            // Get bookings data
            $query = "SELECT id, full_name, email, phone, preferred_date, preferred_time, 
                            charter_length, yacht_name, ip_address, submitted_at, status, 
                            notes, total_price
                     FROM yacht_bookings 
                     $whereClause 
                     ORDER BY submitted_at DESC 
                     LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $bookings = [];
            while ($row = $result->fetch_assoc()) {
                $bookings[] = [
                    'id' => (int)$row['id'],
                    'full_name' => htmlspecialchars($row['full_name']),
                    'email' => htmlspecialchars($row['email']),
                    'phone' => htmlspecialchars($row['phone']),
                    'preferred_date' => $row['preferred_date'],
                    'preferred_time' => $row['preferred_time'],
                    'charter_length' => $row['charter_length'],
                    'yacht_name' => htmlspecialchars($row['yacht_name']),
                    'ip_address' => $row['ip_address'],
                    'submitted_at' => $row['submitted_at'],
                    'status' => $row['status'],
                    'notes' => htmlspecialchars($row['notes'] ?? ''),
                    'total_price' => (float)$row['total_price'],
                    'formatted_price' => number_format($row['total_price'], 0) . ' AED',
                    'submitted_date' => date('M j, Y', strtotime($row['submitted_at'])),
                    'submitted_time' => date('g:i A', strtotime($row['submitted_at'])),
                    'preferred_date_formatted' => date('M j, Y', strtotime($row['preferred_date'])),
                    'preferred_time_formatted' => date('g:i A', strtotime($row['preferred_time']))
                ];
            }
            
            logAdminActivity($admin['admin_id'], 'view_bookings', "Viewed bookings page $page");
            
            echo json_encode([
                'success' => true,
                'data' => $bookings,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalCount / $limit),
                    'total_count' => (int)$totalCount,
                    'per_page' => $limit,
                    'has_next' => $page < ceil($totalCount / $limit),
                    'has_prev' => $page > 1
                ],
                'filters' => [
                    'status' => $status,
                    'search' => $search,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'yacht_name' => $yachtName,
                    'charter_length' => $charterLength
                ]
            ]);
            break;
            
        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }
            
            $bookingId = intval($_POST['booking_id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if ($bookingId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
                exit();
            }
            
            $allowedStatuses = ['new', 'confirmed', 'completed', 'cancelled'];
            if (!in_array($newStatus, $allowedStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE yacht_bookings SET status = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("ssi", $newStatus, $notes, $bookingId);
            
            if ($stmt->execute()) {
                logAdminActivity($admin['admin_id'], 'update_booking_status', "Updated booking #$bookingId status to $newStatus");
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking status updated successfully',
                    'booking_id' => $bookingId,
                    'new_status' => $newStatus,
                    'notes' => $notes
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update booking status']);
            }
            break;
            
        case 'update_price':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }
            
            $bookingId = intval($_POST['booking_id'] ?? 0);
            $newPrice = floatval($_POST['total_price'] ?? 0);
            
            if ($bookingId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
                exit();
            }
            
            if ($newPrice < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid price']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE yacht_bookings SET total_price = ? WHERE id = ?");
            $stmt->bind_param("di", $newPrice, $bookingId);
            
            if ($stmt->execute()) {
                logAdminActivity($admin['admin_id'], 'update_booking_price', "Updated booking #$bookingId price to $newPrice AED");
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking price updated successfully',
                    'booking_id' => $bookingId,
                    'new_price' => $newPrice,
                    'formatted_price' => number_format($newPrice, 0) . ' AED'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update booking price']);
            }
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }
            
            $bookingId = intval($_POST['booking_id'] ?? 0);
            
            if ($bookingId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
                exit();
            }
            
            // Check if booking exists
            $checkStmt = $conn->prepare("SELECT id, full_name, email, yacht_name FROM yacht_bookings WHERE id = ?");
            $checkStmt->bind_param("i", $bookingId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                exit();
            }
            
            $bookingInfo = $checkResult->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM yacht_bookings WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            
            if ($stmt->execute()) {
                logAdminActivity($admin['admin_id'], 'delete_booking', "Deleted booking #$bookingId ({$bookingInfo['full_name']} - {$bookingInfo['yacht_name']})");
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking deleted successfully',
                    'booking_id' => $bookingId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete booking']);
            }
            break;
            
        case 'stats':
            // Get booking statistics
            $stats = [];
            
            // Total bookings
            $totalResult = $conn->query("SELECT COUNT(*) as total FROM yacht_bookings");
            $stats['total'] = (int)$totalResult->fetch_assoc()['total'];
            
            // Bookings by status
            $statusResult = $conn->query("SELECT status, COUNT(*) as count FROM yacht_bookings GROUP BY status");
            $stats['by_status'] = [];
            while ($row = $statusResult->fetch_assoc()) {
                $stats['by_status'][$row['status']] = (int)$row['count'];
            }
            
            // Recent bookings (last 7 days)
            $recentResult = $conn->query("SELECT COUNT(*) as count FROM yacht_bookings WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stats['recent'] = (int)$recentResult->fetch_assoc()['count'];
            
            // Total revenue
            $revenueResult = $conn->query("SELECT SUM(total_price) as total_revenue FROM yacht_bookings WHERE status IN ('confirmed', 'completed')");
            $stats['total_revenue'] = (float)($revenueResult->fetch_assoc()['total_revenue'] ?? 0);
            $stats['formatted_revenue'] = number_format($stats['total_revenue'], 0) . ' AED';
            
            // Bookings by yacht
            $yachtResult = $conn->query("SELECT yacht_name, COUNT(*) as count FROM yacht_bookings GROUP BY yacht_name ORDER BY count DESC");
            $stats['by_yacht'] = [];
            while ($row = $yachtResult->fetch_assoc()) {
                $stats['by_yacht'][] = [
                    'yacht_name' => $row['yacht_name'],
                    'count' => (int)$row['count']
                ];
            }
            
            // Bookings by charter length
            $charterResult = $conn->query("SELECT charter_length, COUNT(*) as count FROM yacht_bookings GROUP BY charter_length ORDER BY count DESC");
            $stats['by_charter_length'] = [];
            while ($row = $charterResult->fetch_assoc()) {
                $stats['by_charter_length'][] = [
                    'charter_length' => $row['charter_length'],
                    'count' => (int)$row['count']
                ];
            }
            
            // Bookings by day (last 30 days)
            $dailyResult = $conn->query("
                SELECT DATE(submitted_at) as date, COUNT(*) as count, SUM(total_price) as daily_revenue
                FROM yacht_bookings 
                WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(submitted_at)
                ORDER BY date DESC
            ");
            $stats['daily'] = [];
            while ($row = $dailyResult->fetch_assoc()) {
                $stats['daily'][] = [
                    'date' => $row['date'],
                    'count' => (int)$row['count'],
                    'revenue' => (float)($row['daily_revenue'] ?? 0)
                ];
            }
            
            // Upcoming bookings (next 30 days)
            $upcomingResult = $conn->query("
                SELECT COUNT(*) as count 
                FROM yacht_bookings 
                WHERE preferred_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND status IN ('new', 'confirmed')
            ");
            $stats['upcoming'] = (int)$upcomingResult->fetch_assoc()['count'];
            
            logAdminActivity($admin['admin_id'], 'view_booking_stats', 'Viewed booking statistics');
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'options':
            // Get dropdown options for filters
            $options = [];
            
            // Yacht names
            $yachtResult = $conn->query("SELECT DISTINCT yacht_name FROM yacht_bookings ORDER BY yacht_name");
            $options['yacht_names'] = [];
            while ($row = $yachtResult->fetch_assoc()) {
                $options['yacht_names'][] = $row['yacht_name'];
            }
            
            // Charter lengths
            $charterResult = $conn->query("SELECT DISTINCT charter_length FROM yacht_bookings ORDER BY charter_length");
            $options['charter_lengths'] = [];
            while ($row = $charterResult->fetch_assoc()) {
                $options['charter_lengths'][] = $row['charter_length'];
            }
            
            // Status options
            $options['statuses'] = ['new', 'confirmed', 'completed', 'cancelled'];
            
            echo json_encode([
                'success' => true,
                'options' => $options
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Booking Data API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request'
    ]);
} finally {
    $conn->close();
}
?>
