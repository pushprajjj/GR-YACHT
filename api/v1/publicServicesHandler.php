<?php
/**
 * Public Services Page Content API
 * Handles public read-only operations for services page content
 * No authentication required for frontend display
 */

require_once 'config/_db.php';
require_once 'config/_config.php';

// Set content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests for public API
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Only GET requests allowed']);
    exit;
}

if (isset($_GET['action'])) {
    
    if ($_GET['action'] === 'getHeroSection') {
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
            echo json_encode(['success' => false, 'message' => 'Error loading hero section']);
        }
    }
    elseif ($_GET['action'] === 'getServicesContent') {
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
            echo json_encode(['success' => false, 'message' => 'Error loading content']);
        }
    }
    elseif ($_GET['action'] === 'getServices') {
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
                    'sort_order' => $row['sort_order']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $services]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error loading services']);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
}

$conn->close();
?>
