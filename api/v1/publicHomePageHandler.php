<?php
/**
 * Public Home Page Content API
 * Serves home page content to the public website (no authentication required)
 */

// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/_db.php';
require_once 'config/_config.php';

// Remove authentication requirement for public API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

/**
 * Ensure tables exist with fallback creation
 */
function ensureTablesExist() {
    global $conn;
    
    // Check if home_page_content table exists
    $result = $conn->query("SHOW TABLES LIKE 'home_page_content'");
    if ($result->num_rows == 0) {
        // Create table if it doesn't exist
        $sql = "CREATE TABLE home_page_content (
            id INT PRIMARY KEY AUTO_INCREMENT,
            section VARCHAR(50) NOT NULL,
            content_key VARCHAR(100) NOT NULL,
            content_value TEXT,
            image_path VARCHAR(255) NULL,
            display_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_section_key (section, content_key)
        )";
        $conn->query($sql);
    }
    
    // Check if home_yacht_fleet table exists
    $result = $conn->query("SHOW TABLES LIKE 'home_yacht_fleet'");
    if ($result->num_rows == 0) {
        // Create table if it doesn't exist
        $sql = "CREATE TABLE home_yacht_fleet (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            price VARCHAR(100) NOT NULL,
            image_path VARCHAR(255) NULL,
            display_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
    }
}

/**
 * Get home page content for public display
 */
function getPublicHomePageContent() {
    global $conn;
    
    // Ensure tables exist
    ensureTablesExist();
    
    // Get all content
    $stmt = $conn->prepare("SELECT section, content_key, content_value, image_path FROM home_page_content WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $content = [];
    while ($row = $result->fetch_assoc()) {
        $content[$row['section']][$row['content_key']] = [
            'value' => $row['content_value'],
            'image_path' => $row['image_path']
        ];
    }
    
    // Get yacht fleet
    $stmt = $conn->prepare("SELECT name, price, image_path FROM home_yacht_fleet WHERE is_active = 1 ORDER BY display_order");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $yachts = [];
    while ($row = $result->fetch_assoc()) {
        $yachts[] = [
            'name' => $row['name'],
            'price' => $row['price'],
            'image' => $row['image_path']
        ];
    }
    
    // Structure the response for public consumption - only return data that exists
    $response = [
        'hero' => [
            'title' => $content['hero']['title']['value'] ?? null,
            'subtitle' => $content['hero']['subtitle']['value'] ?? null,
            'button_text' => $content['hero']['button_text']['value'] ?? null,
            'button_link' => $content['hero']['button_link']['value'] ?? null,
            'background_image' => $content['hero']['background_image']['image_path'] ?? $content['hero']['background_image']['value'] ?? null
        ],
        'fleet' => [
            'title' => $content['fleet']['title']['value'] ?? null,
            'subtitle' => $content['fleet']['description']['value'] ?? null,
            'yachts' => $yachts
        ],
        'about' => [
            'title' => $content['about']['title']['value'] ?? null,
            'description' => $content['about']['description']['value'] ?? null,
            'button_text' => $content['about']['button_text']['value'] ?? null,
            'button_link' => $content['about']['button_link']['value'] ?? null
        ],
        'services' => [
            'title' => $content['services']['title']['value'] ?? null,
            'description' => $content['services']['description']['value'] ?? null,
            'button_text' => $content['services']['button_text']['value'] ?? null,
            'button_link' => $content['services']['button_link']['value'] ?? null
        ],
        'contact' => [
            'title' => $content['contact']['title']['value'] ?? null,
            'description' => $content['contact']['description']['value'] ?? null,
            'phone' => $content['contact']['phone']['value'] ?? null,
            'email' => $content['contact']['email']['value'] ?? null,
            'address' => $content['contact']['address']['value'] ?? null
        ]
    ];
    
    return $response;
}

// Handle different actions
$action = $_GET['action'] ?? 'content';

switch ($action) {
    case 'content':
        // Get home page content for public display
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }
        
        try {
            $content = getPublicHomePageContent();
            echo json_encode([
                'success' => true,
                'data' => $content
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load content'
            ]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
?>
