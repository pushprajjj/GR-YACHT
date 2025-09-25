<?php
/**
 * Blog Management Handler API
 * Handles CRUD operations for blog posts with SEO-friendly slugs
 */

require_once 'config/_db.php';
require_once 'config/_config.php';
require_once 'jwt.php';

// Set content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create blogs table if it doesn't exist
function createBlogsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS blogs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        excerpt TEXT,
        content LONGTEXT NOT NULL,
        featured_image VARCHAR(255),
        author VARCHAR(100) DEFAULT 'GR Yachts',
        status ENUM('draft', 'published') DEFAULT 'draft',
        meta_title VARCHAR(255),
        meta_description TEXT,
        tags TEXT,
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at TIMESTAMP NULL,
        INDEX idx_slug (slug),
        INDEX idx_status (status),
        INDEX idx_published_at (published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    
    // Create blog page content table for hero section
    $heroSql = "CREATE TABLE IF NOT EXISTS blog_page_content (
        id INT PRIMARY KEY AUTO_INCREMENT,
        section VARCHAR(50) NOT NULL,
        title VARCHAR(255),
        subtitle VARCHAR(255),
        content TEXT,
        button_text VARCHAR(100),
        button_link VARCHAR(255),
        image_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section (section)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($heroSql);
    
    // Insert default hero content if not exists
    $checkHero = $conn->query("SELECT COUNT(*) as count FROM blog_page_content WHERE section = 'hero'");
    $heroRow = $checkHero->fetch_assoc();
    
    if ($heroRow['count'] == 0) {
        $defaultHeroTitle = 'Read Blog for know about Us';
        $defaultHeroSubtitle = 'Discover insights, tips, and stories about luxury yacht experiences';
        $defaultButtonText = 'BOOK A YACHT';
        $defaultButtonLink = 'contact.html';
        
        $heroStmt = $conn->prepare("INSERT INTO blog_page_content (section, title, subtitle, button_text, button_link) VALUES (?, ?, ?, ?, ?)");
        $heroStmt->bind_param("sssss", $section, $defaultHeroTitle, $defaultHeroSubtitle, $defaultButtonText, $defaultButtonLink);
        $section = 'hero';
        $heroStmt->execute();
    }
    
    $conn->query($sql);
}

// Create upload directory if it doesn't exist
$uploadDir = '../uploads/blog/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Initialize database
createBlogsTable($conn);

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            handleListBlogs($conn);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if ($id) {
                handleGetBlog($conn, $id);
            } else {
                throw new Exception('Blog ID is required');
            }
            break;
            
        case 'add':
            // checkAuth(); // Uncomment when authentication is ready
            handleAddBlog($conn);
            break;
            
        case 'update':
            // checkAuth(); // Uncomment when authentication is ready
            handleUpdateBlog($conn);
            break;
            
        case 'delete':
            // checkAuth(); // Uncomment when authentication is ready
            $id = $_POST['id'] ?? null;
            if ($id) {
                handleDeleteBlog($conn, $id);
            } else {
                throw new Exception('Blog ID is required');
            }
            break;
            
        case 'generateSlug':
            $title = $_GET['title'] ?? '';
            echo json_encode([
                'success' => true,
                'slug' => generateSlug($title)
            ]);
            break;
            
        case 'getHeroContent':
            handleGetHeroContent($conn);
            break;
            
        case 'updateHeroContent':
            // checkAuth(); // Uncomment when authentication is ready
            handleUpdateHeroContent($conn);
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

function handleListBlogs($conn) {
    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;
        
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        $types = '';
        
        if ($status && in_array($status, ['draft', 'published'])) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        if ($search) {
            $whereConditions[] = "(title LIKE ? OR content LIKE ? OR tags LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM blogs $whereClause";
        $countStmt = $conn->prepare($countSql);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        
        // Get blogs
        $sql = "SELECT * FROM blogs $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $blogs = [];
        while ($row = $result->fetch_assoc()) {
            $blogs[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $blogs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit,
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to fetch blogs: ' . $e->getMessage());
    }
}

function handleGetBlog($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM blogs WHERE id = ? OR slug = ?");
        $stmt->bind_param("ss", $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($blog = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'data' => $blog
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Blog not found'
            ]);
        }
    } catch (Exception $e) {
        throw new Exception('Failed to fetch blog: ' . $e->getMessage());
    }
}

function handleAddBlog($conn) {
    try {
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? generateSlug($title);
        $excerpt = $_POST['excerpt'] ?? '';
        $content = $_POST['content'] ?? '';
        $author = $_POST['author'] ?? 'GR Yachts';
        $status = $_POST['status'] ?? 'draft';
        $metaTitle = $_POST['meta_title'] ?? $title;
        $metaDescription = $_POST['meta_description'] ?? $excerpt;
        $tags = $_POST['tags'] ?? '';
        
        // Validate required fields
        if (empty($title) || empty($content)) {
            throw new Exception('Title and content are required');
        }
        
        // Ensure unique slug
        $slug = ensureUniqueSlug($conn, $slug);
        
        // Handle image upload
        $featuredImage = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $featuredImage = handleImageUpload($_FILES['featured_image'], '../uploads/blog/', 'blog_');
            if (!$featuredImage) {
                throw new Exception('Failed to upload featured image');
            }
        }
        
        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $conn->prepare("INSERT INTO blogs (title, slug, excerpt, content, featured_image, author, status, meta_title, meta_description, tags, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $title, $slug, $excerpt, $content, $featuredImage, $author, $status, $metaTitle, $metaDescription, $tags, $publishedAt);
        
        if ($stmt->execute()) {
            $blogId = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Blog created successfully',
                'data' => ['id' => $blogId, 'slug' => $slug]
            ]);
        } else {
            throw new Exception('Failed to create blog');
        }
    } catch (Exception $e) {
        throw new Exception('Failed to add blog: ' . $e->getMessage());
    }
}

function handleUpdateBlog($conn) {
    try {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            throw new Exception('Blog ID is required');
        }
        
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $content = $_POST['content'] ?? '';
        $author = $_POST['author'] ?? 'GR Yachts';
        $status = $_POST['status'] ?? 'draft';
        $metaTitle = $_POST['meta_title'] ?? '';
        $metaDescription = $_POST['meta_description'] ?? '';
        $tags = $_POST['tags'] ?? '';
        
        // Validate required fields
        if (empty($title) || empty($content)) {
            throw new Exception('Title and content are required');
        }
        
        // Get current blog data
        $stmt = $conn->prepare("SELECT * FROM blogs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentBlog = $result->fetch_assoc();
        
        if (!$currentBlog) {
            throw new Exception('Blog not found');
        }
        
        // Ensure unique slug (excluding current blog)
        $slug = ensureUniqueSlug($conn, $slug, $id);
        
        // Handle image upload
        $featuredImage = $currentBlog['featured_image'];
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            // Delete old image
            if ($featuredImage && file_exists("../uploads/blog/$featuredImage")) {
                unlink("../uploads/blog/$featuredImage");
            }
            
            $featuredImage = handleImageUpload($_FILES['featured_image'], '../uploads/blog/', 'blog_');
            if (!$featuredImage) {
                throw new Exception('Failed to upload featured image');
            }
        }
        
        // Handle published_at timestamp
        $publishedAt = $currentBlog['published_at'];
        if ($status === 'published' && $currentBlog['status'] !== 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        } elseif ($status !== 'published') {
            $publishedAt = null;
        }
        
        $stmt = $conn->prepare("UPDATE blogs SET title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, author = ?, status = ?, meta_title = ?, meta_description = ?, tags = ?, published_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sssssssssssi", $title, $slug, $excerpt, $content, $featuredImage, $author, $status, $metaTitle, $metaDescription, $tags, $publishedAt, $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Blog updated successfully',
                'data' => ['id' => $id, 'slug' => $slug]
            ]);
        } else {
            throw new Exception('Failed to update blog');
        }
    } catch (Exception $e) {
        throw new Exception('Failed to update blog: ' . $e->getMessage());
    }
}

function handleDeleteBlog($conn, $id) {
    try {
        // Get blog data for image deletion
        $stmt = $conn->prepare("SELECT featured_image FROM blogs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $blog = $result->fetch_assoc();
        
        if (!$blog) {
            throw new Exception('Blog not found');
        }
        
        // Delete blog
        $stmt = $conn->prepare("DELETE FROM blogs WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Delete associated image
            if ($blog['featured_image'] && file_exists("../uploads/blog/{$blog['featured_image']}")) {
                unlink("../uploads/blog/{$blog['featured_image']}");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Blog deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete blog');
        }
    } catch (Exception $e) {
        throw new Exception('Failed to delete blog: ' . $e->getMessage());
    }
}

function generateSlug($title) {
    // Convert to lowercase and replace spaces with hyphens
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    return $slug ?: 'untitled-blog';
}

function ensureUniqueSlug($conn, $slug, $excludeId = null) {
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $checkSql = "SELECT id FROM blogs WHERE slug = ?";
        $params = [$slug];
        $types = 's';
        
        if ($excludeId) {
            $checkSql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            break;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

function handleImageUpload($file, $uploadDir, $prefix = '') {
    try {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        } else {
            throw new Exception('Failed to move uploaded file');
        }
    } catch (Exception $e) {
        error_log('Image upload error: ' . $e->getMessage());
        return false;
    }
}

function handleGetHeroContent($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM blog_page_content WHERE section = 'hero'");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($heroContent = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'data' => $heroContent
            ]);
        } else {
            // Return default content if none exists
            echo json_encode([
                'success' => true,
                'data' => [
                    'title' => 'Read Blog for know about Us',
                    'subtitle' => 'Discover insights, tips, and stories about luxury yacht experiences',
                    'button_text' => 'BOOK A YACHT',
                    'button_link' => 'contact.html',
                    'image_path' => null
                ]
            ]);
        }
    } catch (Exception $e) {
        throw new Exception('Failed to fetch hero content: ' . $e->getMessage());
    }
}

function handleUpdateHeroContent($conn) {
    try {
        $title = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';
        $buttonText = $_POST['button_text'] ?? '';
        $buttonLink = $_POST['button_link'] ?? '';
        
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] == 0) {
            // Get current image to delete old one
            $currentStmt = $conn->prepare("SELECT image_path FROM blog_page_content WHERE section = 'hero'");
            $currentStmt->execute();
            $currentResult = $currentStmt->get_result();
            $currentData = $currentResult->fetch_assoc();
            
            // Delete old image if exists
            if ($currentData && $currentData['image_path'] && file_exists("../uploads/blog/{$currentData['image_path']}")) {
                unlink("../uploads/blog/{$currentData['image_path']}");
            }
            
            $imagePath = handleImageUpload($_FILES['hero_image'], '../uploads/blog/', 'hero_');
            if (!$imagePath) {
                throw new Exception('Failed to upload hero image');
            }
        }
        
        // Check if hero content exists
        $checkStmt = $conn->prepare("SELECT id FROM blog_page_content WHERE section = 'hero'");
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Update existing content
            if ($imagePath) {
                $stmt = $conn->prepare("UPDATE blog_page_content SET title = ?, subtitle = ?, button_text = ?, button_link = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP WHERE section = 'hero'");
                $stmt->bind_param("sssss", $title, $subtitle, $buttonText, $buttonLink, $imagePath);
            } else {
                $stmt = $conn->prepare("UPDATE blog_page_content SET title = ?, subtitle = ?, button_text = ?, button_link = ?, updated_at = CURRENT_TIMESTAMP WHERE section = 'hero'");
                $stmt->bind_param("ssss", $title, $subtitle, $buttonText, $buttonLink);
            }
        } else {
            // Insert new content
            $stmt = $conn->prepare("INSERT INTO blog_page_content (section, title, subtitle, button_text, button_link, image_path) VALUES ('hero', ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $title, $subtitle, $buttonText, $buttonLink, $imagePath);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Hero content updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update hero content');
        }
    } catch (Exception $e) {
        throw new Exception('Failed to update hero content: ' . $e->getMessage());
    }
}

$conn->close();
?>
  