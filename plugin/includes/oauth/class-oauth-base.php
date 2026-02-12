<?php
/**
 * Base OAuth Handler
 * 
 * Abstract class for OAuth 2.0 authentication with streaming services
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class GD_OAuth_Base {
    
    /**
     * Service name
     */
    protected $service_name;
    
    /**
     * Client ID
     */
    protected $client_id;
    
    /**
     * Client secret
     */
    protected $client_secret;
    
    /**
     * Redirect URI
     */
    protected $redirect_uri;
    
    /**
     * Authorization endpoint
     */
    protected $auth_endpoint;
    
    /**
     * Token endpoint
     */
    protected $token_endpoint;
    
    /**
     * API base URL
     */
    protected $api_base_url;
    
    /**
     * Required scopes
     */
    protected $scopes = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_config();
        $this->redirect_uri = admin_url('admin-ajax.php?action=gd_oauth_callback&service=' . $this->service_name);
    }
    
    /**
     * Load configuration from WordPress options
     */
    abstract protected function load_config();
    
    /**
     * Get authorization URL
     * 
     * @param string $state CSRF state token
     * @return string Authorization URL
     */
    public function get_authorization_url($state) {
        $params = array(
            'client_id' => $this->client_id,
            'response_type' => 'code',
            'redirect_uri' => $this->redirect_uri,
            'state' => $state,
            'scope' => implode(' ', $this->scopes)
        );
        
        // Add service-specific parameters
        $params = $this->add_auth_params($params);
        
        return $this->auth_endpoint . '?' . http_build_query($params);
    }
    
    /**
     * Add service-specific authorization parameters
     * 
     * @param array $params Base parameters
     * @return array Modified parameters
     */
    protected function add_auth_params($params) {
        return $params;
    }
    
    /**
     * Exchange authorization code for access token
     * 
     * @param string $code Authorization code
     * @return array|WP_Error Token data or error
     */
    public function exchange_code_for_token($code) {
        $body = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        );
        
        // Add service-specific parameters
        $body = $this->add_token_params($body);
        
        $response = wp_remote_post($this->token_endpoint, array(
            'body' => $body,
            'headers' => $this->get_token_headers()
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('oauth_error', $body['error_description'] ?? $body['error']);
        }
        
        return $this->format_token_response($body);
    }
    
    /**
     * Add service-specific token parameters
     * 
     * @param array $params Base parameters
     * @return array Modified parameters
     */
    protected function add_token_params($params) {
        return $params;
    }
    
    /**
     * Get headers for token request
     * 
     * @return array Headers
     */
    protected function get_token_headers() {
        return array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        );
    }
    
    /**
     * Format token response
     * 
     * @param array $response Raw response
     * @return array Formatted response
     */
    protected function format_token_response($response) {
        $formatted = array(
            'access_token' => $response['access_token'],
            'token_type' => $response['token_type'] ?? 'Bearer',
            'expires_in' => $response['expires_in'] ?? 3600
        );
        
        if (isset($response['refresh_token'])) {
            $formatted['refresh_token'] = $response['refresh_token'];
        }
        
        // Calculate expiration time
        if ($formatted['expires_in']) {
            $formatted['expires_at'] = date('Y-m-d H:i:s', time() + $formatted['expires_in']);
        }
        
        return $formatted;
    }
    
    /**
     * Refresh access token
     * 
     * @param string $refresh_token Refresh token
     * @return array|WP_Error New token data or error
     */
    public function refresh_access_token($refresh_token) {
        $body = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        );
        
        $response = wp_remote_post($this->token_endpoint, array(
            'body' => $body,
            'headers' => $this->get_token_headers()
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('oauth_error', $body['error_description'] ?? $body['error']);
        }
        
        return $this->format_token_response($body);
    }
    
    /**
     * Search for a song
     * 
     * @param string $song_title Song title
     * @param string $artist Artist name
     * @param string $access_token Access token
     * @return array|WP_Error Search results or error
     */
    abstract public function search_song($song_title, $artist, $access_token);
    
    /**
     * Make authenticated API request
     * 
     * @param string $endpoint API endpoint
     * @param string $access_token Access token
     * @param array $params Query parameters
     * @return array|WP_Error Response or error
     */
    protected function api_request($endpoint, $access_token, $params = array()) {
        $url = $this->api_base_url . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code >= 400) {
            return new WP_Error(
                'api_error',
                isset($body['error']['message']) ? $body['error']['message'] : 'API request failed'
            );
        }
        
        return $body;
    }
    
    /**
     * Validate configuration
     * 
     * @return bool Is valid
     */
    public function is_configured() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }
    
    /**
     * Get service name
     * 
     * @return string Service name
     */
    public function get_service_name() {
        return $this->service_name;
    }
}
