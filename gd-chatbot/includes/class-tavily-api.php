<?php
/**
 * Tavily API Integration Class
 * 
 * Handles web search functionality via Tavily API
 * 
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Tavily_API {
    
    /**
     * Tavily API endpoint
     */
    const API_ENDPOINT = 'https://api.tavily.com/search';
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Search depth (basic or advanced)
     */
    private $search_depth;
    
    /**
     * Maximum results to return
     */
    private $max_results;
    
    /**
     * Domains to include in search
     */
    private $include_domains;
    
    /**
     * Domains to exclude from search
     */
    private $exclude_domains;
    
    /**
     * Constructor
     */
    public function __construct($api_key = null) {
        $this->api_key = $api_key ?: get_option('gd_chatbot_v2_tavily_api_key', '');
        $this->search_depth = get_option('gd_chatbot_v2_tavily_search_depth', 'basic');
        $this->max_results = (int) get_option('gd_chatbot_v2_tavily_max_results', 5);
        
        // Parse domain lists
        $include = get_option('gd_chatbot_v2_tavily_include_domains', '');
        $exclude = get_option('gd_chatbot_v2_tavily_exclude_domains', '');
        
        $this->include_domains = $this->parse_domain_list($include);
        $this->exclude_domains = $this->parse_domain_list($exclude);
    }
    
    /**
     * Parse comma-separated domain list
     */
    private function parse_domain_list($domains_string) {
        if (empty($domains_string)) {
            return array();
        }
        
        $domains = explode(',', $domains_string);
        $domains = array_map('trim', $domains);
        $domains = array_filter($domains);
        
        return array_values($domains);
    }
    
    /**
     * Check if Tavily is enabled and configured
     */
    public function is_enabled() {
        return get_option('gd_chatbot_v2_tavily_enabled', false) && !empty($this->api_key);
    }
    
    /**
     * Perform a web search
     * 
     * @param string $query Search query
     * @param array $options Additional options
     * @return array|WP_Error Search results or error
     */
    public function search($query, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Tavily API key is not configured.');
        }
        
        // Build request body
        $body = array(
            'api_key' => $this->api_key,
            'query' => $query,
            'search_depth' => isset($options['search_depth']) ? $options['search_depth'] : $this->search_depth,
            'max_results' => isset($options['max_results']) ? $options['max_results'] : $this->max_results,
            'include_answer' => true,
            'include_raw_content' => false,
        );
        
        // Add domain filters if set
        $include_domains = isset($options['include_domains']) ? $options['include_domains'] : $this->include_domains;
        $exclude_domains = isset($options['exclude_domains']) ? $options['exclude_domains'] : $this->exclude_domains;
        
        if (!empty($include_domains)) {
            $body['include_domains'] = $include_domains;
        }
        
        if (!empty($exclude_domains)) {
            $body['exclude_domains'] = $exclude_domains;
        }
        
        // Make API request
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body)
        ));
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        // Handle API errors
        if ($response_code !== 200) {
            $error_message = isset($data['detail']) ? $data['detail'] : 'Unknown API error';
            
            switch ($response_code) {
                case 401:
                    return new WP_Error('auth_error', 'Invalid Tavily API key.');
                case 429:
                    return new WP_Error('rate_limit', 'Tavily rate limit exceeded.');
                default:
                    return new WP_Error('api_error', $error_message);
            }
        }
        
        return $this->format_results($data);
    }
    
    /**
     * Format search results for use in chat context
     * 
     * @param array $data Raw API response
     * @return array Formatted results
     */
    private function format_results($data) {
        $formatted = array(
            'answer' => isset($data['answer']) ? $data['answer'] : '',
            'results' => array(),
            'query' => $data['query'] ?? ''
        );
        
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $result) {
                $formatted['results'][] = array(
                    'title' => $result['title'] ?? '',
                    'url' => $result['url'] ?? '',
                    'content' => $result['content'] ?? '',
                    'score' => $result['score'] ?? 0
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Convert search results to context string for Claude
     * 
     * @param array $results Search results
     * @return string Formatted context
     */
    public function results_to_context($results) {
        if (empty($results['results'])) {
            return '';
        }
        
        $context = "### Web Search Results\n\n";
        
        // Include Tavily's direct answer if available
        if (!empty($results['answer'])) {
            $context .= "**Summary:** " . $results['answer'] . "\n\n";
        }
        
        $context .= "**Sources:**\n\n";
        
        foreach ($results['results'] as $index => $result) {
            $num = $index + 1;
            $context .= "**{$num}. {$result['title']}**\n";
            $context .= "Source: {$result['url']}\n";
            $context .= "{$result['content']}\n\n";
        }
        
        return $context;
    }
    
    /**
     * Determine if a query would benefit from web search
     * 
     * @param string $query User query
     * @return bool
     */
    public function should_search($query) {
        // Keywords that suggest need for current/web information
        $search_triggers = array(
            'latest',
            'recent',
            'current',
            'today',
            'news',
            'update',
            '2024',
            '2025',
            'what is',
            'who is',
            'where is',
            'when did',
            'how to',
            'search for',
            'find',
            'look up'
        );
        
        $query_lower = strtolower($query);
        
        foreach ($search_triggers as $trigger) {
            if (strpos($query_lower, $trigger) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API key is required.');
        }
        
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'api_key' => $this->api_key,
                'query' => 'test',
                'max_results' => 1
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return true;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $error_message = isset($body['detail']) ? $body['detail'] : 'Connection failed';
        
        return new WP_Error('connection_failed', $error_message);
    }
    
    /**
     * Get search depth options
     */
    public static function get_search_depth_options() {
        return array(
            'basic' => 'Basic (Faster, less comprehensive)',
            'advanced' => 'Advanced (Slower, more comprehensive)'
        );
    }
}
