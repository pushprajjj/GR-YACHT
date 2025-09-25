<?php
/**
 * Public Blog Handler API
 * Provides read-only access to published blog posts for public consumption
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

// Create blogs table if it doesn't exist (with default content)
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
}

// Initialize database
createBlogsTable($conn);

// Get action from request
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            handleListBlogs($conn);
            break;
            
        case 'get':
            $slug = $_GET['slug'] ?? null;
            if ($slug) {
                handleGetBlog($conn, $slug);
            } else {
                throw new Exception('Blog slug is required');
            }
            break;
            
        case 'recent':
            handleRecentBlogs($conn);
            break;
            
        case 'getHeroContent':
            handleGetHeroContent($conn);
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
        $limit = isset($_GET['limit']) ? max(1, min(20, intval($_GET['limit']))) : 6;
        $offset = ($page - 1) * $limit;
        
        $search = $_GET['search'] ?? '';
        $tag = $_GET['tag'] ?? '';
        
        // Build WHERE clause - only show published blogs
        $whereConditions = ["status = 'published'"];
        $params = [];
        $types = '';
        
        if ($search) {
            $whereConditions[] = "(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        if ($tag) {
            $whereConditions[] = "tags LIKE ?";
            $params[] = "%$tag%";
            $types .= 's';
        }
        
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        
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
        $sql = "SELECT id, title, slug, excerpt, featured_image, author, views, published_at, created_at, tags FROM blogs $whereClause ORDER BY published_at DESC, created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $blogs = [];
        while ($row = $result->fetch_assoc()) {
            // Format date for display
            $publishedDate = $row['published_at'] ? new DateTime($row['published_at']) : new DateTime($row['created_at']);
            $row['formatted_date'] = $publishedDate->format('F Y, l'); // e.g., "August 2025, Sunday"
            $row['formatted_date_short'] = $publishedDate->format('M d, Y'); // e.g., "Aug 15, 2025"
            
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

function handleGetBlog($conn, $slug) {
    try {
        // Get blog by slug (only published)
        $stmt = $conn->prepare("SELECT * FROM blogs WHERE slug = ? AND status = 'published'");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($blog = $result->fetch_assoc()) {
            // Increment view count
            $updateStmt = $conn->prepare("UPDATE blogs SET views = views + 1 WHERE id = ?");
            $updateStmt->bind_param("i", $blog['id']);
            $updateStmt->execute();
            
            // Format date for display
            $publishedDate = $blog['published_at'] ? new DateTime($blog['published_at']) : new DateTime($blog['created_at']);
            $blog['formatted_date'] = $publishedDate->format('F d, Y'); // e.g., "August 15, 2025"
            $blog['formatted_date_long'] = $publishedDate->format('F Y, l'); // e.g., "August 2025, Sunday"
            
            // Get related blogs (same tags, excluding current blog)
            $relatedBlogs = [];
            if ($blog['tags']) {
                $tags = array_map('trim', explode(',', $blog['tags']));
                if (!empty($tags)) {
                    $tagConditions = array_fill(0, count($tags), "tags LIKE ?");
                    $tagWhere = "(" . implode(" OR ", $tagConditions) . ")";
                    
                    $relatedSql = "SELECT id, title, slug, excerpt, featured_image, author, published_at, created_at 
                                  FROM blogs 
                                  WHERE status = 'published' AND id != ? AND $tagWhere 
                                  ORDER BY published_at DESC 
                                  LIMIT 3";
                    
                    $relatedStmt = $conn->prepare($relatedSql);
                    $relatedParams = [$blog['id']];
                    $relatedTypes = 'i';
                    
                    foreach ($tags as $tag) {
                        $relatedParams[] = "%$tag%";
                        $relatedTypes .= 's';
                    }
                    
                    $relatedStmt->bind_param($relatedTypes, ...$relatedParams);
                    $relatedStmt->execute();
                    $relatedResult = $relatedStmt->get_result();
                    
                    while ($relatedRow = $relatedResult->fetch_assoc()) {
                        $relatedDate = $relatedRow['published_at'] ? new DateTime($relatedRow['published_at']) : new DateTime($relatedRow['created_at']);
                        $relatedRow['formatted_date'] = $relatedDate->format('F Y, l');
                        $relatedBlogs[] = $relatedRow;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $blog,
                'related_blogs' => $relatedBlogs
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Blog not found or not published'
            ]);
        }
    } catch (Exception $e) {
        throw new Exception('Failed to fetch blog: ' . $e->getMessage());
    }
}

function handleRecentBlogs($conn) {
    try {
        $limit = isset($_GET['limit']) ? max(1, min(10, intval($_GET['limit']))) : 5;
        
        $sql = "SELECT id, title, slug, excerpt, featured_image, author, published_at, created_at 
                FROM blogs 
                WHERE status = 'published' 
                ORDER BY published_at DESC, created_at DESC 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $blogs = [];
        while ($row = $result->fetch_assoc()) {
            $publishedDate = $row['published_at'] ? new DateTime($row['published_at']) : new DateTime($row['created_at']);
            $row['formatted_date'] = $publishedDate->format('F Y, l');
            $row['formatted_date_short'] = $publishedDate->format('M d, Y');
            
            $blogs[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $blogs
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to fetch recent blogs: ' . $e->getMessage());
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

$conn->close();
?>
    