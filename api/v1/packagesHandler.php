<?php
/**
 * Packages Page Content Management API
 * Handles CRUD operations for packages page content and package items
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

// Create packages_content table if it doesn't exist
function createPackagesContentTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS packages_content (
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
        error_log("Error creating packages_content table: " . $conn->error);
    }
}

// Create packages table if it doesn't exist
function createPackagesTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price VARCHAR(100),
        filename VARCHAR(255),
        image_path VARCHAR(255),
        book_button_text VARCHAR(255),
        book_button_link VARCHAR(500),
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        is_featured TINYINT(1) DEFAULT 0,
        package_type ENUM('main', 'sidebar') DEFAULT 'sidebar',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        error_log("Error creating packages table: " . $conn->error);
    }
    
    // Add is_featured column if it doesn't exist (for existing installations)
    $conn->query("ALTER TABLE packages ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE packages ADD COLUMN IF NOT EXISTS book_button_text VARCHAR(255)");
    $conn->query("ALTER TABLE packages ADD COLUMN IF NOT EXISTS book_button_link VARCHAR(500)");
}

// Create package_features table for main package features
function createPackageFeaturesTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS package_features (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_id INT,
        feature_title VARCHAR(255) NOT NULL,
        feature_description TEXT,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === FALSE) {
        error_log("Error creating package_features table: " . $conn->error);
    }
}

// Initialize tables
createPackagesContentTable($conn);
createPackagesTable($conn);
createPackageFeaturesTable($conn);

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

            $uploadDir = '../uploads/packages/';
            
            // Process hero section
            $heroTitle = trim($_POST['heroTitle']);
            $heroButtonText = trim($_POST['heroButtonText'] ?? '');
            $heroButtonLink = trim($_POST['heroButtonLink'] ?? '');
            
            // Get existing hero image to preserve if no new one uploaded
            $existingHeroImage = null;
            $stmt = $conn->prepare("SELECT image_url FROM packages_content WHERE section = 'hero'");
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
                'buttonText' => $heroButtonText,
                'buttonLink' => $heroButtonLink
            ]);
            
            // Update or insert hero section
            if ($heroImageUrl) {
                $stmt = $conn->prepare("INSERT INTO packages_content (section, title, content, image_url) VALUES ('hero', ?, ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?, image_url = ?");
                $stmt->bind_param("ssssss", $heroTitle, $heroContent, $heroImageUrl, $heroTitle, $heroContent, $heroImageUrl);
            } else {
                $stmt = $conn->prepare("INSERT INTO packages_content (section, title, content) VALUES ('hero', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
                $stmt->bind_param("ssss", $heroTitle, $heroContent, $heroTitle, $heroContent);
            }
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Packages hero section updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'updateMainPackage') {
        try {
            $title = trim($_POST['title'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $bookButtonText = trim($_POST['bookButtonText'] ?? '');
            $bookButtonLink = trim($_POST['bookButtonLink'] ?? '');
            
            if (empty($title)) {
                throw new Exception('Package title is required');
            }
            
            // Get existing main package
            $stmt = $conn->prepare("SELECT * FROM packages WHERE package_type = 'main' LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            $existingPackage = $result->fetch_assoc();
            
            $uploadDir = '../uploads/packages/';
            $filename = $existingPackage['filename'] ?? null;
            $imagePath = $existingPackage['image_path'] ?? null;
            
            // Handle image update if new image provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Delete old image
                if ($existingPackage && $existingPackage['filename']) {
                    deleteImage($existingPackage['filename'], $uploadDir);
                }
                
                $filename = handleImageUpload($_FILES['image'], $uploadDir, 'main_package_');
                $imagePath = 'uploads/packages/' . $filename;
            }
            
            // Create additional content JSON
            $additionalContent = json_encode([
                'bookButtonText' => $bookButtonText,
                'bookButtonLink' => $bookButtonLink
            ]);
            
            if ($existingPackage) {
                // Update existing main package
                $stmt = $conn->prepare("UPDATE packages SET title = ?, description = ?, price = ?, filename = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $title, $description, $price, $filename, $imagePath, $existingPackage['id']);
                $stmt->execute();
                $packageId = $existingPackage['id'];
            } else {
                // Create new main package
                $stmt = $conn->prepare("INSERT INTO packages (title, description, price, filename, image_path, package_type, sort_order) VALUES (?, ?, ?, ?, ?, 'main', 1)");
                $stmt->bind_param("sssss", $title, $description, $price, $filename, $imagePath);
                $stmt->execute();
                $packageId = $conn->insert_id;
            }
            
            // Store additional content in packages_content table
            $stmt = $conn->prepare("INSERT INTO packages_content (section, title, content) VALUES ('main_package', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
            $stmt->bind_param("ssss", $title, $additionalContent, $title, $additionalContent);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Main package updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'addPackage') {
        try {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $bookButtonText = trim($_POST['bookButtonText'] ?? '');
            $bookButtonLink = trim($_POST['bookButtonLink'] ?? '');
            $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
            
            if (empty($title)) {
                throw new Exception('Package title is required');
            }
            
            // If this is being set as featured, unset all other featured packages
            if ($isFeatured) {
                $stmt = $conn->prepare("UPDATE packages SET is_featured = 0");
                $stmt->execute();
            }
            
            $uploadDir = '../uploads/packages/';
            $filename = '';
            $imagePath = '';
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $filename = handleImageUpload($_FILES['image'], $uploadDir, 'package_');
                $imagePath = 'uploads/packages/' . $filename;
            }
            
            // Get next sort order
            $stmt = $conn->prepare("SELECT MAX(sort_order) as max_order FROM packages");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $sortOrder = ($row['max_order'] ?? 0) + 1;
            
            $stmt = $conn->prepare("INSERT INTO packages (title, description, price, filename, image_path, book_button_text, book_button_link, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssii", $title, $description, $price, $filename, $imagePath, $bookButtonText, $bookButtonLink, $isFeatured, $sortOrder);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Package added successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'addSidebarPackage') {
        try {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title)) {
                throw new Exception('Package title is required');
            }
            
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Package image is required');
            }
            
            $uploadDir = '../uploads/packages/';
            $filename = handleImageUpload($_FILES['image'], $uploadDir, 'sidebar_package_');
            $imagePath = 'uploads/packages/' . $filename;
            
            // Get next sort order
            $stmt = $conn->prepare("SELECT MAX(sort_order) as max_order FROM packages WHERE package_type = 'sidebar'");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $sortOrder = ($row['max_order'] ?? 0) + 1;
            
            $stmt = $conn->prepare("INSERT INTO packages (title, description, filename, image_path, package_type, sort_order) VALUES (?, ?, ?, ?, 'sidebar', ?)");
            $stmt->bind_param("ssssi", $title, $description, $filename, $imagePath, $sortOrder);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Sidebar package added successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'updatePackage') {
        try {
            $packageId = intval($_POST['package_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $bookButtonText = trim($_POST['bookButtonText'] ?? '');
            $bookButtonLink = trim($_POST['bookButtonLink'] ?? '');
            $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
            
            if ($packageId <= 0) {
                throw new Exception('Invalid package ID');
            }
            
            if (empty($title)) {
                throw new Exception('Package title is required');
            }
            
            // If this is being set as featured, unset all other featured packages
            if ($isFeatured) {
                $stmt = $conn->prepare("UPDATE packages SET is_featured = 0");
                $stmt->execute();
            }
            
            // Get existing package data
            $stmt = $conn->prepare("SELECT filename FROM packages WHERE id = ?");
            $stmt->bind_param("i", $packageId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Package not found');
            }
            
            $currentPackage = $result->fetch_assoc();
            $filename = $currentPackage['filename'];
            $imagePath = $filename ? 'uploads/packages/' . $filename : '';
            
            // Handle image update if new image provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/packages/';
                
                // Delete old image
                if ($currentPackage['filename']) {
                    deleteImage($currentPackage['filename'], $uploadDir);
                }
                
                $filename = handleImageUpload($_FILES['image'], $uploadDir, 'package_');
                $imagePath = 'uploads/packages/' . $filename;
            }
            
            $stmt = $conn->prepare("UPDATE packages SET title = ?, description = ?, price = ?, filename = ?, image_path = ?, book_button_text = ?, book_button_link = ?, is_featured = ? WHERE id = ?");
            $stmt->bind_param("sssssssii", $title, $description, $price, $filename, $imagePath, $bookButtonText, $bookButtonLink, $isFeatured, $packageId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Package updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'setFeaturedPackage') {
        try {
            $packageId = intval($_POST['package_id'] ?? 0);
            
            if ($packageId <= 0) {
                throw new Exception('Invalid package ID');
            }
            
            // Unset all featured packages first
            $stmt = $conn->prepare("UPDATE packages SET is_featured = 0");
            $stmt->execute();
            
            // Set this package as featured
            $stmt = $conn->prepare("UPDATE packages SET is_featured = 1 WHERE id = ?");
            $stmt->bind_param("i", $packageId);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Package not found');
            }
            
            echo json_encode(['success' => true, 'message' => 'Package set as featured successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'updateSidebarPackage') {
        try {
            $packageId = intval($_POST['package_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($packageId <= 0) {
                throw new Exception('Invalid package ID');
            }
            
            if (empty($title)) {
                throw new Exception('Package title is required');
            }
            
            // Get existing package data
            $stmt = $conn->prepare("SELECT filename FROM packages WHERE id = ? AND package_type = 'sidebar'");
            $stmt->bind_param("i", $packageId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Package not found');
            }
            
            $currentPackage = $result->fetch_assoc();
            $filename = $currentPackage['filename'];
            $imagePath = 'uploads/packages/' . $filename;
            
            // Handle image update if new image provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/packages/';
                
                // Delete old image
                if ($currentPackage['filename']) {
                    deleteImage($currentPackage['filename'], $uploadDir);
                }
                
                $filename = handleImageUpload($_FILES['image'], $uploadDir, 'sidebar_package_');
                $imagePath = 'uploads/packages/' . $filename;
            }
            
            $stmt = $conn->prepare("UPDATE packages SET title = ?, description = ?, filename = ?, image_path = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $title, $description, $filename, $imagePath, $packageId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Package updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($_POST['action'] === 'deleteSidebarPackage') {
        try {
            $packageId = intval($_POST['package_id'] ?? 0);
            
            if ($packageId <= 0) {
                throw new Exception('Invalid package ID');
            }
            
            // Get package data to delete image
            $stmt = $conn->prepare("SELECT filename FROM packages WHERE id = ? AND package_type = 'sidebar'");
            $stmt->bind_param("i", $packageId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Package not found');
            }
            
            $package = $result->fetch_assoc();
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
            $stmt->bind_param("i", $packageId);
            $stmt->execute();
            
            // Delete image file
            if ($package['filename']) {
                deleteImage($package['filename'], '../uploads/packages/');
            }
            
            echo json_encode(['success' => true, 'message' => 'Package deleted successfully']);
            
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
            $stmt = $conn->prepare("SELECT * FROM packages_content WHERE section = 'hero'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $heroData = null;
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $heroData = [
                    'title' => $row['title'],
                    'imageUrl' => $row['image_url'] ? 'api/uploads/packages/' . $row['image_url'] : null
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
            echo json_encode(['success' => false, 'message' => 'Error loading hero section']);
        }
    }
    elseif (isset($_GET['action']) && $_GET['action'] === 'getMainPackage') {
        try {
            $stmt = $conn->prepare("SELECT * FROM packages WHERE package_type = 'main' LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $packageData = null;
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $packageData = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'filename' => $row['filename'],
                    'image_path' => $row['image_path']
                ];
            }
            
            // Get additional content from packages_content
            $stmt = $conn->prepare("SELECT * FROM packages_content WHERE section = 'main_package'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $contentRow = $result->fetch_assoc();
                if ($contentRow['content']) {
                    $jsonContent = json_decode($contentRow['content'], true);
                    if ($jsonContent && $packageData) {
                        $packageData = array_merge($packageData, $jsonContent);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'data' => $packageData]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error loading main package']);
        }
    }
    elseif (isset($_GET['action']) && $_GET['action'] === 'getAllPackages') {
        try {
            $stmt = $conn->prepare("SELECT * FROM packages WHERE status = 'active' ORDER BY is_featured DESC, sort_order ASC, created_at DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $packages = [];
            while ($row = $result->fetch_assoc()) {
                $packages[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'filename' => $row['filename'],
                    'image_path' => $row['image_path'],
                    'book_button_text' => $row['book_button_text'],
                    'book_button_link' => $row['book_button_link'],
                    'is_featured' => $row['is_featured'],
                    'sort_order' => $row['sort_order']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $packages]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error loading packages']);
        }
    }
    elseif (isset($_GET['action']) && $_GET['action'] === 'getSidebarPackages') {
        try {
            $stmt = $conn->prepare("SELECT * FROM packages WHERE package_type = 'sidebar' AND status = 'active' ORDER BY sort_order ASC, created_at DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $packages = [];
            while ($row = $result->fetch_assoc()) {
                $packages[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'filename' => $row['filename'],
                    'image_path' => $row['image_path'],
                    'sort_order' => $row['sort_order']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $packages]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error loading sidebar packages']);
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
