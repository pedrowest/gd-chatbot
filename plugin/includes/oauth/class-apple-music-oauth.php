<?php
/**
 * Apple Music OAuth Handler
 * 
 * Handles MusicKit JS authentication and API integration with Apple Music
 * Note: Apple Music uses a different auth flow (JWT-based developer tokens)
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-oauth-base.php';

class GD_Apple_Music_OAuth extends GD_OAuth_Base {
    
    /**
     * Developer token
     */
    private $developer_token;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->service_name = 'apple_music';
        $this->api_base_url = 'https://api.music.apple.com/v1';
        
        parent::__construct();
    }
    
    /**
     * Load configuration
     */
    protected function load_config() {
        // Apple Music uses developer tokens, not OAuth
        $this->developer_token = get_option('gd_chatbot_v2_apple_music_developer_token', '');
        $this->client_id = get_option('gd_chatbot_v2_apple_music_team_id', '');
        $this->client_secret = get_option('gd_chatbot_v2_apple_music_key_id', '');
    }
    
    /**
     * Get authorization URL
     * Apple Music uses MusicKit JS, so this returns the setup URL
     * 
     * @param string $state CSRF state token
     * @return string Authorization URL
     */
    public function get_authorization_url($state) {
        // Apple Music uses client-side MusicKit JS
        // Return admin URL to configure MusicKit
        return admin_url('admin.php?page=gd-chatbot-v2&tab=streaming&service=apple_music');
    }
    
    /**
     * Exchange code for token
     * Apple Music doesn't use this flow
     * 
     * @param string $code Authorization code
     * @return array Token data
     */
    public function exchange_code_for_token($code) {
        // Apple Music uses developer tokens
        return array(
            'access_token' => $this->developer_token,
            'token_type' => 'Bearer',
            'expires_in' => 15777000 // 6 months
        );
    }
    
    /**
     * Refresh access token
     * Apple Music developer tokens don't expire frequently
     * 
     * @param string $refresh_token Refresh token
     * @return array Token data
     */
    public function refresh_access_token($refresh_token) {
        return array(
            'access_token' => $this->developer_token,
            'token_type' => 'Bearer',
            'expires_in' => 15777000
        );
    }
    
    /**
     * Search for a song
     * 
     * @param string $song_title Song title
     * @param string $artist Artist name (default: "Grateful Dead")
     * @param string $access_token Access token (developer token)
     * @return array|WP_Error Search results or error
     */
    public function search_song($song_title, $artist = 'Grateful Dead', $access_token = null) {
        // Use developer token if no access token provided
        if (empty($access_token)) {
            $access_token = $this->developer_token;
        }
        
        // Build search query
        $query = $song_title . ' ' . $artist;
        
        $params = array(
            'term' => $query,
            'types' => 'songs',
            'limit' => 20
        );
        
        $response = $this->api_request('/catalog/us/search', $access_token, $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->format_search_results($response);
    }
    
    /**
     * Format search results
     * 
     * @param array $response API response
     * @return array Formatted results
     */
    private function format_search_results($response) {
        $results = array();
        
        if (empty($response['results']['songs']['data'])) {
            return $results;
        }
        
        foreach ($response['results']['songs']['data'] as $song) {
            $attributes = $song['attributes'];
            
            $results[] = array(
                'id' => $song['id'],
                'title' => $attributes['name'] ?? '',
                'artist' => $attributes['artistName'] ?? 'Unknown',
                'album' => $attributes['albumName'] ?? '',
                'duration_ms' => ($attributes['durationInMillis'] ?? 0),
                'url' => $attributes['url'] ?? '',
                'preview_url' => $attributes['previews'][0]['url'] ?? null,
                'image' => str_replace('{w}x{h}', '300x300', $attributes['artwork']['url'] ?? ''),
                'popularity' => 0, // Apple Music doesn't provide popularity
                'service' => 'apple_music'
            );
        }
        
        return $results;
    }
    
    /**
     * Get song details
     * 
     * @param string $song_id Song ID
     * @param string $access_token Access token
     * @return array|WP_Error Song details or error
     */
    public function get_song($song_id, $access_token = null) {
        if (empty($access_token)) {
            $access_token = $this->developer_token;
        }
        
        $response = $this->api_request('/catalog/us/songs/' . $song_id, $access_token);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $attributes = $response['data'][0]['attributes'];
        
        return array(
            'id' => $response['data'][0]['id'],
            'title' => $attributes['name'] ?? '',
            'artist' => $attributes['artistName'] ?? 'Unknown',
            'album' => $attributes['albumName'] ?? '',
            'duration_ms' => ($attributes['durationInMillis'] ?? 0),
            'url' => $attributes['url'] ?? '',
            'preview_url' => $attributes['previews'][0]['url'] ?? null,
            'image' => str_replace('{w}x{h}', '300x300', $attributes['artwork']['url'] ?? ''),
            'service' => 'apple_music'
        );
    }
    
    /**
     * Validate configuration
     * 
     * @return bool Is valid
     */
    public function is_configured() {
        return !empty($this->developer_token);
    }
}
