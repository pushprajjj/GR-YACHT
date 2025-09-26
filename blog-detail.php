<?php
/**
 * Blog Detail Page with Server-Side Meta Tags
 * This ensures social media crawlers get the correct meta tags
 */

require_once 'api/v1/config/_db.php';

// Get slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$blog = null;
$pageTitle = 'Blog Post | GR Yachts Dubai';
$metaDescription = 'Read our latest blog post';
$ogImage = 'assets/images/logo.png';
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

// Fetch blog data if slug is provided
if ($slug) {
    try {
        $stmt = $conn->prepare("
            SELECT id, title, slug, excerpt, content, featured_image, author, 
                   meta_title, meta_description, tags, views, created_at, published_at,
                   DATE_FORMAT(published_at, '%M %d, %Y') as formatted_date
            FROM blogs 
            WHERE slug = ? AND status = 'published'
        ");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $blog = $result->fetch_assoc();
            
            // Update view count
            $updateStmt = $conn->prepare("UPDATE blogs SET views = views + 1 WHERE id = ?");
            $updateStmt->bind_param("i", $blog['id']);
            $updateStmt->execute();
            
            // Set meta data
            $pageTitle = $blog['meta_title'] ?: ($blog['title'] . ' | GR Yachts Dubai');
            $metaDescription = $blog['meta_description'] ?: ($blog['excerpt'] ?: substr(strip_tags($blog['content']), 0, 160));
            $ogImage = $blog['featured_image'] ? ($blog['featured_image']) : 'assets/images/logo.png';
        }
    } catch (Exception $e) {
        error_log("Error fetching blog: " . $e->getMessage());
    }
}

// If no blog found, we'll show the not found state via JavaScript
?>
<!DOCTYPE html>
<html lang="en" translate="yes">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="google-translate-customization" content="enabled">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:image" content="<?=$host?>/api/uploads/blog/<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:site_name" content="GR Yachts Dubai">
    <?php if ($blog): ?>
    <meta property="article:author" content="<?php echo htmlspecialchars($blog['author']); ?>">
    <meta property="article:published_time" content="<?php echo htmlspecialchars($blog['published_at'] ?: $blog['created_at']); ?>">
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($currentUrl . '/' . $ogImage); ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($currentUrl); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=5" rel="stylesheet">
    <link href="assets/css/blog.css?v=5" rel="stylesheet">
    <link href="assets/css/translation-widget.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Translate Script -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,ar,ru,fr,es,zh-CN,hi,ur,de,it,pt,ja,ko,th,tr,nl,pl,sv,da,no,fi,he,fa',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false,
                multilanguagePage: true
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    
    <style>
        
        .blog-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333;
        }
        
        .blog-content h1, .blog-content h2, .blog-content h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #39b8c1;
        }
        
        .blog-content p {
            margin-bottom: 1.5rem;
        }
        
        .blog-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        
        .blog-meta {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .blog-tags .badge {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .related-blog-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .related-blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .social-share {
            position: sticky;
            top: 100px;
        }
        
        .share-button {
            display: block;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: white;
            text-align: center;
            line-height: 50px;
            margin-bottom: 10px;
            transition: transform 0.2s;
            text-decoration: none;
        }
        
        .share-button:hover {
            transform: scale(1.1);
            color: white;
        }
        
        .share-facebook { background-color: #39b8c1; }
        .share-twitter { background-color: #40E0D0; }
        .share-linkedin { background-color: #2BA7A7; }
        .share-whatsapp { background-color: #25d366; }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .breadcrumb-item a {
            color: #40E0D0;
            text-decoration: none;
        }
        
        .blog-navigation {
            border-top: 1px solid #eee;
            padding-top: 2rem;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            .social-share {
                position: static;
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .share-button {
                display: inline-block;
                margin: 0 5px 10px 0;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <div id="header"></div>

    <!-- Loading State -->
    <div id="blogLoading" class="text-center py-5" style="min-height: 50vh; display: flex; align-items: center; justify-content: center;">
        <div>
            <div class="spinner-border mb-3" style="color: #39b8c1;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Loading blog post...</p>
        </div>
    </div>

    <!-- Blog Not Found -->
    <div id="blogNotFound" class="text-center py-5" style="min-height: 50vh; display: none; align-items: center; justify-content: center;">
        <div>
            <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
            <h2>Blog Post Not Found</h2>
            <p class="text-muted">The blog post you're looking for doesn't exist or has been removed.</p>
            <a href="blog.html" class="btn" style="color: white;">
                <i class="fas fa-arrow-left me-2"></i>Back to Blog
            </a>
        </div>
    </div>

    <!-- Blog Detail Content -->
    <div id="blogDetailContent" style="display: none;">
        <!-- Hero Section -->
        <section class="hero" id="blogHeroSection">
            <div class="container h-100">
                <div class="row h-100 align-items-center" style="margin-top: -40px;">
                    <div class="col-12 text-center text-white">
                        <h1 class="hero-title" id="heroTitle">Read Blog for know about Us</h1>
                        <p class="hero-subtitle mb-4" id="heroSubtitle" style="font-size: 1.2rem; display: none;">Discover insights, tips, and stories about luxury yacht experiences</p>
                        <button class="btn btn-primary mt-3" id="heroButton">BOOK A YACHT</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Blog Content -->
        <section class="py-5">
            <div class="container">
                <div class="row">
                    <!-- Social Share Sidebar -->
                    <div class="col-lg-1 d-none d-lg-block">
                        <div class="social-share">
                            <a href="#" class="share-button share-facebook" id="shareFacebook" title="Share on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="share-button share-twitter" id="shareTwitter" title="Share on Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="share-button share-linkedin" id="shareLinkedin" title="Share on LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="share-button share-whatsapp" id="shareWhatsapp" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Blog Title and Meta -->
                        <div class="mb-4">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                                    <li class="breadcrumb-item"><a href="blog.html">Blog</a></li>
                                    <li class="breadcrumb-item active" aria-current="page" id="breadcrumbTitle">Blog Post</li>
                                </ol>
                            </nav>
                            <h1 class="mb-3" id="blogTitle" style="color: #39b8c1;">Blog Post Title</h1>
                            <div class="blog-meta mb-4" style="background: #f8f9fa; border-radius: 8px; padding: 1rem;">
                                <span class="me-4">
                                    <i class="fas fa-user me-2" style="color: #39b8c1;"></i>
                                    <span id="blogAuthor">GR Yachts</span>
                                </span>
                                <span class="me-4">
                                    <i class="fas fa-calendar me-2" style="color: #39b8c1;"></i>
                                    <span id="blogDate">January 1, 2025</span>
                                </span>
                                <span>
                                    <i class="fas fa-eye me-2" style="color: #39b8c1;"></i>
                                    <span id="blogViews">0</span> views
                                </span>
                            </div>
                        </div>

                        <!-- Featured Image -->
                        <div class="mb-4" id="featuredImageContainer" style="display: none;">
                            <img id="featuredImage" class="img-fluid rounded" alt="Featured Image">
                        </div>

                        <!-- Blog Content -->
                        <div class="blog-content" id="blogContent">
                            <p>Loading blog content...</p>
                        </div>

                        <!-- Tags -->
                        <div class="blog-tags" id="blogTagsContainer" style="display: none;">
                            <h6 class="text-muted mb-3">Tags:</h6>
                            <div id="blogTags"></div>
                        </div>

                        <!-- Social Share Mobile -->
                        <div class="d-lg-none mt-4">
                            <h6 class="text-muted mb-3">Share this post:</h6>
                            <div class="social-share">
                                <a href="#" class="share-button share-facebook" title="Share on Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="share-button share-twitter" title="Share on Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="share-button share-linkedin" title="Share on LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="share-button share-whatsapp" title="Share on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Blog Navigation -->
                        <!-- <div class="blog-navigation">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="blog.html" class="btn" style="border: 1px solid #39b8c1; color: #39b8c1;">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Blog
                                    </a>
                                </div>
                                <div class="col-md-6 text-end">
                                    <a href="contact.html" class="btn" style="background-color: #39b8c1; border-color: #39b8c1; color: white;">
                                        <i class="fas fa-envelope me-2"></i>Contact Us
                                    </a>
                                </div>
                            </div>
                        </div> -->
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-3">
                        <!-- Related Posts -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header text-white" style="background-color: #39b8c1;">
                                <h5 class="mb-0">
                                    <i class="fas fa-newspaper me-2"></i>Related Posts
                                </h5>
                            </div>
                            <div class="card-body p-0" id="relatedPostsContainer">
                                <div class="text-center p-4">
                                    <div class="spinner-border mb-2" style="color: #39b8c1;" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mb-0">Loading related posts...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Posts -->
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-header text-white" style="background-color: #40E0D0;">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Recent Posts
                                </h5>
                            </div>
                            <div class="card-body p-0" id="recentPostsContainer">
                                <div class="text-center p-4">
                                    <div class="spinner-border mb-2" style="color: #40E0D0;" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mb-0">Loading recent posts...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <div id="footer"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>

    <!-- Blog Detail Script -->
    <script>
        let currentBlog = null;
        
        // Pre-loaded blog data from server
        const preloadedBlog = <?php echo $blog ? json_encode($blog) : 'null'; ?>;
        
        // Get slug from URL parameters
        function getSlugFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('slug');
        }
        
        // Load blog post by slug
        async function loadBlogPost() {
            const slug = getSlugFromUrl();
            
            if (!slug) {
                showBlogNotFound();
                return;
            }
            
            // Use preloaded data if available
            if (preloadedBlog) {
                currentBlog = preloadedBlog;
                displayBlogPost(preloadedBlog);
                setupSocialSharing(preloadedBlog);
                loadRelatedPosts(slug);
            } else {
                // Fallback to API call
                try {
                    showLoading();
                    
                    const response = await fetch(`api/v1/publicBlogHandler.php?action=get&slug=${encodeURIComponent(slug)}`);
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        currentBlog = data.data;
                        displayBlogPost(data.data);
                        displayRelatedPosts(data.related_blogs || []);
                        setupSocialSharing(data.data);
                    } else {
                        showBlogNotFound();
                    }
                } catch (error) {
                    console.error('Error loading blog post:', error);
                    showBlogNotFound();
                }
            }
            
            // Load recent posts
            loadRecentPosts();
        }
        
        // Load related posts separately
        async function loadRelatedPosts(slug) {
            try {
                const response = await fetch(`api/v1/publicBlogHandler.php?action=get&slug=${encodeURIComponent(slug)}`);
                const data = await response.json();
                
                if (data.success && data.related_blogs) {
                    displayRelatedPosts(data.related_blogs);
                }
            } catch (error) {
                console.error('Error loading related posts:', error);
            }
        }
        
        function showLoading() {
            document.getElementById('blogLoading').style.display = 'flex';
            document.getElementById('blogNotFound').style.display = 'none';
            document.getElementById('blogDetailContent').style.display = 'none';
        }
        
        function showBlogNotFound() {
            document.getElementById('blogLoading').style.display = 'none';
            document.getElementById('blogNotFound').style.display = 'flex';
            document.getElementById('blogDetailContent').style.display = 'none';
        }
        
        function displayBlogPost(blog) {
            // Update content
            document.getElementById('breadcrumbTitle').textContent = truncateText(blog.title, 50);
            document.getElementById('blogTitle').textContent = blog.title;
            document.getElementById('blogAuthor').textContent = blog.author;
            document.getElementById('blogDate').textContent = blog.formatted_date;
            document.getElementById('blogViews').textContent = blog.views;
            document.getElementById('blogContent').innerHTML = blog.content;
            
            // Featured image
            if (blog.featured_image) {
                const featuredImg = document.getElementById('featuredImage');
                const featuredContainer = document.getElementById('featuredImageContainer');
                
                featuredImg.src = `api/uploads/blog/${blog.featured_image}`;
                featuredImg.alt = blog.title;
                featuredContainer.style.display = 'block';
            }
            
            // Tags
            if (blog.tags) {
                const tagsContainer = document.getElementById('blogTags');
                const tags = blog.tags.split(',').map(tag => tag.trim()).filter(tag => tag);
                
                if (tags.length > 0) {
                    tagsContainer.innerHTML = tags.map(tag => 
                        `<span class="badge" style="background-color: #40E0D0;">${tag}</span>`
                    ).join(' ');
                    document.getElementById('blogTagsContainer').style.display = 'block';
                }
            }
            
            // Show content
            document.getElementById('blogLoading').style.display = 'none';
            document.getElementById('blogDetailContent').style.display = 'block';
        }
        
        function displayRelatedPosts(relatedBlogs) {
            const container = document.getElementById('relatedPostsContainer');
            
            if (!relatedBlogs || relatedBlogs.length === 0) {
                container.innerHTML = '<p class="text-muted text-center p-4">No related posts found</p>';
                return;
            }
            
            container.innerHTML = relatedBlogs.map(blog => `
                <div class="border-bottom p-3">
                    <a href="blog-detail.php?slug=${blog.slug}" class="text-decoration-none">
                        <div class="row g-2 align-items-center">
                            <div class="col-4">
                                <img src="${blog.featured_image ? `api/uploads/blog/${blog.featured_image}` : 'assets/images/yatch1.png'}" 
                                     class="img-fluid rounded" 
                                     alt="${blog.title}"
                                     style="height: 60px; object-fit: cover;">
                            </div>
                            <div class="col-8">
                                <h6 class="mb-1 text-dark">${truncateText(blog.title, 60)}</h6>
                                <small class="text-muted">${blog.formatted_date}</small>
                            </div>
                        </div>
                    </a>
                </div>
            `).join('');
        }
        
        async function loadRecentPosts() {
            try {
                const response = await fetch('api/v1/publicBlogHandler.php?action=recent&limit=5');
                const data = await response.json();
                
                if (data.success && data.data) {
                    displayRecentPosts(data.data);
                }
            } catch (error) {
                console.error('Error loading recent posts:', error);
                document.getElementById('recentPostsContainer').innerHTML = 
                    '<p class="text-muted text-center p-4">Failed to load recent posts</p>';
            }
        }
        
        function displayRecentPosts(recentBlogs) {
            const container = document.getElementById('recentPostsContainer');
            
            if (!recentBlogs || recentBlogs.length === 0) {
                container.innerHTML = '<p class="text-muted text-center p-4">No recent posts found</p>';
                return;
            }
            
            // Filter out current blog if it's in the recent posts
            const filteredBlogs = currentBlog ? 
                recentBlogs.filter(blog => blog.id !== currentBlog.id) : 
                recentBlogs;
            
            container.innerHTML = filteredBlogs.map(blog => `
                <div class="border-bottom p-3">
                    <a href="blog-detail.php?slug=${blog.slug}" class="text-decoration-none">
                        <div class="row g-2 align-items-center">
                            <div class="col-4">
                                <img src="${blog.featured_image ? `api/uploads/blog/${blog.featured_image}` : 'assets/images/yatch1.png'}" 
                                     class="img-fluid rounded" 
                                     alt="${blog.title}"
                                     style="height: 60px; object-fit: cover;">
                            </div>
                            <div class="col-8">
                                <h6 class="mb-1 text-dark">${truncateText(blog.title, 60)}</h6>
                                <small class="text-muted">${blog.formatted_date_short}</small>
                            </div>
                        </div>
                    </a>
                </div>
            `).join('');
        }
        
        function setupSocialSharing(blog) {
            const currentUrl = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(blog.title);
            const excerpt = encodeURIComponent(blog.excerpt || blog.meta_description || '');
            
            // Facebook
            document.querySelectorAll('#shareFacebook, .share-facebook').forEach(btn => {
                btn.href = `https://www.facebook.com/sharer/sharer.php?u=${currentUrl}`;
                btn.target = '_blank';
            });
            
            // Twitter
            document.querySelectorAll('#shareTwitter, .share-twitter').forEach(btn => {
                btn.href = `https://twitter.com/intent/tweet?url=${currentUrl}&text=${title}`;
                btn.target = '_blank';
            });
            
            // LinkedIn
            document.querySelectorAll('#shareLinkedin, .share-linkedin').forEach(btn => {
                btn.href = `https://www.linkedin.com/sharing/share-offsite/?url=${currentUrl}`;
                btn.target = '_blank';
            });
            
            // WhatsApp
            document.querySelectorAll('#shareWhatsapp, .share-whatsapp').forEach(btn => {
                btn.href = `https://wa.me/?text=${title} ${currentUrl}`;
                btn.target = '_blank';
            });
        }
        
        // Utility functions
        function truncateText(text, maxLength) {
            if (!text) return '';
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength).trim() + '...';
        }
        
        function stripHtml(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        }
        
        // Load components and blog post when page loads
        async function loadComponent(id, file) {
            const response = await fetch(file);
            const html = await response.text();
            document.getElementById(id).innerHTML = html;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadComponent("header", "_partials/header.html");
            loadComponent("footer", "_partials/footer.html");
            loadBlogPost();
        });
    </script>
</body>
</html>
