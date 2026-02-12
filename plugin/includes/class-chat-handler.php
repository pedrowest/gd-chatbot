<?php
/**
 * Chat Handler Class
 * 
 * Orchestrates chat interactions between user, Claude, Tavily, and Pinecone
 * 
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Chat_Handler {
    
    /**
     * Claude API instance
     */
    private $claude;
    
    /**
     * Tavily API instance
     */
    private $tavily;
    
    /**
     * Pinecone API instance
     */
    private $pinecone;
    
    /**
     * Setlist Search instance
     */
    private $setlist_search;
    
    /**
     * KB Integration instance
     */
    private $kb_integration;
    
    /**
     * AI Power Integration instance
     */
    private $aipower_integration;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->claude = new GD_Claude_API();
        $this->tavily = new GD_Tavily_API();
        $this->pinecone = new GD_Pinecone_API();
        $this->setlist_search = new GD_Setlist_Search();
        $this->kb_integration = new GD_KB_Integration();
        $this->aipower_integration = new GD_AIPower_Integration();
    }
    
    /**
     * Process an incoming chat message with streaming
     * 
     * @param string $message User's message
     * @param array $conversation_history Previous messages
     * @param string $session_id Session identifier
     * @param callable $callback Function to call for each chunk
     * @return void
     */
    public function process_message_stream($message, $conversation_history = array(), $session_id = '', $callback = null) {
        // Check for token optimization
        $optimization_enabled = get_option('gd_chatbot_v2_token_optimization_enabled', false);
        if (defined('GD_CHATBOT_DISABLE_OPTIMIZATION') && GD_CHATBOT_DISABLE_OPTIMIZATION) {
            $optimization_enabled = false;
        }

        if ($optimization_enabled) {
            $this->process_message_stream_optimized($message, $conversation_history, $session_id, $callback);
            return;
        }

        $context_parts = array();
        $sources = array();
        $full_response = '';
        
        // 0. Check for setlist queries and add relevant show data
        if ($this->setlist_search->is_setlist_query($message)) {
            $setlist_data = $this->setlist_search->search($message);
            
            if (!empty($setlist_data)) {
                $context_parts[] = "## Setlist Database Results\n\n" . $setlist_data;
                $sources['setlist_database'] = array(
                    array(
                        'title' => 'Grateful Dead Setlist Database (1965-1995)',
                        'url' => '',
                        'score' => 100
                    )
                );
            }
        }
        
        // 1. Query Pinecone for relevant knowledge base content
        if ($this->pinecone->is_enabled()) {
            $pinecone_results = $this->pinecone->query($message);
            
            if (!is_wp_error($pinecone_results) && !empty($pinecone_results['matches'])) {
                $kb_context = $this->pinecone->results_to_context($pinecone_results);
                if (!empty($kb_context)) {
                    $context_parts[] = $kb_context;
                    $sources['knowledge_base'] = $this->extract_kb_sources($pinecone_results);
                }
            }
        }
        
        // 2. Perform web search via Tavily if enabled (ALWAYS search when enabled)
        if ($this->tavily->is_enabled()) {
            $tavily_results = $this->tavily->search($message);
            
            if (!is_wp_error($tavily_results) && !empty($tavily_results['results'])) {
                $web_context = $this->tavily->results_to_context($tavily_results);
                if (!empty($web_context)) {
                    $context_parts[] = $web_context;
                    $sources['web_search'] = $this->extract_web_sources($tavily_results);
                }
            }
        }
        
        // 3. Combine context from setlists, Pinecone, and Tavily
        $additional_context = '';
        if (!empty($context_parts)) {
            $additional_context = implode("\n\n---\n\n", $context_parts);
        }
        
        // Send sources first if available
        if (!empty($sources) && $callback) {
            call_user_func($callback, array(
                'type' => 'sources',
                'sources' => $sources
            ));
        }
        
        // 4. Send to Claude with streaming callback
        $stream_callback = function($data) use ($callback, &$full_response) {
            if ($data['type'] === 'content') {
                $full_response = $data['full_text'];
            }
            
            // Enrich response with song links when complete
            if ($data['type'] === 'done' && !empty($data['full_text'])) {
                $enricher = new GD_Response_Enricher();
                $data['full_text'] = $enricher->enrich($data['full_text']);
                $full_response = $data['full_text'];
            }
            
            if ($callback) {
                call_user_func($callback, $data);
            }
        };
        
        $result = $this->claude->send_message_stream($message, $conversation_history, $additional_context, $stream_callback);
        
        if (is_wp_error($result)) {
            if ($callback) {
                call_user_func($callback, array(
                    'type' => 'error',
                    'error' => $result->get_error_message()
                ));
            }
            return;
        }
        
        // 5. Log conversation after streaming completes
        if (!empty($full_response)) {
            $this->log_conversation($session_id, $message, $full_response, $sources);
        }
    }
    
    /**
     * Process an incoming chat message
     * 
     * @param string $message User's message
     * @param array $conversation_history Previous messages
     * @param string $session_id Session identifier
     * @return array|WP_Error Response data or error
     */
    public function process_message($message, $conversation_history = array(), $session_id = '') {
        // Check for token optimization
        $optimization_enabled = get_option('gd_chatbot_v2_token_optimization_enabled', false);
        if (defined('GD_CHATBOT_DISABLE_OPTIMIZATION') && GD_CHATBOT_DISABLE_OPTIMIZATION) {
            $optimization_enabled = false;
        }

        if ($optimization_enabled) {
            return $this->process_message_optimized($message, $conversation_history, $session_id);
        }

        $context_parts = array();
        $sources = array();
        
        // 0. Check for setlist queries and add relevant show data
        if ($this->setlist_search->is_setlist_query($message)) {
            $setlist_data = $this->setlist_search->search($message);
            
            if (!empty($setlist_data)) {
                $context_parts[] = "## Setlist Database Results\n\n" . $setlist_data;
                $sources['setlist_database'] = array(
                    array(
                        'title' => 'Grateful Dead Setlist Database (1965-1995)',
                        'url' => '',
                        'score' => 100
                    )
                );
            }
        }
        
        // 0.5. Query GD Knowledgebase Loader if available
        if ($this->kb_integration->is_available() && $this->kb_integration->should_use_kb($message)) {
            $kb_results = $this->kb_integration->search($message);
            
            if (!is_wp_error($kb_results) && !empty($kb_results['matches'])) {
                $kb_context = $this->kb_integration->results_to_context($kb_results);
                if (!empty($kb_context)) {
                    $context_parts[] = $kb_context;
                    $sources['knowledgebase_loader'] = $this->kb_integration->extract_sources($kb_results);
                }
            }
        }
        
        // 0.6. Query AI Power (gpt-ai-content-generator) indexed content if available
        if ($this->aipower_integration->is_available() && $this->aipower_integration->should_use($message)) {
            $aipower_results = $this->aipower_integration->search($message);
            
            if (!is_wp_error($aipower_results) && !empty($aipower_results['matches'])) {
                $aipower_context = $this->aipower_integration->results_to_context($aipower_results);
                if (!empty($aipower_context)) {
                    $context_parts[] = $aipower_context;
                    $sources['aipower_content'] = $this->aipower_integration->extract_sources($aipower_results);
                }
            }
        }
        
        // 1. Query Pinecone for relevant knowledge base content
        if ($this->pinecone->is_enabled()) {
            $pinecone_results = $this->pinecone->query($message);
            
            if (!is_wp_error($pinecone_results) && !empty($pinecone_results['matches'])) {
                $kb_context = $this->pinecone->results_to_context($pinecone_results);
                if (!empty($kb_context)) {
                    $context_parts[] = $kb_context;
                    $sources['knowledge_base'] = $this->extract_kb_sources($pinecone_results);
                }
            }
        }
        
        // 2. Perform web search via Tavily if enabled (ALWAYS search when enabled)
        if ($this->tavily->is_enabled()) {
            $tavily_results = $this->tavily->search($message);
            
            if (!is_wp_error($tavily_results) && !empty($tavily_results['results'])) {
                $web_context = $this->tavily->results_to_context($tavily_results);
                if (!empty($web_context)) {
                    $context_parts[] = $web_context;
                    $sources['web_search'] = $this->extract_web_sources($tavily_results);
                }
            }
        }
        
        // 3. Combine context from setlists, Pinecone, and Tavily
        $additional_context = '';
        if (!empty($context_parts)) {
            $additional_context = implode("\n\n---\n\n", $context_parts);
        }
        
        // 4. Send to Claude (Grateful Dead context is automatically loaded in system prompt)
        $response = $this->claude->send_message($message, $conversation_history, $additional_context);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // 4.5. Enrich response with song links
        $enricher = new GD_Response_Enricher();
        $response['message'] = $enricher->enrich($response['message']);
        
        // 5. Log conversation
        $this->log_conversation($session_id, $message, $response['message'], $sources);
        
        // 6. Build response
        return array(
            'message' => $response['message'],
            'sources' => $sources,
            'usage' => $response['usage'],
            'model' => $response['model']
        );
    }
    
    /**
     * Process streaming message with token optimization.
     *
     * @param string $message User's message
     * @param array $conversation_history Previous messages
     * @param string $session_id Session identifier
     * @param callable $callback Function to call for each chunk
     */
    private function process_message_stream_optimized($message, $conversation_history, $session_id, $callback) {
        $context_builder = new GD_Context_Builder();
        $sources = array();
        $full_response = '';

        // Gather raw results for context builder
        $options = array();

        // Setlist search
        if ($this->setlist_search->is_setlist_query($message)) {
            $setlist_data = $this->setlist_search->search_compact($message);

            if (!empty($setlist_data)) {
                $options['setlist_results'] = $setlist_data;
                $sources['setlist_database'] = array(
                    array(
                        'title' => 'Grateful Dead Setlist Database (1965-1995)',
                        'url' => '',
                        'score' => 100
                    )
                );
            }
        }

        // Pinecone KB
        if ($this->pinecone->is_enabled()) {
            $pinecone_results = $this->pinecone->query($message);

            if (!is_wp_error($pinecone_results) && !empty($pinecone_results['matches'])) {
                $options['pinecone_results'] = $pinecone_results;
                $sources['knowledge_base'] = $this->extract_kb_sources($pinecone_results);
            }
        }

        // Tavily web search
        if ($this->tavily->is_enabled()) {
            $tavily_results = $this->tavily->search($message);

            if (!is_wp_error($tavily_results) && !empty($tavily_results['results'])) {
                $options['tavily_results'] = $tavily_results;
                $sources['web_search'] = $this->extract_web_sources($tavily_results);
            }
        }

        // Build optimized context
        $context_data = $context_builder->build_context($message, $options);

        // Log debug info
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'GD Chatbot [%s]: Intent=%s, Tokens=%d, Sources=%s',
                $session_id,
                $context_data['intent'],
                $context_data['debug_info']['used_tokens'],
                implode(',', $context_data['sources_used'])
            ));
        }

        // Prepare optimized conversation history
        $optimized_history = $this->prepare_conversation_history($conversation_history);

        // Send sources first if available
        if (!empty($sources) && $callback) {
            call_user_func($callback, array(
                'type' => 'sources',
                'sources' => $sources
            ));
        }

        // Stream to Claude
        $stream_callback = function ($data) use ($callback, &$full_response) {
            if ($data['type'] === 'content') {
                $full_response = $data['full_text'];
            }

            if ($callback) {
                call_user_func($callback, $data);
            }
        };

        $result = $this->claude->send_message_stream($message, $optimized_history, $context_data['context'], $stream_callback);

        if (is_wp_error($result)) {
            if ($callback) {
                call_user_func($callback, array(
                    'type' => 'error',
                    'error' => $result->get_error_message()
                ));
            }
            return;
        }

        // Log conversation
        if (!empty($full_response)) {
            $this->log_conversation($session_id, $message, $full_response, $sources);
        }
    }

    /**
     * Process non-streaming message with token optimization.
     *
     * @param string $message User's message
     * @param array $conversation_history Previous messages
     * @param string $session_id Session identifier
     * @return array|WP_Error Response data or error
     */
    private function process_message_optimized($message, $conversation_history, $session_id) {
        $context_builder = new GD_Context_Builder();
        $sources = array();

        // Gather raw results for context builder
        $options = array();

        // Setlist search
        if ($this->setlist_search->is_setlist_query($message)) {
            $setlist_data = $this->setlist_search->search_compact($message);

            if (!empty($setlist_data)) {
                $options['setlist_results'] = $setlist_data;
                $sources['setlist_database'] = array(
                    array(
                        'title' => 'Grateful Dead Setlist Database (1965-1995)',
                        'url' => '',
                        'score' => 100
                    )
                );
            }
        }

        // KB Integration (non-streaming path)
        if ($this->kb_integration->is_available() && $this->kb_integration->should_use_kb($message)) {
            $kb_results = $this->kb_integration->search($message);

            if (!is_wp_error($kb_results) && !empty($kb_results['matches'])) {
                $options['kb_results'] = $kb_results;
                $sources['knowledgebase_loader'] = $this->kb_integration->extract_sources($kb_results);
            }
        }

        // AI Power Integration (non-streaming path)
        if ($this->aipower_integration->is_available() && $this->aipower_integration->should_use($message)) {
            $aipower_results = $this->aipower_integration->search($message);

            if (!is_wp_error($aipower_results) && !empty($aipower_results['matches'])) {
                $options['aipower_results'] = $aipower_results;
                $sources['aipower_content'] = $this->aipower_integration->extract_sources($aipower_results);
            }
        }

        // Pinecone KB
        if ($this->pinecone->is_enabled()) {
            $pinecone_results = $this->pinecone->query($message);

            if (!is_wp_error($pinecone_results) && !empty($pinecone_results['matches'])) {
                $options['pinecone_results'] = $pinecone_results;
                $sources['knowledge_base'] = $this->extract_kb_sources($pinecone_results);
            }
        }

        // Tavily web search
        if ($this->tavily->is_enabled()) {
            $tavily_results = $this->tavily->search($message);

            if (!is_wp_error($tavily_results) && !empty($tavily_results['results'])) {
                $options['tavily_results'] = $tavily_results;
                $sources['web_search'] = $this->extract_web_sources($tavily_results);
            }
        }

        // Build optimized context
        $context_data = $context_builder->build_context($message, $options);

        // Log debug info
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'GD Chatbot [%s]: Intent=%s, Tokens=%d, Sources=%s',
                $session_id,
                $context_data['intent'],
                $context_data['debug_info']['used_tokens'],
                implode(',', $context_data['sources_used'])
            ));
        }

        // Prepare optimized conversation history
        $optimized_history = $this->prepare_conversation_history($conversation_history);

        // Send to Claude
        $response = $this->claude->send_message($message, $optimized_history, $context_data['context']);

        if (is_wp_error($response)) {
            return $response;
        }

        // Log conversation
        $this->log_conversation($session_id, $message, $response['message'], $sources);

        return array(
            'message' => $response['message'],
            'sources' => $sources,
            'usage' => $response['usage'],
            'model' => $response['model']
        );
    }

    /**
     * Prepare conversation history with token budget enforcement.
     * Limits to last 3 exchanges within a 200-token budget.
     *
     * @param array $history Full conversation history
     * @param int $token_budget Maximum tokens for history (default: 200)
     * @return array Truncated conversation history
     */
    private function prepare_conversation_history($history, $token_budget = 200) {
        if (empty($history)) {
            return array();
        }

        // Keep last 3 exchanges (6 messages: 3 user + 3 assistant)
        $recent = array_slice($history, -6);
        $truncated = array();
        $tokens = 0;

        foreach ($recent as $message) {
            $content = isset($message['content']) ? $message['content'] : '';
            $msg_tokens = GD_Token_Estimator::estimate($content);

            if ($tokens + $msg_tokens <= $token_budget) {
                $truncated[] = $message;
                $tokens += $msg_tokens;
            } else {
                // Truncate this message to fit remaining budget
                $remaining = $token_budget - $tokens;
                if ($remaining > 20) {
                    $message['content'] = GD_Token_Estimator::truncate($content, $remaining);
                    $truncated[] = $message;
                }
                break;
            }
        }

        return $truncated;
    }

    /**
     * Extract source information from Pinecone results
     */
    private function extract_kb_sources($results) {
        $sources = array();
        
        foreach ($results['matches'] as $match) {
            $metadata = $match['metadata'];
            $sources[] = array(
                'title' => $metadata['title'] ?? $metadata['name'] ?? 'Knowledge Base Document',
                'url' => $metadata['source'] ?? $metadata['url'] ?? '',
                'score' => round($match['score'] * 100, 1)
            );
        }
        
        return $sources;
    }
    
    /**
     * Extract source information from Tavily results
     */
    private function extract_web_sources($results) {
        $sources = array();
        
        foreach ($results['results'] as $result) {
            $sources[] = array(
                'title' => $result['title'],
                'url' => $result['url'],
                'score' => round($result['score'] * 100, 1)
            );
        }
        
        return $sources;
    }
    
    /**
     * Log conversation to database
     */
    private function log_conversation($session_id, $user_message, $assistant_message, $sources) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id' => get_current_user_id() ?: null,
                'user_message' => $user_message,
                'assistant_message' => $assistant_message,
                'sources' => json_encode($sources),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get conversation history for a session
     * 
     * @param string $session_id Session identifier
     * @param int $limit Number of messages to retrieve
     * @return array Conversation history
     */
    public function get_conversation_history($session_id, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_message, assistant_message FROM $table_name 
                WHERE session_id = %s 
                ORDER BY created_at DESC 
                LIMIT %d",
                $session_id,
                $limit
            ),
            ARRAY_A
        );
        
        if (empty($results)) {
            return array();
        }
        
        // Reverse to get chronological order and format for Claude
        $results = array_reverse($results);
        $history = array();
        
        foreach ($results as $row) {
            $history[] = array(
                'role' => 'user',
                'content' => $row['user_message']
            );
            $history[] = array(
                'role' => 'assistant',
                'content' => $row['assistant_message']
            );
        }
        
        return $history;
    }
    
    /**
     * Clear conversation history for a session
     * 
     * @param string $session_id Session identifier
     * @return bool Success
     */
    public function clear_conversation_history($session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
        
        return $wpdb->delete(
            $table_name,
            array('session_id' => $session_id),
            array('%s')
        ) !== false;
    }
    
    /**
     * Get analytics data
     * 
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Analytics data
     */
    public function get_analytics($start_date = null, $end_date = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
        
        $where = '1=1';
        $params = array();
        
        if ($start_date) {
            $where .= ' AND created_at >= %s';
            $params[] = $start_date . ' 00:00:00';
        }
        
        if ($end_date) {
            $where .= ' AND created_at <= %s';
            $params[] = $end_date . ' 23:59:59';
        }
        
        // Total conversations
        $total_query = "SELECT COUNT(*) FROM $table_name WHERE $where";
        $total = $wpdb->get_var($params ? $wpdb->prepare($total_query, $params) : $total_query);
        
        // Unique sessions
        $sessions_query = "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE $where";
        $sessions = $wpdb->get_var($params ? $wpdb->prepare($sessions_query, $params) : $sessions_query);
        
        // Logged in users
        $users_query = "SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE user_id IS NOT NULL AND $where";
        $users = $wpdb->get_var($params ? $wpdb->prepare($users_query, $params) : $users_query);
        
        // Daily breakdown
        $daily_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                        FROM $table_name 
                        WHERE $where 
                        GROUP BY DATE(created_at) 
                        ORDER BY date DESC 
                        LIMIT 30";
        $daily = $wpdb->get_results($params ? $wpdb->prepare($daily_query, $params) : $daily_query, ARRAY_A);
        
        return array(
            'total_messages' => (int) $total,
            'unique_sessions' => (int) $sessions,
            'logged_in_users' => (int) $users,
            'daily_breakdown' => $daily
        );
    }
}
