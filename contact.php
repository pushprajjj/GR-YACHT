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
    <link href="assets/css/contatc.css?v=5" rel="stylesheet">
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
    <section class="hero">
        <div class="container h-100">
            <div class="row h-100 align-items-center" style="margin-top: -40px;">
                <div class="col-12 text-center text-white">
                    <h1 class="hero-title" id="heroTitle">Embark on an Unforgettable Luxury <br> Experience with Us</h1>
                    <button class="btn btn-primary mt-3" id="heroButton">BOOK A YACHT</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Content Section -->
    <section class="contact-section py-5">
        <div class="container">
            <!-- Contact Header -->
            <div class="contact-header text-center mb-5">
                <div class="d-flex justify-content-center align-items-center gap-4 mb-4">
                    <span class="contact-info-text" id="contactInfoTitle">CONTACT US FOR MORE INFO</span>
                    <a href="https://wa.me/+971581862811" id="whatsappLink" class="whatsapp-btn btn btn-success d-flex align-items-center gap-2">
                        <i class="fab fa-whatsapp"></i>
                        <span id="whatsappNumber">+971 58 186 2811</span>
                    </a>
                </div>
            </div>

            <!-- Contact Form Section -->
            <div class="contact-form-section" id="contact-form">
                <div class="text-center" style="margin: 6rem 0 0 0 ;">
                    <p class="subheading" id="formSubtitle">Fill the details</p>
                    <h2 class="heading" id="formTitle">Drop us message for business & query</h2>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <!-- Alert Messages -->
                        <div id="alertMessage" class="alert alert-dismissible fade" role="alert" style="display: none;">
                            <span id="alertText"></span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        
                        <form class="contact-form" id="contactForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control contact-input" name="name" placeholder="Name" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" class="form-control contact-input" name="email" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <textarea class="form-control contact-textarea" name="message" rows="6" placeholder="Message" required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="contact-submit-btn" id="submitBtn">
                                    <span class="btn-text">Send</span>
                                    <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="map-section mt-5">
                <div class="map-container" id="mapContainer">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4286.068757984126!2d55.14171589999999!3d25.092280099999993!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e5f6bf95c8a25d1%3A0x38632f3f01b35be4!2sDubai%20Harbour%20-%20Yacht%20Club!5e1!3m2!1sen!2sin!4v1757347257302!5m2!1sen!2sin"
                        width="100%" 
                        height="400" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <!-- Contact Info Section -->
            <div class="contact-info-section mt-5">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="contact-info-item">
                            <div class="contact-icon mb-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h5 class="contact-info-title" id="locationTitle">GR YACHTS</h5>
                            <p class="contact-info-details" id="locationAddress">Dubai Harbour, Dubai Marina, Dubai</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="contact-info-item">
                            <div class="contact-icon mb-3">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <h5 class="contact-info-title">Whatsapp</h5>
                            <p class="contact-info-details" id="locationWhatsapp">+971 58 186 2811</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="contact-info-item">
                            <div class="contact-icon mb-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h5 class="contact-info-title">Email us at</h5>
                            <p class="contact-info-details" id="locationEmail">sales@gr-yachts.com</p>
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
    
    <!-- Contact Page Dynamic Content Script -->
    <script>
        // Load contact page content from API
        async function loadContactPageContent() {
            try {
                const response = await fetch('api/v1/publicContactPageHandler.php?action=getContactContent');
                const data = await response.json();
                
                if (data.success && data.data) {
                    updateContactPageContent(data.data);
                } else {
                    console.warn('Failed to load contact page content:', data.message);
                }
            } catch (error) {
                console.error('Error loading contact page content:', error);
            }
        }

        function updateContactPageContent(content) {
            // Update hero section
            if (content.hero) {
                const heroTitle = document.getElementById('heroTitle');
                const heroButton = document.getElementById('heroButton');
                const heroSection = document.querySelector('.hero');
                
                if (heroTitle && content.hero.title && content.hero.subtitle) {
                    heroTitle.innerHTML = `${content.hero.title} <br> ${content.hero.subtitle}`;
                }
                
                if (heroButton) {
                    if (content.hero.button_text) {
                        heroButton.textContent = content.hero.button_text;
                    }
                    if (content.hero.button_link) {
                        heroButton.onclick = () => {
                            if (content.hero.button_link.startsWith('#')) {
                                document.querySelector(content.hero.button_link)?.scrollIntoView({ behavior: 'smooth' });
                            } else {
                                window.open(content.hero.button_link, '_blank');
                            }
                        };
                    }
                }
                
                // Update hero background image
                if (heroSection && content.hero.image_path) {
                    const imageUrl = `api/uploads/contact/${content.hero.image_path}`;
                    
                    // Add loading state
                    heroSection.style.opacity = '0.8';
                    
                    // Test if image exists before applying
                    const testImage = new Image();
                    testImage.onload = function() {
                        heroSection.style.backgroundImage = `url('${imageUrl}')`;
                        heroSection.style.backgroundSize = 'cover';
                        heroSection.style.backgroundPosition = 'center center';
                        heroSection.style.backgroundRepeat = 'no-repeat';
                        heroSection.style.opacity = '1';
                        console.log('Hero background updated to:', imageUrl);
                    };
                    testImage.onerror = function() {
                        // Fallback to default background if custom image fails to load
                        heroSection.style.backgroundImage = "url('assets/images/contact/hero.png')";
                        heroSection.style.backgroundSize = 'cover';
                        heroSection.style.backgroundPosition = 'center center';
                        heroSection.style.backgroundRepeat = 'no-repeat';
                        heroSection.style.opacity = '1';
                        console.warn('Failed to load custom hero image, using default:', imageUrl);
                    };
                    testImage.src = imageUrl;
                } else if (heroSection) {
                    // Fallback to default background if no custom image
                    heroSection.style.backgroundImage = "url('assets/images/contact/hero.png')";
                    heroSection.style.backgroundSize = 'cover';
                    heroSection.style.backgroundPosition = 'center center';
                    heroSection.style.backgroundRepeat = 'no-repeat';
                    console.log('Using default hero background');
                }
            }

            // Update contact info section
            if (content.contact_info) {
                const contactInfoTitle = document.getElementById('contactInfoTitle');
                const whatsappNumber = document.getElementById('whatsappNumber');
                const whatsappLink = document.getElementById('whatsappLink');
                
                if (contactInfoTitle && content.contact_info.title) {
                    contactInfoTitle.textContent = content.contact_info.title;
                }
                
                if (whatsappNumber && content.contact_info.whatsapp_number) {
                    whatsappNumber.textContent = content.contact_info.whatsapp_number;
                }
                
                if (whatsappLink && content.contact_info.whatsapp_number) {
                    const cleanNumber = content.contact_info.whatsapp_number.replace(/\s+/g, '');
                    whatsappLink.href = `https://wa.me/${cleanNumber}`;
                }
            }

            // Update form section
            if (content.form_section) {
                const formSubtitle = document.getElementById('formSubtitle');
                const formTitle = document.getElementById('formTitle');
                
                if (formSubtitle && content.form_section.subtitle) {
                    formSubtitle.textContent = content.form_section.subtitle;
                }
                
                if (formTitle && content.form_section.title) {
                    formTitle.textContent = content.form_section.title;
                }
            }

            // Update location info section
            if (content.location_info) {
                const locationTitle = document.getElementById('locationTitle');
                const locationAddress = document.getElementById('locationAddress');
                const locationWhatsapp = document.getElementById('locationWhatsapp');
                const locationEmail = document.getElementById('locationEmail');
                
                if (locationTitle && content.location_info.title) {
                    locationTitle.textContent = content.location_info.title;
                }
                
                if (locationAddress && content.location_info.address) {
                    locationAddress.textContent = content.location_info.address;
                }
                
                if (locationWhatsapp && content.location_info.whatsapp_number) {
                    locationWhatsapp.textContent = content.location_info.whatsapp_number;
                }
                
                if (locationEmail && content.location_info.email) {
                    locationEmail.textContent = content.location_info.email;
                }
            }

            // Update map section
            if (content.map && content.map.map_iframe) {
                const mapContainer = document.getElementById('mapContainer');
                if (mapContainer) {
                    let mapContent = content.map.map_iframe;
                    
                    // Check if it's already complete iframe code
                    if (mapContent.trim().toLowerCase().includes('<iframe')) {
                        // It's complete iframe code, use it directly but ensure proper dimensions
                        mapContent = mapContent.replace(/width=["'][^"']*["']/gi, 'width="100%"');
                        mapContent = mapContent.replace(/height=["'][^"']*["']/gi, 'height="400"');
                        
                        // If no width/height specified, add them
                        if (!mapContent.includes('width=')) {
                            mapContent = mapContent.replace('<iframe', '<iframe width="100%"');
                        }
                        if (!mapContent.includes('height=')) {
                            mapContent = mapContent.replace('<iframe', '<iframe height="400"');
                        }
                        
                        mapContainer.innerHTML = mapContent;
                    } else {
                        // It's just a URL, create iframe element
                        mapContainer.innerHTML = `
                            <iframe 
                                src="${mapContent}"
                                width="100%" 
                                height="400" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        `;
                    }
                }
            }
        }

        // Load content when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadContactPageContent();
        });
    </script>

    <!-- Contact Form AJAX Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnSpinner = submitBtn.querySelector('.btn-spinner');
            const alertMessage = document.getElementById('alertMessage');
            const alertText = document.getElementById('alertText');

            // Function to show alert messages
            function showAlert(message, type) {
                alertText.textContent = message;
                alertMessage.className = `alert alert-${type} alert-dismissible fade show`;
                alertMessage.style.display = 'block';
                
                // Auto hide after 5 seconds
                setTimeout(() => {
                    alertMessage.style.display = 'none';
                }, 5000);
            }

            // Function to set loading state
            function setLoadingState(isLoading) {
                if (isLoading) {
                    submitBtn.disabled = true;
                    btnText.textContent = 'Sending...';
                    btnSpinner.classList.remove('d-none');
                } else {
                    submitBtn.disabled = false;
                    btnText.textContent = 'Send';
                    btnSpinner.classList.add('d-none');
                }
            }

            // Handle form submission
            contactForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(contactForm);
                
                // Validate form fields
                const name = formData.get('name').trim();
                const email = formData.get('email').trim();
                const message = formData.get('message').trim();
                
                if (!name || !email || !message) {
                    showAlert('Please fill in all required fields.', 'danger');
                    return;
                }
                
                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showAlert('Please enter a valid email address.', 'danger');
                    return;
                }
                
                // Set loading state
                setLoadingState(true);
                
                try {
                    // Make API request
                    const response = await fetch('api/v1/contacHandler.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    // Parse response
                    const result = await response.json();
                    
                    if (response.ok && result.success) {
                        // Success
                        showAlert(result.message || 'Your message has been sent successfully! We will get back to you soon.', 'success');
                        contactForm.reset();
                    } else {
                        // Error from server
                        showAlert(result.message || 'Failed to send message. Please try again.', 'danger');
                    }
                    
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('Network error occurred. Please check your connection and try again.', 'danger');
                } finally {
                    // Reset loading state
                    setLoadingState(false);
                }
            });
            
            // Hide alert when user starts typing
            const formInputs = contactForm.querySelectorAll('input, textarea');
            formInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (alertMessage.style.display !== 'none') {
                        alertMessage.style.display = 'none';
                    }
                });
            });
        });
    </script>

      <script>
         async function loadComponent(id, file) {
      const response = await fetch(file);
      const html = await response.text();
      document.getElementById(id).innerHTML = html;
    }
    loadComponent("header", "_partials/header.html");
    loadComponent("footer", "_partials/footer.html");
  
    </script>
</body>
</html>