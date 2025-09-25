<?php
/**
 * Yacht Fleet Management API
 * Handles CRUD operations for yacht fleet
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

// Authentication check for protected actions
function checkAuth() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        echo json_encode(['success' => false, 'message' => 'Authorization header missing']);
        exit;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    if (!verifyJWT($token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }
    
    return true;
}

// Get action from query parameter
$action = $_GET['action'] ?? '';

// Create yacht_fleet table if it doesn't exist
function createYachtFleetTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS yacht_fleet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        price_per_hour DECIMAL(10,2),
        price_per_day DECIMAL(10,2),
        length DECIMAL(8,2),
        number_of_guests INT,
        overnight_guests INT,
        min_charter_length VARCHAR(100),
        facilities TEXT,
        experiences TEXT,
        watersports TEXT,
        crew TEXT,
        google_map_iframe TEXT,
        charter_member_name VARCHAR(255),
        whatsapp_link VARCHAR(500),
        main_image VARCHAR(255),
        secondary_images JSON,
        yacht_images JSON,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        error_log("Error creating yacht_fleet table: " . $conn->error);
    }
    
    // Add new columns if they don't exist (for existing installations)
    $alterQueries = [
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS price_per_hour DECIMAL(10,2)",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS price_per_day DECIMAL(10,2)",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS length DECIMAL(8,2)",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS number_of_guests INT",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS overnight_guests INT",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS min_charter_length VARCHAR(100)",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS facilities TEXT",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS experiences TEXT",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS watersports TEXT",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS crew TEXT",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS google_map_iframe TEXT",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS charter_member_name VARCHAR(255)",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS whatsapp_link VARCHAR(500)",
        "ALTER TABLE yacht_fleet ADD COLUMN IF NOT EXISTS yacht_images JSON"
    ];
    
    foreach ($alterQueries as $query) {
        $conn->query($query);
    }
}

// Initialize table
createYachtFleetTable($conn);

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

switch ($action) {
    case 'list':
        // Get all yachts
        try {
            $sql = "SELECT * FROM yacht_fleet WHERE status = 'active' ORDER BY created_at DESC";
            $result = $conn->query($sql);
            
            $yachts = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $yachts[] = $row;
                }
            }
            
            echo json_encode(['success' => true, 'data' => $yachts]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching yachts: ' . $e->getMessage()]);
        }
        break;

    case 'add':
        // checkAuth(); // TODO: Implement authentication
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            // Validate required fields
            if (empty($_POST['title']) || empty($_POST['price'])) {
                throw new Exception('Title and price are required');
            }

            if (!isset($_FILES['yacht_images']) || empty($_FILES['yacht_images']['name'][0])) {
                throw new Exception('At least one yacht image is required');
            }

            // Get form data
            $title = trim($_POST['title']);
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price']);
            $pricePerHour = !empty($_POST['price_per_hour']) ? floatval($_POST['price_per_hour']) : null;
            $pricePerDay = !empty($_POST['price_per_day']) ? floatval($_POST['price_per_day']) : null;
            $length = !empty($_POST['length']) ? floatval($_POST['length']) : null;
            $numberOfGuests = !empty($_POST['number_of_guests']) ? intval($_POST['number_of_guests']) : null;
            $overnightGuests = !empty($_POST['overnight_guests']) ? intval($_POST['overnight_guests']) : null;
            $minCharterLength = trim($_POST['min_charter_length'] ?? '');
            $facilities = trim($_POST['facilities'] ?? '');
            $experiences = trim($_POST['experiences'] ?? '');
            $watersports = trim($_POST['water_sports'] ?? '');
            $crewDetails = trim($_POST['crew'] ?? '');
            $googleMapIframe = trim($_POST['google_map_iframe'] ?? '');
            $charterMemberName = trim($_POST['charter_member_name'] ?? '');
            $whatsappLink = trim($_POST['whatsapp_link'] ?? '');

            if ($price <= 0) {
                throw new Exception('Price must be greater than 0');
            }

            $uploadDir = '../uploads/yachtFleet/';
            
            // Handle yacht images upload
            $yachtImages = [];
            if (isset($_FILES['yacht_images']) && is_array($_FILES['yacht_images']['name'])) {
                $fileCount = count($_FILES['yacht_images']['name']);
                
                if ($fileCount > 10) {
                    throw new Exception('Maximum 10 images allowed');
                }

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['yacht_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['yacht_images']['name'][$i],
                            'type' => $_FILES['yacht_images']['type'][$i],
                            'tmp_name' => $_FILES['yacht_images']['tmp_name'][$i],
                            'error' => $_FILES['yacht_images']['error'][$i],
                            'size' => $_FILES['yacht_images']['size'][$i]
                        ];
                        
                        $yachtImages[] = handleImageUpload($file, $uploadDir, 'yacht_');
                    }
                }
            }

            if (empty($yachtImages)) {
                throw new Exception('At least one valid image is required');
            }

            // For backward compatibility, set main_image as first image
            $mainImage = $yachtImages[0];
            $secondaryImages = array_slice($yachtImages, 1);

            // Insert into database
            $stmt = $conn->prepare("INSERT INTO yacht_fleet (
                title, description, price, price_per_hour, price_per_day, length, 
                number_of_guests, overnight_guests, min_charter_length, facilities, 
                experiences, watersports, crew, google_map_iframe, 
                charter_member_name, whatsapp_link, main_image, secondary_images, yacht_images
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $secondaryImagesJson = json_encode($secondaryImages);
            $yachtImagesJson = json_encode($yachtImages);
            
            $stmt->bind_param("ssddddiisssssssssss", 
                $title, $description, $price, $pricePerHour, $pricePerDay, $length,
                $numberOfGuests, $overnightGuests, $minCharterLength, $facilities,
                $experiences, $watersports, $crewDetails, $googleMapIframe,
                $charterMemberName, $whatsappLink, $mainImage, $secondaryImagesJson, $yachtImagesJson
            );

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Yacht added successfully',
                    'data' => [
                        'id' => $conn->insert_id,
                        'title' => $title,
                        'description' => $description,
                        'price' => $price,
                        'price_per_hour' => $pricePerHour,
                        'price_per_day' => $pricePerDay,
                        'length' => $length,
                        'number_of_guests' => $numberOfGuests,
                        'overnight_guests' => $overnightGuests,
                        'min_charter_length' => $minCharterLength,
                        'facilities' => $facilities,
                        'experiences' => $experiences,
                        'watersports' => $watersports,
                        'crew' => $crewDetails,
                        'google_map_iframe' => $googleMapIframe,
                        'charter_member_name' => $charterMemberName,
                        'whatsapp_link' => $whatsappLink,
                        'main_image' => $mainImage,
                        'secondary_images' => $secondaryImages,
                        'yacht_images' => $yachtImages
                    ]
                ]);
            } else {
                // Clean up uploaded images on database error
                foreach ($yachtImages as $img) {
                    deleteImage($img, $uploadDir);
                }
                throw new Exception('Failed to save yacht to database');
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        // checkAuth(); // TODO: Implement authentication
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            if (empty($_POST['yacht_id']) || empty($_POST['title']) || empty($_POST['price'])) {
                throw new Exception('Yacht ID, title and price are required');
            }

            $yachtId = intval($_POST['yacht_id']);
            
            // Get form data
            $title = trim($_POST['title']);
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price']);
            $pricePerHour = !empty($_POST['price_per_hour']) ? floatval($_POST['price_per_hour']) : null;
            $pricePerDay = !empty($_POST['price_per_day']) ? floatval($_POST['price_per_day']) : null;
            $length = !empty($_POST['length']) ? floatval($_POST['length']) : null;
            $numberOfGuests = !empty($_POST['number_of_guests']) ? intval($_POST['number_of_guests']) : null;
            $overnightGuests = !empty($_POST['overnight_guests']) ? intval($_POST['overnight_guests']) : null;
            $minCharterLength = trim($_POST['min_charter_length'] ?? '');
            $facilities = trim($_POST['facilities'] ?? '');
            $experiences = trim($_POST['experiences'] ?? '');
            $watersports = trim($_POST['water_sports'] ?? '');
            $crewDetails = trim($_POST['crew'] ?? '');
            $googleMapIframe = trim($_POST['google_map_iframe'] ?? '');
            $charterMemberName = trim($_POST['charter_member_name'] ?? '');
            $whatsappLink = trim($_POST['whatsapp_link'] ?? '');

            if ($price <= 0) {
                throw new Exception('Price must be greater than 0');
            }

            // Get current yacht data
            $stmt = $conn->prepare("SELECT * FROM yacht_fleet WHERE id = ?");
            $stmt->bind_param("i", $yachtId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Yacht not found');
            }
            
            $currentYacht = $result->fetch_assoc();
            $uploadDir = '../uploads/yachtFleet/';
            
            // Keep existing images by default
            $yachtImages = !empty($currentYacht['yacht_images']) ? json_decode($currentYacht['yacht_images'], true) : [];
            $mainImage = $currentYacht['main_image'];
            $secondaryImages = !empty($currentYacht['secondary_images']) ? json_decode($currentYacht['secondary_images'], true) : [];

            // Handle yacht images update
            if (isset($_FILES['yacht_images']) && !empty($_FILES['yacht_images']['name'][0])) {
                // Check if we should replace all images or add to existing ones
                $replaceAll = isset($_POST['replace_all_images']) && $_POST['replace_all_images'] === '1';
                
                if ($replaceAll) {
                    // Delete all old images
                    foreach ($yachtImages as $oldImage) {
                        deleteImage($oldImage, $uploadDir);
                    }
                    $yachtImages = [];
                }
                
                $newImages = [];
                $fileCount = count($_FILES['yacht_images']['name']);
                
                if ($fileCount > 10) {
                    throw new Exception('Maximum 10 images allowed');
                }

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['yacht_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['yacht_images']['name'][$i],
                            'type' => $_FILES['yacht_images']['type'][$i],
                            'tmp_name' => $_FILES['yacht_images']['tmp_name'][$i],
                            'error' => $_FILES['yacht_images']['error'][$i],
                            'size' => $_FILES['yacht_images']['size'][$i]
                        ];
                        
                        $newImages[] = handleImageUpload($file, $uploadDir, 'yacht_');
                    }
                }
                
                if ($replaceAll) {
                    $yachtImages = $newImages;
                } else {
                    $yachtImages = array_merge($yachtImages, $newImages);
                    // Ensure we don't exceed 10 images total
                    if (count($yachtImages) > 10) {
                        $yachtImages = array_slice($yachtImages, 0, 10);
                    }
                }
                
                // Update backward compatibility fields
                if (!empty($yachtImages)) {
                    $mainImage = $yachtImages[0];
                    $secondaryImages = array_slice($yachtImages, 1);
                }
            }

            // Update database
            $stmt = $conn->prepare("UPDATE yacht_fleet SET 
                title = ?, description = ?, price = ?, price_per_hour = ?, price_per_day = ?, 
                length = ?, number_of_guests = ?, overnight_guests = ?, min_charter_length = ?, 
                facilities = ?, experiences = ?, watersports = ?, crew = ?, 
                google_map_iframe = ?, charter_member_name = ?, whatsapp_link = ?, 
                main_image = ?, secondary_images = ?, yacht_images = ? 
                WHERE id = ?");
            
            $secondaryImagesJson = json_encode($secondaryImages);
            $yachtImagesJson = json_encode($yachtImages);
            
            $stmt->bind_param("ssddddiisssssssssssi", 
                $title, $description, $price, $pricePerHour, $pricePerDay, $length,
                $numberOfGuests, $overnightGuests, $minCharterLength, $facilities,
                $experiences, $watersports, $crewDetails, $googleMapIframe,
                $charterMemberName, $whatsappLink, $mainImage, $secondaryImagesJson, $yachtImagesJson, $yachtId
            );

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Yacht updated successfully',
                    'data' => [
                        'id' => $yachtId,
                        'title' => $title,
                        'description' => $description,
                        'price' => $price,
                        'price_per_hour' => $pricePerHour,
                        'price_per_day' => $pricePerDay,
                        'length' => $length,
                        'number_of_guests' => $numberOfGuests,
                        'overnight_guests' => $overnightGuests,
                        'min_charter_length' => $minCharterLength,
                        'facilities' => $facilities,
                        'experiences' => $experiences,
                        'watersports' => $watersports,
                        'crew' => $crewDetails,
                        'google_map_iframe' => $googleMapIframe,
                        'charter_member_name' => $charterMemberName,
                        'whatsapp_link' => $whatsappLink,
                        'main_image' => $mainImage,
                        'secondary_images' => $secondaryImages,
                        'yacht_images' => $yachtImages
                    ]
                ]);
            } else {
                throw new Exception('Failed to update yacht in database');
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['yacht_id'])) {
                throw new Exception('Yacht ID is required');
            }

            $yachtId = intval($input['yacht_id']);

            // Get yacht data for image deletion
            $stmt = $conn->prepare("SELECT * FROM yacht_fleet WHERE id = ?");
            $stmt->bind_param("i", $yachtId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Yacht not found');
            }
            
            $yacht = $result->fetch_assoc();
            $uploadDir = '../uploads/yachtFleet/';

            // Delete images
            deleteImage($yacht['main_image'], $uploadDir);
            
            $secondaryImages = !empty($yacht['secondary_images']) ? json_decode($yacht['secondary_images'], true) : [];
            foreach ($secondaryImages as $image) {
                deleteImage($image, $uploadDir);
            }
            
            // Also delete yacht_images if they exist
            $yachtImages = !empty($yacht['yacht_images']) ? json_decode($yacht['yacht_images'], true) : [];
            foreach ($yachtImages as $image) {
                deleteImage($image, $uploadDir);
            }

            // Soft delete (mark as inactive)
            $stmt = $conn->prepare("UPDATE yacht_fleet SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $yachtId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Yacht deleted successfully']);
            } else {
                throw new Exception('Failed to delete yacht from database');
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            if (empty($_GET['id'])) {
                throw new Exception('Yacht ID is required');
            }

            $yachtId = intval($_GET['id']);
            
            $stmt = $conn->prepare("SELECT * FROM yacht_fleet WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $yachtId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Yacht not found');
            }
            
            $yacht = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $yacht]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
?>
