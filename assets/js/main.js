document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown item clicks
    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const dropdownMenu = this.closest('.dropdown-menu');
            const button = dropdownMenu.previousElementSibling;
            const selectedValue = this.getAttribute('data-value');
            
            // Update button text
            button.querySelector('.selected-value').textContent = selectedValue;
            
            // Update active state
            dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');
            
            // Optional: Store the selection
            const dropdownType = button.querySelector('.selected-value').textContent === 'AED' || button.querySelector('.selected-value').textContent === 'USD' ? 'currency' : 'language';
            localStorage.setItem(dropdownType, selectedValue);
        });
    });

    // Initialize translation functionality
    initializeTranslation();

    // Close translation menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.translate-widget')) {
            closeAllTranslateMenus();
        }
    });
});

// Translation Widget Functions
let isGoogleTranslateLoaded = false;

function initializeTranslation() {
    // Check if Google Translate is available
    if (typeof google !== 'undefined' && google.translate) {
        isGoogleTranslateLoaded = true;
        console.log('Google Translate loaded successfully');
    } else {
        // Wait for Google Translate to load
        const checkInterval = setInterval(() => {
            if (typeof google !== 'undefined' && google.translate) {
                isGoogleTranslateLoaded = true;
                console.log('Google Translate loaded successfully');
                clearInterval(checkInterval);
            }
        }, 100);
        
        // Stop checking after 10 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
            if (!isGoogleTranslateLoaded) {
                console.warn('Google Translate failed to load');
            }
        }, 10000);
    }

    // Load saved language preference - but don't auto-trigger translation
    const savedLanguage = localStorage.getItem('selectedLanguage');
    if (savedLanguage && savedLanguage !== 'en') {
        const langData = getLanguageData(savedLanguage);
        if (langData) {
            updateLanguageDisplay(langData.code, langData.display, langData.flag);
            updateActiveLanguageOption(savedLanguage);
        }
    }
}

function toggleTranslateMenu() {
    const menu = document.getElementById('translateMenu');
    const isVisible = menu.classList.contains('show');
    
    closeAllTranslateMenus();
    
    if (!isVisible) {
        menu.classList.add('show');
    }
}

function toggleMobileTranslateMenu() {
    const menu = document.getElementById('mobileTranslateMenu');
    if (!menu) {
        // Try to find it with a more general selector
        const allMenus = document.querySelectorAll('.mobile-translate-menu');
        if (allMenus.length > 0) {
            const firstMenu = allMenus[0];
            const isVisible = firstMenu.classList.contains('show');
            closeAllTranslateMenus();
            if (!isVisible) {
                firstMenu.classList.add('show');
                
                // Force inline styles for fallback menu too
                firstMenu.style.display = 'block';
                firstMenu.style.visibility = 'visible';
                firstMenu.style.opacity = '1';
                firstMenu.style.maxHeight = '250px';
                firstMenu.style.overflowY = 'auto';
                firstMenu.style.zIndex = '1070';
            }
        }
        return;
    }
    
    const isVisible = menu.classList.contains('show');
    closeAllTranslateMenus();
    
    if (!isVisible) {
        menu.classList.add('show');
        
        // Force inline styles as backup for CSS specificity issues
        menu.style.display = 'block';
        menu.style.visibility = 'visible';
        menu.style.opacity = '1';
        menu.style.maxHeight = '250px';
        menu.style.overflowY = 'auto';
        menu.style.zIndex = '1070';
        
        // Force a style recalculation
        menu.offsetHeight;
    }
}

function closeAllTranslateMenus() {
    const menus = document.querySelectorAll('.translate-menu');
    menus.forEach(menu => {
        menu.classList.remove('show');
        // Clear inline styles
        if (menu.classList.contains('mobile-translate-menu')) {
            menu.style.display = '';
            menu.style.visibility = '';
            menu.style.opacity = '';
            menu.style.maxHeight = '';
            menu.style.overflowY = '';
            menu.style.zIndex = '';
        }
    });
}

function changeLanguage(langCode, displayCode, flag) {
    // Update display
    updateLanguageDisplay(displayCode, displayCode, flag);
    
    // Close menus
    closeAllTranslateMenus();
    
    // Save preference
    localStorage.setItem('selectedLanguage', langCode);
    
    // Trigger Google Translate
    if (langCode === 'en') {
        // Reset to original language
        resetToOriginalLanguage();
    } else {
        triggerGoogleTranslate(langCode);
    }
    
    // Update active states
    updateActiveLanguageOption(langCode);
}

function updateLanguageDisplay(code, display, flag) {
    const currentLanguageElements = document.querySelectorAll('.current-language');
    currentLanguageElements.forEach(element => {
        element.textContent = code;
    });
}

function updateActiveLanguageOption(langCode) {
    const options = document.querySelectorAll('.translate-option');
    options.forEach(option => {
        option.classList.remove('active');
        const onclick = option.getAttribute('onclick');
        if (onclick && onclick.includes(`'${langCode}'`)) {
            option.classList.add('active');
        }
    });
}

function triggerGoogleTranslate(targetLang) {
    if (!isGoogleTranslateLoaded) {
        console.warn('Google Translate not loaded yet');
        // Set cookie and reload only once
        setCookie('googtrans', `/en/${targetLang}`, 1);
        setTimeout(() => window.location.reload(), 100);
        return;
    }

    try {
        // Method 1: Try to use Google Translate's internal functions
        if (window.google && window.google.translate && window.google.translate.TranslateElement) {
            const translateElement = document.querySelector('.goog-te-combo');
            if (translateElement) {
                translateElement.value = targetLang;
                translateElement.dispatchEvent(new Event('change'));
                return;
            }
        }

        // Method 2: Use cookie-based approach with single reload
        const currentCookie = getCookie('googtrans');
        const newCookie = `/en/${targetLang}`;
        
        if (currentCookie !== newCookie) {
            setCookie('googtrans', newCookie, 1);
            setTimeout(() => window.location.reload(), 100);
        }

    } catch (error) {
        console.error('Translation error:', error);
        // Fallback: Set cookie and reload only if different
        const currentCookie = getCookie('googtrans');
        const newCookie = `/en/${targetLang}`;
        
        if (currentCookie !== newCookie) {
            setCookie('googtrans', newCookie, 1);
            setTimeout(() => window.location.reload(), 100);
        }
    }
}

function resetToOriginalLanguage() {
    try {
        // Check current cookie state
        const currentCookie = getCookie('googtrans');
        
        if (currentCookie && currentCookie !== '/en/en' && currentCookie !== '') {
            // Clear Google Translate cookie
            setCookie('googtrans', '/en/en', 1);
            
            // Try to reset using Google Translate API first
            if (window.google && window.google.translate) {
                const translateElement = document.querySelector('.goog-te-combo');
                if (translateElement) {
                    translateElement.value = '';
                    translateElement.dispatchEvent(new Event('change'));
                    return;
                }
            }
            
            // Fallback: Reload page only if needed
            setTimeout(() => window.location.reload(), 100);
        }
    } catch (error) {
        console.error('Reset error:', error);
        // Only reload if there was actually a translation active
        const currentCookie = getCookie('googtrans');
        if (currentCookie && currentCookie !== '/en/en' && currentCookie !== '') {
            setCookie('googtrans', '/en/en', 1);
            setTimeout(() => window.location.reload(), 100);
        }
    }
}

function setCookie(name, value, days) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + (value || '') + expires + '; path=/';
}

function getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function getLanguageData(langCode) {
    const languages = {
        'en': { code: 'EN', display: 'EN', flag: '🇺🇸' },
        'ar': { code: 'AR', display: 'AR', flag: '🇸🇦' },
        'ru': { code: 'RU', display: 'RU', flag: '🇷🇺' },
        'fr': { code: 'FR', display: 'FR', flag: '🇫🇷' },
        'es': { code: 'ES', display: 'ES', flag: '🇪🇸' },
        'zh-CN': { code: 'CN', display: 'CN', flag: '🇨🇳' },
        'hi': { code: 'HI', display: 'HI', flag: '🇮🇳' },
        'ur': { code: 'UR', display: 'UR', flag: '🇵🇰' },
        'de': { code: 'DE', display: 'DE', flag: '🇩🇪' },
        'it': { code: 'IT', display: 'IT', flag: '🇮🇹' },
        'pt': { code: 'PT', display: 'PT', flag: '🇵🇹' },
        'ja': { code: 'JP', display: 'JP', flag: '🇯🇵' },
        'ko': { code: 'KR', display: 'KR', flag: '🇰🇷' },
        'th': { code: 'TH', display: 'TH', flag: '🇹🇭' },
        'tr': { code: 'TR', display: 'TR', flag: '🇹🇷' }
    };
    
    return languages[langCode] || null;
}

// Check for existing translation on page load - but don't auto-reload
document.addEventListener('DOMContentLoaded', function() {
    const existingTranslation = getCookie('googtrans');
    if (existingTranslation && existingTranslation !== '/en/en' && existingTranslation !== '') {
        const parts = existingTranslation.split('/');
        if (parts.length >= 3) {
            const langCode = parts[2];
            const langData = getLanguageData(langCode);
            if (langData) {
                updateLanguageDisplay(langData.code, langData.display, langData.flag);
                updateActiveLanguageOption(langCode);
                // Store the language preference
                localStorage.setItem('selectedLanguage', langCode);
            }
        }
    }
});

// Make translation functions globally available
window.toggleTranslateMenu = toggleTranslateMenu;
window.toggleMobileTranslateMenu = toggleMobileTranslateMenu;
window.changeLanguage = changeLanguage;
window.closeAllTranslateMenus = closeAllTranslateMenus;

// Debug function for mobile menu
window.debugMobileMenu = function() {
    console.log('=== Mobile Menu Debug ===');
    const menu = document.getElementById('mobileTranslateMenu');
    console.log('Menu by ID:', menu);
    
    const menusByClass = document.querySelectorAll('.mobile-translate-menu');
    console.log('Menus by class:', menusByClass.length);
    
    if (menu) {
        console.log('Menu classes:', menu.className);
        console.log('Menu inline styles:', menu.style.cssText);
        console.log('Menu computed display:', window.getComputedStyle(menu).display);
        console.log('Menu computed visibility:', window.getComputedStyle(menu).visibility);
        console.log('Menu parent:', menu.parentElement);
        console.log('Menu parent classes:', menu.parentElement.className);
        console.log('Menu has show class:', menu.classList.contains('show'));
    }
    
    menusByClass.forEach((m, i) => {
        console.log(`Menu ${i}:`, m);
        console.log(`Menu ${i} classes:`, m.className);
        console.log(`Menu ${i} display:`, window.getComputedStyle(m).display);
        console.log(`Menu ${i} visibility:`, window.getComputedStyle(m).visibility);
    });
    
    console.log('=== End Debug ===');
};

// Initialize mobile translation functionality after header loads
function initializeMobileTranslation() {
    // Wait for header to be loaded
    const checkHeader = setInterval(() => {
        const mobileMenu = document.getElementById('mobileTranslateMenu');
        if (mobileMenu) {
            console.log('Mobile translation menu found and initialized');
            clearInterval(checkHeader);
            
            // Apply any existing language state to mobile menu
            const existingTranslation = getCookie('googtrans');
            if (existingTranslation && existingTranslation !== '/en/en' && existingTranslation !== '') {
                const parts = existingTranslation.split('/');
                if (parts.length >= 3) {
                    const langCode = parts[2];
                    const langData = getLanguageData(langCode);
                    if (langData) {
                        updateLanguageDisplay(langData.code, langData.display, langData.flag);
                        updateActiveLanguageOption(langCode);
                    }
                }
            }
        }
    }, 100);
    
    // Stop checking after 10 seconds
    setTimeout(() => clearInterval(checkHeader), 10000);
}

// Initialize mobile translation when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initializeMobileTranslation, 500);
});



   