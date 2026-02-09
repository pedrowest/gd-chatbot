<?php
/**
 * AI Power (gpt-ai-content-generator) Integration
 * 
 * Integrates with the AI Power plugin's Pinecone embeddings
 * to provide context from indexed WordPress posts to the chatbot
 * 
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_AIPower_Integration {
    
    /**
     * Pinecone API instance
     */
    private $pinecone_api = null;
    
    /**
     * Whether AI Power is available
     */
    private $is_available = false;
    
    /**
     * AI Power's Pinecone index name
     */
    private $index_name = '';
    
    /**
     * AI Power's Pinecone namespace
     */
    private $namespace = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if AI Power plugin is available
        add_action('plugins_loaded', array($this, 'check_availability'), 20);
    }
    
    /**
     * Check if AI Power is available and configured
     */
    public function check_availability() {
        // Check if AI Power plugin class exists
        if (class_exists('WPAICG\\WP_AI_Content_Generator')) {
            // Get Pinecone configuration from AI Power
            $this->load_aipower_pinecone_config();
            
            if ($this->is_available) {
                error_log('GD Chatbot: AI Power Pinecone integration active');
            }
        }
    }
    
    /**
     * Load Pinecone configuration from AI Power
     */
    private function load_aipower_pinecone_config() {
        // AI Power stores Pinecone config in options
        // Check if AIPKit_Providers class is available
        if (class_exists('WPAICG\\AIPKit_Providers')) {
            $pinecone_data = \WPAICG\AIPKit_Providers::get_provider_data('Pinecone');
            
            if (!empty($pinecone_data['api_key'])) {
                // Get the default/first Pinecone index name
                // AI Power may store this in different ways, check options
                $this->index_name = get_option('wpaicg_pinecone_index', '');
                $this->namespace = get_option('wpaicg_pinecone_namespace', '');
                
                // Initialize our Pinecone API class with AI Power's credentials
                $pinecone_host = get_option('wpaicg_pinecone_host', '');
                
                if (!empty($pinecone_host)) {
                    $this->pinecone_api = new GD_Pinecone_API($pinecone_data['api_key'], $pinecone_host);
                    $this->is_available = true;
                }
            }
        }
    }
    
    /**
     * Check if AI Power integration is available
     */
    public function is_available() {
        return $this->is_available && $this->pinecone_api !== null;
    }
    
    /**
     * Search for relevant WordPress posts using Pinecone
     * 
     * @param string $query User's query
     * @param array $options Search options
     * @return array|WP_Error Search results or error
     */
    public function search($query, $options = array()) {
        if (!$this->is_available()) {
            return new WP_Error('aipower_not_available', 'AI Power Pinecone integration not available');
        }
        
        // Default options
        $defaults = array(
            'top_k' => get_option('gd_chatbot_v2_kb_max_results', 10),
            'min_score' => get_option('gd_chatbot_v2_kb_min_score', 0.35),
            'post_types' => array('post', 'page'), // Filter by post types
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Build metadata filter for AI Power's structure
        // AI Power uses two source types:
        // 1. 'wordpress_post' for indexed posts/pages
        // 2. 'chat_file_upload' for uploaded files
        $filter = array(
            'source' => array('$in' => array('wordpress_post', 'chat_file_upload'))
        );
        
        // Add post type filter if specified (only applies to wordpress_post sources)
        if (!empty($options['post_types'])) {
            $filter['type'] = array('$in' => $options['post_types']);
        }
        
        // Perform search via Pinecone
        $results = $this->pinecone_api->query($query, $filter, $options['top_k']);
        
        if (is_wp_error($results)) {
            error_log('GD Chatbot: AI Power search error - ' . $results->get_error_message());
            return $results;
        }
        
        // Filter by minimum score
        if (!empty($results['matches']) && $options['min_score'] > 0) {
            $results['matches'] = array_filter($results['matches'], function($match) use ($options) {
                return isset($match['score']) && $match['score'] >= $options['min_score'];
            });
        }
        
        return $results;
    }
    
    /**
     * Get formatted context for Claude from AI Power indexed posts
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
            $max_results = get_option('gd_chatbot_v2_kb_max_results', 10);
        }
        
        // Search for relevant posts
        $results = $this->search($query, array('top_k' => $max_results));
        
        if (is_wp_error($results) || empty($results['matches'])) {
            return '';
        }
        
        // Convert results to context
        return $this->results_to_context($results);
    }
    
    /**
     * Get best matching posts
     * 
     * @param string $query User's query
     * @param int $count Number of matches
     * @return array Array of matches
     */
    public function get_best_matches($query, $count = 10) {
        if (!$this->is_available()) {
            return array();
        }
        
        $results = $this->search($query, array('top_k' => $count));
        
        if (is_wp_error($results)) {
            return array();
        }
        
        return $results['matches'] ?? array();
    }
    
    /**
     * Convert search results to context format for Claude
     * 
     * @param array $results Search results from Pinecone
     * @return string Formatted context
     */
    public function results_to_context($results) {
        if (empty($results['matches'])) {
            return '';
        }
        
        $context_parts = array();
        
        foreach ($results['matches'] as $match) {
            $metadata = $match['metadata'] ?? array();
            
            if (empty($metadata)) {
                continue;
            }
            
            $source = $metadata['source'] ?? '';
            $score = isset($match['score']) ? round($match['score'] * 100, 1) : 0;
            
            // Handle WordPress posts/pages
            if ($source === 'wordpress_post') {
                $post_id = isset($metadata['post_id']) ? (int)$metadata['post_id'] : 0;
                $title = $metadata['title'] ?? 'Untitled';
                $post_type = $metadata['type'] ?? 'post';
                $url = $metadata['url'] ?? '';
                
                // Get the actual post content
                if ($post_id > 0) {
                    $post = get_post($post_id);
                    if ($post) {
                        $content = wp_strip_all_tags($post->post_content);
                        // Limit content length
                        $content = strlen($content) > 2000 ? substr($content, 0, 2000) . '...' : $content;
                        
                        $context_parts[] = sprintf(
                            "### %s (Type: %s, Relevance: %s%%)\nURL: %s\n\n%s",
                            $title,
                            $post_type,
                            $score,
                            $url,
                            $content
                        );
                    }
                }
            }
            // Handle uploaded files
            elseif ($source === 'chat_file_upload') {
                $filename = $metadata['original_filename'] ?? 'Uploaded File';
                $timestamp = isset($metadata['timestamp']) ? date('Y-m-d H:i:s', $metadata['timestamp']) : 'Unknown';
                
                // For uploaded files, we need to query Pinecone again to get the actual content
                // since it's stored in the vector, not as a WordPress post
                // For now, we'll indicate the file is available
                $context_parts[] = sprintf(
                    "### %s (Uploaded File, Relevance: %s%%)\nUploaded: %s\n\n[Content from uploaded file - matched your query with %s%% relevance]",
                    $filename,
                    $score,
                    $timestamp,
                    $score
                );
            }
        }
        
        if (empty($context_parts)) {
            return '';
        }
        
        return "## WORDPRESS & UPLOADED CONTENT CONTEXT\n\n" . 
               "The following information is from WordPress posts/pages and uploaded files indexed with AI Power:\n\n" .
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
            $metadata = $match['metadata'] ?? array();
            $score = isset($match['score']) ? round($match['score'] * 100, 1) : 0;
            $source_type = $metadata['source'] ?? 'unknown';
            
            if ($source_type === 'wordpress_post') {
                $sources[] = array(
                    'title' => $metadata['title'] ?? 'Untitled',
                    'type' => 'wordpress_post',
                    'post_type' => $metadata['type'] ?? 'post',
                    'score' => $score,
                    'url' => $metadata['url'] ?? '',
                    'post_id' => $metadata['post_id'] ?? 0,
                );
            } elseif ($source_type === 'chat_file_upload') {
                $sources[] = array(
                    'title' => $metadata['original_filename'] ?? 'Uploaded File',
                    'type' => 'uploaded_file',
                    'post_type' => 'file',
                    'score' => $score,
                    'url' => '',
                    'uploaded' => isset($metadata['timestamp']) ? date('Y-m-d H:i:s', $metadata['timestamp']) : 'Unknown',
                );
            }
        }
        
        return $sources;
    }
    
    /**
     * Get statistics
     * 
     * @return array|null Statistics or null if not available
     */
    public function get_stats() {
        if (!$this->is_available()) {
            return null;
        }
        
        // Get Pinecone index stats
        $stats = $this->pinecone_api->describe_index_stats();
        
        if (is_wp_error($stats)) {
            return null;
        }
        
        // Count how many WordPress posts are indexed in AI Power
        $indexed_posts = array();
        $all_posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($all_posts as $post_id) {
            if (get_post_meta($post_id, '_aipkit_indexed_to_vs_' . sanitize_key($this->index_name), true)) {
                $indexed_posts[] = $post_id;
            }
        }
        
        return array(
            'total_vectors' => $stats['totalVectorCount'] ?? 0,
            'indexed_posts' => count($indexed_posts),
            'index_name' => $this->index_name,
            'namespace' => $this->namespace,
        );
    }
    
    /**
     * Check if query should use AI Power indexed content
     * 
     * @param string $query User's query
     * @return bool Whether to use AI Power content
     */
    public function should_use($query) {
        if (!$this->is_available()) {
            return false;
        }
        
        // Check if integration is enabled in settings
        $enabled = get_option('gd_chatbot_v2_aipower_enabled', true);
        
        if (!$enabled) {
            return false;
        }
        
        // Always use if available (filtered by relevance score anyway)
        return true;
    }
}
