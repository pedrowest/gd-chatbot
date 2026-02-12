<?php
/**
 * Song Detector Class
 * 
 * Detects Grateful Dead song mentions in chatbot responses
 * and enriches them with clickable links for music streaming
 * 
 * @package GD_Chatbot
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Song_Detector {
    
    /**
     * Cached song list
     */
    private $songs = null;
    
    /**
     * Song title to metadata map
     */
    private $song_map = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_songs();
    }
    
    /**
     * Load songs from CSV file
     * 
     * @return array Song list
     */
    private function load_songs() {
        // Check cache first
        $cache_key = 'gd_chatbot_songs_list';
        $cached = get_transient($cache_key);
        
        if (false !== $cached) {
            $this->songs = $cached['songs'];
            $this->song_map = $cached['map'];
            return $this->songs;
        }
        
        // Load from CSV
        $csv_path = GD_CHATBOT_PLUGIN_DIR . 'context/reference/songs.csv';
        
        if (!file_exists($csv_path)) {
            error_log('GD Chatbot: songs.csv not found at: ' . $csv_path);
            return array();
        }
        
        $this->songs = array();
        $this->song_map = array();
        
        $file = fopen($csv_path, 'r');
        if ($file === false) {
            return array();
        }
        
        // Skip header
        fgetcsv($file);
        
        // Read all songs
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) >= 2) {
                $title = trim($row[0]);
                $author = trim($row[1]);
                
                if (!empty($title)) {
                    $this->songs[] = array(
                        'title' => $title,
                        'author' => $author,
                        'slug' => sanitize_title($title),
                        'normalized' => $this->normalize_title($title)
                    );
                    
                    // Map normalized title to original
                    $normalized = $this->normalize_title($title);
                    $this->song_map[$normalized] = array(
                        'title' => $title,
                        'author' => $author
                    );
                }
            }
        }
        
        fclose($file);
        
        // Sort by length (longest first) for better matching
        usort($this->songs, function($a, $b) {
            return strlen($b['title']) - strlen($a['title']);
        });
        
        // Cache for 24 hours
        set_transient($cache_key, array(
            'songs' => $this->songs,
            'map' => $this->song_map
        ), 24 * HOUR_IN_SECONDS);
        
        return $this->songs;
    }
    
    /**
     * Normalize song title for matching
     * 
     * @param string $title Song title
     * @return string Normalized title
     */
    private function normalize_title($title) {
        // Convert to lowercase
        $normalized = strtolower($title);
        
        // Remove punctuation except apostrophes
        $normalized = preg_replace("/[^\w\s']/", '', $normalized);
        
        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }
    
    /**
     * Detect songs in text and return enriched HTML
     * 
     * @param string $text Chatbot response text
     * @return string Text with song links
     */
    public function enrich_response($text) {
        if (empty($text) || empty($this->songs)) {
            return $text;
        }
        
        // Track detected songs to avoid duplicates
        $detected = array();
        
        // Find all song mentions
        foreach ($this->songs as $song) {
            $title = $song['title'];
            $author = $song['author'];
            $slug = $song['slug'];
            
            // Skip if already detected
            if (isset($detected[$slug])) {
                continue;
            }
            
            // Build regex pattern for this song
            // Match word boundaries to avoid partial matches
            $pattern = '/\b' . preg_quote($title, '/') . '\b/i';
            
            // Check if song exists in text
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                // Get the actual matched text (preserves original case)
                $matched_text = $matches[0][0];
                $position = $matches[0][1];
                
                // Check context to avoid false positives
                if ($this->is_valid_context($text, $position, strlen($matched_text))) {
                    $detected[$slug] = array(
                        'title' => $title,
                        'author' => $author,
                        'matched_text' => $matched_text,
                        'pattern' => $pattern
                    );
                }
            }
        }
        
        // If no songs detected, return original text
        if (empty($detected)) {
            return $text;
        }
        
        // Replace detected songs with enriched HTML
        // Process in reverse order of length to avoid nested replacements
        $songs_by_length = $detected;
        uasort($songs_by_length, function($a, $b) {
            return strlen($b['matched_text']) - strlen($a['matched_text']);
        });
        
        foreach ($songs_by_length as $slug => $song) {
            $pattern = $song['pattern'];
            $title = $song['title'];
            $author = $song['author'];
            $matched_text = $song['matched_text'];
            
            // Create enriched HTML
            $replacement = sprintf(
                '<span class="gd-song-link" data-song-id="%s" data-song-title="%s" data-song-author="%s">%s</span>',
                esc_attr($slug),
                esc_attr($title),
                esc_attr($author),
                esc_html($matched_text)
            );
            
            // Replace first occurrence only (to avoid replacing inside already-replaced HTML)
            $text = preg_replace($pattern, $replacement, $text, 1);
        }
        
        return $text;
    }
    
    /**
     * Check if song mention is in valid context
     * 
     * @param string $text Full text
     * @param int $position Position of match
     * @param int $length Length of match
     * @return bool Whether context is valid
     */
    private function is_valid_context($text, $position, $length) {
        // Get surrounding context (50 chars before and after)
        $start = max(0, $position - 50);
        $end = min(strlen($text), $position + $length + 50);
        $context = substr($text, $start, $end - $start);
        $context_lower = strtolower($context);
        
        // Exclude if inside HTML tags
        if (preg_match('/<[^>]*' . preg_quote(substr($text, $position, $length), '/') . '[^>]*>/', $text)) {
            return false;
        }
        
        // Exclude if inside quotes (likely discussing the song title itself)
        $before = substr($text, 0, $position);
        $quote_count = substr_count($before, '"') + substr_count($before, "'");
        if ($quote_count % 2 !== 0) {
            return false;
        }
        
        // Exclude common false positives
        $false_positive_patterns = array(
            '/titled?\s+["\']?' . preg_quote(substr($text, $position, $length), '/') . '/i',
            '/called\s+["\']?' . preg_quote(substr($text, $position, $length), '/') . '/i',
            '/song\s+["\']?' . preg_quote(substr($text, $position, $length), '/') . '/i'
        );
        
        foreach ($false_positive_patterns as $pattern) {
            if (preg_match($pattern, $context)) {
                // Actually, these ARE valid contexts for song mentions!
                return true;
            }
        }
        
        // Positive indicators (song-related context)
        $positive_indicators = array(
            'played', 'performed', 'song', 'track', 'setlist', 'show',
            'concert', 'live', 'version', 'recording', 'performance'
        );
        
        foreach ($positive_indicators as $indicator) {
            if (strpos($context_lower, $indicator) !== false) {
                return true;
            }
        }
        
        // Default: allow if no negative indicators found
        return true;
    }
    
    /**
     * Detect songs in text and return metadata only
     * 
     * @param string $text Text to analyze
     * @return array Array of detected songs with positions
     */
    public function detect_songs($text) {
        if (empty($text) || empty($this->songs)) {
            return array();
        }
        
        $detected = array();
        
        foreach ($this->songs as $song) {
            $title = $song['title'];
            $pattern = '/\b' . preg_quote($title, '/') . '\b/i';
            
            if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $position = $match[1];
                    $matched_text = $match[0];
                    
                    if ($this->is_valid_context($text, $position, strlen($matched_text))) {
                        $detected[] = array(
                            'title' => $title,
                            'author' => $song['author'],
                            'slug' => $song['slug'],
                            'matched_text' => $matched_text,
                            'position' => $position,
                            'length' => strlen($matched_text)
                        );
                    }
                }
            }
        }
        
        // Sort by position
        usort($detected, function($a, $b) {
            return $a['position'] - $b['position'];
        });
        
        return $detected;
    }
    
    /**
     * Get song metadata by title
     * 
     * @param string $title Song title
     * @return array|null Song metadata or null if not found
     */
    public function get_song_metadata($title) {
        $normalized = $this->normalize_title($title);
        
        if (isset($this->song_map[$normalized])) {
            return $this->song_map[$normalized];
        }
        
        return null;
    }
    
    /**
     * Clear song cache
     */
    public static function clear_cache() {
        delete_transient('gd_chatbot_songs_list');
    }
    
    /**
     * Get all songs (for admin/debugging)
     * 
     * @return array All songs
     */
    public function get_all_songs() {
        return $this->songs;
    }
    
    /**
     * Get song count
     * 
     * @return int Number of songs
     */
    public function get_song_count() {
        return count($this->songs);
    }
}
