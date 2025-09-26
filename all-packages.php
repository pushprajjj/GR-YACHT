<!DOCTYPE html>
<html lang="en" translate="yes">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="google-translate-customization" content="enabled">
    <title>GR Yachts Dubai - Luxury Yacht Rentals</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=5" rel="stylesheet">
    <link href="assets/css/allPackages.css?v=2" rel="stylesheet">
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
</head>

<body>
    <!-- Navigation -->
   <?php include '_partials/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero" id="heroSection">
        <div class="container h-100">
            <div class="row h-100 align-items-center" style="margin-top: -40px;">
                <div class="col-12 text-center text-white">
                    <h1 class="hero-title" id="heroTitle">Embark on an Unforgettable Luxury</h1>
                    <a href="#" class="btn btn-primary mt-3" id="heroButton">BOOK A YACHT</a>
                </div>
            </div>
        </div>
    </section>


    <!-- Main Content Section -->
    <section class="main-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Left Content - Featured Package -->
                <div class="col-lg-8">
                    <div class="yacht-details px-4" id="featuredPackageContent">
                        <!-- Loading state -->
                        <div class="text-center py-5" id="featuredPackageLoading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading featured package...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading featured package...</p>
                        </div>
                        
                        <!-- Content will be loaded here -->
                        <div id="featuredPackageData" style="display: none;">
                            <!-- Back to all packages link -->
                          
                            
                            <h2 class="yacht-title mb-2" id="featuredTitle">Super Yacht Rental - Sail Like Royalty!</h2>
                            <h3 class="price-heading mb-4" id="featuredPrice">For AED 50,000</h3>
                            
                            <!-- Package Image -->
                            <div class="yacht-image-container mb-4">
                                <img id="featuredImage" src="assets/images/allPackages/hero.png" alt="Featured Package" class="img-fluid w-100" style="border-radius: 15px;">
                            </div>

                            <!-- Description -->
                            <div class="yacht-description">
                                <div id="featuredDescription" class="mb-4">
                                    <p>Loading package description...</p>
                                </div>

                                <!-- Book Now Button -->
                                <div>
                                    <a href="#" id="featuredBookButton" class="btn btn-success btn-lg px-5 py-3" style="background: #25d366; border: none; border-radius: 25px;">
                                        <i class="fab fa-whatsapp me-2"></i> Book Now
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- No featured package state -->
                        <div id="noFeaturedPackage" style="display: none;" class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Featured Package</h4>
                            <p class="text-muted">Please check back later for our featured packages.</p>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar - All Packages -->
                <div class="col-lg-4">
                    <div class="packages-sidebar px-4">
                        <h3 class="sidebar-title mb-4">All <span style="font-weight: 400;">Packages</span></h3>
                        <hr style="height: 10px;">
                        <!-- Loading state -->
                        <div id="sidebarPackagesLoading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading packages...</span>
                            </div>
                            <p class="mt-2 text-muted small">Loading packages...</p>
                        </div>

                        <!-- Packages container -->
                        <div id="sidebarPackagesContainer">
                            <!-- Packages will be loaded here -->
                        </div>

                        <!-- Empty state -->
                        <div id="noSidebarPackages" style="display: none;" class="text-center py-4">
                            <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                            <p class="text-muted small">No packages available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <?php include '_partials/footer.php'; ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <script>
        // Global variables
        let allPackages = [];
        
  

        // Load packages data from API
        async function loadPackagesContent() {
            try {
                // Load hero section data
                await loadHeroSection();
                
                // Load all packages data
                await loadAllPackages();
                
            } catch (error) {
                console.error('Error loading packages content:', error);
                showError();
            }
        }

        // Load hero section content
        async function loadHeroSection() {
            try {
                const response = await fetch('api/v1/publicPackagesHandler.php?action=getHeroSection');
                const data = await response.json();
                
                if (data.success && data.data && data.data.homePageContent) {
                    const hero = data.data.homePageContent;
                    
                    // Update hero title
                    if (hero.title) {
                        document.getElementById('heroTitle').textContent = hero.title;
                    }
                    
                    // Update hero button
                    const heroButton = document.getElementById('heroButton');
                    if (hero.buttonText) {
                        heroButton.textContent = hero.buttonText;
                    }
                    if (hero.buttonLink) {
                        heroButton.href = hero.buttonLink;
                    }
                    
                    // Update hero background image
                    if (hero.imageUrl) {
                        document.getElementById('heroSection').style.backgroundImage = `url('${hero.imageUrl}')`;
                    }
                }
            } catch (error) {
                console.error('Error loading hero section:', error);
            }
        }

        // Load all packages and display featured + sidebar
        async function loadAllPackages() {
            try {
                const response = await fetch('api/v1/publicPackagesHandler.php?action=getAllPackages');
                const data = await response.json();
                
                if (data.success && data.data) {
                    allPackages = data.data; // Store globally
                    
                    // Check if there's a package specified in URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const packageId = urlParams.get('id');
                    
                    let featuredPackage;
                    let sidebarPackages;
                    
                    if (packageId) {
                        // Show specific package as featured
                        featuredPackage = allPackages.find(pkg => pkg.id == packageId);
                        if (featuredPackage) {
                            // All other packages go to sidebar
                            sidebarPackages = allPackages.filter(pkg => pkg.id != packageId);
                        } else {
                            // Package not found, fall back to default featured
                            featuredPackage = allPackages.find(pkg => pkg.is_featured == 1);
                            sidebarPackages = allPackages.filter(pkg => pkg.is_featured != 1);
                        }
                    } else {
                        // Default behavior - show featured package
                        featuredPackage = allPackages.find(pkg => pkg.is_featured == 1);
                        sidebarPackages = allPackages.filter(pkg => pkg.is_featured != 1);
                    }
                    
                    // Display featured package
                    if (featuredPackage) {
                        displayFeaturedPackage(featuredPackage);
                    } else {
                        showNoFeaturedPackage();
                    }
                    
                    // Display sidebar packages
                    displaySidebarPackages(sidebarPackages);
                    
                } else {
                    throw new Error('Failed to load packages');
                }
            } catch (error) {
                console.error('Error loading packages:', error);
                showNoFeaturedPackage();
                showNoSidebarPackages();
            }
        }

        // Display the featured package in the main content area
        function displayFeaturedPackage(pkg) {
            // Hide loading, show content
            document.getElementById('featuredPackageLoading').style.display = 'none';
            document.getElementById('featuredPackageData').style.display = 'block';
            
            // Show/hide back button based on URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const packageId = urlParams.get('id');
           
            
            // Update page title
            updatePageTitle(pkg.title);
            
            // Update featured package content
            document.getElementById('featuredTitle').textContent = pkg.title;
            // Update price
            const currency = localStorage.getItem('selectedCurrency') || 'AED';    
            const rate = localStorage.getItem('currencyRate') || 1;

            if (pkg.price) {
                document.getElementById('featuredPrice').textContent ='For '+ currency + ' '+ pkg.price*rate;
                document.getElementById('featuredPrice').style.display = 'block';
            } else {
                document.getElementById('featuredPrice').style.display = 'none';
            }
            
            // Update image
            if (pkg.filename) {
                document.getElementById('featuredImage').src = `api/uploads/packages/${pkg.filename}`;
                document.getElementById('featuredImage').alt = pkg.title;
            }
            
            // Update description
            if (pkg.description) {
                document.getElementById('featuredDescription').innerHTML = pkg.description;
            }
            
            // Update book button
            const bookButton = document.getElementById('featuredBookButton');
            if (pkg.book_button_text) {
                bookButton.innerHTML = `<i class="fab fa-whatsapp me-2"></i> ${pkg.book_button_text}`;
            }
            if (pkg.book_button_link) {
                bookButton.href = pkg.book_button_link;
            }
        }

        // Show no featured package state
        function showNoFeaturedPackage() {
            document.getElementById('featuredPackageLoading').style.display = 'none';
            document.getElementById('noFeaturedPackage').style.display = 'block';
        }

        // Display sidebar packages
        function displaySidebarPackages(packages) {
            const container = document.getElementById('sidebarPackagesContainer');
            const loading = document.getElementById('sidebarPackagesLoading');
            
            // Hide loading
            loading.style.display = 'none';
            
            if (!packages || packages.length === 0) {
                showNoSidebarPackages();
                return;
            }
            
            // Generate package cards HTML
            const packagesHTML = packages.map(pkg => `
                <div class="package-card mb-3" onclick="selectPackage(${pkg.id})">
                    <div class="row g-0">
                        <div class="col-4">
                            <img src="api/uploads/packages/${pkg.filename}" alt="${pkg.title}" class="package-image" onerror="this.src='assets/images/allPackages/hero.png'">
                        </div>
                        <div class="col-8">
                            <div class="package-content">
                                <h6 class="package-title">${pkg.title}</h6>
                                <p class="package-status">Know More <i class="fa-regular fa-circle-right"></i></p>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = packagesHTML;
        }

        // Show no sidebar packages state
        function showNoSidebarPackages() {
            document.getElementById('sidebarPackagesLoading').style.display = 'none';
            document.getElementById('noSidebarPackages').style.display = 'block';
        }

        // Handle package selection from sidebar
        function selectPackage(packageId) {
            // Update URL with package slug and reload content
            const pkg = allPackages.find(p => p.id == packageId);
            if (pkg) {
                const slug = createSlug(pkg.title);
                // Update URL without page reload
                window.history.pushState({packageId: packageId}, pkg.title, `?package=${slug}&id=${packageId}`);
                
                // Display selected package as featured
                displayFeaturedPackage(pkg);
                
                // Update sidebar to exclude the now-featured package
                const remainingPackages = allPackages.filter(p => p.id != packageId);
                displaySidebarPackages(remainingPackages);
            }
        }

        // Create URL-friendly slug from title
        function createSlug(title) {
            return title
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-') // Replace spaces with hyphens
                .replace(/-+/g, '-') // Replace multiple hyphens with single
                .trim('-'); // Remove leading/trailing hyphens
        }

        // Show general error state
        function showError() {
            document.getElementById('featuredPackageLoading').innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h4 class="text-danger">Error Loading Packages</h4>
                    <p class="text-muted">Please try refreshing the page.</p>
                    <button class="btn btn-primary" onclick="loadPackagesContent()">
                        <i class="fas fa-redo me-2"></i>Try Again
                    </button>
                </div>
            `;
            showNoSidebarPackages();
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.packageId) {
                // User went back/forward to a specific package
                const pkg = allPackages.find(p => p.id == event.state.packageId);
                if (pkg) {
                    displayFeaturedPackage(pkg);
                    const remainingPackages = allPackages.filter(p => p.id != event.state.packageId);
                    displaySidebarPackages(remainingPackages);
                }
            } else {
                // User went back to default state, reload content
                loadPackagesContent();
            }
        });

        // Update page title based on featured package
        function updatePageTitle(packageTitle) {
            if (packageTitle) {
                document.title = `${packageTitle} - GR Yachts Dubai`;
            } else {
                document.title = 'GR Yachts Dubai - Luxury Yacht Rentals';
            }
        }

        // Load content when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadPackagesContent();
        });
    </script>
</body>
</html>