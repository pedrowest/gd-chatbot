<?php
/**
 * Pinecone API Integration Class
 * 
 * Handles vector database operations via Pinecone API
 * 
 * @package GD_Claude_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Pinecone_API {
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Pinecone host (index endpoint)
     */
    private $host;
    
    /**
     * Index name
     */
    private $index_name;
    
    /**
     * Namespace
     */
    private $namespace;
    
    /**
     * Number of results to return
     */
    private $top_k;
    
    /**
     * Embedding API key (for generating embeddings)
     */
    private $embedding_api_key;
    
    /**
     * Embedding model to use
     */
    private $embedding_model;
    
    /**
     * Constructor
     */
    public function __construct($api_key = null, $host = null) {
        $this->api_key = $api_key ?: get_option('gd_chatbot_pinecone_api_key', '');
        $this->host = $host ?: get_option('gd_chatbot_pinecone_host', '');
        $this->index_name = get_option('gd_chatbot_pinecone_index_name', '');
        $this->namespace = get_option('gd_chatbot_pinecone_namespace', '');
        $this->top_k = (int) get_option('gd_chatbot_pinecone_top_k', 5);
        
        // Embedding configuration (using OpenAI as default embedding provider)
        $this->embedding_api_key = get_option('gd_chatbot_embedding_api_key', '');
        $this->embedding_model = get_option('gd_chatbot_embedding_model', 'text-embedding-3-small');
    }
    
    /**
     * Check if Pinecone is enabled and configured
     */
    public function is_enabled() {
        return get_option('gd_chatbot_pinecone_enabled', false) 
            && !empty($this->api_key) 
            && !empty($this->host);
    }
    
    /**
     * Query the vector database for similar content
     * 
     * @param string $query Query text
     * @param array $filter Optional metadata filters
     * @param int $top_k Number of results (optional)
     * @return array|WP_Error Query results or error
     */
    public function query($query, $filter = array(), $top_k = null) {
        if (empty($this->api_key) || empty($this->host)) {
            return new WP_Error('not_configured', 'Pinecone is not properly configured.');
        }
        
        // Generate embedding for the query
        $embedding = $this->generate_embedding($query);
        
        if (is_wp_error($embedding)) {
            return $embedding;
        }
        
        // Build query request
        $body = array(
            'vector' => $embedding,
            'topK' => $top_k ?: $this->top_k,
            'includeMetadata' => true,
            'includeValues' => false
        );
        
        // Add namespace if set
        if (!empty($this->namespace)) {
            $body['namespace'] = $this->namespace;
        }
        
        // Add filter if provided
        if (!empty($filter)) {
            $body['filter'] = $filter;
        }
        
        // Make API request
        $url = rtrim($this->host, '/') . '/query';
        
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Api-Key' => $this->api_key
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
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown Pinecone API error';
            return new WP_Error('api_error', $error_message);
        }
        
        return $this->format_results($data);
    }
    
    /**
     * Generate embedding for text using OpenAI
     * 
     * @param string $text Text to embed
     * @return array|WP_Error Embedding vector or error
     */
    public function generate_embedding($text) {
        if (empty($this->embedding_api_key)) {
            return new WP_Error('no_embedding_key', 'Embedding API key is not configured.');
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/embeddings', array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->embedding_api_key
            ),
            'body' => json_encode(array(
                'model' => $this->embedding_model,
                'input' => $text
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Embedding generation failed';
            return new WP_Error('embedding_error', $error_message);
        }
        
        if (!isset($data['data'][0]['embedding'])) {
            return new WP_Error('parse_error', 'Unable to parse embedding response');
        }
        
        return $data['data'][0]['embedding'];
    }
    
    /**
     * Format query results
     * 
     * @param array $data Raw API response
     * @return array Formatted results
     */
    private function format_results($data) {
        $formatted = array(
            'matches' => array()
        );
        
        if (isset($data['matches']) && is_array($data['matches'])) {
            foreach ($data['matches'] as $match) {
                $formatted['matches'][] = array(
                    'id' => $match['id'] ?? '',
                    'score' => $match['score'] ?? 0,
                    'metadata' => $match['metadata'] ?? array()
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Convert query results to context string for Claude
     * 
     * @param array $results Query results
     * @return string Formatted context
     */
    public function results_to_context($results) {
        if (empty($results['matches'])) {
            return '';
        }
        
        $context = "### Knowledge Base Results\n\n";
        $context .= "The following relevant information was found in the knowledge base:\n\n";
        
        foreach ($results['matches'] as $index => $match) {
            $num = $index + 1;
            $metadata = $match['metadata'];
            
            // Extract common metadata fields
            $title = $metadata['title'] ?? $metadata['name'] ?? "Document {$num}";
            $content = $metadata['text'] ?? $metadata['content'] ?? $metadata['chunk'] ?? '';
            $source = $metadata['source'] ?? $metadata['url'] ?? '';
            $score = round($match['score'] * 100, 1);
            
            $context .= "**{$num}. {$title}** (Relevance: {$score}%)\n";
            
            if (!empty($source)) {
                $context .= "Source: {$source}\n";
            }
            
            if (!empty($content)) {
                // Truncate very long content
                $content = strlen($content) > 1000 ? substr($content, 0, 1000) . '...' : $content;
                $context .= "{$content}\n";
            }
            
            $context .= "\n";
        }
        
        return $context;
    }
    
    /**
     * Upsert vectors to the index
     * 
     * @param array $vectors Array of vectors with id, values, and metadata
     * @return bool|WP_Error Success or error
     */
    public function upsert($vectors) {
        if (empty($this->api_key) || empty($this->host)) {
            return new WP_Error('not_configured', 'Pinecone is not properly configured.');
        }
        
        $body = array(
            'vectors' => $vectors
        );
        
        if (!empty($this->namespace)) {
            $body['namespace'] = $this->namespace;
        }
        
        $url = rtrim($this->host, '/') . '/vectors/upsert';
        
        $response = wp_remote_post($url, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Api-Key' => $this->api_key
            ),
            'body' => json_encode($body)
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($data['message']) ? $data['message'] : 'Upsert failed';
            return new WP_Error('upsert_error', $error_message);
        }
        
        return true;
    }
    
    /**
     * Delete vectors by ID
     * 
     * @param array $ids Vector IDs to delete
     * @return bool|WP_Error Success or error
     */
    public function delete($ids) {
        if (empty($this->api_key) || empty($this->host)) {
            return new WP_Error('not_configured', 'Pinecone is not properly configured.');
        }
        
        $body = array(
            'ids' => $ids
        );
        
        if (!empty($this->namespace)) {
            $body['namespace'] = $this->namespace;
        }
        
        $url = rtrim($this->host, '/') . '/vectors/delete';
        
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Api-Key' => $this->api_key
            ),
            'body' => json_encode($body)
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($data['message']) ? $data['message'] : 'Delete failed';
            return new WP_Error('delete_error', $error_message);
        }
        
        return true;
    }
    
    /**
     * Get index statistics
     * 
     * @return array|WP_Error Stats or error
     */
    public function describe_index_stats() {
        if (empty($this->api_key) || empty($this->host)) {
            return new WP_Error('not_configured', 'Pinecone is not properly configured.');
        }
        
        $url = rtrim($this->host, '/') . '/describe_index_stats';
        
        $response = wp_remote_post($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Api-Key' => $this->api_key
            ),
            'body' => json_encode(array())
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200) {
            $error_message = isset($data['message']) ? $data['message'] : 'Failed to get index stats';
            return new WP_Error('stats_error', $error_message);
        }
        
        return $data;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API key is required.');
        }
        
        if (empty($this->host)) {
            return new WP_Error('no_host', 'Pinecone host URL is required.');
        }
        
        // Try to get index stats as a connection test
        $result = $this->describe_index_stats();
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Get available embedding models
     */
    public static function get_embedding_models() {
        return array(
            'text-embedding-3-small' => 'OpenAI text-embedding-3-small (1536 dimensions)',
            'text-embedding-3-large' => 'OpenAI text-embedding-3-large (3072 dimensions)',
            'text-embedding-ada-002' => 'OpenAI text-embedding-ada-002 (1536 dimensions)'
        );
    }
}
