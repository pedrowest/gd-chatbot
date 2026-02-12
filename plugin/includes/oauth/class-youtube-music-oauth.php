<?php
/**
 * YouTube Music OAuth Handler
 * 
 * Handles OAuth 2.0 authentication and API integration with YouTube Music
 * Uses YouTube Data API v3
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-oauth-base.php';

class GD_Youtube_Music_OAuth extends GD_OAuth_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->service_name = 'youtube_music';
        $this->auth_endpoint = 'https://accounts.google.com/o/oauth2/v2/auth';
        $this->token_endpoint = 'https://oauth2.googleapis.com/token';
        $this->api_base_url = 'https://www.googleapis.com/youtube/v3';
        $this->scopes = array('https://www.googleapis.com/auth/youtube.readonly');
        
        parent::__construct();
    }
    
    /**
     * Load configuration
     */
    protected function load_config() {
        $this->client_id = get_option('gd_chatbot_v2_youtube_music_client_id', '');
        $this->client_secret = get_option('gd_chatbot_v2_youtube_music_client_secret', '');
    }
    
    /**
     * Add authorization parameters
     */
    protected function add_auth_params($params) {
        $params['access_type'] = 'offline'; // Get refresh token
        $params['prompt'] = 'consent'; // Force consent screen
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
        $query = $song_title . ' ' . $artist . ' official audio';
        
        $params = array(
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'videoCategoryId' => '10', // Music category
            'maxResults' => 20,
            'order' => 'relevance'
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
        
        if (empty($response['items'])) {
            return $results;
        }
        
        foreach ($response['items'] as $item) {
            $snippet = $item['snippet'];
            
            $results[] = array(
                'id' => $item['id']['videoId'],
                'title' => $snippet['title'] ?? '',
                'artist' => $snippet['channelTitle'] ?? 'Unknown',
                'album' => '',
                'duration_ms' => 0, // Would need additional API call to get duration
                'url' => 'https://music.youtube.com/watch?v=' . $item['id']['videoId'],
                'preview_url' => null, // YouTube doesn't provide preview URLs
                'image' => $snippet['thumbnails']['high']['url'] ?? null,
                'popularity' => 0,
                'service' => 'youtube_music',
                'published_at' => $snippet['publishedAt'] ?? ''
            );
        }
        
        return $results;
    }
    
    /**
     * Get video details
     * 
     * @param string $video_id Video ID
     * @param string $access_token Access token
     * @return array|WP_Error Video details or error
     */
    public function get_video($video_id, $access_token) {
        $params = array(
            'part' => 'snippet,contentDetails',
            'id' => $video_id
        );
        
        $response = $this->api_request('/videos', $access_token, $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        if (empty($response['items'][0])) {
            return new WP_Error('not_found', 'Video not found');
        }
        
        $video = $response['items'][0];
        $snippet = $video['snippet'];
        $duration = $this->parse_duration($video['contentDetails']['duration']);
        
        return array(
            'id' => $video['id'],
            'title' => $snippet['title'] ?? '',
            'artist' => $snippet['channelTitle'] ?? 'Unknown',
            'album' => '',
            'duration_ms' => $duration * 1000,
            'url' => 'https://music.youtube.com/watch?v=' . $video['id'],
            'preview_url' => null,
            'image' => $snippet['thumbnails']['high']['url'] ?? null,
            'service' => 'youtube_music'
        );
    }
    
    /**
     * Parse ISO 8601 duration to seconds
     * 
     * @param string $duration ISO 8601 duration (e.g., "PT4M33S")
     * @return int Duration in seconds
     */
    private function parse_duration($duration) {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
        
        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
        $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
        
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}
