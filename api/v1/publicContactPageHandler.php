<?php
/**
 * Public Contact Page Content Handler API
 * Provides read-only access to contact page content for public consumption
 */

require_once 'config/_db.php';

// Set content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests for public API
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only GET requests are accepted.'
    ]);
    exit();
}

// Create contact_page_content table if it doesn't exist (with default content)
function createContactPageContentTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS contact_page_content (
        id INT PRIMARY KEY AUTO_INCREMENT,
        section VARCHAR(50) NOT NULL,
        title VARCHAR(255),
        subtitle VARCHAR(255),
        content TEXT,
        button_text VARCHAR(100),
        button_link VARCHAR(255),
        whatsapp_number VARCHAR(20),
        address TEXT,
        email VARCHAR(100),
        map_iframe TEXT,
        image_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section (section)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    
    // Insert default content if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM contact_page_content");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $defaultContent = [
            [
                'section' => 'hero',
                'title' => 'Embark on an Unforgettable Luxury',
                'subtitle' => 'Experience with Us',
                'button_text' => 'BOOK A YACHT',
                'button_link' => '#contact-form'
            ],
            [
                'section' => 'contact_info',
                'title' => 'CONTACT US FOR MORE INFO',
                'whatsapp_number' => '+971 58 186 2811'
            ],
            [
                'section' => 'form_section',
                'title' => 'Drop us message for business & query',
                'subtitle' => 'Fill the details'
            ],
            [
                'section' => 'location_info',
                'title' => 'GR YACHTS',
                'address' => 'Dubai Harbour, Dubai Marina, Dubai',
                'whatsapp_number' => '+971 58 186 2811',
                'email' => 'sales@gr-yachts.com'
            ],
            [
                'section' => 'map',
                'map_iframe' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4286.068757984126!2d55.14171589999999!3d25.092280099999993!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e5f6bf95c8a25d1%3A0x38632f3f01b35be4!2sDubai%20Harbour%20-%20Yacht%20Club!5e1!3m2!1sen!2sin!4v1757347257302!5m2!1sen!2sin'
            ]
        ];
        
        $stmt = $conn->prepare("INSERT INTO contact_page_content (section, title, subtitle, content, button_text, button_link, whatsapp_number, address, email, map_iframe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($defaultContent as $content) {
            // Prepare variables for bind_param (must be variables, not expressions)
            $section = $content['section'];
            $title = $content['title'] ?? null;
            $subtitle = $content['subtitle'] ?? null;
            $contentText = $content['content'] ?? null;
            $buttonText = $content['button_text'] ?? null;
            $buttonLink = $content['button_link'] ?? null;
            $whatsappNumber = $content['whatsapp_number'] ?? null;
            $address = $content['address'] ?? null;
            $email = $content['email'] ?? null;
            $mapIframe = $content['map_iframe'] ?? null;
            
            $stmt->bind_param("ssssssssss", 
                $section,
                $title,
                $subtitle,
                $contentText,
                $buttonText,
                $buttonLink,
                $whatsappNumber,
                $address,
                $email,
                $mapIframe
            );
            $stmt->execute();
        }
    }
}

// Initialize database
createContactPageContentTable($conn);

// Get action from request
$action = $_GET['action'] ?? 'getContactContent';

try {
    switch ($action) {
        case 'getContactContent':
            handleGetContactContent($conn);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function handleGetContactContent($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM contact_page_content ORDER BY section");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $content = [];
        while ($row = $result->fetch_assoc()) {
            $content[$row['section']] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $content
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to fetch contact page content: ' . $e->getMessage());
    }
}

$conn->close();
?>
