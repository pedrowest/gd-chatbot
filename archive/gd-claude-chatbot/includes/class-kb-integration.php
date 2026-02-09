<?php
/**
 * GD Knowledgebase Loader Integration
 * 
 * Integrates with the GD Knowledgebase Loader plugin to provide
 * context from uploaded documents to the chatbot
 * 
 * @package GD_Claude_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_KB_Integration {
    
    /**
     * KB API instance
     */
    private $kb_api = null;
    
    /**
     * Whether KB Loader is available
     */
    private $is_available = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if KB Loader plugin is available
        add_action('plugins_loaded', array($this, 'check_availability'), 20);
        
        // Hook into KB Loader API ready event
        add_action('gd_kb_api_ready', array($this, 'on_kb_ready'));
    }
    
    /**
     * Check if KB Loader is available
     */
    public function check_availability() {
        if (function_exists('gd_kb_get_api')) {
            $this->kb_api = gd_kb_get_api();
            $this->is_available = $this->kb_api->is_ready();
            
            if ($this->is_available) {
                error_log('GD Chatbot: KB Loader integration active');
            }
        }
    }
    
    /**
     * Called when KB Loader API is ready
     */
    public function on_kb_ready($api) {
        $this->kb_api = $api;
        $this->is_available = $api->is_ready();
    }
    
    /**
     * Check if KB Loader is available and configured
     */
    public function is_available() {
        return $this->is_available && $this->kb_api !== null;
    }
    
    /**
     * Search the knowledgebase for relevant context
     * 
     * @param string $query User's query
     * @param array $options Search options
     * @return array|WP_Error Search results or error
     */
    public function search($query, $options = array()) {
        if (!$this->is_available()) {
            return new WP_Error('kb_not_available', 'Knowledgebase not available');
        }
        
        // Default options
        $defaults = array(
            'top_k' => get_option('gd_chatbot_kb_max_results', 10),
            'min_score' => get_option('gd_chatbot_kb_min_score', 0.35),
            'include_text' => true,
            'include_metadata' => true,
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Perform search
        $results = $this->kb_api->search($query, $options);
        
        if (is_wp_error($results)) {
            error_log('GD Chatbot: KB search error - ' . $results->get_error_message());
            return $results;
        }
        
        return $results;
    }
    
    /**
     * Get formatted context for Claude
     * 
     * @param string $query User's query
     * @param int $max_results Maximum number of results
     * @return string Formatted context
     */
    public function get_context($query, $max_results = null) {
        if (!$this->is_available()) {
            return '';
        }
        
        if ($max_results === null) {
            $max_results = get_option('gd_chatbot_kb_max_results', 10);
        }
        
        $context = $this->kb_api->get_context_for_query($query, $max_results);
        
        if (empty($context)) {
            return '';
        }
        
        // Wrap context with clear delimiters
        return "## KNOWLEDGEBASE CONTEXT\n\n" . 
               "The following information is from your uploaded knowledgebase documents. " .
               "Use this information to provide accurate, detailed answers.\n\n" .
               $context;
    }
    
    /**
     * Get best matching chunks
     * 
     * @param string $query User's query
     * @param int $count Number of matches
     * @return array Array of matches
     */
    public function get_best_matches($query, $count = 10) {
        if (!$this->is_available()) {
            return array();
        }
        
        $matches = $this->kb_api->get_best_matches($query, $count);
        
        if (is_wp_error($matches)) {
            return array();
        }
        
        return $matches;
    }
    
    /**
     * Convert search results to context format
     * 
     * @param array $results Search results
     * @return string Formatted context
     */
    public function results_to_context($results) {
        if (empty($results['matches'])) {
            return '';
        }
        
        $context_parts = array();
        
        foreach ($results['matches'] as $match) {
            if (!isset($match['text'])) {
                continue;
            }
            
            $source = isset($match['metadata']['source_file']) ? $match['metadata']['source_file'] : 'Unknown';
            $score = isset($match['score']) ? round($match['score'] * 100, 1) : 0;
            
            $context_parts[] = sprintf(
                "### Source: %s (Relevance: %s%%)\n\n%s",
                $source,
                $score,
                $match['text']
            );
        }
        
        if (empty($context_parts)) {
            return '';
        }
        
        return "## KNOWLEDGEBASE CONTEXT\n\n" . 
               "The following information is from your uploaded knowledgebase documents:\n\n" .
               implode("\n\n---\n\n", $context_parts);
    }
    
    /**
     * Extract sources from search results
     * 
     * @param array $results Search results
     * @return array Array of sources
     */
    public function extract_sources($results) {
        if (empty($results['matches'])) {
            return array();
        }
        
        $sources = array();
        
        foreach ($results['matches'] as $match) {
            $source_file = isset($match['metadata']['source_file']) ? $match['metadata']['source_file'] : 'Unknown';
            $score = isset($match['score']) ? round($match['score'] * 100, 1) : 0;
            
            $sources[] = array(
                'title' => $source_file,
                'type' => 'knowledgebase',
                'score' => $score,
                'url' => admin_url('admin.php?page=gd-kb-loader-manage'),
            );
        }
        
        return $sources;
    }
    
    /**
     * Get knowledgebase statistics
     * 
     * @return array|null Statistics or null if not available
     */
    public function get_stats() {
        if (!$this->is_available()) {
            return null;
        }
        
        return $this->kb_api->get_stats();
    }
    
    /**
     * Check if query should use knowledgebase
     * 
     * @param string $query User's query
     * @return bool Whether to use KB
     */
    public function should_use_kb($query) {
        if (!$this->is_available()) {
            return false;
        }
        
        // Check if KB is enabled in settings
        $kb_enabled = get_option('gd_chatbot_kb_enabled', true);
        
        if (!$kb_enabled) {
            return false;
        }
        
        // Always use KB if available (it's filtered by relevance score anyway)
        return true;
    }
}
