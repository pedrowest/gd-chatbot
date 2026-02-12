<?php
/**
 * Streaming Service Manager
 * 
 * Coordinates searches across multiple streaming services
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Streaming_Service_Manager {
    
    /**
     * Credentials manager
     */
    private $credentials;
    
    /**
     * OAuth handlers
     */
    private $oauth_handlers = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->credentials = new GD_Streaming_Credentials();
        $this->load_oauth_handlers();
    }
    
    /**
     * Load OAuth handlers for all services
     */
    private function load_oauth_handlers() {
        $services = array(
            'spotify' => 'GD_Spotify_OAuth',
            'apple_music' => 'GD_Apple_Music_OAuth',
            'youtube_music' => 'GD_Youtube_Music_OAuth',
            'amazon_music' => 'GD_Amazon_Music_OAuth',
            'tidal' => 'GD_Tidal_OAuth'
        );
        
        foreach ($services as $service => $class) {
            if (class_exists($class)) {
                $this->oauth_handlers[$service] = new $class();
            }
        }
    }
    
    /**
     * Search for a song across all connected services
     * 
     * @param int $user_id User ID
     * @param string $song_title Song title
     * @param string $artist Artist name
     * @return array Results grouped by service
     */
    public function search_all_services($user_id, $song_title, $artist = 'Grateful Dead') {
        $results = array();
        $connected_services = $this->credentials->get_connected_services($user_id);
        
        foreach ($connected_services as $service) {
            $service_results = $this->search_service($user_id, $service, $song_title, $artist);
            
            if (!is_wp_error($service_results) && !empty($service_results)) {
                $results[$service] = $service_results;
            }
        }
        
        return $results;
    }
    
    /**
     * Search a specific service
     * 
     * @param int $user_id User ID
     * @param string $service Service name
     * @param string $song_title Song title
     * @param string $artist Artist name
     * @return array|WP_Error Search results or error
     */
    public function search_service($user_id, $service, $song_title, $artist = 'Grateful Dead') {
        if (!isset($this->oauth_handlers[$service])) {
            return new WP_Error('invalid_service', 'Service not supported');
        }
        
        // Get valid access token
        $access_token = $this->credentials->get_valid_token($user_id, $service);
        
        if (empty($access_token)) {
            return new WP_Error('no_token', 'No valid access token');
        }
        
        // Check cache first
        $cache_key = 'gd_streaming_search_' . md5($service . $song_title . $artist);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Search service
        $handler = $this->oauth_handlers[$service];
        $results = $handler->search_song($song_title, $artist, $access_token);
        
        if (!is_wp_error($results)) {
            // Cache for 1 hour
            set_transient($cache_key, $results, HOUR_IN_SECONDS);
        }
        
        return $results;
    }
    
    /**
     * Get unified search results (Archive.org + streaming services)
     * 
     * @param int $user_id User ID
     * @param string $song_title Song title
     * @param array $options Search options
     * @return array Unified results
     */
    public function get_unified_results($user_id, $song_title, $options = array()) {
        $defaults = array(
            'artist' => 'Grateful Dead',
            'include_archive' => true,
            'sort_by' => 'service' // service, popularity, date
        );
        
        $options = array_merge($defaults, $options);
        
        $results = array(
            'archive' => array(),
            'streaming' => array()
        );
        
        // Get Archive.org results
        if ($options['include_archive']) {
            $archive_api = new GD_Archive_API();
            $archive_results = $archive_api->search_performances(array(
                'song' => $song_title,
                'rows' => 20
            ));
            
            if (!is_wp_error($archive_results)) {
                $results['archive'] = $archive_results;
            }
        }
        
        // Get streaming service results
        $streaming_results = $this->search_all_services($user_id, $song_title, $options['artist']);
        $results['streaming'] = $streaming_results;
        
        // Add metadata
        $results['meta'] = array(
            'song_title' => $song_title,
            'artist' => $options['artist'],
            'total_sources' => count($results['streaming']) + ($options['include_archive'] ? 1 : 0),
            'connected_services' => $this->credentials->get_connected_services($user_id)
        );
        
        return $results;
    }
    
    /**
     * Get OAuth authorization URL for a service
     * 
     * @param string $service Service name
     * @param int $user_id User ID
     * @return string|WP_Error Authorization URL or error
     */
    public function get_auth_url($service, $user_id) {
        if (!isset($this->oauth_handlers[$service])) {
            return new WP_Error('invalid_service', 'Service not supported');
        }
        
        $handler = $this->oauth_handlers[$service];
        
        if (!$handler->is_configured()) {
            return new WP_Error('not_configured', 'Service not configured');
        }
        
        // Generate and store state token
        $state = wp_generate_password(32, false);
        set_transient('gd_oauth_state_' . $user_id . '_' . $service, $state, 600); // 10 minutes
        
        return $handler->get_authorization_url($state);
    }
    
    /**
     * Handle OAuth callback
     * 
     * @param string $service Service name
     * @param string $code Authorization code
     * @param string $state State token
     * @param int $user_id User ID
     * @return bool|WP_Error Success or error
     */
    public function handle_oauth_callback($service, $code, $state, $user_id) {
        if (!isset($this->oauth_handlers[$service])) {
            return new WP_Error('invalid_service', 'Service not supported');
        }
        
        // Verify state token
        $stored_state = get_transient('gd_oauth_state_' . $user_id . '_' . $service);
        
        if ($stored_state !== $state) {
            return new WP_Error('invalid_state', 'Invalid state token');
        }
        
        // Delete state token
        delete_transient('gd_oauth_state_' . $user_id . '_' . $service);
        
        // Exchange code for token
        $handler = $this->oauth_handlers[$service];
        $credentials = $handler->exchange_code_for_token($code);
        
        if (is_wp_error($credentials)) {
            return $credentials;
        }
        
        // Store credentials
        $result = $this->credentials->store_credentials($user_id, $service, $credentials);
        
        return $result ? true : new WP_Error('storage_failed', 'Failed to store credentials');
    }
    
    /**
     * Disconnect a service
     * 
     * @param int $user_id User ID
     * @param string $service Service name
     * @return bool Success
     */
    public function disconnect_service($user_id, $service) {
        return $this->credentials->delete_credentials($user_id, $service);
    }
    
    /**
     * Get connection status for all services
     * 
     * @param int $user_id User ID
     * @return array Status array
     */
    public function get_connection_status($user_id) {
        $status = $this->credentials->get_connection_status($user_id);
        
        // Add configuration status
        foreach ($status as $service => &$info) {
            if (isset($this->oauth_handlers[$service])) {
                $info['configured'] = $this->oauth_handlers[$service]->is_configured();
            } else {
                $info['configured'] = false;
            }
        }
        
        return $status;
    }
    
    /**
     * Get available services (configured and ready)
     * 
     * @return array Available services
     */
    public function get_available_services() {
        $available = array();
        
        foreach ($this->oauth_handlers as $service => $handler) {
            if ($handler->is_configured()) {
                $available[$service] = GD_Streaming_Credentials::SERVICES[$service];
            }
        }
        
        return $available;
    }
}
