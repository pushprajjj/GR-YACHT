<?php
/**
 * Home Page Content Management API
 * Handles CRUD operations for home page content
 */

require_once 'config/_db.php';
require_once 'config/_config.php';
require_once 'jwt.php';

// Create gallery_images table if it doesn't exist
function createGalleryTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        filename VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        error_log("Error creating gallery_images table: " . $conn->error);
    }
}

// Initialize table
createGalleryTable($conn);

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    if($_POST['action'] === 'updateHeroSection') {
        // Validate required fields
        if(empty($_POST['heroTitle']) || empty($_POST['heroButtonText']) || empty($_POST['heroButtonLink'])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        $title = strip_tags($_POST['heroTitle']);
        $titlebold = strip_tags($_POST['heroTitlebold']);
        $buttonText = strip_tags($_POST['heroButtonText']);
        $buttonLink = filter_var($_POST['heroButtonLink'], FILTER_SANITIZE_URL);
        $galleryTitle = strip_tags($_POST['galleryTitle']);
        $galleryDescription = strip_tags($_POST['galleryDesc']);
        $yachtTitle = strip_tags($_POST['yachtTitle']);
        $yachtDescription = strip_tags($_POST['yachtDesc']);

        $imageUrl = "";
        if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['image']['tmp_name'];
            $imageName = $_FILES['image']['name'];
            $imageSize = $_FILES['image']['size'];
            
            // Add file size limit (5MB)
            if($imageSize > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Image size should be less than 5MB']);
                exit;
            }

            $imageType = $_FILES['image']['type'];
            $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if(in_array($imageExtension, $allowedExtensions)) {
                $newImageName = "hero.jpg"; // Standardize image name
                $uploadDir = '../uploads/home/';
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $destPath = $uploadDir . $newImageName;
                if(move_uploaded_file($imageTmpPath, $destPath)) {
                    $imageUrl = 'api/uploads/home/' . $newImageName;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error moving uploaded file']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid image format']);
                exit;
            }
        }

        // Read existing content
        $jsonFile = '../json-files/homePageContent.json';
        $existingContent = [];
        if(file_exists($jsonFile)) {
            $existingContent = json_decode(file_get_contents($jsonFile), true) ?? [];
        }

        // Update hero section while preserving other sections
        $existingContent['homePageContent'] = [
            'title' => $title,
            'titlebold' => $titlebold,
            'buttonText' => $buttonText,
            'buttonLink' => $buttonLink,
            'imageUrl' => $imageUrl ?: ($existingContent['homePageContent']['imageUrl'] ?? ''),
            'galleryTitle' => $galleryTitle,
            'galleryDescription' => $galleryDescription,
            'yachtTitle' => $yachtTitle,
            'yachtDescription' => $yachtDescription
        ];

        if(!is_dir(dirname($jsonFile))) {
            mkdir(dirname($jsonFile), 0755, true);
        }

        if(file_put_contents($jsonFile, json_encode($existingContent, JSON_PRETTY_PRINT)) === false) {
            echo json_encode(['success' => false, 'message' => 'Error saving content']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Hero section updated successfully']);     
     

    } 
    
   elseif($_POST['action'] === 'updateYachtSection') {


       $title = strip_tags($_POST['yachtTitle']);
       $description = strip_tags($_POST['yachtDescription']);

        //update yacht section json file
        $jsonFile = '../json-files/homePageContent.json';   
        $existingContent = [];
        if(file_exists($jsonFile)) {
            $existingContent = json_decode(file_get_contents($jsonFile), true) ?? [];
        }   
        $existingContent['yachtSection'] = [
            'title' => $title,
            'description' => $description
        ];  
        if(!is_dir(dirname($jsonFile))) {
            mkdir(dirname($jsonFile), 0755, true);
        }   
        if(file_put_contents($jsonFile, json_encode($existingContent, JSON_PRETTY_PRINT)) === false) {
            echo json_encode(['success' => false, 'message' => 'Error saving content']);
            exit;
        }   
        echo json_encode(['success' => true, 'message' => 'Yacht section updated successfully']);   



    }
    
    // Gallery Image Management
    elseif($_POST['action'] === 'addGalleryImage') {
        // Validate required fields
        if(empty($_POST['title'])) {
            echo json_encode(['success' => false, 'message' => 'Image title is required']);
            exit;
        }
        
        if(!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Image file is required']);
            exit;
        }
        
        $title = strip_tags(trim($_POST['title']));
        $description = isset($_POST['description']) ? strip_tags(trim($_POST['description'])) : '';
        
        // Handle image upload
        $imageTmpPath = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];
        $imageSize = $_FILES['image']['size'];
        
        // Validate file size (5MB)
        if($imageSize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image size should be less than 5MB']);
            exit;
        }
        
        $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(!in_array($imageExtension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP']);
            exit;
        }
        
        // Create upload directory
        $uploadDir = '../uploads/eventGallery/';
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $newImageName = 'gallery_' . uniqid() . '_' . time() . '.' . $imageExtension;
        $destPath = $uploadDir . $newImageName;
        
        if(move_uploaded_file($imageTmpPath, $destPath)) {
            // Get next sort order
            $sortOrderQuery = $conn->query("SELECT MAX(sort_order) as max_order FROM gallery_images WHERE status = 'active'");
            $sortOrder = ($sortOrderQuery->fetch_assoc()['max_order'] ?? 0) + 1;
            
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO gallery_images (title, description, filename, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $description, $newImageName, $sortOrder);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Gallery image added successfully']);
            } else {
                // Delete uploaded file if database insert fails
                unlink($destPath);
                echo json_encode(['success' => false, 'message' => 'Error saving image to database']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error uploading image file']);
        }
    }
    
    elseif($_POST['action'] === 'updateGalleryImage') {
        // Validate required fields
        if(empty($_POST['image_id']) || empty($_POST['title'])) {
            echo json_encode(['success' => false, 'message' => 'Image ID and title are required']);
            exit;
        }
        
        $imageId = intval($_POST['image_id']);
        $title = strip_tags(trim($_POST['title']));
        $description = isset($_POST['description']) ? strip_tags(trim($_POST['description'])) : '';
        
        // Get current image data
        $stmt = $conn->prepare("SELECT * FROM gallery_images WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Gallery image not found']);
            exit;
        }
        
        $currentImage = $result->fetch_assoc();
        $filename = $currentImage['filename'];
        
        // Handle new image upload if provided
        if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['image']['tmp_name'];
            $imageName = $_FILES['image']['name'];
            $imageSize = $_FILES['image']['size'];
            
            // Validate file size (5MB)
            if($imageSize > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Image size should be less than 5MB']);
                exit;
            }
            
            $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if(!in_array($imageExtension, $allowedExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP']);
                exit;
            }
            
            $uploadDir = '../uploads/eventGallery/';
            $newImageName = 'gallery_' . uniqid() . '_' . time() . '.' . $imageExtension;
            $destPath = $uploadDir . $newImageName;
            
            if(move_uploaded_file($imageTmpPath, $destPath)) {
                // Delete old image file
                if(file_exists($uploadDir . $currentImage['filename'])) {
                    unlink($uploadDir . $currentImage['filename']);
                }
                $filename = $newImageName;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error uploading new image file']);
                exit;
            }
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE gallery_images SET title = ?, description = ?, filename = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $description, $filename, $imageId);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Gallery image updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating gallery image']);
        }
    }
    
    elseif($_POST['action'] === 'deleteGalleryImage') {
        if(empty($_POST['image_id'])) {
            echo json_encode(['success' => false, 'message' => 'Image ID is required']);
            exit;
        }
        
        $imageId = intval($_POST['image_id']);
        
        // Get image data for file deletion
        $stmt = $conn->prepare("SELECT * FROM gallery_images WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Gallery image not found']);
            exit;
        }
        
        $image = $result->fetch_assoc();
        
        // Delete image file
        $uploadDir = '../uploads/eventGallery/';
        if(file_exists($uploadDir . $image['filename'])) {
            unlink($uploadDir . $image['filename']);
        }
        
        // Soft delete from database
        $stmt = $conn->prepare("UPDATE gallery_images SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Gallery image deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting gallery image']);
        }
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
   
}else if($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if(isset($_GET['action']) && $_GET['action'] === 'getHeroSection') {
        $jsonFile = '../json-files/homePageContent.json';
        if(file_exists($jsonFile)) {
            $content = json_decode(file_get_contents($jsonFile), true);
            echo json_encode(['success' => true, 'data' => $content]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Content not found']);
        }
    } 
    elseif(isset($_GET['action']) && $_GET['action'] === 'getGalleryImages') {
        $stmt = $conn->prepare("SELECT * FROM gallery_images WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $images = [];
        while($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $images]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }



} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}   


?>
