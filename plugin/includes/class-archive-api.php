<?php
/**
 * Archive.org API Integration
 * 
 * Handles searches and metadata retrieval from Internet Archive
 * for Grateful Dead live performances
 * 
 * @package GD_Chatbot
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Archive_API {
    
    /**
     * Archive.org API base URL
     */
    const API_BASE = 'https://archive.org';
    
    /**
     * Advanced search endpoint
     */
    const SEARCH_ENDPOINT = 'https://archive.org/advancedsearch.php';
    
    /**
     * Metadata endpoint
     */
    const METADATA_ENDPOINT = 'https://archive.org/metadata/';
    
    /**
     * Download/streaming base URL
     */
    const DOWNLOAD_BASE = 'https://archive.org/download/';
    
    /**
     * Default cache duration (24 hours)
     */
    const CACHE_DURATION = 86400;
    
    /**
     * Search for Grateful Dead performances
     * 
     * @param array $args Search arguments
     * @return array|WP_Error Search results or error
     */
    public function search_performances($args = array()) {
        $defaults = array(
            'song_title' => '',
            'date' => '',
            'year' => '',
            'venue' => '',
            'sort_by' => 'downloads', // downloads, date, avg_rating
            'limit' => 50,
            'use_cache' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build cache key
        $cache_key = 'gd_archive_search_' . md5(serialize($args));
        
        // Check cache
        if ($args['use_cache']) {
            $cached = get_transient($cache_key);
            if (false !== $cached) {
                return $cached;
            }
        }
        
        // Build query
        $query = $this->build_search_query($args);
        
        // Build URL
        $url = add_query_arg(array(
            'q' => $query,
            'fl[]' => array(
                'identifier',
                'title',
                'date',
                'venue',
                'coverage',
                'downloads',
                'avg_rating',
                'num_reviews',
                'format'
            ),
            'sort[]' => $this->get_sort_param($args['sort_by']),
            'rows' => $args['limit'],
            'output' => 'json'
        ), self::SEARCH_ENDPOINT);
        
        // Make request
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'GD-Chatbot/' . GD_CHATBOT_VERSION
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['response']['docs'])) {
            return array();
        }
        
        // Format results
        $results = $this->format_search_results($data['response']['docs']);
        
        // Cache results
        if ($args['use_cache']) {
            set_transient($cache_key, $results, self::CACHE_DURATION);
        }
        
        return $results;
    }
    
    /**
     * Build search query string
     * 
     * @param array $args Search arguments
     * @return string Query string
     */
    private function build_search_query($args) {
        $conditions = array();
        
        // Always search Grateful Dead collection
        $conditions[] = 'collection:GratefulDead';
        $conditions[] = 'mediatype:etree';
        
        // Add song title if specified
        if (!empty($args['song_title'])) {
            $conditions[] = 'title:("' . esc_attr($args['song_title']) . '")';
        }
        
        // Add date if specified
        if (!empty($args['date'])) {
            $conditions[] = 'date:' . esc_attr($args['date']);
        }
        
        // Add year if specified
        if (!empty($args['year'])) {
            $conditions[] = 'year:' . esc_attr($args['year']);
        }
        
        // Add venue if specified
        if (!empty($args['venue'])) {
            $conditions[] = 'venue:("' . esc_attr($args['venue']) . '")';
        }
        
        // Prefer VBR MP3 format
        $conditions[] = 'format:"VBR MP3"';
        
        return implode(' AND ', $conditions);
    }
    
    /**
     * Get sort parameter for Archive.org
     * 
     * @param string $sort_by Sort method
     * @return string Sort parameter
     */
    private function get_sort_param($sort_by) {
        switch ($sort_by) {
            case 'date':
                return 'date asc';
            case 'rating':
            case 'avg_rating':
                return 'avg_rating desc';
            case 'downloads':
            default:
                return 'downloads desc';
        }
    }
    
    /**
     * Format search results
     * 
     * @param array $docs Raw Archive.org documents
     * @return array Formatted results
     */
    private function format_search_results($docs) {
        $results = array();
        
        foreach ($docs as $doc) {
            $identifier = $doc['identifier'] ?? '';
            
            if (empty($identifier)) {
                continue;
            }
            
            // Parse date and venue from title or metadata
            $parsed = $this->parse_show_info($doc);
            
            $results[] = array(
                'identifier' => $identifier,
                'title' => $doc['title'] ?? '',
                'date' => $parsed['date'],
                'venue' => $parsed['venue'],
                'location' => $parsed['location'],
                'downloads' => intval($doc['downloads'] ?? 0),
                'rating' => floatval($doc['avg_rating'] ?? 0),
                'num_reviews' => intval($doc['num_reviews'] ?? 0),
                'thumbnail' => $this->get_thumbnail_url($identifier),
                'archive_url' => self::API_BASE . '/details/' . $identifier,
                'stream_url' => '', // Will be populated when needed
                'metadata' => $doc
            );
        }
        
        return $results;
    }
    
    /**
     * Parse show information from Archive.org metadata
     * 
     * @param array $doc Archive.org document
     * @return array Parsed info (date, venue, location)
     */
    private function parse_show_info($doc) {
        $info = array(
            'date' => null,
            'venue' => null,
            'location' => null
        );
        
        // Try to get date from 'date' field
        if (!empty($doc['date'])) {
            $date_str = $doc['date'];
            // Archive.org uses ISO format: 1977-05-08T00:00:00Z
            $info['date'] = substr($date_str, 0, 10); // Extract YYYY-MM-DD
        }
        
        // Try to get venue from 'venue' field
        if (!empty($doc['venue'])) {
            $info['venue'] = $doc['venue'];
        }
        
        // Try to get location from 'coverage' field
        if (!empty($doc['coverage'])) {
            $info['location'] = $doc['coverage'];
        }
        
        // Fallback: parse from title
        // Title format: "Grateful Dead Live at Fillmore West on 1969-02-27"
        if (empty($info['venue']) || empty($info['date'])) {
            $title = $doc['title'] ?? '';
            
            // Extract date from title (YYYY-MM-DD)
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $title, $matches)) {
                $info['date'] = $matches[1];
            }
            
            // Extract venue from "Live at [Venue] on"
            if (preg_match('/Live at (.+?) on \d{4}-\d{2}-\d{2}/', $title, $matches)) {
                $info['venue'] = trim($matches[1]);
            }
        }
        
        return $info;
    }
    
    /**
     * Get thumbnail URL for a show
     * 
     * @param string $identifier Archive.org identifier
     * @return string Thumbnail URL
     */
    private function get_thumbnail_url($identifier) {
        return 'https://archive.org/services/img/' . $identifier;
    }
    
    /**
     * Get detailed metadata for a specific performance
     * 
     * @param string $identifier Archive.org identifier
     * @param bool $use_cache Whether to use cache
     * @return array|WP_Error Metadata or error
     */
    public function get_metadata($identifier, $use_cache = true) {
        if (empty($identifier)) {
            return new WP_Error('invalid_identifier', 'Archive.org identifier is required');
        }
        
        // Check cache
        $cache_key = 'gd_archive_meta_' . $identifier;
        
        if ($use_cache) {
            $cached = get_transient($cache_key);
            if (false !== $cached) {
                return $cached;
            }
        }
        
        // Make request
        $url = self::METADATA_ENDPOINT . $identifier;
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'GD-Chatbot/' . GD_CHATBOT_VERSION
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            return new WP_Error('invalid_response', 'Invalid response from Archive.org');
        }
        
        // Cache metadata (7 days - it's static)
        if ($use_cache) {
            set_transient($cache_key, $data, 7 * DAY_IN_SECONDS);
        }
        
        return $data;
    }
    
    /**
     * Get streaming URL for a performance
     * 
     * @param string $identifier Archive.org identifier
     * @param string $format File format (mp3, ogg, flac)
     * @return string|WP_Error Streaming URL or error
     */
    public function get_streaming_url($identifier, $format = 'mp3') {
        // Get metadata to find files
        $metadata = $this->get_metadata($identifier);
        
        if (is_wp_error($metadata)) {
            return $metadata;
        }
        
        // Find the best file for streaming
        $files = $metadata['files'] ?? array();
        $best_file = null;
        
        // Preferred formats in order
        $preferred_formats = array(
            'VBR MP3' => '.mp3',
            'Ogg Vorbis' => '.ogg',
            'FLAC' => '.flac'
        );
        
        foreach ($preferred_formats as $format_name => $extension) {
            foreach ($files as $file) {
                $filename = $file['name'] ?? '';
                $file_format = $file['format'] ?? '';
                
                if ($file_format === $format_name && strpos($filename, $extension) !== false) {
                    // Skip derivative files
                    if (strpos($filename, '_') === 0) {
                        continue;
                    }
                    
                    $best_file = $filename;
                    break 2;
                }
            }
        }
        
        if (empty($best_file)) {
            return new WP_Error('no_streaming_file', 'No suitable streaming file found');
        }
        
        // Build streaming URL
        return self::DOWNLOAD_BASE . $identifier . '/' . $best_file;
    }
    
    /**
     * Get all MP3 files for a performance (for setlist parsing)
     * 
     * @param string $identifier Archive.org identifier
     * @return array Array of MP3 files with metadata
     */
    public function get_mp3_files($identifier) {
        $metadata = $this->get_metadata($identifier);
        
        if (is_wp_error($metadata)) {
            return array();
        }
        
        $files = $metadata['files'] ?? array();
        $mp3_files = array();
        
        foreach ($files as $file) {
            $filename = $file['name'] ?? '';
            $format = $file['format'] ?? '';
            
            // Only get VBR MP3 files, skip derivatives
            if ($format === 'VBR MP3' && strpos($filename, '_') !== 0) {
                $mp3_files[] = array(
                    'filename' => $filename,
                    'title' => $file['title'] ?? '',
                    'track' => $file['track'] ?? '',
                    'length' => $file['length'] ?? '',
                    'size' => $file['size'] ?? 0,
                    'url' => self::DOWNLOAD_BASE . $identifier . '/' . $filename
                );
            }
        }
        
        // Sort by track number
        usort($mp3_files, function($a, $b) {
            return intval($a['track']) - intval($b['track']);
        });
        
        return $mp3_files;
    }
    
    /**
     * Search for performances by date
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @param string $sort_by Sort method
     * @return array|WP_Error Search results or error
     */
    public function search_by_date($date, $sort_by = 'downloads') {
        return $this->search_performances(array(
            'date' => $date,
            'sort_by' => $sort_by
        ));
    }
    
    /**
     * Search for performances by year
     * 
     * @param int $year Year (1965-1995)
     * @param string $sort_by Sort method
     * @param int $limit Result limit
     * @return array|WP_Error Search results or error
     */
    public function search_by_year($year, $sort_by = 'date', $limit = 100) {
        return $this->search_performances(array(
            'year' => $year,
            'sort_by' => $sort_by,
            'limit' => $limit
        ));
    }
    
    /**
     * Search for performances featuring a specific song
     * 
     * @param string $song_title Song title
     * @param string $sort_by Sort method
     * @param int $limit Result limit
     * @return array|WP_Error Search results or error
     */
    public function search_by_song($song_title, $sort_by = 'downloads', $limit = 50) {
        return $this->search_performances(array(
            'song_title' => $song_title,
            'sort_by' => $sort_by,
            'limit' => $limit
        ));
    }
    
    /**
     * Clear all Archive.org caches
     * 
     * @return int Number of cache entries deleted
     */
    public function clear_cache() {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_gd_archive_%' 
             OR option_name LIKE '_transient_timeout_gd_archive_%'"
        );
        
        return $deleted;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache stats
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $stats = array();
        
        $stats['search_cache_count'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_gd_archive_search_%'"
        );
        
        $stats['metadata_cache_count'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_gd_archive_meta_%'"
        );
        
        $stats['total_cache_size'] = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_gd_archive_%'"
        );
        
        return $stats;
    }
}
