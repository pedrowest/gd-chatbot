<?php
/**
 * Plugin Name: GD Chatbot v2
 * Plugin URI: https://it-influentials.com
 * Description: AI-powered chatbot using Anthropic's Claude with Tavily web search and Pinecone vector database support.
 * Version: 2.2.0
 * Author: IT Influentials
 * Author URI: https://it-influentials.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gd-chatbot
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GD_CHATBOT_VERSION', '2.2.0');
define('GD_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GD_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GD_CHATBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class GD_Chatbot {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Token management classes (load before chat handler)
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-token-estimator.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-context-cache.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-token-budget-manager.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-query-optimizer.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-context-builder.php';

        // Core classes
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-claude-api.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-tavily-api.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-pinecone-api.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-setlist-search.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-kb-integration.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-aipower-integration.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-chat-handler.php';
        
        // Music streaming classes (hybrid approach)
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-streaming-database.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-archive-api.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-archive-sync.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-song-detector.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-response-enricher.php';
        
        // Streaming services integration (Phase 4)
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-streaming-credentials.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-oauth-base.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-spotify-oauth.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-apple-music-oauth.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-youtube-music-oauth.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-amazon-music-oauth.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/oauth/class-tidal-oauth.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-streaming-service-manager.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-user-profile-integration.php';
        
        // Admin
        if (is_admin()) {
            require_once GD_CHATBOT_PLUGIN_DIR . 'admin/class-admin-settings.php';
        }
        
        // Public
        require_once GD_CHATBOT_PLUGIN_DIR . 'public/class-chatbot-public.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize components
        add_action('plugins_loaded', array($this, 'init_components'));
        
        // AJAX handlers
        add_action('wp_ajax_gd_chatbot_send_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_nopriv_gd_chatbot_send_message', array($this, 'handle_chat_message'));
        
        // Streaming endpoint
        add_action('wp_ajax_gd_chatbot_stream_message', array($this, 'handle_stream_message'));
        add_action('wp_ajax_nopriv_gd_chatbot_stream_message', array($this, 'handle_stream_message'));
        
        // Test connection AJAX
        add_action('wp_ajax_gd_test_claude_connection', array($this, 'test_claude_connection'));
        add_action('wp_ajax_gd_test_tavily_connection', array($this, 'test_tavily_connection'));
        add_action('wp_ajax_gd_test_pinecone_connection', array($this, 'test_pinecone_connection'));

        // Token management AJAX
        add_action('wp_ajax_gd_chatbot_clear_context_cache', array($this, 'handle_clear_context_cache'));

        // Refresh models AJAX
        add_action('wp_ajax_gd_chatbot_refresh_models', array($this, 'handle_refresh_models'));
        
        // Archive.org streaming AJAX endpoints
        add_action('wp_ajax_gd_chatbot_archive_search', array($this, 'handle_archive_search'));
        add_action('wp_ajax_nopriv_gd_chatbot_archive_search', array($this, 'handle_archive_search'));
        add_action('wp_ajax_gd_chatbot_get_recordings', array($this, 'handle_get_recordings'));
        add_action('wp_ajax_nopriv_gd_chatbot_get_recordings', array($this, 'handle_get_recordings'));
        add_action('wp_ajax_gd_chatbot_get_stream_url', array($this, 'handle_get_stream_url'));
        add_action('wp_ajax_nopriv_gd_chatbot_get_stream_url', array($this, 'handle_get_stream_url'));
        add_action('wp_ajax_gd_chatbot_trigger_sync', array($this, 'handle_trigger_sync'));
        
        // Admin-only streaming management endpoints
        add_action('wp_ajax_gd_chatbot_clear_archive_cache', array($this, 'handle_clear_archive_cache'));
        add_action('wp_ajax_gd_chatbot_cleanup_database', array($this, 'handle_cleanup_database'));
        add_action('wp_ajax_gd_chatbot_test_detection', array($this, 'handle_test_detection'));
        add_action('wp_ajax_gd_chatbot_clear_all_data', array($this, 'handle_clear_all_data'));
        add_action('wp_ajax_gd_chatbot_reset_tables', array($this, 'handle_reset_tables'));
        add_action('wp_ajax_gd_chatbot_clear_song_cache', array($this, 'handle_clear_song_cache'));
        
        // OAuth and streaming services endpoints
        add_action('wp_ajax_gd_oauth_callback', array($this, 'handle_oauth_callback'));
        add_action('wp_ajax_gd_chatbot_connect_service', array($this, 'handle_connect_service'));
        add_action('wp_ajax_gd_chatbot_disconnect_service', array($this, 'handle_disconnect_service'));
        add_action('wp_ajax_gd_chatbot_search_streaming', array($this, 'handle_search_streaming'));
        add_action('wp_ajax_nopriv_gd_chatbot_search_streaming', array($this, 'handle_search_streaming'));
        add_action('wp_ajax_gd_chatbot_get_connection_status', array($this, 'handle_get_connection_status'));
        add_action('wp_ajax_gd_chatbot_test_service_config', array($this, 'handle_test_service_config'));
    }
    
    /**
     * Initialize plugin components
     */
    public function init_components() {
        if (is_admin()) {
            new GD_Chatbot_Admin_Settings();
            new GD_User_Profile_Integration();
        }
        new GD_Chatbot_Public();
        
        // Initialize streaming database and sync service
        new GD_Streaming_Database();
        new GD_Archive_Sync();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        $default_options = array(
            // Claude settings
            'claude_api_key' => '',
            'claude_model' => 'claude-sonnet-4-20250514',
            'claude_max_tokens' => 4096,
            'claude_temperature' => 0.7,
            'claude_system_prompt' => $this->get_default_system_prompt(),
            
            // Tavily settings
            'tavily_enabled' => false,
            'tavily_api_key' => '',
            'tavily_search_depth' => 'basic',
            'tavily_max_results' => 5,
            'tavily_include_domains' => '',
            'tavily_exclude_domains' => '',
            
            // Pinecone settings
            'pinecone_enabled' => false,
            'pinecone_api_key' => '',
            'pinecone_host' => '',
            'pinecone_index_name' => '',
            'pinecone_namespace' => '',
            'pinecone_top_k' => 5,
            
            // Knowledgebase Loader settings
            'kb_enabled' => false,
            'kb_max_results' => 10,
            'kb_min_score' => 0.35,
            
            // AI Power integration settings
            'aipower_enabled' => true,
            'aipower_max_results' => 10,
            'aipower_min_score' => 0.35,
            
            // Token optimization settings
            'token_optimization_enabled' => false,
            'token_budget' => 500,
            'token_cache_ttl' => 3600,

            // Appearance settings
            'chatbot_title' => '🌹 Grateful Dead Guide ⚡',
            'chatbot_welcome_message' => '🎸 Hey there, Deadhead! Ready to explore the music, shows, and culture of the Grateful Dead? Ask me anything!',
            'chatbot_placeholder' => 'Ask about shows, songs, or the Dead...',
            'chatbot_primary_color' => '#DC143C',
            'chatbot_position' => 'bottom-right',
            'chatbot_width' => '420',
            'chatbot_height' => '650',
        );
        
        foreach ($default_options as $key => $value) {
            if (get_option('gd_chatbot_v2_' . $key) === false) {
                add_option('gd_chatbot_v2_' . $key, $value);
            }
        }
        
        // Create conversation log table
        $this->create_tables();
        
        // Create streaming database tables (hybrid approach)
        $streaming_db = new GD_Streaming_Database();
        $streaming_db->maybe_create_tables();
        
        // Initialize encryption salt for credentials
        GD_Streaming_Credentials::init_encryption_salt();
        
        // Trigger activation hook for other components
        do_action('gd_chatbot_activate');
        
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            user_message longtext NOT NULL,
            assistant_message longtext NOT NULL,
            sources longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Handle incoming chat messages
     */
    public function handle_chat_message() {
        // Verify nonce
        if (!check_ajax_referer('gd_chatbot_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Get message
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $conversation_history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();
        
        if (empty($message)) {
            wp_send_json_error(array('message' => 'Message cannot be empty.'));
        }
        
        // Initialize chat handler
        $chat_handler = new GD_Chat_Handler();
        $response = $chat_handler->process_message($message, $conversation_history, $session_id);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle streaming chat messages
     */
    public function handle_stream_message() {
        // Verify nonce
        if (!check_ajax_referer('gd_chatbot_nonce', 'nonce', false)) {
            $this->send_sse_error('Security check failed.');
            exit;
        }
        
        // Get message
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $conversation_history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();
        
        if (empty($message)) {
            $this->send_sse_error('Message cannot be empty.');
            exit;
        }
        
        // Set headers for Server-Sent Events
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        
        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Initialize chat handler
        $chat_handler = new GD_Chat_Handler();
        $chat_handler->process_message_stream($message, $conversation_history, $session_id, array($this, 'send_sse_chunk'));
        
        exit;
    }
    
    /**
     * Send SSE chunk
     */
    public function send_sse_chunk($data) {
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    /**
     * Send SSE error
     */
    private function send_sse_error($message) {
        echo "data: " . json_encode(array('error' => $message)) . "\n\n";
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    /**
     * Test Claude API connection
     */
    public function test_claude_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        check_ajax_referer('gd_chatbot_admin_nonce', 'nonce');
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'));
        }
        
        $claude = new GD_Claude_API($api_key);
        $result = $claude->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Connection successful!'));
    }
    
    /**
     * Test Tavily API connection
     */
    public function test_tavily_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        check_ajax_referer('gd_chatbot_admin_nonce', 'nonce');
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'));
        }
        
        $tavily = new GD_Tavily_API($api_key);
        $result = $tavily->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Connection successful!'));
    }
    
    /**
     * Test Pinecone API connection
     */
    public function test_pinecone_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        check_ajax_referer('gd_chatbot_admin_nonce', 'nonce');
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '';
        
        if (empty($api_key) || empty($host)) {
            wp_send_json_error(array('message' => 'API key and host are required'));
        }
        
        $pinecone = new GD_Pinecone_API($api_key, $host);
        $result = $pinecone->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Connection successful!'));
    }
    
    /**
     * Handle clearing the context cache via AJAX
     */
    public function handle_clear_context_cache() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        check_ajax_referer('gd_chatbot_admin_nonce', 'nonce');

        $cache = new GD_Context_Cache();
        $cache->clear();

        wp_send_json_success(array('message' => 'Context cache cleared successfully.'));
    }

    /**
     * Handle AJAX request to refresh available models from the Anthropic API.
     */
    public function handle_refresh_models() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        check_ajax_referer('gd_chatbot_admin_nonce', 'nonce');

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'));
        }

        $claude = new GD_Claude_API($api_key);
        $models = $claude->list_models($api_key);

        if (is_wp_error($models)) {
            wp_send_json_error(array('message' => $models->get_error_message()));
        }

        // Filter to claude models only and format for the dropdown
        $formatted = array();
        foreach ($models as $model) {
            if (!isset($model['id']) || strpos($model['id'], 'claude') === false) {
                continue;
            }
            $formatted[] = array(
                'id'           => $model['id'],
                'display_name' => isset($model['display_name']) ? $model['display_name'] : $model['id'],
                'created_at'   => isset($model['created_at']) ? $model['created_at'] : '',
            );
        }

        wp_send_json_success(array(
            'models' => $formatted,
            'count'  => count($formatted),
        ));
    }

    /**
     * Get default system prompt
     */
    private function get_default_system_prompt() {
        return '## Role

You are an expert historian of the Grateful Dead, powered by comprehensive knowledge from the Grateful Dead Archive.

---

**TONE & APPROACH:**
- Knowledgeable but accessible to newcomers
- Respect for the community and culture
- Balance statistical/archival detail with cultural context
- Acknowledge era differences without bias
- Reference specific shows/performances when relevant

## Response Guidelines

### Content Standards
- Prioritize accuracy above all else—never fabricate facts, sources, or citations
- Provide direct, actionable information
- Base responses on verifiable information and established expertise from the provided context
- When uncertain, clearly state limitations and suggest verification methods

### Formatting
- Use **bold headers** for main sections
- Organize complex topics into clear, scannable sections
- Use bullet points for readability
- Maintain an engaging, knowledgeable tone appropriate for Deadheads
- Structure for easy scanning while maintaining depth';
    }
    
    /**
     * Load Grateful Dead context from markdown file
     */
    public function load_grateful_dead_context() {
        $context_file = GD_CHATBOT_PLUGIN_DIR . 'grateful-dead-context.md';
        
        if (!file_exists($context_file)) {
            error_log('GD Chatbot: grateful-dead-context.md file not found at: ' . $context_file);
            return '';
        }
        
        $context = file_get_contents($context_file);
        
        if (empty($context)) {
            error_log('GD Chatbot: grateful-dead-context.md file is empty');
            return '';
        }
        
        // Return the context wrapped with clear delimiters
        return "\n\n## GRATEFUL DEAD KNOWLEDGE BASE\n\nThe following is comprehensive reference material about the Grateful Dead. Use this information to answer user questions accurately and in detail.\n\n" . $context;
    }
    
    /**
     * Handle Archive.org search AJAX request
     */
    public function handle_archive_search() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        $song_title = isset($_POST['song_title']) ? sanitize_text_field($_POST['song_title']) : '';
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'downloads';
        
        if (empty($song_title)) {
            wp_send_json_error(array('message' => 'Song title is required'));
            return;
        }
        
        $archive_api = new GD_Archive_API();
        $results = $archive_api->search_by_song($song_title, $sort_by, 50);
        
        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
            return;
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Handle get recordings AJAX request (from database)
     */
    public function handle_get_recordings() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        global $wpdb;
        
        $song_title = isset($_POST['song_title']) ? sanitize_text_field($_POST['song_title']) : '';
        $dates = isset($_POST['dates']) ? sanitize_text_field($_POST['dates']) : '';
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'downloads';
        
        if (empty($song_title)) {
            wp_send_json_error(array('message' => 'Song title is required'));
            return;
        }
        
        // Build query
        $table_recordings = $wpdb->prefix . 'gd_show_recordings';
        $table_songs = $wpdb->prefix . 'gd_song_recordings';
        
        $query = "SELECT DISTINCT r.* 
                  FROM $table_recordings r
                  JOIN $table_songs s ON r.id = s.recording_id
                  WHERE s.song_title LIKE %s";
        
        $params = array('%' . $wpdb->esc_like($song_title) . '%');
        
        // Add date filter if provided
        if (!empty($dates)) {
            $date_array = array_map('trim', explode(',', $dates));
            $placeholders = implode(',', array_fill(0, count($date_array), '%s'));
            $query .= " AND r.show_date IN ($placeholders)";
            $params = array_merge($params, $date_array);
        }
        
        // Add sorting
        switch ($sort_by) {
            case 'date':
                $query .= " ORDER BY r.show_date ASC";
                break;
            case 'rating':
                $query .= " ORDER BY r.avg_rating DESC, r.downloads DESC";
                break;
            case 'downloads':
            default:
                $query .= " ORDER BY r.downloads DESC";
                break;
        }
        
        $query .= " LIMIT 50";
        
        $recordings = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        
        // Format results
        $formatted = array();
        foreach ($recordings as $recording) {
            $formatted[] = array(
                'identifier' => $recording['archive_identifier'],
                'date' => $recording['show_date'],
                'venue' => $recording['venue_name'],
                'location' => $recording['venue_location'],
                'downloads' => intval($recording['downloads']),
                'rating' => floatval($recording['avg_rating']),
                'num_reviews' => intval($recording['num_reviews']),
                'thumbnail' => $recording['thumbnail_url'],
                'archive_url' => $recording['archive_url'],
                'stream_url' => $recording['stream_url_mp3']
            );
        }
        
        wp_send_json_success($formatted);
    }
    
    /**
     * Handle get stream URL AJAX request
     */
    public function handle_get_stream_url() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        $identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
        
        if (empty($identifier)) {
            wp_send_json_error(array('message' => 'Identifier is required'));
            return;
        }
        
        $archive_api = new GD_Archive_API();
        $stream_url = $archive_api->get_streaming_url($identifier, 'mp3');
        
        if (is_wp_error($stream_url)) {
            wp_send_json_error(array('message' => $stream_url->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('stream_url' => $stream_url));
    }
    
    /**
     * Handle trigger sync AJAX request (admin only)
     */
    public function handle_trigger_sync() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : 'incremental';
        $year = isset($_POST['year']) ? intval($_POST['year']) : null;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : null;
        
        $archive_sync = new GD_Archive_Sync();
        $results = $archive_sync->run_sync(array(
            'sync_type' => $sync_type,
            'year' => $year,
            'date' => $date,
            'force' => true
        ));
        
        wp_send_json_success($results);
    }
    
    /**
     * Handle clear archive cache AJAX request (admin only)
     */
    public function handle_clear_archive_cache() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $archive_api = new GD_Archive_API();
        $deleted = $archive_api->clear_cache();
        
        wp_send_json_success(array(
            'message' => 'Cache cleared successfully',
            'deleted' => $deleted
        ));
    }
    
    /**
     * Handle cleanup database AJAX request (admin only)
     */
    public function handle_cleanup_database() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $streaming_db = new GD_Streaming_Database();
        $results = $streaming_db->cleanup_orphaned_records();
        
        wp_send_json_success(array(
            'message' => 'Database cleaned successfully',
            'deleted_songs' => $results['deleted_songs'],
            'fixed_favorites' => $results['fixed_favorites']
        ));
    }
    
    /**
     * Handle test detection AJAX request (admin only)
     */
    public function handle_test_detection() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $text = isset($_POST['text']) ? wp_kses_post($_POST['text']) : '';
        
        if (empty($text)) {
            wp_send_json_error(array('message' => 'Text is required'));
            return;
        }
        
        $enricher = new GD_Response_Enricher();
        $preview = $enricher->preview_enrichment($text);
        
        wp_send_json_success($preview);
    }
    
    /**
     * Handle clear all data AJAX request (admin only)
     */
    public function handle_clear_all_data() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        global $wpdb;
        
        // Truncate all tables
        $tables = array(
            $wpdb->prefix . 'gd_show_recordings',
            $wpdb->prefix . 'gd_song_recordings',
            $wpdb->prefix . 'gd_user_show_favorites',
            $wpdb->prefix . 'gd_archive_sync_log'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE $table");
        }
        
        // Clear caches
        $archive_api = new GD_Archive_API();
        $archive_api->clear_cache();
        
        wp_send_json_success(array('message' => 'All data cleared successfully'));
    }
    
    /**
     * Handle reset tables AJAX request (admin only)
     */
    public function handle_reset_tables() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        // Drop tables
        GD_Streaming_Database::drop_tables();
        
        // Recreate tables
        $streaming_db = new GD_Streaming_Database();
        $streaming_db->maybe_create_tables();
        
        wp_send_json_success(array('message' => 'Tables reset successfully'));
    }
    
    /**
     * Handle clear song cache AJAX request (admin only)
     */
    public function handle_clear_song_cache() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        GD_Song_Detector::clear_cache();
        
        wp_send_json_success(array('message' => 'Song cache cleared successfully'));
    }
    
    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback() {
        if (!is_user_logged_in()) {
            wp_die('You must be logged in to connect streaming services.');
        }
        
        $service = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        
        if ($error) {
            wp_redirect(add_query_arg(array(
                'page' => 'gd-chatbot-v2',
                'tab' => 'streaming',
                'oauth_error' => $error
            ), admin_url('admin.php')));
            exit;
        }
        
        if (empty($service) || empty($code) || empty($state)) {
            wp_die('Invalid OAuth callback parameters.');
        }
        
        $user_id = get_current_user_id();
        $manager = new GD_Streaming_Service_Manager();
        $result = $manager->handle_oauth_callback($service, $code, $state, $user_id);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(array(
                'page' => 'gd-chatbot-v2',
                'tab' => 'streaming',
                'oauth_error' => $result->get_error_message()
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'gd-chatbot-v2',
                'tab' => 'streaming',
                'oauth_success' => $service
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Handle connect service AJAX request
     */
    public function handle_connect_service() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
        
        if (empty($service)) {
            wp_send_json_error(array('message' => 'Service is required'));
            return;
        }
        
        $user_id = get_current_user_id();
        $manager = new GD_Streaming_Service_Manager();
        $auth_url = $manager->get_auth_url($service, $user_id);
        
        if (is_wp_error($auth_url)) {
            wp_send_json_error(array('message' => $auth_url->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('auth_url' => $auth_url));
    }
    
    /**
     * Handle disconnect service AJAX request
     */
    public function handle_disconnect_service() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
        
        if (empty($service)) {
            wp_send_json_error(array('message' => 'Service is required'));
            return;
        }
        
        $user_id = get_current_user_id();
        $manager = new GD_Streaming_Service_Manager();
        $result = $manager->disconnect_service($user_id, $service);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Service disconnected successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to disconnect service'));
        }
    }
    
    /**
     * Handle search streaming services AJAX request
     */
    public function handle_search_streaming() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        $song_title = isset($_POST['song_title']) ? sanitize_text_field($_POST['song_title']) : '';
        $artist = isset($_POST['artist']) ? sanitize_text_field($_POST['artist']) : 'Grateful Dead';
        
        if (empty($song_title)) {
            wp_send_json_error(array('message' => 'Song title is required'));
            return;
        }
        
        // Get user ID (0 for guests)
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        
        if ($user_id === 0) {
            // Guest users only get Archive.org results
            wp_send_json_success(array(
                'archive' => array(),
                'streaming' => array(),
                'meta' => array(
                    'song_title' => $song_title,
                    'artist' => $artist,
                    'total_sources' => 0,
                    'connected_services' => array(),
                    'guest_user' => true
                )
            ));
            return;
        }
        
        $manager = new GD_Streaming_Service_Manager();
        $results = $manager->get_unified_results($user_id, $song_title, array(
            'artist' => $artist,
            'include_archive' => true
        ));
        
        wp_send_json_success($results);
    }
    
    /**
     * Handle get connection status AJAX request
     */
    public function handle_get_connection_status() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $manager = new GD_Streaming_Service_Manager();
        $status = $manager->get_connection_status($user_id);
        
        wp_send_json_success($status);
    }
    
    /**
     * Handle test service configuration AJAX request (admin only)
     */
    public function handle_test_service_config() {
        check_ajax_referer('gd_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
        $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
        $client_secret = isset($_POST['client_secret']) ? sanitize_text_field($_POST['client_secret']) : '';
        
        if (empty($service) || empty($client_id) || empty($client_secret)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
            return;
        }
        
        // Temporarily store credentials for testing
        $original_client_id = get_option('gd_chatbot_v2_' . $service . '_client_id');
        $original_client_secret = get_option('gd_chatbot_v2_' . $service . '_client_secret');
        
        update_option('gd_chatbot_v2_' . $service . '_client_id', $client_id);
        update_option('gd_chatbot_v2_' . $service . '_client_secret', $client_secret);
        
        // Try to create OAuth handler
        $class_map = array(
            'spotify' => 'GD_Spotify_OAuth',
            'apple_music' => 'GD_Apple_Music_OAuth',
            'youtube_music' => 'GD_Youtube_Music_OAuth',
            'amazon_music' => 'GD_Amazon_Music_OAuth',
            'tidal' => 'GD_Tidal_OAuth'
        );
        
        if (!isset($class_map[$service]) || !class_exists($class_map[$service])) {
            // Restore original credentials
            update_option('gd_chatbot_v2_' . $service . '_client_id', $original_client_id);
            update_option('gd_chatbot_v2_' . $service . '_client_secret', $original_client_secret);
            
            wp_send_json_error(array('message' => 'Service not supported'));
            return;
        }
        
        $oauth = new $class_map[$service]();
        
        if ($oauth->is_configured()) {
            wp_send_json_success(array(
                'message' => 'Configuration is valid! Users can now connect their ' . ucfirst(str_replace('_', ' ', $service)) . ' accounts.'
            ));
        } else {
            // Restore original credentials
            update_option('gd_chatbot_v2_' . $service . '_client_id', $original_client_id);
            update_option('gd_chatbot_v2_' . $service . '_client_secret', $original_client_secret);
            
            wp_send_json_error(array('message' => 'Configuration is invalid. Please check your credentials.'));
        }
    }
}

// Initialize the plugin
function gd_chatbot() {
    return GD_Chatbot::get_instance();
}

// Start the plugin
gd_chatbot();
