<?php
/**
 * Admin Authentication API
 * JWT-based authentication for admin users
 */

require_once 'config/_db.php';
require_once 'config/_config.php';
require_once 'jwt.php';


/**
 * Log admin activity
 */
function logAdminActivity($adminId, $action, $details = '') {
    global $conn;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issss", $adminId, $action, $details, $ipAddress, $userAgent);
    $stmt->execute();
}

/**
 * Update last login time
 */
function updateLastLogin($adminId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
}

/**
 * Check rate limiting for login attempts
 */
function checkLoginRateLimit($identifier, $returnData = false) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM admin_login_attempts WHERE identifier = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempts = $result->fetch_assoc()['attempts'];
    
    $maxAttempts = 5; // Max 5 attempts per 15 minutes
    $remainingAttempts = max(0, $maxAttempts - $attempts);
    $isAllowed = $attempts < $maxAttempts;
    
    if ($returnData) {
        return [
            'allowed' => $isAllowed,
            'attempts_made' => (int)$attempts,
            'max_attempts' => $maxAttempts,
            'remaining_attempts' => $remainingAttempts,
            'lockout_duration' => 15, // minutes
            'window_description' => '15 minutes'
        ];
    }
    
    return $isAllowed;
}

/**
 * Record login attempt
 */
function recordLoginAttempt($identifier, $success = false) {
    global $conn;
    
    $successInt = $success ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO admin_login_attempts (identifier, success, attempted_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("si", $identifier, $successInt);
    $stmt->execute();
}

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'check_attempts':
        // Return current login attempts status
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attemptData = checkLoginRateLimit($identifier, true);
        
        echo json_encode([
            'success' => true,
            'data' => $attemptData
        ]);
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and password are required']);
            exit();
        }

        // Check rate limiting
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attemptData = checkLoginRateLimit($identifier, true);
        
        if (!$attemptData['allowed']) {
            http_response_code(429);
            echo json_encode([
                'success' => false, 
                'message' => 'Too many login attempts. Please try again later.',
                'attempts_data' => $attemptData
            ]);
            exit();
        }

        // Check admin credentials
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, full_name, role, is_active FROM admins WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($admin = $result->fetch_assoc()) {
            if (password_verify($password, $admin['password_hash'])) {
                // Generate JWT token
                $token = JWT::generateAdminToken($admin);
                
                if ($token) {
                    updateLastLogin($admin['id']);
                    recordLoginAttempt($identifier, true);
                    logAdminActivity($admin['id'], 'login', 'Successful login');
                    // Clear old login attempts on successful login
                    $stmt = $conn->prepare("DELETE FROM admin_login_attempts WHERE identifier = ?");    
                    $stmt->bind_param("s", $identifier);
                    $stmt->execute();   
                    http_response_code(200);
                    
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'admin' => [
                            'id' => $admin['id'],
                            'username' => $admin['username'],
                            'email' => $admin['email'],
                            'full_name' => $admin['full_name'],
                            'role' => $admin['role']
                        ],
                        'token' => $token,
                        'expires_in' => 24 * 3600 // 24 hours in seconds
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to generate token']);
                }
            } else {
                recordLoginAttempt($identifier, false);
                // Get updated attempt data after recording the failed attempt
                $updatedAttemptData = checkLoginRateLimit($identifier, true);
                http_response_code(401);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid credentials',
                    'attempts_data' => $updatedAttemptData
                ]);
            }
        } else {
            recordLoginAttempt($identifier, false);
            // Get updated attempt data after recording the failed attempt
            $updatedAttemptData = checkLoginRateLimit($identifier, true);
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid credentials',
                'attempts_data' => $updatedAttemptData
            ]);
        }
        break;

    case 'logout':
        $token = JWT::getValidToken();
        
        if ($token) {
            $payload = JWT::decode($token);
            if ($payload && isset($payload['admin_id'])) {
                logAdminActivity($payload['admin_id'], 'logout', 'User logged out');
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        break;

    case 'verify':
        $token = JWT::getValidToken();
        
        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No token provided']);
            exit();
        }

        $admin = JWT::verifyAdminToken($token);
        
        if ($admin) {
            echo json_encode([
                'success' => true,
                'admin' => [
                    'id' => $admin['admin_id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        }
        break;

    case 'refresh':
        $token = JWT::getValidToken();
        
        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No token provided']);
            exit();
        }

        $newToken = JWT::refreshToken($token);
        
        if ($newToken) {
            $admin = JWT::verifyAdminToken($newToken);
            echo json_encode([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'token' => $newToken,
                'expires_in' => 24 * 3600,
                'admin' => [
                    'id' => $admin['admin_id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
        }
        break;

    case 'change_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        $token = JWT::getValidToken();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($token) || empty($currentPassword) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }

        $admin = JWT::verifyAdminToken($token);
        if (!$admin) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit();
        }

        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentAdmin = $result->fetch_assoc();

        if (!password_verify($currentPassword, $currentAdmin['password_hash'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit();
        }

        // Validate new password strength
        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
            exit();
        }

        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newPasswordHash, $admin['admin_id']);
        
        if ($stmt->execute()) {
            logAdminActivity($admin['admin_id'], 'password_change', 'Password changed successfully');
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update password']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
?>
