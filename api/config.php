<?php
/**
 * Configuration file for GR Yachts API
 */

// Email Configuration
define('CONTACT_EMAIL', 'info@gryachts.com');
define('CONTACT_NAME', 'GR Yachts Dubai');
define('NOREPLY_EMAIL', 'noreply@gryachts.com');

// Site Configuration
define('SITE_NAME', 'GR Yachts Dubai');
define('SITE_URL', 'https://gryachts.com');

// Security Settings
define('RATE_LIMIT_WINDOW', 300); // 5 minutes in seconds
define('RATE_LIMIT_MAX_REQUESTS', 3); // Max requests per window
define('MAX_MESSAGE_LENGTH', 2000);
define('MIN_MESSAGE_LENGTH', 10);

// Logging
define('ENABLE_LOGGING', true);
define('LOG_PATH', __DIR__ . '/../logs/');

// Spam Detection
$SPAM_WORDS = [
    'viagra', 'casino', 'loan', 'cheap', 'free money', 'click here',
    'make money fast', 'guaranteed', 'limited time', 'act now',
    'bitcoin', 'cryptocurrency', 'investment opportunity'
];

// Timezone
date_default_timezone_set('Asia/Dubai');
?>
