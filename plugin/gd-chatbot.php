<?php
/**
 * Plugin Name: GD Chatbot v2
 * Plugin URI: https://it-influentials.com
 * Description: AI-powered chatbot using Anthropic's Claude with Tavily web search and Pinecone vector database support.
 * Version: 2.0.7
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
define('GD_CHATBOT_VERSION', '2.0.7');
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
        // Core classes
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-claude-api.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-tavily-api.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-pinecone-api.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-setlist-search.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-kb-integration.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-aipower-integration.php';
        require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-chat-handler.php';
        
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
    }
    
    /**
     * Initialize plugin components
     */
    public function init_components() {
        if (is_admin()) {
            new GD_Chatbot_Admin_Settings();
        }
        new GD_Chatbot_Public();
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
            'kb_enabled' => true,
            'kb_max_results' => 10,
            'kb_min_score' => 0.35,
            
            // AI Power integration settings
            'aipower_enabled' => true,
            'aipower_max_results' => 10,
            'aipower_min_score' => 0.35,
            
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
}

// Initialize the plugin
function gd_chatbot() {
    return GD_Chatbot::get_instance();
}

// Start the plugin
gd_chatbot();
