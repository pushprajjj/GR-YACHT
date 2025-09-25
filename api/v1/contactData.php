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
                $whereConditions[] = "(name LIKE ? OR email LIKE ? OR message LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'sss';
            }
            
            if (!empty($dateFrom)) {
                $whereConditions[] = "submitted_at >= ?";
                $params[] = $dateFrom . ' 00:00:00';
                $types .= 's';
            }
            
            if (!empty($dateTo)) {
                $whereConditions[] = "submitted_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
                $types .= 's';
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM contacts $whereClause";
            if (!empty($params)) {
                $countStmt = $conn->prepare($countQuery);
                $countStmt->bind_param($types, ...$params);
                $countStmt->execute();
                $countResult = $countStmt->get_result();
            } else {
                $countResult = $conn->query($countQuery);
            }
            $totalCount = $countResult->fetch_assoc()['total'];
            
            // Get contacts data
            $query = "SELECT id, name, email, message, ip_address, submitted_at, status 
                     FROM contacts 
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
            
            $contacts = [];
            while ($row = $result->fetch_assoc()) {
                $contacts[] = [
                    'id' => (int)$row['id'],
                    'name' => htmlspecialchars($row['name']),
                    'email' => htmlspecialchars($row['email']),
                    'message' => htmlspecialchars($row['message']),
                    'ip_address' => $row['ip_address'],
                    'submitted_at' => $row['submitted_at'],
                    'status' => $row['status'],
                    'submitted_date' => date('M j, Y', strtotime($row['submitted_at'])),
                    'submitted_time' => date('g:i A', strtotime($row['submitted_at']))
                ];
            }
            
            logAdminActivity($admin['admin_id'], 'view_contacts', "Viewed contacts page $page");
            
            echo json_encode([
                'success' => true,
                'data' => $contacts,
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
                    'date_to' => $dateTo
                ]
            ]);
            break;
            
        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }
            
            $contactId = intval($_POST['contact_id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';
            
            if ($contactId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
                exit();
            }
            
            $allowedStatuses = ['new', 'read', 'replied', 'resolved'];
            if (!in_array($newStatus, $allowedStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE contacts SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $contactId);
            
            if ($stmt->execute()) {
                logAdminActivity($admin['admin_id'], 'update_contact_status', "Updated contact #$contactId status to $newStatus");
                echo json_encode([
                    'success' => true,
                    'message' => 'Contact status updated successfully',
                    'contact_id' => $contactId,
                    'new_status' => $newStatus
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update contact status']);
            }
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }
            
            $contactId = intval($_POST['contact_id'] ?? 0);
            
            if ($contactId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
                exit();
            }
            
            // Check if contact exists
            $checkStmt = $conn->prepare("SELECT id, name, email FROM contacts WHERE id = ?");
            $checkStmt->bind_param("i", $contactId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contact not found']);
                exit();
            }
            
            $contactInfo = $checkResult->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->bind_param("i", $contactId);
            
            if ($stmt->execute()) {
                logAdminActivity($admin['admin_id'], 'delete_contact', "Deleted contact #$contactId ({$contactInfo['name']} - {$contactInfo['email']})");
                echo json_encode([
                    'success' => true,
                    'message' => 'Contact deleted successfully',
                    'contact_id' => $contactId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete contact']);
            }
            break;
            
        case 'stats':
            // Get contact statistics
            $stats = [];
            
            // Total contacts
            $totalResult = $conn->query("SELECT COUNT(*) as total FROM contacts");
            $stats['total'] = (int)$totalResult->fetch_assoc()['total'];
            
            // Contacts by status
            $statusResult = $conn->query("SELECT status, COUNT(*) as count FROM contacts GROUP BY status");
            $stats['by_status'] = [];
            while ($row = $statusResult->fetch_assoc()) {
                $stats['by_status'][$row['status']] = (int)$row['count'];
            }
            
            // Recent contacts (last 7 days)
            $recentResult = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stats['recent'] = (int)$recentResult->fetch_assoc()['count'];
            
            // Contacts by day (last 30 days)
            $dailyResult = $conn->query("
                SELECT DATE(submitted_at) as date, COUNT(*) as count 
                FROM contacts 
                WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(submitted_at)
                ORDER BY date DESC
            ");
            $stats['daily'] = [];
            while ($row = $dailyResult->fetch_assoc()) {
                $stats['daily'][] = [
                    'date' => $row['date'],
                    'count' => (int)$row['count']
                ];
            }
            
            logAdminActivity($admin['admin_id'], 'view_contact_stats', 'Viewed contact statistics');
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Contact Data API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request'
    ]);
} finally {
    $conn->close();
}
?>
