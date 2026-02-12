<?php
/**
 * Tidal OAuth Handler
 * 
 * Handles OAuth 2.0 authentication and API integration with Tidal
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-oauth-base.php';

class GD_Tidal_OAuth extends GD_OAuth_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->service_name = 'tidal';
        $this->auth_endpoint = 'https://login.tidal.com/authorize';
        $this->token_endpoint = 'https://auth.tidal.com/v1/oauth2/token';
        $this->api_base_url = 'https://openapi.tidal.com/v1';
        $this->scopes = array('r_usr', 'w_usr');
        
        parent::__construct();
    }
    
    /**
     * Load configuration
     */
    protected function load_config() {
        $this->client_id = get_option('gd_chatbot_v2_tidal_client_id', '');
        $this->client_secret = get_option('gd_chatbot_v2_tidal_client_secret', '');
    }
    
    /**
     * Add authorization parameters
     */
    protected function add_auth_params($params) {
        $params['code_challenge_method'] = 'S256';
        // In production, generate and store code_verifier, then create code_challenge
        // For simplicity, using basic flow here
        return $params;
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
            'type' => 'TRACKS',
            'limit' => 20,
            'countryCode' => 'US'
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
                'url' => 'https://tidal.com/browse/track/' . ($track['id'] ?? ''),
                'preview_url' => null, // Tidal doesn't provide preview URLs
                'image' => isset($track['album']['cover']) ? 
                    'https://resources.tidal.com/images/' . str_replace('-', '/', $track['album']['cover']) . '/640x640.jpg' : 
                    null,
                'popularity' => $track['popularity'] ?? 0,
                'service' => 'tidal',
                'quality' => $track['audioQuality'] ?? 'LOSSLESS'
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
        $response = $this->api_request('/tracks/' . $track_id, $access_token, array(
            'countryCode' => 'US'
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return array(
            'id' => $response['id'] ?? '',
            'title' => $response['title'] ?? '',
            'artist' => $response['artist']['name'] ?? 'Unknown',
            'album' => $response['album']['title'] ?? '',
            'duration_ms' => ($response['duration'] ?? 0) * 1000,
            'url' => 'https://tidal.com/browse/track/' . ($response['id'] ?? ''),
            'preview_url' => null,
            'image' => isset($response['album']['cover']) ? 
                'https://resources.tidal.com/images/' . str_replace('-', '/', $response['album']['cover']) . '/640x640.jpg' : 
                null,
            'service' => 'tidal',
            'quality' => $response['audioQuality'] ?? 'LOSSLESS'
        );
    }
}
