<?php
/**
 * Simple JWT Implementation for Admin Authentication
 * Lightweight JWT library without external dependencies
 */

class JWT {
    private static $secret_key = 'GRYachts2024SecretKeyForJWTTokenGeneration';
    private static $algorithm = 'HS256';
    
    /**
     * Encode payload into JWT token
     */
    public static function encode($payload, $exp_hours = 24) {
        // Header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ]);
        
        // Add expiration to payload
        $payload['iat'] = time();
        $payload['exp'] = time() + ($exp_hours * 3600);
        
        $payload_json = json_encode($payload);
        
        // Base64Url encode
        $header_encoded = self::base64urlEncode($header);
        $payload_encoded = self::base64urlEncode($payload_json);
        
        // Create signature
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, self::$secret_key, true);
        $signature_encoded = self::base64urlEncode($signature);
        
        // Create JWT token
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Decode and verify JWT token
     */
    public static function decode($token) {
        if (empty($token)) {
            return false;
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        // Decode header and payload
        $header = json_decode(self::base64urlDecode($header_encoded), true);
        $payload = json_decode(self::base64urlDecode($payload_encoded), true);
        
        if (!$header || !$payload) {
            return false;
        }
        
        // Verify signature
        $signature = self::base64urlDecode($signature_encoded);
        $expected_signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, self::$secret_key, true);
        
        if (!hash_equals($signature, $expected_signature)) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && time() > $payload['exp']) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Verify token and return admin data
     */
    public static function verifyAdminToken($token) {
        $payload = self::decode($token);
        
        if (!$payload || !isset($payload['admin_id'])) {
            return false;
        }
        
        // Additional verification against database
        global $conn;
        $stmt = $conn->prepare("SELECT id, username, email, full_name, role, is_active FROM admins WHERE id = ? AND is_active = 1");
        $stmt->bind_param("i", $payload['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($admin = $result->fetch_assoc()) {
            // Merge token data with fresh admin data
            return array_merge($payload, $admin);
        }
        
        return false;
    }
    
    /**
     * Generate admin JWT token
     */
    public static function generateAdminToken($admin_data) {
        $payload = [
            'admin_id' => $admin_data['id'],
            'username' => $admin_data['username'],
            'email' => $admin_data['email'],
            'full_name' => $admin_data['full_name'],
            'role' => $admin_data['role'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Refresh token (generate new token with updated expiration)
     */
    public static function refreshToken($token) {
        $payload = self::decode($token);
        
        if (!$payload) {
            return false;
        }
        
        // Remove old timestamps
        unset($payload['iat'], $payload['exp']);
        
        // Generate new token
        return self::encode($payload);
    }
    
    /**
     * Base64URL encode
     */
    private static function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64URL decode
     */
    private static function base64urlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Get token from Authorization header
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Validate token from multiple sources
     */
    public static function getValidToken() {
        // Try Authorization header first
        $token = self::getTokenFromHeader();
        
        // Try POST/GET parameters
        if (!$token) {
            $token = $_POST['token'] ?? $_GET['token'] ?? '';
        }
        
        return $token;
    }
}

/**
 * Helper function to get all headers (compatibility)
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
?>
