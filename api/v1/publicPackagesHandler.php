<?php
/**
 * Public Packages API - Read-only access for frontend
 * Provides public access to packages data without authentication
 */

require_once 'config/_db.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
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
    elseif (isset($_GET['action']) && $_GET['action'] === 'getFeaturedPackage') {
        try {
            $stmt = $conn->prepare("SELECT * FROM packages WHERE is_featured = 1 AND status = 'active' LIMIT 1");
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
                    'image_path' => $row['image_path'],
                    'book_button_text' => $row['book_button_text'],
                    'book_button_link' => $row['book_button_link'],
                    'is_featured' => $row['is_featured']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $packageData]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error loading featured package']);
        }
    }
    elseif (isset($_GET['action']) && $_GET['action'] === 'getPackages') {
        try {
            $stmt = $conn->prepare("SELECT * FROM packages WHERE is_featured = 0 AND status = 'active' ORDER BY sort_order ASC, created_at DESC");
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
            echo json_encode(['success' => false, 'message' => 'Error loading packages']);
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