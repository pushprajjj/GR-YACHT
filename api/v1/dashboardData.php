<?php
/**
 * Dashboard Data API
 * Provides dashboard statistics and data
 */

require_once 'config/_db.php';
require_once 'jwt.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

$action = $_GET['action'] ?? 'stats';

try {
    switch ($action) {
        case 'stats':
            echo json_encode(getDashboardStats());
            break;
        
        case 'recent_contacts':
            echo json_encode(getRecentContacts());
            break;
        
        case 'recent_bookings':
            echo json_encode(getRecentBookings());
            break;
        
        case 'contacts':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            echo json_encode(getContacts($page, $limit));
            break;
        
        case 'bookings':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            echo json_encode(getBookings($page, $limit));
            break;
        
        case 'chart_data':
            $type = $_GET['type'] ?? 'bookings';
            $period = $_GET['period'] ?? '30';
            echo json_encode(getChartData($type, $period));
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Dashboard API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function getDashboardStats() {
    global $conn;
    
    // Get total contacts
    $contactsStmt = $conn->query("SELECT COUNT(*) as total FROM contacts");
    $totalContacts = $contactsStmt->fetch_assoc()['total'];
    
    // Get new contacts (last 30 days)
    $newContactsStmt = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $newContacts = $newContactsStmt->fetch_assoc()['count'];
    
    // Get contacts from previous 30 days for comparison
    $prevContactsStmt = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE submitted_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $prevContacts = $prevContactsStmt->fetch_assoc()['count'];
    $contactsChange = $prevContacts > 0 ? round((($newContacts - $prevContacts) / $prevContacts) * 100, 1) : 100;
    
    // Get total bookings
    $bookingsStmt = $conn->query("SELECT COUNT(*) as total FROM yacht_bookings");
    $totalBookings = $bookingsStmt->fetch_assoc()['total'];
    
    // Get new bookings (last 30 days)
    $newBookingsStmt = $conn->query("SELECT COUNT(*) as count FROM yacht_bookings WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $newBookings = $newBookingsStmt->fetch_assoc()['count'];
    
    // Get bookings from previous 30 days for comparison
    $prevBookingsStmt = $conn->query("SELECT COUNT(*) as count FROM yacht_bookings WHERE submitted_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $prevBookings = $prevBookingsStmt->fetch_assoc()['count'];
    $bookingsChange = $prevBookings > 0 ? round((($newBookings - $prevBookings) / $prevBookings) * 100, 1) : 100;
    
    // Get total revenue
    $revenueStmt = $conn->query("SELECT SUM(total_price) as total FROM yacht_bookings WHERE status IN ('confirmed', 'completed')");
    $totalRevenue = $revenueStmt->fetch_assoc()['total'] ?? 0;
    
    // Get revenue from last 30 days
    $newRevenueStmt = $conn->query("SELECT SUM(total_price) as total FROM yacht_bookings WHERE status IN ('confirmed', 'completed') AND submitted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $newRevenue = $newRevenueStmt->fetch_assoc()['total'] ?? 0;
    
    // Get revenue from previous 30 days for comparison
    $prevRevenueStmt = $conn->query("SELECT SUM(total_price) as total FROM yacht_bookings WHERE status IN ('confirmed', 'completed') AND submitted_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $prevRevenue = $prevRevenueStmt->fetch_assoc()['total'] ?? 0;
    $revenueChange = $prevRevenue > 0 ? round((($newRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 100;
    
    // Get pending items
    $pendingContactsStmt = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'");
    $pendingContacts = $pendingContactsStmt->fetch_assoc()['count'];
    
    $pendingBookingsStmt = $conn->query("SELECT COUNT(*) as count FROM yacht_bookings WHERE status = 'new'");
    $pendingBookings = $pendingBookingsStmt->fetch_assoc()['count'];
    
    return [
        'success' => true,
        'data' => [
            'contacts' => [
                'total' => $totalContacts,
                'change' => $contactsChange
            ],
            'bookings' => [
                'total' => $totalBookings,
                'change' => $bookingsChange
            ],
            'revenue' => [
                'total' => number_format($totalRevenue, 0),
                'change' => $revenueChange
            ],
            'pending' => [
                'total' => $pendingContacts + $pendingBookings,
                'contacts' => $pendingContacts,
                'bookings' => $pendingBookings
            ]
        ]
    ];
}

function getRecentContacts($limit = 5) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, name, email, LEFT(message, 50) as message_preview, submitted_at, status 
        FROM contacts 
        ORDER BY submitted_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $row['time_ago'] = timeAgo($row['submitted_at']);
        $contacts[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $contacts
    ];
}

function getRecentBookings($limit = 5) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, full_name, email, phone, preferred_date, preferred_time, 
               charter_length, yacht_name, total_price, status, submitted_at 
        FROM yacht_bookings 
        ORDER BY submitted_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $row['time_ago'] = timeAgo($row['submitted_at']);
        $row['formatted_date'] = date('M j, Y', strtotime($row['preferred_date']));
        $row['formatted_time'] = date('H:i', strtotime($row['preferred_time']));
        $bookings[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $bookings
    ];
}

function getContacts($page = 1, $limit = 20) {
    global $conn;
    
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $conn->query("SELECT COUNT(*) as total FROM contacts");
    $totalCount = $countStmt->fetch_assoc()['total'];
    
    // Get contacts
    $stmt = $conn->prepare("
        SELECT id, name, email, message, submitted_at, status, ip_address 
        FROM contacts 
        ORDER BY submitted_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $row['time_ago'] = timeAgo($row['submitted_at']);
        $row['message_preview'] = substr($row['message'], 0, 100) . (strlen($row['message']) > 100 ? '...' : '');
        $contacts[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $contacts,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $limit),
            'total_count' => $totalCount,
            'per_page' => $limit
        ]
    ];
}

function getBookings($page = 1, $limit = 20) {
    global $conn;
    
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $conn->query("SELECT COUNT(*) as total FROM yacht_bookings");
    $totalCount = $countStmt->fetch_assoc()['total'];
    
    // Get bookings
    $stmt = $conn->prepare("
        SELECT id, full_name, email, phone, preferred_date, preferred_time, 
               charter_length, yacht_name, total_price, status, submitted_at, ip_address 
        FROM yacht_bookings 
        ORDER BY submitted_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $row['time_ago'] = timeAgo($row['submitted_at']);
        $row['formatted_date'] = date('M j, Y', strtotime($row['preferred_date']));
        $row['formatted_time'] = date('H:i', strtotime($row['preferred_time']));
        $row['formatted_price'] = number_format($row['total_price'], 0) . ' AED';
        $bookings[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $bookings,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $limit),
            'total_count' => $totalCount,
            'per_page' => $limit
        ]
    ];
}

function getChartData($type, $period) {
    global $conn;
    
    $data = [];
    
    if ($type === 'bookings') {
        // Get bookings data for the specified period
        $stmt = $conn->prepare("
            SELECT DATE(submitted_at) as date, COUNT(*) as count 
            FROM yacht_bookings 
            WHERE submitted_at > DATE_SUB(NOW(), INTERVAL ? DAY) 
            GROUP BY DATE(submitted_at) 
            ORDER BY date ASC
        ");
        $stmt->bind_param("i", $period);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data['labels'] = [];
        $data['values'] = [];
        
        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = date('M j', strtotime($row['date']));
            $data['values'][] = (int)$row['count'];
        }
    } elseif ($type === 'status') {
        // Get booking status distribution
        $stmt = $conn->query("
            SELECT status, COUNT(*) as count 
            FROM yacht_bookings 
            GROUP BY status
        ");
        $result = $stmt->get_result();
        
        $data['labels'] = [];
        $data['values'] = [];
        
        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = ucfirst($row['status']);
            $data['values'][] = (int)$row['count'];
        }
    }
    
    return [
        'success' => true,
        'data' => $data
    ];
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

$conn->close();
?>
