<?php
/**
 * Streaming Service Credentials Manager
 * 
 * Handles encrypted storage and retrieval of user streaming service credentials
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Streaming_Credentials {
    
    /**
     * Encryption method
     */
    const ENCRYPTION_METHOD = 'AES-256-CBC';
    
    /**
     * User meta key prefix
     */
    const META_PREFIX = 'gd_streaming_';
    
    /**
     * Supported streaming services
     */
    const SERVICES = array(
        'spotify' => 'Spotify',
        'apple_music' => 'Apple Music',
        'youtube_music' => 'YouTube Music',
        'amazon_music' => 'Amazon Music',
        'tidal' => 'Tidal'
    );
    
    /**
     * Get encryption key
     * 
     * @return string Encryption key
     */
    private function get_encryption_key() {
        // Use WordPress AUTH_KEY as base
        $key = AUTH_KEY;
        
        // Add site-specific salt
        $key .= get_option('gd_chatbot_encryption_salt', wp_generate_password(32, true, true));
        
        // Hash to ensure consistent length
        return hash('sha256', $key, true);
    }
    
    /**
     * Encrypt data
     * 
     * @param string $data Data to encrypt
     * @return string Encrypted data (base64 encoded)
     */
    private function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $key = $this->get_encryption_key();
        $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        $encrypted = openssl_encrypt(
            $data,
            self::ENCRYPTION_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Combine IV and encrypted data
        $result = base64_encode($iv . $encrypted);
        
        return $result;
    }
    
    /**
     * Decrypt data
     * 
     * @param string $data Encrypted data (base64 encoded)
     * @return string|false Decrypted data or false on failure
     */
    private function decrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $key = $this->get_encryption_key();
        $data = base64_decode($data);
        
        $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            self::ENCRYPTION_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    /**
     * Store credentials for a user
     * 
     * @param int $user_id User ID
     * @param string $service Service name (spotify, apple_music, etc.)
     * @param array $credentials Credentials array
     * @return bool Success
     */
    public function store_credentials($user_id, $service, $credentials) {
        if (!in_array($service, array_keys(self::SERVICES))) {
            return false;
        }
        
        // Add timestamp
        $credentials['stored_at'] = current_time('mysql');
        
        // Encrypt credentials
        $encrypted = $this->encrypt(json_encode($credentials));
        
        // Store in user meta
        $meta_key = self::META_PREFIX . $service;
        $result = update_user_meta($user_id, $meta_key, $encrypted);
        
        // Log the connection
        do_action('gd_streaming_credentials_stored', $user_id, $service);
        
        return $result !== false;
    }
    
    /**
     * Get credentials for a user
     * 
     * @param int $user_id User ID
     * @param string $service Service name
     * @return array|null Credentials array or null if not found
     */
    public function get_credentials($user_id, $service) {
        if (!in_array($service, array_keys(self::SERVICES))) {
            return null;
        }
        
        $meta_key = self::META_PREFIX . $service;
        $encrypted = get_user_meta($user_id, $meta_key, true);
        
        if (empty($encrypted)) {
            return null;
        }
        
        // Decrypt credentials
        $decrypted = $this->decrypt($encrypted);
        
        if ($decrypted === false) {
            return null;
        }
        
        $credentials = json_decode($decrypted, true);
        
        return $credentials;
    }
    
    /**
     * Delete credentials for a user
     * 
     * @param int $user_id User ID
     * @param string $service Service name
     * @return bool Success
     */
    public function delete_credentials($user_id, $service) {
        if (!in_array($service, array_keys(self::SERVICES))) {
            return false;
        }
        
        $meta_key = self::META_PREFIX . $service;
        $result = delete_user_meta($user_id, $meta_key);
        
        // Log the disconnection
        do_action('gd_streaming_credentials_deleted', $user_id, $service);
        
        return $result;
    }
    
    /**
     * Check if user has credentials for a service
     * 
     * @param int $user_id User ID
     * @param string $service Service name
     * @return bool Has credentials
     */
    public function has_credentials($user_id, $service) {
        $credentials = $this->get_credentials($user_id, $service);
        return !empty($credentials);
    }
    
    /**
     * Get all connected services for a user
     * 
     * @param int $user_id User ID
     * @return array Array of connected service names
     */
    public function get_connected_services($user_id) {
        $connected = array();
        
        foreach (self::SERVICES as $service => $label) {
            if ($this->has_credentials($user_id, $service)) {
                $connected[] = $service;
            }
        }
        
        return $connected;
    }
    
    /**
     * Check if credentials are expired
     * 
     * @param array $credentials Credentials array
     * @return bool Is expired
     */
    public function is_expired($credentials) {
        if (empty($credentials['expires_at'])) {
            return false; // No expiration
        }
        
        $expires_at = strtotime($credentials['expires_at']);
        return $expires_at < time();
    }
    
    /**
     * Refresh access token (if refresh token available)
     * 
     * @param int $user_id User ID
     * @param string $service Service name
     * @return bool Success
     */
    public function refresh_token($user_id, $service) {
        $credentials = $this->get_credentials($user_id, $service);
        
        if (empty($credentials) || empty($credentials['refresh_token'])) {
            return false;
        }
        
        // Get service-specific OAuth handler
        $oauth_class = 'GD_' . ucfirst($service) . '_OAuth';
        
        if (!class_exists($oauth_class)) {
            return false;
        }
        
        $oauth = new $oauth_class();
        $new_credentials = $oauth->refresh_access_token($credentials['refresh_token']);
        
        if ($new_credentials) {
            // Preserve refresh token if not returned
            if (empty($new_credentials['refresh_token'])) {
                $new_credentials['refresh_token'] = $credentials['refresh_token'];
            }
            
            return $this->store_credentials($user_id, $service, $new_credentials);
        }
        
        return false;
    }
    
    /**
     * Get valid access token (refresh if expired)
     * 
     * @param int $user_id User ID
     * @param string $service Service name
     * @return string|null Access token or null
     */
    public function get_valid_token($user_id, $service) {
        $credentials = $this->get_credentials($user_id, $service);
        
        if (empty($credentials)) {
            return null;
        }
        
        // Check if expired
        if ($this->is_expired($credentials)) {
            // Try to refresh
            if ($this->refresh_token($user_id, $service)) {
                $credentials = $this->get_credentials($user_id, $service);
            } else {
                return null; // Refresh failed
            }
        }
        
        return isset($credentials['access_token']) ? $credentials['access_token'] : null;
    }
    
    /**
     * Get connection status for all services
     * 
     * @param int $user_id User ID
     * @return array Status array
     */
    public function get_connection_status($user_id) {
        $status = array();
        
        foreach (self::SERVICES as $service => $label) {
            $credentials = $this->get_credentials($user_id, $service);
            
            $status[$service] = array(
                'label' => $label,
                'connected' => !empty($credentials),
                'expired' => !empty($credentials) && $this->is_expired($credentials),
                'stored_at' => isset($credentials['stored_at']) ? $credentials['stored_at'] : null
            );
        }
        
        return $status;
    }
    
    /**
     * Initialize encryption salt (run on plugin activation)
     */
    public static function init_encryption_salt() {
        if (!get_option('gd_chatbot_encryption_salt')) {
            $salt = wp_generate_password(32, true, true);
            add_option('gd_chatbot_encryption_salt', $salt, '', 'no');
        }
    }
    
    /**
     * Get supported services
     * 
     * @return array Services array
     */
    public static function get_services() {
        return self::SERVICES;
    }
}
