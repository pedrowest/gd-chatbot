<?php
/**
 * Response Enricher Class
 * 
 * Enriches chatbot responses with interactive elements
 * Currently handles song link detection and enrichment
 * 
 * @package GD_Chatbot
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Response_Enricher {
    
    /**
     * Song detector instance
     */
    private $song_detector;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->song_detector = new GD_Song_Detector();
    }
    
    /**
     * Enrich a chatbot response with interactive elements
     * 
     * @param string $response Raw chatbot response
     * @param array $options Enrichment options
     * @return string Enriched response
     */
    public function enrich($response, $options = array()) {
        $defaults = array(
            'enable_song_links' => true,
            'enable_venue_links' => false, // Future feature
            'enable_date_links' => false,  // Future feature
            'preserve_markdown' => true
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Check if streaming is enabled
        $streaming_enabled = get_option('gd_chatbot_streaming_enabled', false);
        
        if (!$streaming_enabled || !$options['enable_song_links']) {
            return $response;
        }
        
        // Enrich with song links
        $response = $this->enrich_song_links($response);
        
        return $response;
    }
    
    /**
     * Enrich response with clickable song links
     * 
     * @param string $response Response text
     * @return string Enriched response
     */
    private function enrich_song_links($response) {
        // Use song detector to find and enrich songs
        return $this->song_detector->enrich_response($response);
    }
    
    /**
     * Get statistics about enrichment
     * 
     * @param string $response Response text
     * @return array Statistics
     */
    public function get_enrichment_stats($response) {
        $stats = array(
            'songs_detected' => 0,
            'songs_enriched' => 0,
            'original_length' => strlen($response),
            'enriched_length' => 0
        );
        
        // Detect songs
        $detected = $this->song_detector->detect_songs($response);
        $stats['songs_detected'] = count($detected);
        
        // Enrich and measure
        $enriched = $this->enrich($response);
        $stats['enriched_length'] = strlen($enriched);
        
        // Count enriched spans
        preg_match_all('/<span class="gd-song-link"/', $enriched, $matches);
        $stats['songs_enriched'] = count($matches[0]);
        
        return $stats;
    }
    
    /**
     * Preview enrichment (for testing)
     * 
     * @param string $response Response text
     * @return array Preview data
     */
    public function preview_enrichment($response) {
        $detected = $this->song_detector->detect_songs($response);
        $enriched = $this->enrich($response);
        
        return array(
            'original' => $response,
            'enriched' => $enriched,
            'detected_songs' => $detected,
            'stats' => $this->get_enrichment_stats($response)
        );
    }
}
