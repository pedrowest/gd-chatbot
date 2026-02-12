<?php
/**
 * Amazon Music OAuth Handler
 * 
 * Handles OAuth 2.0 authentication and API integration with Amazon Music
 * Uses Login with Amazon (LWA)
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-oauth-base.php';

class GD_Amazon_Music_OAuth extends GD_OAuth_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->service_name = 'amazon_music';
        $this->auth_endpoint = 'https://www.amazon.com/ap/oa';
        $this->token_endpoint = 'https://api.amazon.com/auth/o2/token';
        $this->api_base_url = 'https://api.music.amazon.com/v1';
        $this->scopes = array('profile', 'music:access');
        
        parent::__construct();
    }
    
    /**
     * Load configuration
     */
    protected function load_config() {
        $this->client_id = get_option('gd_chatbot_v2_amazon_music_client_id', '');
        $this->client_secret = get_option('gd_chatbot_v2_amazon_music_client_secret', '');
    }
    
    /**
     * Search for a song
     * 
     * @param string $song_title Song title
     * @param string $artist Artist name (default: "Grateful Dead")
     * @param string $access_token Access token
     * @return array|WP_Error Search results or error
     */
    public function search_song($song_title, $artist = 'Grateful Dead', $access_token) {
        // Build search query
        $query = $song_title . ' ' . $artist;
        
        $params = array(
            'query' => $query,
            'type' => 'track',
            'limit' => 20
        );
        
        $response = $this->api_request('/search', $access_token, $params);
        
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
        
        if (empty($response['tracks'])) {
            return $results;
        }
        
        foreach ($response['tracks'] as $track) {
            $results[] = array(
                'id' => $track['id'] ?? '',
                'title' => $track['title'] ?? '',
                'artist' => $track['artist']['name'] ?? 'Unknown',
                'album' => $track['album']['title'] ?? '',
                'duration_ms' => ($track['duration'] ?? 0) * 1000,
                'url' => $track['url'] ?? '',
                'preview_url' => $track['previewUrl'] ?? null,
                'image' => $track['album']['image'] ?? null,
                'popularity' => $track['popularity'] ?? 0,
                'service' => 'amazon_music'
            );
        }
        
        return $results;
    }
    
    /**
     * Get track details
     * 
     * @param string $track_id Track ID
     * @param string $access_token Access token
     * @return array|WP_Error Track details or error
     */
    public function get_track($track_id, $access_token) {
        $response = $this->api_request('/tracks/' . $track_id, $access_token);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return array(
            'id' => $response['id'] ?? '',
            'title' => $response['title'] ?? '',
            'artist' => $response['artist']['name'] ?? 'Unknown',
            'album' => $response['album']['title'] ?? '',
            'duration_ms' => ($response['duration'] ?? 0) * 1000,
            'url' => $response['url'] ?? '',
            'preview_url' => $response['previewUrl'] ?? null,
            'image' => $response['album']['image'] ?? null,
            'service' => 'amazon_music'
        );
    }
}
