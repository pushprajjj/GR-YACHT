<?php
/**
 * About Page Content Management API
 * Handles CRUD operations for about page content
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

// Create about_content table if it doesn't exist
function createAboutContentTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS about_content (
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
        error_log("Error creating about_content table: " . $conn->error);
    }
}

// Initialize table
createAboutContentTable($conn);

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
    
    if ($_POST['action'] === 'updateAboutContent') {
        try {
            // Validate required fields
            if (empty($_POST['heroTitle'])) {
                throw new Exception('Hero title is required');
            }

            $uploadDir = '../uploads/about/';
            
            // Process hero section
            $heroTitle = trim($_POST['heroTitle']);
            $heroTitleBold = trim($_POST['heroTitleBold'] ?? '');
            $heroButtonText = trim($_POST['heroButtonText'] ?? '');
            $heroButtonLink = trim($_POST['heroButtonLink'] ?? '');
            
            // Get existing hero image to preserve if no new one uploaded
            $existingHeroImage = null;
            $stmt = $conn->prepare("SELECT image_url FROM about_content WHERE section = 'hero'");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $currentHero = $result->fetch_assoc();
                $existingHeroImage = $currentHero['image_url'];
            }
            
            $heroImageUrl = $existingHeroImage; // Keep existing by default
            if (isset($_FILES['heroImage']) && $_FILES['heroImage']['error'] === UPLOAD_ERR_OK) {
                // Delete old image if exists
                if ($existingHeroImage) {
                    deleteImage($existingHeroImage, $uploadDir);
                }
                $heroImageUrl = handleImageUpload($_FILES['heroImage'], $uploadDir, 'hero_');
            }
            
            // Create hero content JSON
            $heroContent = json_encode([
                'titleBold' => $heroTitleBold,
                'buttonText' => $heroButtonText,
                'buttonLink' => $heroButtonLink
            ]);
            
            // Update or insert hero section
            if ($heroImageUrl) {
                $stmt = $conn->prepare("INSERT INTO about_content (section, title, content, image_url) VALUES ('hero', ?, ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?, image_url = ?");
                $stmt->bind_param("ssssss", $heroTitle, $heroContent, $heroImageUrl, $heroTitle, $heroContent, $heroImageUrl);
            } else {
                $stmt = $conn->prepare("INSERT INTO about_content (section, title, content) VALUES ('hero', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
                $stmt->bind_param("ssss", $heroTitle, $heroContent, $heroTitle, $heroContent);
            }
            $stmt->execute();
            
            // Process statistics section
            $statisticsTitle = trim($_POST['statisticsTitle'] ?? '');
            $stats1Title = trim($_POST['stats1Title'] ?? '');
            $stats1Number = trim($_POST['stats1Number'] ?? '');
            $stats2Title = trim($_POST['stats2Title'] ?? '');
            $stats2Number = trim($_POST['stats2Number'] ?? '');
            $stats3Title = trim($_POST['stats3Title'] ?? '');
            $stats3Number = trim($_POST['stats3Number'] ?? '');
            
            $statisticsContent = json_encode([
                'stat1Title' => $stats1Title,
                'stat1Number' => $stats1Number,
                'stat2Title' => $stats2Title,
                'stat2Number' => $stats2Number,
                'stat3Title' => $stats3Title,
                'stat3Number' => $stats3Number
            ]);
            
            if ($statisticsTitle) {
                $stmt = $conn->prepare("INSERT INTO about_content (section, title, content) VALUES ('statistics', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
                $stmt->bind_param("ssss", $statisticsTitle, $statisticsContent, $statisticsTitle, $statisticsContent);
                $stmt->execute();
            }
            
            // Process vision section
            $visionTitle = trim($_POST['visionTitle'] ?? '');
            $visionContent = trim($_POST['visionContent'] ?? '');
            
            if ($visionTitle || $visionContent) {
                $stmt = $conn->prepare("INSERT INTO about_content (section, title, content) VALUES ('vision', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
                $stmt->bind_param("ssss", $visionTitle, $visionContent, $visionTitle, $visionContent);
                $stmt->execute();
            }
            
            // Process team section
            $teamTitle = trim($_POST['teamTitle'] ?? '');
            $member1Name = trim($_POST['member1Name'] ?? '');
            $member1Designation = trim($_POST['member1Designation'] ?? '');
            $member1Description = trim($_POST['member1Description'] ?? '');
            $member2Name = trim($_POST['member2Name'] ?? '');
            $member2Designation = trim($_POST['member2Designation'] ?? '');
            $member2Description = trim($_POST['member2Description'] ?? '');
            
            // Get existing team data to preserve old images if no new ones uploaded
            $existingMember1Image = null;
            $existingMember2Image = null;
            
            $stmt = $conn->prepare("SELECT content FROM about_content WHERE section = 'team'");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $existingTeam = $result->fetch_assoc();
                $existingTeamData = json_decode($existingTeam['content'], true);
                if ($existingTeamData) {
                    $existingMember1Image = $existingTeamData['member1Image'] ?? null;
                    $existingMember2Image = $existingTeamData['member2Image'] ?? null;
                }
            }
            
            // Handle member images - only update if new image uploaded
            $member1ImageUrl = $existingMember1Image; // Keep existing by default
            $member2ImageUrl = $existingMember2Image; // Keep existing by default
            
            if (isset($_FILES['member1Image']) && $_FILES['member1Image']['error'] === UPLOAD_ERR_OK) {
                // Delete old image if exists
                if ($existingMember1Image) {
                    deleteImage($existingMember1Image, $uploadDir);
                }
                $member1ImageUrl = handleImageUpload($_FILES['member1Image'], $uploadDir, 'member1_');
            }
            // If no new image uploaded, keep existing image (already set above)
            
            if (isset($_FILES['member2Image']) && $_FILES['member2Image']['error'] === UPLOAD_ERR_OK) {
                // Delete old image if exists
                if ($existingMember2Image) {
                    deleteImage($existingMember2Image, $uploadDir);
                }
                $member2ImageUrl = handleImageUpload($_FILES['member2Image'], $uploadDir, 'member2_');
            }
            // If no new image uploaded, keep existing image (already set above)
            
            $teamContent = json_encode([
                'member1Name' => $member1Name,
                'member1Designation' => $member1Designation,
                'member1Description' => $member1Description,
                'member1Image' => $member1ImageUrl,
                'member2Name' => $member2Name,
                'member2Designation' => $member2Designation,
                'member2Description' => $member2Description,
                'member2Image' => $member2ImageUrl
            ]);
            
            if ($teamTitle) {
                $stmt = $conn->prepare("INSERT INTO about_content (section, title, content) VALUES ('team', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
                $stmt->bind_param("ssss", $teamTitle, $teamContent, $teamTitle, $teamContent);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'About page content updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (isset($_GET['action']) && $_GET['action'] === 'getAboutContent') {
        try {
            $stmt = $conn->prepare("SELECT * FROM about_content ORDER BY section");
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
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
