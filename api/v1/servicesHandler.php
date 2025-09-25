<?php
/**
 * Services Page Content Management API
 * Handles CRUD operations for services page content and services
 */

require_once 'config/_db.php';
require_once 'config/_config.php';
require_once 'jwt.php';

// Set content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Create services_content table if it doesn't exist
function createServicesContentTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS services_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section VARCHAR(50) NOT NULL,
        title VARCHAR(255),
        subtitle VARCHAR(255),
        content TEXT,
        image_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section (section)
    )";
    
    if ($conn->query($sql) === FALSE) {
        error_log("Error creating services_content table: " . $conn->error);
    }
}

// Create services table if it doesn't exist
function createServicesTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        filename VARCHAR(255),
        image_path VARCHAR(255),
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        error_log("Error creating services table: " . $conn->error);
    }
}

// Initialize tables
createServicesContentTable($conn);
createServicesTable($conn);

// Handle file upload
function handleImageUpload($file, $uploadDir, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $imageSize = $file['size'];
    if ($imageSize > 5 * 1024 * 1024) { // 5MB limit
        throw new Exception('Image size should be less than 5MB');
    }

    $imageExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($imageExtension, $allowedExtensions)) {
        throw new Exception('Invalid image format. Allowed: JPG, JPEG, PNG, GIF, WEBP');
    }

    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $newImageName = $prefix . uniqid() . '_' . time() . '.' . $imageExtension;
    $uploadPath = $uploadDir . $newImageName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $newImageName;
    } else {
        throw new Exception('Failed to upload image');
    }
}

// Delete image file
function deleteImage($imageName, $uploadDir) {
    if ($imageName && file_exists($uploadDir . $imageName)) {
        unlink($uploadDir . $imageName);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($_POST['action'] === 'updateHeroSection') {
        try {
            // Validate required fields
            if (empty($_POST['heroTitle'])) {
                throw new Exception('Hero title is required');
            }

            $uploadDir = '../uploads/services/';
            
            // Process hero section
            $heroTitle = trim($_POST['heroTitle']);
            $heroTitleBold = trim($_POST['heroTitlebold'] ?? '');
            $heroDescription = trim($_POST['heroDescription'] ?? '');
            $heroButtonText = trim($_POST['heroButtonText'] ?? '');
            $heroButtonLink = trim($_POST['heroButtonLink'] ?? '');
            
            // Get existing hero image to preserve if no new one uploaded
            $existingHeroImage = null;
            $stmt = $conn->prepare("SELECT image_url FROM services_content WHERE section = 'hero'");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $currentHero = $result->fetch_assoc();
                $existingHeroImage = $currentHero['image_url'];
            }
            
            $heroImageUrl = $existingHeroImage; // Keep existing by default
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Delete old image if exists
                if ($existingHeroImage) {
                    deleteImage($existingHeroImage, $uploadDir);
                }
                $heroImageUrl = handleImageUpload($_FILES['image'], $uploadDir, 'hero_');
            }
            
            // Create hero content JSON
            $heroContent = json_encode([
                'titleBold' => $heroTitleBold,
                'description' => $heroDescription,
                'buttonText' => $heroButtonText,
                'buttonLink' => $heroButtonLink
            ]);
            
            // Update or insert hero section
            if ($heroImageUrl) {
                $stmt = $conn->prepare("INSERT INTO services_content (section, title, content, image_url) VALUES ('hero', ?, ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?, image_url = ?");
                $stmt->bind_param("ssssss", $heroTitle, $heroContent, $heroImageUrl, $heroTitle, $heroContent, $heroImageUrl);
            } else {
                $stmt = $conn->prepare("INSERT INTO services_content (section, title, content) VALUES ('hero', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
                $stmt->bind_param("ssss", $heroTitle, $heroContent, $heroTitle, $heroContent);
            }
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Services hero section updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } 
    elseif ($_POST['action'] === 'updateServicesSection') {
        try {
            $sectionTitle = trim($_POST['sectionTitle'] ?? '');
            $sectionDescription = trim($_POST['sectionDescription'] ?? '');
            
            $sectionContent = json_encode([
                'description' => $sectionDescription
            ]);
            
            if ($sectionTitle) {
                $stmt = $conn->prepare("INSERT INTO services_content (section, title, content) VALUES ('services_section', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
                $stmt->bind_param("ssss", $sectionTitle, $sectionContent, $sectionTitle, $sectionContent);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'Services section updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'addService') {
        try {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title)) {
                throw new Exception('Service title is required');
            }
            
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Service image is required');
            }
            
            $uploadDir = '../uploads/eventGallery/';
            $filename = handleImageUpload($_FILES['image'], $uploadDir, 'service_');
            $imagePath = 'uploads/eventGallery/' . $filename;
            
            // Get next sort order
            $stmt = $conn->prepare("SELECT MAX(sort_order) as max_order FROM services");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $sortOrder = ($row['max_order'] ?? 0) + 1;
            
            $stmt = $conn->prepare("INSERT INTO services (title, description, filename, image_path, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $title, $description, $filename, $imagePath, $sortOrder);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Service added successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'updateService') {
        try {
            $serviceId = intval($_POST['service_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($serviceId <= 0) {
                throw new Exception('Invalid service ID');
            }
            
            if (empty($title)) {
                throw new Exception('Service title is required');
            }
            
            // Get existing service data
            $stmt = $conn->prepare("SELECT filename FROM services WHERE id = ?");
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Service not found');
            }
            
            $currentService = $result->fetch_assoc();
            $filename = $currentService['filename'];
            $imagePath = 'uploads/eventGallery/' . $filename;
            
            // Handle image update if new image provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/eventGallery/';
                
                // Delete old image
                if ($currentService['filename']) {
                    deleteImage($currentService['filename'], $uploadDir);
                }
                
                $filename = handleImageUpload($_FILES['image'], $uploadDir, 'service_');
                $imagePath = 'uploads/eventGallery/' . $filename;
            }
            
            $stmt = $conn->prepare("UPDATE services SET title = ?, description = ?, filename = ?, image_path = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $title, $description, $filename, $imagePath, $serviceId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Service updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'deleteService') {
        try {
            $serviceId = intval($_POST['service_id'] ?? 0);
            
            if ($serviceId <= 0) {
                throw new Exception('Invalid service ID');
            }
            
            // Get service data to delete image
            $stmt = $conn->prepare("SELECT filename FROM services WHERE id = ?");
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Service not found');
            }
            
            $service = $result->fetch_assoc();
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();
            
            // Delete image file
            if ($service['filename']) {
                deleteImage($service['filename'], '../uploads/eventGallery/');
            }
            
            echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (isset($_GET['action']) && $_GET['action'] === 'getHeroSection') {
        try {
            $stmt = $conn->prepare("SELECT * FROM services_content WHERE section = 'hero'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $heroData = null;
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $heroData = [
                    'title' => $row['title'],
                    'imageUrl' => $row['image_url'] ? 'api/uploads/services/' . $row['image_url'] : null
                ];
                
                // Parse JSON content if it exists
                if ($row['content']) {
                    $jsonContent = json_decode($row['content'], true);
                    if ($jsonContent) {
                        $heroData = array_merge($heroData, $jsonContent);
                    }
                }
            }
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'homePageContent' => $heroData
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif (isset($_GET['action']) && $_GET['action'] === 'getServicesContent') {
        try {
            $stmt = $conn->prepare("SELECT * FROM services_content ORDER BY section");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $content = [];
            while ($row = $result->fetch_assoc()) {
                $sectionData = [
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'],
                    'image_url' => $row['image_url'],
                    'imagePath' => $row['image_url'] // For compatibility with frontend
                ];
                
                // Parse JSON content if it exists
                if ($row['content']) {
                    $jsonContent = json_decode($row['content'], true);
                    if ($jsonContent) {
                        $sectionData = array_merge($sectionData, $jsonContent);
                    } else {
                        $sectionData['content'] = $row['content'];
                    }
                }
                
                $content[$row['section']] = $sectionData;
            }
            
            echo json_encode(['success' => true, 'data' => $content]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif (isset($_GET['action']) && $_GET['action'] === 'getServices') {
        try {
            $stmt = $conn->prepare("SELECT * FROM services WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $services = [];
            while ($row = $result->fetch_assoc()) {
                $services[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'filename' => $row['filename'],
                    'image_path' => $row['image_path'],
                    'sort_order' => $row['sort_order'],
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $services]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
