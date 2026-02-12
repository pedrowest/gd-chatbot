<?php
/**
 * Spotify OAuth Handler
 * 
 * Handles OAuth 2.0 authentication and API integration with Spotify
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-oauth-base.php';

class GD_Spotify_OAuth extends GD_OAuth_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->service_name = 'spotify';
        $this->auth_endpoint = 'https://accounts.spotify.com/authorize';
        $this->token_endpoint = 'https://accounts.spotify.com/api/token';
        $this->api_base_url = 'https://api.spotify.com/v1';
        $this->scopes = array('user-read-private', 'user-read-email');
        
        parent::__construct();
    }
    
    /**
     * Load configuration
     */
    protected function load_config() {
        $this->client_id = get_option('gd_chatbot_v2_spotify_client_id', '');
        $this->client_secret = get_option('gd_chatbot_v2_spotify_client_secret', '');
    }
    
    /**
     * Get token headers (Spotify requires Basic auth)
     */
    protected function get_token_headers() {
        $auth = base64_encode($this->client_id . ':' . $this->client_secret);
        
        return array(
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/x-www-form-urlencoded'
        );
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
        $query = $song_title . ' artist:' . $artist;
        
        $params = array(
            'q' => $query,
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
        
        if (empty($response['tracks']['items'])) {
            return $results;
        }
        
        foreach ($response['tracks']['items'] as $track) {
            $results[] = array(
                'id' => $track['id'],
                'title' => $track['name'],
                'artist' => $track['artists'][0]['name'] ?? 'Unknown',
                'album' => $track['album']['name'] ?? '',
                'duration_ms' => $track['duration_ms'] ?? 0,
                'url' => $track['external_urls']['spotify'] ?? '',
                'preview_url' => $track['preview_url'] ?? null,
                'image' => $track['album']['images'][0]['url'] ?? null,
                'popularity' => $track['popularity'] ?? 0,
                'service' => 'spotify'
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
            'id' => $response['id'],
            'title' => $response['name'],
            'artist' => $response['artists'][0]['name'] ?? 'Unknown',
            'album' => $response['album']['name'] ?? '',
            'duration_ms' => $response['duration_ms'] ?? 0,
            'url' => $response['external_urls']['spotify'] ?? '',
            'preview_url' => $response['preview_url'] ?? null,
            'image' => $response['album']['images'][0]['url'] ?? null,
            'service' => 'spotify'
        );
    }
}
