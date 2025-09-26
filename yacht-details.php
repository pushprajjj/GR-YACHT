<!DOCTYPE html>
<html lang="en" translate="yes">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="google" content="notranslate">
    <meta name="google-translate-customization" content="enabled">
    <title>GR Yachts Dubai - Luxury Yacht Rentals</title>
    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <!-- IntlTelInput CSS -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css"
    />
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=54" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/yacht-details.css?v=23" />
    <link href="assets/css/translation-widget.css" rel="stylesheet" />

    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    
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
    <!-- Hero Section with Image Grid -->
    <section class="images" id="yachtImageSection">
      <div class="col-12 imageSection">
        <!-- Loading placeholder -->
        <div id="imageGridLoading" class="d-flex justify-content-center align-items-center" style="height: 600px; background-color: #f8f9fa;">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading yacht images...</p>
          </div>
        </div>
        
        <!-- Image Grid -->
        <div id="yachtImageGrid" class="yacht-image-grid" style="display: none;">
          <div class="row g-0">
            <div class="col-md-7 p-0 main-image-container position-relative">
              <img id="mainImage" src="" alt="Main yacht image" class="img-fluid w-100 h-100 main-yacht-image"
                   style="border-right: 3px solid rgb(255, 255, 255); min-height: 600px; object-fit: cover;">
              <button class="view-all-btn position-absolute" onclick="openLightbox()" type="button">
                <i class="fas fa-images me-2"></i>
                <span>View All Photos</span>
                <span id="imageCount" class="image-count ms-2 badge bg-light text-dark">0</span>
              </button>
            </div>
            <div class="col-md-5 p-0">
              <div class="row g-0">
                <div class="col-12 side-image-wrapper">
                  <img id="sideImage1" src="" alt="Yacht image 2" class="img-fluid w-100 h-100 side-yacht-image"
                       style="min-height: 299px; object-fit: cover; cursor: pointer;" onclick="openLightbox(1)">
                </div>
              </div>
              <div class="row g-0">
                <div class="col-12 side-image-wrapper position-relative">
                  <img id="sideImage2" src="" alt="Yacht image 3" class="img-fluid w-100 h-100 side-yacht-image"
                       style="border-top: 3px solid rgb(255, 255, 255); min-height: 299px; object-fit: cover; cursor: pointer;" onclick="openLightbox(2)">
                  <div class="more-images-overlay position-absolute d-flex align-items-center justify-content-center" 
                       style="top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); color: white; cursor: pointer;"
                       onclick="openLightbox(2)" id="moreImagesOverlay">
                    <div class="text-center">
                      <i class="fas fa-plus-circle fa-3x mb-2"></i>
                      <div class="h5">View More</div>
                      <!-- <div ><span id="remainingCount">0</span> Photos</div> -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Lightbox Modal -->
    <div class="modal fade" id="yachtLightbox" tabindex="-1" aria-labelledby="yachtLightboxLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
          <div class="modal-header border-0">
            <h5 class="modal-title text-white" id="yachtLightboxLabel">Yacht Gallery</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div id="lightboxCarousel" class="carousel slide" data-bs-ride="false">
              <!-- Carousel indicators -->
              <div class="carousel-indicators" id="lightboxIndicators">
                <!-- Will be populated by JavaScript -->
              </div>
              
              <!-- Carousel inner -->
              <div class="carousel-inner" id="lightboxCarouselInner" style="max-height: 80vh;">
                <!-- Will be populated by JavaScript -->
              </div>
              
              <!-- Carousel controls -->
              <button class="carousel-control-prev" type="button" data-bs-target="#lightboxCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#lightboxCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
              </button>
            </div>
          </div>
          <div class="modal-footer border-0 justify-content-center">
            <div class="lightbox-info text-white">
              <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <section class="DetailsSection">
      <div class="container detailsMenu">
        <ul>
          <li><a href="#yachtImageSection" class="text-decoration-none text-dark">IMAGES</a></li>
           <li><a href="#yachtDetailsContent" class="text-decoration-none text-dark">OVERVIEW</a></li>
           <li><a href="#accordionFlushExample" class="text-decoration-none text-dark">HIGHLIGHTS</a></li>
           <li><a href="#locationSection" class="text-decoration-none text-dark">LOCATION</a></li>
        </ul>
      </div>

      <div class="col-12 mt-5">
        <div class="row g-4"> 
          <div class="col-md-7 detailsContent" id="yachtDetailsContent">
            <!-- // Left Column Content -->
            <div class="container overview">
              <div class="">
                <h2 id="yachtTitle">Loading...</h2>

                <h4 id="yachtSubtitle"><span id="yachtLength">--</span> FT YACHT</h4>

                <h4 class="mt-4">
                  PRICE PER HOUR: <strong id="pricePerHour">AED --</strong> | PRICE PER DAY:
                  <strong id="pricePerDay">AED --+VAT</strong>
                </h4>
                <div class="blueStrip mt-3">
                  <p class="text-light">
                    <span class="info-item">
                      <i class="fa-solid fa-user-group"></i> Guests: <span id="numberOfGuests">--</span>
                    </span>
                    <span class="info-separator"> | </span>
                    <span class="info-item">
                      <i class="fa-solid fa-bed-pulse"></i> Overnight Guests: <span id="overnightGuests">--</span>
                    </span>
                    <span class="info-separator"> | </span>
                    <span class="info-item">
                      <i class="fa-solid fa-sailboat"></i> <span id="yachtType">Yacht</span>
                    </span>
                    <span class="info-separator"> | </span>
                    <span class="info-item">
                      <i class="fa-regular fa-clock"></i> Min. Charter Length: <span id="minCharterLength">-- Hours</span>
                    </span>
                  </p>
                </div>
              </div>

              <div class="description mt-4">
                <div id="yachtDescription">
                  <p>Loading yacht description...</p>
                </div>
              </div>

              <div class="accordion accordion-flush" id="accordionFlushExample">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button collapsed"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#flush-collapseOne"
                      aria-expanded="false"
                      aria-controls="flush-collapseOne"
                    >
                      FACILITIES
                    </button>
                  </h2>
                  <div
                    id="flush-collapseOne"
                    class="accordion-collapse collapse"
                    data-bs-parent="#accordionFlushExample"
                  >
                    <div class="accordion-body" id="facilitiesContent">
                      Loading facilities...
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button collapsed"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#flush-collapseTwo"
                      aria-expanded="false"
                      aria-controls="flush-collapseTwo"
                    >
                      EXPERIENCES
                    </button>
                  </h2>
                  <div
                    id="flush-collapseTwo"
                    class="accordion-collapse collapse"
                    data-bs-parent="#accordionFlushExample"
                  >
                    <div class="accordion-body" id="experiencesContent">
                      Loading experiences...
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button collapsed"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#flush-collapseThree"
                      aria-expanded="false"
                      aria-controls="flush-collapseThree"
                    >
                      WATERSPORTS
                    </button>
                  </h2>
                  <div
                    id="flush-collapseThree"
                    class="accordion-collapse collapse"
                    data-bs-parent="#accordionFlushExample"
                  >
                    <div class="accordion-body" id="watersportsContent">
                      Loading watersports...
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button collapsed"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#flush-collapseThreee"
                      aria-expanded="false"
                      aria-controls="flush-collapseThreee"
                    >
                      CREW
                    </button>
                  </h2>
                  <div
                    id="flush-collapseThreee"
                    class="accordion-collapse collapse"
                    data-bs-parent="#accordionFlushExample"
                  >
                    <div class="accordion-body" id="crewContent">
                      Loading crew information...
                    </div>
                  </div>
                </div>
              </div>

              <div class="location" id="locationSection">
                <h4 class="mt-5 mb-4">LOCATION</h4>
                <p>Dubai Harbour</p>
                <div class="mapouter">
                  <div class="gmap_canvas">
                    <iframe
                      width="100%"
                      height="400"
                      id="gmap_canvas"
                      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4286.068757984126!2d55.14171589999999!3d25.092280099999993!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e5f6bf95c8a25d1%3A0x38632f3f01b35be4!2sDubai%20Harbour%20-%20Yacht%20Club!5e1!3m2!1sen!2sin!4v1757347257302!5m2!1sen!2sin"
                      frameborder="0"
                      scrolling="no"
                      marginheight="0"
                      marginwidth="0"
                    ></iframe>
                    <a href="https://123movies-to.org"></a><br />
                    <style>
                      .mapouter {
                        position: relative;
                        text-align: right;
                        height: 400px;
                        width: 100%;
                      }
                    </style>
                    <a href="https://www.embedgooglemap.net"
                      >embedgooglemap.net</a
                    >
                    <style>
                      .gmap_canvas {
                        overflow: hidden;
                        background: none !important;
                        height: 400px;
                        width: 100%;
                      }
                    </style>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-5">
            <div class="container bookingSection">
              <div class="container bookingFormHeading">
                <h2>CHECK AVAILABILITY</h2>
                <div class="yachtName">
                  <h4 id="bookingYachtName">Loading...</h4>
                </div>
              </div>

              <div class="form">
                <!-- Alert Messages -->
                <div id="yachtAlertMessage" class="alert alert-dismissible fade" role="alert" style="display: none; margin-bottom: 20px;">
                    <span id="yachtAlertText"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <form id="yachtBookingForm">
                  <input
                    type="text"
                    name="full_name"
                    id="name"
                    placeholder="FULL NAME"
                    required
                  />
                  <input
                    type="email"
                    name="email"
                    id="email"
                    placeholder="EMAIL ADDRESS"
                    required
                  />
                  <input
                    type="tel"
                    name="phone"
                    id="phone"
                    placeholder="PHONE NUMBER"
                    required
                  />
                  <input
                    type="text"
                    name="preferred_date"
                    id="date"
                    placeholder="PREFERRED DATE"
                    onfocus="(this.type='date')"
                    required
                  />
                  <input
                    type="text"
                    name="preferred_time"
                    id="time"
                    placeholder="PREFERRED TIME"
                    onfocus="(this.type='time')"
                    required
                  />
                  <select name="charter_length" id="CharterLength" required>
                    <option value="" disabled selected>
                      SELECT CHARTER LENGTH
                    </option>
                    <option value="1 Hour">1 Hour</option>
                    <option value="2 Hours">2 Hours</option>
                    <option value="3 Hours">3 Hours</option>
                    <option value="4 Hours">4 Hours</option>
                    <option value="5 Hours">5 Hours</option>
                    <option value="6 Hours">6 Hours</option>
                    <option value="Full Day">Full Day</option>
                  </select>
                  <input type="hidden" name="yacht_name" value="BLACK PEARL 95">
                  <button type="submit" class="btn btn-primary w-100" id="yachtSubmitBtn">
                    <span class="btn-text">SUBMIT</span>
                    <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                  </button>
                </form>
              </div>

              <div class="member">
                <div class="charter-member-card" style=" border-radius: 12px; padding: 24px; text-align: center; ">
                  <!-- Header with WhatsApp icon -->
                  <div class="charter-header mb-4">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                      <i class="fa-brands fa-whatsapp" style="color: #25D366; font-size: 40px;"></i>
                      <span style="font-size: 16px; font-weight: 600; color: #333; letter-spacing: 0.5px;">SPEAK TO OUR</span>
                    </div>
                    <h5 style="font-weight: 700; color: #333; margin: 0; font-size: 16px; letter-spacing: 0.5px;">CHARTER TEAM MEMBER</h5>
                  </div>
                  
                  <!-- Profile Image -->
                  <div class="member-image mb-3">
                    <img
                      src="assets/images/yachtdetailsPage/user.png"
                      alt="Rohit Albert Roy"
                      class="rounded-circle"
                      style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.15);"
                    />
                  </div>
                  
                  <!-- Member Info -->
                  <div class="member-details mb-4">
                    <h5 class="mb-1" style="font-weight: 700; color: #333; font-size: 18px;">Rohit Albert Roy</h5>
                    <p class="mb-0" style="color: #666; font-size: 14px; font-weight: 500;">Yacht Charter Manager</p>
                  </div>
                  
                  <!-- WhatsApp Button -->
                  <a
                    href="https://wa.me/+971505543873"
                    class="btn d-flex align-items-center justify-content-center gap-2 w-100"
                    style="background-color: #25D366; border: none; color: white; border-radius: 8px; padding: 12px 24px; font-weight: 600; font-size: 14px; letter-spacing: 0.5px; text-decoration: none; transition: all 0.3s ease;"
                    onmouseover="this.style.backgroundColor='#1ea952'"
                    onmouseout="this.style.backgroundColor='#25D366'"
                  >
                    <i class="fa-brands fa-whatsapp" style="font-size: 16px;"></i>
                    <span>WHATSAPP</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- More Yachts Section -->
    <section class="yacht-fleet">
      <div class="container-fluid px-4">
        <h2 class="subheading">FIND</h2>
        <h3 class="heading">MORE YACHTS</h3>

        <!-- Loading state -->
        <div id="moreYachtsLoading" class="d-flex justify-content-center align-items-center" style="height: 300px;">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading more yachts...</p>
          </div>
        </div>

        <!-- Yacht Slider -->
        <div id="moreYachtsSlider" class="carousel slide" data-bs-ride="false" style="display: none;">
          <!-- Carousel indicators -->
          <div class="carousel-indicators" id="yachtCarouselIndicators" style="display: none;">
            <!-- Will be populated by JavaScript -->
          </div>
          
          <!-- Carousel inner -->
          <div class="carousel-inner" id="yachtCarouselInner">
            <!-- Will be populated by JavaScript -->
          </div>
          
          <!-- Carousel controls -->
          <button class="carousel-control-prev" type="button" data-bs-target="#moreYachtsSlider" data-bs-slide="prev" id="yachtCarouselPrev" style="display: none;">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#moreYachtsSlider" data-bs-slide="next" id="yachtCarouselNext" style="display: none;">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>

        <!-- Fallback: Regular grid for small screens or when slider fails -->
        <div class="row g-4 px-3" id="moreYachtsFallback" style="display: none;">
          <!-- Will be populated by JavaScript if slider fails -->
        </div>
      </div>
    </section>

    <!-- Navigation Controls for Yacht Slider -->
    <div class="navigation-controls text-center" id="yachtSliderNavigation" style="display: none;">
      <div class="d-flex justify-content-center align-items-center gap-5">
        <button class="nav-arrow prev" onclick="document.getElementById('yachtCarouselPrev').click()" style="font-size: 34px; padding: 35px 35px;">
          <i class="fa-solid fa-arrow-left fa-sm"></i>
        </button>
        <button class="nav-arrow next" onclick="document.getElementById('yachtCarouselNext').click()" style="font-size: 34px; padding: 35px 35px;">
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- Footer -->
  <?php include '_partials/footer.php'; ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- IntlTelInput JS -->
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
    <script>
      const phoneInput = document.querySelector("#phone");
      const intlTelInstance = window.intlTelInput(phoneInput, {
        initialCountry: "ae",
        preferredCountries: ["ae", "gb", "us"],
        separateDialCode: true,
        utilsScript:
          "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
      });
    </script>

    <!-- Yacht Booking Form AJAX Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yachtBookingForm = document.getElementById('yachtBookingForm');
            const yachtSubmitBtn = document.getElementById('yachtSubmitBtn');
            const btnText = yachtSubmitBtn.querySelector('.btn-text');
            const btnSpinner = yachtSubmitBtn.querySelector('.btn-spinner');
            const yachtAlertMessage = document.getElementById('yachtAlertMessage');
            const yachtAlertText = document.getElementById('yachtAlertText');

            // Function to show alert messages
            function showYachtAlert(message, type) {
                yachtAlertText.textContent = message;
                yachtAlertMessage.className = `alert alert-${type} alert-dismissible fade show`;
                yachtAlertMessage.style.display = 'block';
                
                // Auto hide after 8 seconds for success messages
                if (type === 'success') {
                    setTimeout(() => {
                        yachtAlertMessage.style.display = 'none';
                    }, 8000);
                } else {
                    // Auto hide after 5 seconds for error messages
                    setTimeout(() => {
                        yachtAlertMessage.style.display = 'none';
                    }, 5000);
                }
            }

            // Function to set loading state
            function setYachtLoadingState(isLoading) {
                if (isLoading) {
                    yachtSubmitBtn.disabled = true;
                    btnText.textContent = 'SUBMITTING...';
                    btnSpinner.classList.remove('d-none');
                } else {
                    yachtSubmitBtn.disabled = false;
                    btnText.textContent = 'SUBMIT';
                    btnSpinner.classList.add('d-none');
                }
            }

            // Handle form submission
            yachtBookingForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(yachtBookingForm);
                
                // Get the full phone number with country code
                const phoneNumber = intlTelInstance.getNumber();
                if (phoneNumber) {
                    formData.set('phone', phoneNumber);
                }
                
                // Basic validation
                const fullName = formData.get('full_name').trim();
                const email = formData.get('email').trim();
                const phone = formData.get('phone').trim();
                const preferredDate = formData.get('preferred_date');
                const preferredTime = formData.get('preferred_time');
                const charterLength = formData.get('charter_length');
                
                if (!fullName || !email || !phone || !preferredDate || !preferredTime || !charterLength) {
                    showYachtAlert('Please fill in all required fields.', 'danger');
                    return;
                }
                
                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showYachtAlert('Please enter a valid email address.', 'danger');
                    return;
                }
                
                // Date validation (not in past)
                const selectedDate = new Date(preferredDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    showYachtAlert('Booking date cannot be in the past.', 'danger');
                    return;
                }
                
                // Set loading state
                setYachtLoadingState(true);
                
                try {
                    // Make API request
                    const response = await fetch('api/v1/yachtBookingHandler.php', {
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
                        let successMessage = result.message;
                        if (result.estimated_price) {
                            successMessage += ` Estimated price: ${result.estimated_price} ${result.currency || 'AED'}`;
                        }
                        showYachtAlert(successMessage, 'success');
                        yachtBookingForm.reset();
                        
                        // Reset form field types
                        document.getElementById('date').type = 'text';
                        document.getElementById('time').type = 'text';
                    } else {
                        // Error from server
                        showYachtAlert(result.message || 'Failed to submit booking request. Please try again.', 'danger');
                    }
                    
                } catch (error) {
                    console.error('Error:', error);
                    showYachtAlert('Network error occurred. Please check your connection and try again.', 'danger');
                } finally {
                    // Reset loading state
                    setYachtLoadingState(false);
                }
            });
            
            // Hide alert when user starts typing
            const formInputs = yachtBookingForm.querySelectorAll('input, select');
            formInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (yachtAlertMessage.style.display !== 'none') {
                        yachtAlertMessage.style.display = 'none';
                    }
                });
            });

            // Set minimum date to today
            const dateInput = document.getElementById('date');
            const today = new Date().toISOString().split('T')[0];
            dateInput.addEventListener('focus', function() {
                this.type = 'date';
                this.min = today;
            });
        });
    </script>

    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>

      <script>
     
    // Load yacht details from URL parameters
    async function loadYachtDetails() {
        try {
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const yachtId = urlParams.get('id');
            const slug = urlParams.get('slug');
            
            if (!yachtId) {
                console.error('No yacht ID provided in URL');
                showYachtAlert('Yacht not found. Please select a yacht from the fleet.', 'danger');
                return;
            }

            // Fetch yacht data from API
            const response = await fetch(`api/v1/yachtFleetHandler.php?action=get&id=${yachtId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const { success, data } = await response.json();
            if (!success || !data) {
                throw new Error('Yacht not found');
            }

            // Update page with yacht data
            updateYachtDetailsPage(data);
            
            // Update page title with yacht name
            document.title = `${data.title} - GR Yachts Dubai`;
            
            // Load more yachts slider (exclude current yacht)
            loadMoreYachts(data.id);
            
        } catch (error) {
            console.error('Error loading yacht details:', error);
            showYachtAlert('Failed to load yacht details. Please try again.', 'danger');
        }
    }

    function updateYachtDetailsPage(yacht) {
        // Create image grid
        createImageGrid(yacht);
        
        // Update yacht details
        updateYachtInfo(yacht);
    }

    function createImageGrid(yacht) {
        console.log('Creating image grid for yacht:', yacht.title);
        
        const imageGridLoading = document.getElementById('imageGridLoading');
        const yachtImageGrid = document.getElementById('yachtImageGrid');
        const mainImage = document.getElementById('mainImage');
        const sideImage1 = document.getElementById('sideImage1');
        const sideImage2 = document.getElementById('sideImage2');
        const imageCount = document.getElementById('imageCount');
        const remainingCount = document.getElementById('remainingCount');
        const moreImagesOverlay = document.getElementById('moreImagesOverlay');

        // Ensure loading is visible initially
        if (imageGridLoading) {
            imageGridLoading.style.display = 'flex';
        }

        let yachtImages = [];
        
        // Parse yacht images
        if (yacht.yacht_images) {
            try {
                yachtImages = JSON.parse(yacht.yacht_images);
                console.log('Parsed yacht_images:', yachtImages);
            } catch (e) {
                console.error('Error parsing yacht_images:', e);
                yachtImages = yacht.main_image ? [yacht.main_image] : [];
            }
        } else if (yacht.main_image) {
            yachtImages = [yacht.main_image];
            if (yacht.secondary_images) {
                try {
                    const secondaryImages = JSON.parse(yacht.secondary_images);
                    yachtImages = yachtImages.concat(secondaryImages);
                } catch (e) {
                    console.error('Error parsing secondary_images:', e);
                }
            }
        }

        console.log('Final yacht images array:', yachtImages);

        // Store images globally for lightbox
        window.currentYachtImages = yachtImages;

        if (yachtImages.length === 0) {
            console.log('No images found, showing placeholder');
            mainImage.src = 'assets/images/placeholder-yacht.jpg';
            sideImage1.src = 'assets/images/placeholder-yacht.jpg';
            sideImage2.src = 'assets/images/placeholder-yacht.jpg';
            imageCount.textContent = '0';
            moreImagesOverlay.style.display = 'none';
        } else {
            // Set main image (first image)
            mainImage.src = `api/uploads/yachtFleet/${yachtImages[0]}`;
            mainImage.alt = `${yacht.title} - Main Image`;
            
            // Set side images
            if (yachtImages.length > 1) {
                sideImage1.src = `api/uploads/yachtFleet/${yachtImages[1]}`;
                sideImage1.alt = `${yacht.title} - Image 2`;
            } else {
                sideImage1.src = `api/uploads/yachtFleet/${yachtImages[0]}`;
                sideImage1.alt = `${yacht.title} - Image 1`;
            }
            
            if (yachtImages.length > 2) {
                sideImage2.src = `api/uploads/yachtFleet/${yachtImages[2]}`;
                sideImage2.alt = `${yacht.title} - Image 3`;
                
                // Show overlay with remaining count if more than 3 images
                if (yachtImages.length > 3) {
                    const remaining = yachtImages.length - 3;
                    remainingCount.textContent = `+${remaining} Photos`;
                    moreImagesOverlay.style.display = 'flex';
                } else {
                    moreImagesOverlay.style.display = 'none';
                }
            } else {
                sideImage2.src = `api/uploads/yachtFleet/${yachtImages[0]}`;
                sideImage2.alt = `${yacht.title} - Image 1`;
                moreImagesOverlay.style.display = 'none';
            }
            
            // Update image count
            imageCount.textContent = yachtImages.length;
        }

        // Hide loading and show grid
        if (imageGridLoading) {
            imageGridLoading.style.display = 'none';
            imageGridLoading.style.visibility = 'hidden';
            imageGridLoading.classList.add('d-none');
        }
        
        if (yachtImageGrid) {
            yachtImageGrid.style.display = 'block';
            yachtImageGrid.style.visibility = 'visible';
            yachtImageGrid.classList.remove('d-none');
        }

        console.log('Image grid created successfully');
        
        // Additional backup to ensure loading is hidden
        setTimeout(() => {
            const loadingEl = document.getElementById('imageGridLoading');
            if (loadingEl) {
                loadingEl.style.display = 'none';
                loadingEl.style.visibility = 'hidden';
                loadingEl.classList.add('d-none');
            }
        }, 50);
    }

    function updateYachtInfo(yacht) {

         const currency = localStorage.getItem('selectedCurrency') || 'AED';    
            const rate = localStorage.getItem('currencyRate') || 1;

        // Update main title and details
        document.getElementById('yachtTitle').textContent = yacht.title;
        document.getElementById('yachtLength').textContent = yacht.length || '--';
        document.getElementById('pricePerHour').textContent = currency + ' ' + (yacht.price_per_hour * rate);
        document.getElementById('pricePerDay').textContent = currency + ' ' + (yacht.price_per_day * rate) + '+VAT';
        document.getElementById('numberOfGuests').textContent = yacht.number_of_guests || '--';
        document.getElementById('overnightGuests').textContent = yacht.overnight_guests || '--';
        document.getElementById('minCharterLength').textContent = yacht.min_charter_length ? `${yacht.min_charter_length} Hours` : '-- Hours';
        
        // Update booking section yacht name
        document.getElementById('bookingYachtName').textContent = yacht.title;

        // Update hidden yacht name in booking form
        const yachtNameInput = document.querySelector('input[name="yacht_name"]');
        if (yachtNameInput) {
            yachtNameInput.value = yacht.title;
        }

        // Update charter member name and WhatsApp link
        updateCharterMember(yacht);

        // Update description
        const descriptionElement = document.getElementById('yachtDescription');
        if (yacht.description) {
            // Convert line breaks to paragraphs
            const paragraphs = yacht.description.split('\n\n').filter(p => p.trim());
            descriptionElement.innerHTML = paragraphs.map(p => `<p>${p.trim()}</p>`).join('');
        } else {
            descriptionElement.innerHTML = '<p>No description available.</p>';
        }

        // Update accordion sections
        updateAccordionSections(yacht);
        
        // Update map
        updateMap(yacht);

        // Update WhatsApp links
        updateWhatsAppLinks(yacht);
    }

    function updateCharterMember(yacht) {
        // Update charter member name
        const memberNameElement = document.querySelector('.member-details h5');
        if (memberNameElement && yacht.charter_member_name) {
            memberNameElement.textContent = yacht.charter_member_name;
        }

        // Update charter member WhatsApp link
        const whatsappLink = yacht.whatsapp_link || 'https://wa.me/+971505543873';
        const charterWhatsappButton = document.querySelector('.member a[href*="wa.me"]');
        if (charterWhatsappButton) {
            charterWhatsappButton.href = whatsappLink;
        }

        // Also update the alt attribute of the member image if we have the name
        const memberImage = document.querySelector('.member-image img');
        if (memberImage && yacht.charter_member_name) {
            memberImage.alt = yacht.charter_member_name;
        }
    }

    function updateAccordionSections(yacht) {
        // Update facilities
        const facilitiesContent = document.getElementById('facilitiesContent');
        if (facilitiesContent) {
            facilitiesContent.innerHTML = yacht.facilities || 'No facilities information available.';
        }

        // Update experiences
        const experiencesContent = document.getElementById('experiencesContent');
        if (experiencesContent) {
            experiencesContent.innerHTML = yacht.experiences || 'No experiences information available.';
        }

        // Update watersports
        const watersportsContent = document.getElementById('watersportsContent');
        if (watersportsContent) {
            watersportsContent.innerHTML = yacht.watersports || 'No watersports information available.';
        }

        // Update crew
        const crewContent = document.getElementById('crewContent');
        if (crewContent) {
            crewContent.innerHTML = yacht.crew || 'No crew information available.';
        }
    }

    function updateMap(yacht) {
        if (yacht.google_map_iframe) {
            let mapContent = yacht.google_map_iframe;
            
            // Check if it's a Google Maps link instead of iframe
            if (isGoogleMapsLink(yacht.google_map_iframe)) {
                console.log('Converting Google Maps link to iframe:', yacht.google_map_iframe);
                mapContent = convertGoogleMapsLinkToIframe(yacht.google_map_iframe);
            }
            
            const mapContainer = document.querySelector('.mapouter .gmap_canvas');
            if (mapContainer) {
                mapContainer.innerHTML = mapContent;
            } else {
                // If no container, find the iframe directly
                const mapIframe = document.getElementById('gmap_canvas');
                if (mapIframe) {
                    if (mapContent.includes('src=')) {
                        // Extract src from iframe HTML
                        const srcMatch = mapContent.match(/src="([^"]+)"/);
                        if (srcMatch) {
                            mapIframe.src = srcMatch[1];
                        }
                    } else if (isGoogleMapsLink(mapContent)) {
                        // Direct link, convert to embed URL
                        const embedUrl = convertGoogleMapsLinkToEmbedUrl(mapContent);
                        if (embedUrl) {
                            mapIframe.src = embedUrl;
                        }
                    }
                }
            }
        }
    }

    // Function to detect if string is a Google Maps link
    function isGoogleMapsLink(str) {
        if (!str) return false;
        
        // Remove iframe tags if present and check the content
        const cleanStr = str.replace(/<[^>]*>/g, '');
        
        // Check for various Google Maps URL patterns
        const googleMapsPatterns = [
            /maps\.google\.com/,
            /google\.com\/maps/,
            /goo\.gl\/maps/,
            /maps\.app\.goo\.gl/
        ];
        
        return googleMapsPatterns.some(pattern => pattern.test(cleanStr)) && 
               !str.includes('<iframe'); // Not already an iframe
    }

    // Function to convert Google Maps link to iframe
    function convertGoogleMapsLinkToIframe(link) {
        const embedUrl = convertGoogleMapsLinkToEmbedUrl(link);
        if (!embedUrl) return link; // Return original if conversion fails
        
        return `<iframe src="${embedUrl}" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
    }

    // Function to convert Google Maps link to embed URL
    function convertGoogleMapsLinkToEmbedUrl(link) {
        try {
            // Handle different Google Maps URL formats
            let embedUrl = '';
            
            // Format 1: Standard Google Maps URL with coordinates
            if (link.includes('@') && link.includes(',')) {
                const coordsMatch = link.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/);
                if (coordsMatch) {
                    const lat = coordsMatch[1];
                    const lng = coordsMatch[2];
                    embedUrl = `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM°00'00.0"N+00°00'00.0"E!5e0!3m2!1sen!2s!4v1000000000000!5m2!1sen!2s`;
                    return embedUrl;
                }
            }
            
            // Format 2: Google Maps place URL
            if (link.includes('/place/')) {
                const placeMatch = link.match(/\/place\/([^\/\?]+)/);
                if (placeMatch) {
                    const placeName = placeMatch[1].replace(/\+/g, ' ');
                    // Use the generic embed format for places
                    embedUrl = `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d0!3d0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2s${encodeURIComponent(placeName)}!5e0!3m2!1sen!2s!4v1000000000000!5m2!1sen!2s`;
                    return embedUrl;
                }
            }
            
            // Format 3: Short Google Maps URL (goo.gl or maps.app.goo.gl)
            if (link.includes('goo.gl') || link.includes('maps.app.goo.gl')) {
                // For short URLs, we'll use a generic embed approach
                const encodedUrl = encodeURIComponent(link);
                embedUrl = `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d0!3d0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2s${encodedUrl}!5e0!3m2!1sen!2s!4v1000000000000!5m2!1sen!2s`;
                return embedUrl;
            }
            
            // Format 4: Already an embed URL
            if (link.includes('google.com/maps/embed')) {
                return link;
            }
            
            // Fallback: Try to extract any coordinates from the URL
            const coordsMatch = link.match(/(-?\d+\.?\d*),(-?\d+\.?\d*)/);
            if (coordsMatch) {
                const lat = coordsMatch[1];
                const lng = coordsMatch[2];
                embedUrl = `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM°00'00.0"N+00°00'00.0"E!5e0!3m2!1sen!2s!4v1000000000000!5m2!1sen!2s`;
                return embedUrl;
            }
            
            return null;
        } catch (error) {
            console.error('Error converting Google Maps link:', error);
            return null;
        }
    }

    function updateWhatsAppLinks(yacht) {
        const whatsappButtons = document.querySelectorAll('.btn-book, button[onclick*="whatsapp"]');
        const whatsappLink = yacht.whatsapp_link || 'https://wa.me/971505540073';
        
        whatsappButtons.forEach(btn => {
            btn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.open(whatsappLink, '_blank');
            };
        });
    }


    // Load yacht details when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadYachtDetails();
    });

    // Load more yachts for slider
    async function loadMoreYachts(currentYachtId) {
        console.log('Loading more yachts, excluding yacht ID:', currentYachtId);
        
        const moreYachtsLoading = document.getElementById('moreYachtsLoading');
        const moreYachtsSlider = document.getElementById('moreYachtsSlider');
        const moreYachtsFallback = document.getElementById('moreYachtsFallback');
        const yachtSliderNavigation = document.getElementById('yachtSliderNavigation');

        if (!moreYachtsLoading) {
            console.error('moreYachtsLoading element not found');
            return;
        }

        try {
            // Show loading state
            console.log('Showing loading state');
            moreYachtsLoading.style.display = 'flex';
            moreYachtsSlider.style.display = 'none';
            moreYachtsFallback.style.display = 'none';
            yachtSliderNavigation.style.display = 'none';

            const response = await fetch('api/v1/yachtFleetHandler.php?action=list');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const { success, data } = await response.json();
            if (!success || !Array.isArray(data)) {
                throw new Error('Invalid data received from server');
            }

            // Filter out current yacht and limit to 6 yachts
            const otherYachts = data.filter(yacht => yacht.id != currentYachtId).slice(0, 6);
            
            if (otherYachts.length === 0) {
                moreYachtsLoading.innerHTML = '<div class="text-center p-4"><p class="text-muted">No other yachts available</p></div>';
                moreYachtsLoading.style.display = 'flex';
                return;
            }

            // Create yacht slider
            createYachtSlider(otherYachts);

        } catch (error) {
            console.error('Error loading more yachts:', error);
            moreYachtsLoading.innerHTML = '<div class="text-center p-4"><p class="text-danger">Failed to load more yachts</p></div>';
            moreYachtsLoading.style.display = 'flex';
        }
    }

    function createYachtSlider(yachts) {
        const moreYachtsLoading = document.getElementById('moreYachtsLoading');
        const moreYachtsSlider = document.getElementById('moreYachtsSlider');
        const yachtCarouselIndicators = document.getElementById('yachtCarouselIndicators');
        const yachtCarouselInner = document.getElementById('yachtCarouselInner');
        const yachtCarouselPrev = document.getElementById('yachtCarouselPrev');
        const yachtCarouselNext = document.getElementById('yachtCarouselNext');
        const yachtSliderNavigation = document.getElementById('yachtSliderNavigation');

        // Determine yachts per slide based on screen size
        let yachtsPerSlide = 3;
        if (window.innerWidth <= 768) {
            yachtsPerSlide = 2;
        }
        if (window.innerWidth <= 576) {
            yachtsPerSlide = 1;
        }
        
        // Group yachts into slides
        const slides = [];
        for (let i = 0; i < yachts.length; i += yachtsPerSlide) {
            slides.push(yachts.slice(i, i + yachtsPerSlide));
        }

        // Create carousel indicators
        let indicatorsHTML = '';
        slides.forEach((slide, index) => {
            indicatorsHTML += `
                <button type="button" data-bs-target="#moreYachtsSlider" data-bs-slide-to="${index}" 
                        ${index === 0 ? 'class="active" aria-current="true"' : ''} 
                        aria-label="Slide ${index + 1}"></button>
            `;
        });
        yachtCarouselIndicators.innerHTML = indicatorsHTML;

        // Create carousel slides
        let slidesHTML = '';
        slides.forEach((slide, slideIndex) => {
            slidesHTML += `
                <div class="carousel-item ${slideIndex === 0 ? 'active' : ''}">
                    <div class="row g-4 px-3">
                        ${slide.map(yacht => createYachtCardHTML(yacht)).join('')}
                    </div>
                </div>
            `;
        });
        yachtCarouselInner.innerHTML = slidesHTML;

        // Show/hide controls based on number of slides
        const showControls = slides.length > 1;
        yachtCarouselIndicators.style.display = showControls ? 'flex' : 'none';
        yachtCarouselPrev.style.display = showControls ? 'block' : 'none';
        yachtCarouselNext.style.display = showControls ? 'block' : 'none';
        yachtSliderNavigation.style.display = showControls ? 'block' : 'none';

        // Hide loading and show slider
        console.log('Hiding loading and showing slider');
        moreYachtsLoading.style.display = 'none';
        moreYachtsSlider.style.display = 'block';
        
        // Force hide loading with additional methods as backup
        moreYachtsLoading.classList.add('d-none');
        moreYachtsLoading.style.visibility = 'hidden';
        
        // Ensure slider is visible
        moreYachtsSlider.classList.remove('d-none');
        moreYachtsSlider.style.visibility = 'visible';
        
        console.log('More yachts slider created with', yachts.length, 'yachts in', slides.length, 'slides');
        
        // Additional backup to force hide loading after a short delay
        setTimeout(() => {
            if (moreYachtsLoading) {
                moreYachtsLoading.style.display = 'none';
                moreYachtsLoading.classList.add('d-none');
                console.log('Backup: Forced loading to hide');
            }
        }, 100);
    }

    function createYachtCardHTML(yacht) {
       const currency = localStorage.getItem('selectedCurrency') || 'AED';    
            const rate = localStorage.getItem('currencyRate') || 1;

        const slug = createSlug(yacht.title);
        return `
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="yacht-card" onclick="navigateToYachtDetails('${slug}', ${yacht.id})" style="cursor: pointer;">
                     <div class="price-tag">${currency} ${Math.round(yacht.price_per_hour * (rate || 1))} | hr</div>
                    <img class="yacht-image" src="api/uploads/yachtFleet/${yacht.main_image}" alt="${yacht.title}" loading="lazy">
                    <div class="yacht-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="yacht-details">
                                <h5 class="yacht-title">${yacht.title}</h5>
                                <p class="capacity">Capacity Up to ${yacht.number_of_guests} Person</p>
                            </div>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <span>5.0 (179)</span>
                            </div>
                        </div>
                    </div>
                    <div class="yacht-footer">
                        <div class="price-info">
                            <img src="assets/images/coin_1.png" alt="Price" style="width: 20px; height: 20px;">
                            <div class="price-text">
                                <span class="price-label">Price</span>
                                <span class="price-value" price-value data-base-price="${yacht.price}">Half Day: ${Math.round((yacht.price) * (rate || 1))} ${currency}</span>

                            </div>
                        </div>
                        <button class="btn-book" onclick="event.stopPropagation(); window.open('${yacht.whatsapp_link || 'https://wa.me/971505540073'}', '_blank')">
                            BOOK YACHT BY <i class="fab fa-whatsapp"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Create slug function (reused from index.html)
    function createSlug(title) {
        return title.toLowerCase()
            .replace(/[^a-z0-9 -]/g, '') // Remove invalid chars
            .replace(/\s+/g, '-') // Replace spaces with -
            .replace(/-+/g, '-') // Replace multiple - with single -
            .trim('-'); // Trim - from start and end
    }

    // Navigate to yacht details with slug (reused from index.html)
    function navigateToYachtDetails(slug, yachtId) {
        window.location.href = `yacht-details.php?slug=${slug}&id=${yachtId}`;
    }

    // Lightbox functionality
    function openLightbox(startIndex = 0) {
        if (!window.currentYachtImages || window.currentYachtImages.length === 0) {
            console.error('No images available for lightbox');
            return;
        }

        const lightboxModal = new bootstrap.Modal(document.getElementById('yachtLightbox'));
        const lightboxCarouselInner = document.getElementById('lightboxCarouselInner');
        const lightboxIndicators = document.getElementById('lightboxIndicators');
        const currentImageIndex = document.getElementById('currentImageIndex');
        const totalImages = document.getElementById('totalImages');

        // Create carousel items for lightbox
        let carouselHTML = '';
        let indicatorsHTML = '';
        
        window.currentYachtImages.forEach((image, index) => {
            const isActive = index === startIndex ? 'active' : '';
            carouselHTML += `
                <div class="carousel-item ${isActive}">
                    <img src="api/uploads/yachtFleet/${image}" class="d-block w-100" alt="Yacht Image ${index + 1}"
                         style="max-height: 80vh; object-fit: contain;">
                </div>
            `;
            
            indicatorsHTML += `
                <button type="button" data-bs-target="#lightboxCarousel" data-bs-slide-to="${index}" 
                        ${index === startIndex ? 'class="active" aria-current="true"' : ''} 
                        aria-label="Slide ${index + 1}"></button>
            `;
        });

        lightboxCarouselInner.innerHTML = carouselHTML;
        lightboxIndicators.innerHTML = indicatorsHTML;
        
        // Update image counter
        currentImageIndex.textContent = startIndex + 1;
        totalImages.textContent = window.currentYachtImages.length;

        // Listen for carousel slide events to update counter
        const lightboxCarousel = document.getElementById('lightboxCarousel');
        lightboxCarousel.addEventListener('slid.bs.carousel', function (e) {
            const activeIndex = Array.from(e.target.querySelectorAll('.carousel-item')).indexOf(e.relatedTarget);
            currentImageIndex.textContent = activeIndex + 1;
        });

        // Show the modal
        lightboxModal.show();
    }

    // Add click handlers to images for lightbox
    document.addEventListener('DOMContentLoaded', function() {
        // Main image click handler
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'mainImage') {
                openLightbox(0);
            }
        });
    });
  
    </script>
  </body>
</html>
