# GR Yachts API Documentation

## Overview
This API handles contact form submissions for the GR Yachts Dubai website.

## Endpoints

### Contact Form Handler
**POST** `/api/v1/contacHandler.php`

Handles contact form submissions from the website.

#### Request Parameters
- `name` (string, required): Contact person's name (2-100 characters)
- `email` (string, required): Valid email address
- `message` (string, required): Message content (10-2000 characters)

#### Request Headers
- `Content-Type`: `application/x-www-form-urlencoded` or `multipart/form-data`
- `X-Requested-With`: `XMLHttpRequest` (recommended for AJAX requests)

#### Response Format
```json
{
    "success": true|false,
    "message": "Response message",
    "errors": ["array of errors"] // Only present on validation errors
}
```

#### Success Response (200)
```json
{
    "success": true,
    "message": "Thank you for your message! We will get back to you within 24 hours."
}
```

#### Error Responses

**Validation Error (400)**
```json
{
    "success": false,
    "message": "Name is required. Email is required.",
    "errors": ["Name is required.", "Email is required."]
}
```

**Rate Limit Error (400)**
```json
{
    "success": false,
    "message": "Too many requests. Please wait a few minutes before submitting again."
}
```

**Method Not Allowed (405)**
```json
{
    "success": false,
    "message": "Method not allowed. Only POST requests are accepted."
}
```

**Server Error (500)**
```json
{
    "success": false,
    "message": "An unexpected error occurred. Please try again later."
}
```

### Test Endpoint
**GET** `/api/v1/test.php`

Simple endpoint to test if the API is working.

#### Response
```json
{
    "success": true,
    "message": "API is working correctly",
    "timestamp": "2025-09-12 12:30:45",
    "server_time": 1726142245,
    "php_version": "8.1.0"
}
```

## Features

### Security
- Input sanitization and validation
- Rate limiting (3 requests per 5 minutes per IP)
- Basic spam detection
- CORS headers for cross-origin requests
- Protected configuration and log files

### Email Features
- HTML formatted emails
- Automatic reply-to header
- Professional email template
- Contact information included

### Logging
- All submissions are logged to `logs/contact_submissions.log`
- Rate limiting data stored in `logs/rate_limit.json`
- Error logging for debugging

### Validation Rules
- **Name**: 2-100 characters, required
- **Email**: Valid email format, required
- **Message**: 10-2000 characters, required
- **Spam Detection**: Filters common spam keywords
- **Rate Limiting**: Maximum 3 submissions per 5 minutes per IP

## Configuration

Edit `api/config.php` to customize:
- Email addresses
- Rate limiting settings
- Message length limits
- Spam word filters
- Logging preferences

## Installation Requirements

- PHP 7.4 or higher
- Mail function enabled (for email sending)
- Write permissions for `logs/` directory

## Security Notes

1. The `logs/` directory is protected by `.htaccess`
2. Configuration files are blocked from direct access
3. All user inputs are sanitized
4. Rate limiting prevents abuse
5. CORS is configured for web requests

## Testing

1. Test API availability: `GET /api/v1/test.php`
2. Test contact form: Submit via the website contact form
3. Check logs in `logs/` directory (if logging is enabled)

## Troubleshooting

### Common Issues

1. **Email not sending**: Check PHP mail configuration
2. **Permission errors**: Ensure write access to `logs/` directory
3. **CORS errors**: Verify `.htaccess` configuration
4. **Rate limiting**: Wait 5 minutes between excessive requests

### Log Files

- `logs/contact_submissions.log`: All contact form submissions
- `logs/rate_limit.json`: Rate limiting data
- Server error logs: Check your server's PHP error log
