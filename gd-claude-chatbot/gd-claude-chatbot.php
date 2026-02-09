<?php
/**
 * Plugin Name: GD Claude Chatbot
 * Plugin URI: https://it-influentials.com
 * Description: AI-powered chatbot using Anthropic's Claude with Tavily web search and Pinecone vector database support.
 * Version: 1.9.4
 * Author: IT Influentials
 * Author URI: https://it-influentials.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gd-claude-chatbot
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants - use defined() to prevent redefinition errors
if (!defined('GD_CHATBOT_VERSION')) {
    define('GD_CHATBOT_VERSION', '1.9.5');
}
if (!defined('GD_CHATBOT_PLUGIN_DIR')) {
    define('GD_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('GD_CHATBOT_PLUGIN_URL')) {
    define('GD_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('GD_CHATBOT_PLUGIN_BASENAME')) {
    define('GD_CHATBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
if (!defined('GD_CHATBOT_DEBUG_EMAIL')) {
    define('GD_CHATBOT_DEBUG_EMAIL', 'peter@it-influentials.com');
}

/**
 * Diagnostic Logger Class
 * Captures detailed activation logs and emails them
 * NOTE: All methods are defensive - they check if WP functions exist before calling
 */
if (!class_exists('GD_Chatbot_Diagnostic_Logger')) {
class GD_Chatbot_Diagnostic_Logger {
    private static $logs = array();
    private static $start_time = null;
    private static $initialized = false;
    
    public static function init() {
        if (self::$initialized) {
            return; // Prevent double init
        }
        self::$initialized = true;
        self::$start_time = microtime(true);
        
        self::log('=== GD CHATBOT DIAGNOSTIC LOG STARTED ===');
        self::log('Time: ' . date('Y-m-d H:i:s'));
        self::log('PHP Version: ' . PHP_VERSION);
        
        // Safe WordPress version check
        global $wp_version;
        $wp_ver = isset($wp_version) ? $wp_version : 'unknown';
        self::log('WordPress Version: ' . $wp_ver);
        
        self::log('Plugin Version: ' . (defined('GD_CHATBOT_VERSION') ? GD_CHATBOT_VERSION : 'unknown'));
        self::log('Plugin Dir: ' . (defined('GD_CHATBOT_PLUGIN_DIR') ? GD_CHATBOT_PLUGIN_DIR : 'unknown'));
        self::log('Memory Limit: ' . ini_get('memory_limit'));
        self::log('Max Execution Time: ' . ini_get('max_execution_time'));
        self::log('ABSPATH: ' . (defined('ABSPATH') ? ABSPATH : 'NOT DEFINED'));
    }
    
    public static function log($message, $type = 'INFO') {
        $elapsed = self::$start_time ? round((microtime(true) - self::$start_time) * 1000, 2) : 0;
        $entry = sprintf('[%s] [%sms] %s', $type, $elapsed, $message);
        self::$logs[] = $entry;
        
        // Use error_log which is always available
        error_log('GD Chatbot Debug: ' . $entry);
    }
    
    public static function log_file_check($file, $description) {
        $exists = file_exists($file);
        $readable = $exists ? is_readable($file) : false;
        $size = $exists ? @filesize($file) : 0;
        
        self::log(sprintf(
            'File Check: %s | Exists: %s | Readable: %s | Size: %d bytes | Path: %s',
            $description,
            $exists ? 'YES' : 'NO',
            $readable ? 'YES' : 'NO',
            $size,
            $file
        ), $exists ? 'INFO' : 'ERROR');
        
        return $exists && $readable;
    }
    
    public static function log_class_check($class_name) {
        $exists = class_exists($class_name, false); // false = don't autoload
        self::log(sprintf(
            'Class Check: %s | Exists: %s',
            $class_name,
            $exists ? 'YES' : 'NO'
        ), $exists ? 'INFO' : 'ERROR');
        return $exists;
    }
    
    public static function log_error($message, $exception = null) {
        $error_msg = $message;
        if ($exception && is_object($exception)) {
            if (method_exists($exception, 'getMessage')) {
                $error_msg .= ' | Exception: ' . $exception->getMessage();
            }
            if (method_exists($exception, 'getFile')) {
                $error_msg .= ' | File: ' . $exception->getFile();
            }
            if (method_exists($exception, 'getLine')) {
                $error_msg .= ' | Line: ' . $exception->getLine();
            }
            if (method_exists($exception, 'getTraceAsString')) {
                $error_msg .= ' | Trace: ' . $exception->getTraceAsString();
            }
        }
        self::log($error_msg, 'ERROR');
    }
    
    public static function get_logs() {
        return self::$logs;
    }
    
    public static function get_log_text() {
        return implode("\n", self::$logs);
    }
    
    public static function send_email_report($subject_suffix = '') {
        // Check if wp_mail is available
        if (!function_exists('wp_mail')) {
            self::log('wp_mail not available - cannot send email report', 'WARNING');
            return false;
        }
        
        $to = defined('GD_CHATBOT_DEBUG_EMAIL') ? GD_CHATBOT_DEBUG_EMAIL : 'peter@it-influentials.com';
        $subject = 'GD Chatbot Log ' . $subject_suffix . ' - ' . date('Y-m-d H:i:s');
        
        $message = "GD Claude Chatbot Diagnostic Report\n";
        $message .= "====================================\n\n";
        
        // Safe site URL
        if (function_exists('get_site_url')) {
            $message .= "Site URL: " . get_site_url() . "\n";
        }
        
        // Safe admin email
        if (function_exists('get_option')) {
            $message .= "Admin Email: " . get_option('admin_email') . "\n";
        }
        
        $message .= "\nDIAGNOSTIC LOG:\n";
        $message .= "---------------\n\n";
        $message .= self::get_log_text();
        $message .= "\n\n=== END OF LOG ===\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $sent = @wp_mail($to, $subject, $message, $headers);
        self::log('Email report to ' . $to . ': ' . ($sent ? 'SUCCESS' : 'FAILED'), $sent ? 'INFO' : 'WARNING');
        
        return $sent;
    }
}
} // End class_exists check for GD_Chatbot_Diagnostic_Logger

/**
 * Safe File Loader Class
 * LAYER 1: Pre-Installation Validation
 * Prevents fatal errors from missing files
 */
if (!class_exists('GD_Chatbot_Safe_Loader')) {
class GD_Chatbot_Safe_Loader {
    private static $missing_files = array();
    private static $load_errors = array();
    
    /**
     * Get current time safely (works even if WP functions aren't loaded)
     */
    private static function get_safe_time() {
        if (function_exists('current_time')) {
            return current_time('mysql');
        }
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Safely require a file with validation
     */
    public static function require_file($file_path, $file_description = '') {
        GD_Chatbot_Diagnostic_Logger::log("Attempting to load: $file_description");
        
        // Check if file exists
        if (!GD_Chatbot_Diagnostic_Logger::log_file_check($file_path, $file_description)) {
            self::$missing_files[] = array(
                'path' => $file_path,
                'description' => $file_description,
                'time' => self::get_safe_time()
            );
            return false;
        }
        
        // Try to load the file
        try {
            GD_Chatbot_Diagnostic_Logger::log("Including file: $file_path");
            require_once $file_path;
            GD_Chatbot_Diagnostic_Logger::log("Successfully loaded: $file_description");
            return true;
        } catch (Exception $e) {
            self::$load_errors[] = array(
                'file' => $file_path,
                'error' => $e->getMessage(),
                'time' => self::get_safe_time()
            );
            GD_Chatbot_Diagnostic_Logger::log_error("Exception loading $file_description", $e);
            return false;
        } catch (Error $e) {
            self::$load_errors[] = array(
                'file' => $file_path,
                'error' => $e->getMessage(),
                'time' => self::get_safe_time()
            );
            GD_Chatbot_Diagnostic_Logger::log_error("PHP Error loading $file_description: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all missing files
     */
    public static function get_missing_files() {
        return self::$missing_files;
    }
    
    /**
     * Get all load errors
     */
    public static function get_load_errors() {
        return self::$load_errors;
    }
    
    /**
     * Check if plugin can safely activate
     */
    public static function can_activate() {
        return empty(self::$missing_files) && empty(self::$load_errors);
    }
    
    /**
     * Reset error tracking
     */
    public static function reset() {
        self::$missing_files = array();
        self::$load_errors = array();
    }
}
} // End class_exists check for GD_Chatbot_Safe_Loader

/**
 * Main Plugin Class
 */
if (!class_exists('GD_Claude_Chatbot')) {
class GD_Claude_Chatbot {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get current time safely (works even if WP functions aren't fully loaded)
     */
    private static function safe_current_time() {
        if (function_exists('current_time')) {
            return current_time('mysql');
        }
        return date('Y-m-d H:i:s');
    }
    
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
        // Initialize diagnostic logger
        GD_Chatbot_Diagnostic_Logger::init();
        GD_Chatbot_Diagnostic_Logger::log('Constructor started');
        
        // LAYER 4: Register shutdown handler for fatal errors
        register_shutdown_function(array($this, 'handle_shutdown'));
        GD_Chatbot_Diagnostic_Logger::log('Shutdown handler registered');
        
        // Load dependencies safely
        GD_Chatbot_Diagnostic_Logger::log('Starting dependency loading...');
        if (!$this->load_dependencies()) {
            // Dependencies failed to load
            GD_Chatbot_Diagnostic_Logger::log('CRITICAL: Dependencies failed to load!', 'ERROR');
            GD_Chatbot_Diagnostic_Logger::send_email_report('DEPENDENCY FAILURE');
            add_action('admin_notices', array($this, 'show_load_error'));
            return;
        }
        
        GD_Chatbot_Diagnostic_Logger::log('All dependencies loaded successfully');
        
        // Verify all required classes exist
        $this->verify_classes();
        
        GD_Chatbot_Diagnostic_Logger::log('Initializing hooks...');
        $this->init_hooks();
        GD_Chatbot_Diagnostic_Logger::log('Constructor completed successfully');
    }
    
    /**
     * Verify all required classes are loaded
     */
    private function verify_classes() {
        GD_Chatbot_Diagnostic_Logger::log('Verifying required classes...');
        
        $required_classes = array(
            'GD_Claude_API',
            'GD_Tavily_API',
            'GD_Pinecone_API',
            'GD_Chat_Handler',
            'GD_Chatbot_Public',
        );
        
        if (is_admin()) {
            $required_classes[] = 'GD_Chatbot_Admin_Settings';
        }
        
        $optional_classes = array(
            'GD_Setlist_Search',
            'GD_KB_Integration',
            'GD_AIPower_Integration',
        );
        
        foreach ($required_classes as $class) {
            GD_Chatbot_Diagnostic_Logger::log_class_check($class);
        }
        
        foreach ($optional_classes as $class) {
            GD_Chatbot_Diagnostic_Logger::log_class_check($class);
        }
    }
    
    /**
     * Load required files with safety checks
     * LAYER 1: Pre-Installation Validation
     */
    private function load_dependencies() {
        $loader = 'GD_Chatbot_Safe_Loader';
        
        GD_Chatbot_Diagnostic_Logger::log('Plugin directory: ' . GD_CHATBOT_PLUGIN_DIR);
        GD_Chatbot_Diagnostic_Logger::log('is_admin(): ' . (is_admin() ? 'true' : 'false'));
        
        // Check if includes directory exists
        $includes_dir = GD_CHATBOT_PLUGIN_DIR . 'includes/';
        $admin_dir = GD_CHATBOT_PLUGIN_DIR . 'admin/';
        $public_dir = GD_CHATBOT_PLUGIN_DIR . 'public/';
        
        GD_Chatbot_Diagnostic_Logger::log('Checking directories...');
        GD_Chatbot_Diagnostic_Logger::log('Includes dir exists: ' . (is_dir($includes_dir) ? 'YES' : 'NO'));
        GD_Chatbot_Diagnostic_Logger::log('Admin dir exists: ' . (is_dir($admin_dir) ? 'YES' : 'NO'));
        GD_Chatbot_Diagnostic_Logger::log('Public dir exists: ' . (is_dir($public_dir) ? 'YES' : 'NO'));
        
        // Define all required files with criticality
        $files = array(
            // Core classes (critical)
            array(GD_CHATBOT_PLUGIN_DIR . 'includes/class-claude-api.php', 'Claude API handler', true),
            array(GD_CHATBOT_PLUGIN_DIR . 'includes/class-tavily-api.php', 'Tavily API handler', false),
            array(GD_CHATBOT_PLUGIN_DIR . 'includes/class-pinecone-api.php', 'Pinecone API handler', false),
            array(GD_CHATBOT_PLUGIN_DIR . 'includes/class-setlist-search.php', 'Setlist search', false),
            array(GD_CHATBOT_PLUGIN_DIR . 'includes/class-kb-integration.php', 'KB integration', false),
            array(GD_CHATBOT_PLUGIN_DIR . 'includes/class-aipower-integration.php', 'AI Power integration', false),
            array(GD_CHATBOT_PLUGIN_DIR . 'includes/class-chat-handler.php', 'Chat handler', true),
        );
        
        // Add admin files if in admin
        if (is_admin()) {
            GD_Chatbot_Diagnostic_Logger::log('Adding admin settings file to load list');
            $files[] = array(GD_CHATBOT_PLUGIN_DIR . 'admin/class-admin-settings.php', 'Admin settings', true);
        }
        
        // Add public files
        GD_Chatbot_Diagnostic_Logger::log('Adding public chatbot file to load list');
        $files[] = array(GD_CHATBOT_PLUGIN_DIR . 'public/class-chatbot-public.php', 'Public chatbot', true);
        
        GD_Chatbot_Diagnostic_Logger::log('Total files to load: ' . count($files));
        
        $critical_failed = false;
        $loaded_count = 0;
        $failed_count = 0;
        
        foreach ($files as $file) {
            list($path, $description, $critical) = $file;
            GD_Chatbot_Diagnostic_Logger::log("Loading [$description] Critical: " . ($critical ? 'YES' : 'NO'));
            
            $loaded = $loader::require_file($path, $description);
            
            if ($loaded) {
                $loaded_count++;
            } else {
                $failed_count++;
                if ($critical) {
                    GD_Chatbot_Diagnostic_Logger::log("CRITICAL FILE FAILED: $description", 'ERROR');
                    $critical_failed = true;
                }
            }
        }
        
        GD_Chatbot_Diagnostic_Logger::log("File loading complete. Loaded: $loaded_count, Failed: $failed_count");
        GD_Chatbot_Diagnostic_Logger::log('Critical failure: ' . ($critical_failed ? 'YES' : 'NO'));
        
        return !$critical_failed;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // NOTE: Activation/Deactivation hooks are registered at the top level
        // (outside of any hook) to ensure they work properly
        
        // Initialize components on init hook - this is the proper WordPress way
        // Using priority 10 ensures shortcodes are registered at the right time
        add_action('init', array($this, 'init_components'), 10);
        
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
        
        // Cache management AJAX
        add_action('wp_ajax_gd_clear_tavily_cache', array($this, 'clear_tavily_cache'));
    }
    
    /**
     * Initialize plugin components with safety guardrails
     * LAYER 6: Component Isolation - Errors in components don't crash the site
     */
    public function init_components() {
        GD_Chatbot_Diagnostic_Logger::log('init_components() called');
        GD_Chatbot_Diagnostic_Logger::log('is_admin(): ' . (is_admin() ? 'true' : 'false'));
        
        // Safety: Wrap in try-catch to prevent site crashes
        try {
            // Initialize admin components
            if (is_admin() && class_exists('GD_Chatbot_Admin_Settings')) {
                GD_Chatbot_Diagnostic_Logger::log('Initializing Admin Settings...');
                new GD_Chatbot_Admin_Settings();
                GD_Chatbot_Diagnostic_Logger::log('Admin Settings initialized successfully');
            }
            
            // Initialize public components with extra safety
            if (class_exists('GD_Chatbot_Public')) {
                GD_Chatbot_Diagnostic_Logger::log('Initializing Public Chatbot...');
                new GD_Chatbot_Public();
                GD_Chatbot_Diagnostic_Logger::log('Public Chatbot initialized successfully');
            }
            
            GD_Chatbot_Diagnostic_Logger::log('All components initialized successfully');
            
            // Send success email on first activation
            if (get_option('gd_chatbot_send_activation_email', false)) {
                GD_Chatbot_Diagnostic_Logger::log('Sending activation success email...');
                GD_Chatbot_Diagnostic_Logger::send_email_report('ACTIVATION SUCCESS');
                delete_option('gd_chatbot_send_activation_email');
            }
            
        } catch (Exception $e) {
            // Log error but don't crash the site
            GD_Chatbot_Diagnostic_Logger::log_error('Component initialization Exception', $e);
            
            // Store error for admin notice
            update_option('gd_chatbot_component_error', array(
                'message' => $e->getMessage(),
                'time' => self::safe_current_time()
            ));
            
            // Send email report
            GD_Chatbot_Diagnostic_Logger::send_email_report('COMPONENT EXCEPTION');
            
            // Show admin notice
            add_action('admin_notices', array($this, 'show_component_error'));
        } catch (Error $e) {
            // Catch PHP 7+ errors too
            GD_Chatbot_Diagnostic_Logger::log_error('Component initialization PHP Error: ' . $e->getMessage());
            
            update_option('gd_chatbot_component_error', array(
                'message' => $e->getMessage(),
                'time' => self::safe_current_time()
            ));
            
            // Send email report
            GD_Chatbot_Diagnostic_Logger::send_email_report('COMPONENT PHP ERROR');
            
            add_action('admin_notices', array($this, 'show_component_error'));
        }
    }
    
    /**
     * Show component error notice
     */
    public function show_component_error() {
        $error = get_option('gd_chatbot_component_error');
        if (!$error) {
            return;
        }
        ?>
        <div class="notice notice-warning is-dismissible">
            <h3><?php _e('GD Claude Chatbot - Component Warning', 'gd-claude-chatbot'); ?></h3>
            <p><?php _e('Some chatbot components failed to initialize:', 'gd-claude-chatbot'); ?></p>
            <p><code><?php echo esc_html($error['message']); ?></code></p>
            <p><?php _e('The site is still functional. Please check your error logs for details.', 'gd-claude-chatbot'); ?></p>
        </div>
        <?php
        // Clear after showing once
        delete_option('gd_chatbot_component_error');
    }
    
    /**
     * Check if system meets requirements
     * LAYER 2: System Requirements Validation
     */
    private function check_requirements() {
        GD_Chatbot_Diagnostic_Logger::log('Starting requirements check...');
        $errors = array();
        
        // Check PHP version
        GD_Chatbot_Diagnostic_Logger::log('PHP Version: ' . PHP_VERSION);
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = sprintf(
                'PHP version 7.4 or higher required. Current version: %s',
                PHP_VERSION
            );
            GD_Chatbot_Diagnostic_Logger::log('PHP version check: FAILED', 'ERROR');
        } else {
            GD_Chatbot_Diagnostic_Logger::log('PHP version check: PASSED');
        }
        
        // Check WordPress version
        global $wp_version;
        GD_Chatbot_Diagnostic_Logger::log('WordPress Version: ' . $wp_version);
        if (version_compare($wp_version, '6.0', '<')) {
            $errors[] = sprintf(
                'WordPress version 6.0 or higher required. Current version: %s',
                $wp_version
            );
            GD_Chatbot_Diagnostic_Logger::log('WordPress version check: FAILED', 'ERROR');
        } else {
            GD_Chatbot_Diagnostic_Logger::log('WordPress version check: PASSED');
        }
        
        // Check required PHP extensions
        $required_extensions = array('curl', 'json', 'mbstring', 'mysqli');
        foreach ($required_extensions as $ext) {
            $loaded = extension_loaded($ext);
            GD_Chatbot_Diagnostic_Logger::log("PHP Extension '$ext': " . ($loaded ? 'LOADED' : 'MISSING'));
            if (!$loaded) {
                $errors[] = sprintf('Required PHP extension missing: %s', $ext);
            }
        }
        
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->return_bytes($memory_limit);
        GD_Chatbot_Diagnostic_Logger::log("Memory limit: $memory_limit ($memory_bytes bytes)");
        if ($memory_bytes > 0 && $memory_bytes < 67108864) { // 64MB
            $errors[] = sprintf(
                'Memory limit too low. Minimum 64MB required. Current: %s',
                $memory_limit
            );
            GD_Chatbot_Diagnostic_Logger::log('Memory limit check: FAILED', 'ERROR');
        } else {
            GD_Chatbot_Diagnostic_Logger::log('Memory limit check: PASSED');
        }
        
        // Check write permissions
        $upload_dir = wp_upload_dir();
        GD_Chatbot_Diagnostic_Logger::log('Upload directory: ' . $upload_dir['basedir']);
        $is_writable = is_writable($upload_dir['basedir']);
        GD_Chatbot_Diagnostic_Logger::log('Upload directory writable: ' . ($is_writable ? 'YES' : 'NO'));
        if (!$is_writable) {
            $errors[] = 'Upload directory is not writable';
        }
        
        // Store errors if any
        if (!empty($errors)) {
            GD_Chatbot_Diagnostic_Logger::log('Requirements check FAILED with ' . count($errors) . ' errors', 'ERROR');
            foreach ($errors as $error) {
                GD_Chatbot_Diagnostic_Logger::log('Requirement Error: ' . $error, 'ERROR');
            }
            update_option('gd_chatbot_requirement_errors', $errors);
            return false;
        }
        
        GD_Chatbot_Diagnostic_Logger::log('All requirements check PASSED');
        return true;
    }
    
    /**
     * Convert memory limit to bytes
     */
    private function return_bytes($val) {
        $val = trim($val);
        if ($val == '-1') {
            return -1; // Unlimited
        }
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    /**
     * Plugin activation with comprehensive error handling
     * LAYER 2: Safe Activation with Error Handling
     */
    public function activate() {
        try {
            // Log activation attempt
            GD_Chatbot_Diagnostic_Logger::log('activate() method started');
            
            // Check system requirements
            GD_Chatbot_Diagnostic_Logger::log('Checking system requirements...');
            if (!$this->check_requirements()) {
                GD_Chatbot_Diagnostic_Logger::log('System requirements check FAILED', 'ERROR');
                throw new Exception('System requirements not met');
            }
            GD_Chatbot_Diagnostic_Logger::log('System requirements check PASSED');
            
            // Create database tables
            GD_Chatbot_Diagnostic_Logger::log('Creating database tables...');
            if (!$this->create_tables()) {
                GD_Chatbot_Diagnostic_Logger::log('Database table creation FAILED', 'ERROR');
                throw new Exception('Failed to create database tables');
            }
            GD_Chatbot_Diagnostic_Logger::log('Database tables created successfully');
            
            // Mark as successfully activated before setting options
            update_option('gd_chatbot_activation_status', 'success');
            update_option('gd_chatbot_activation_time', self::safe_current_time());
            GD_Chatbot_Diagnostic_Logger::log('Activation status saved to database');
            
            // Create default options
            GD_Chatbot_Diagnostic_Logger::log('Setting default options...');
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
                if (get_option('gd_chatbot_' . $key) === false) {
                    add_option('gd_chatbot_' . $key, $value);
                }
            }
            GD_Chatbot_Diagnostic_Logger::log('Default options set (' . count($default_options) . ' options)');
            
            flush_rewrite_rules();
            GD_Chatbot_Diagnostic_Logger::log('Rewrite rules flushed');
            
            // Log success
            GD_Chatbot_Diagnostic_Logger::log('activate() method completed successfully');
            
        } catch (Exception $e) {
            // Log error
            GD_Chatbot_Diagnostic_Logger::log_error('Activation failed with Exception', $e);
            
            // Store error for admin notice
            update_option('gd_chatbot_activation_error', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'time' => self::safe_current_time()
            ));
            
            // Mark as failed
            update_option('gd_chatbot_activation_status', 'failed');
            
            // Send email report before deactivating
            GD_Chatbot_Diagnostic_Logger::send_email_report('ACTIVATION EXCEPTION IN activate()');
            
            // Deactivate plugin automatically
            deactivate_plugins(plugin_basename(__FILE__));
            
            // Show error page
            wp_die(
                '<h1>' . __('Plugin Activation Failed', 'gd-claude-chatbot') . '</h1>' .
                '<p><strong>' . __('GD Claude Chatbot could not be activated:', 'gd-claude-chatbot') . '</strong></p>' .
                '<p>' . esc_html($e->getMessage()) . '</p>' .
                '<h3>' . __('What to do:', 'gd-claude-chatbot') . '</h3>' .
                '<ol>' .
                '<li>' . __('Check that your server meets the minimum requirements', 'gd-claude-chatbot') . '</li>' .
                '<li>' . __('Verify all plugin files were uploaded correctly', 'gd-claude-chatbot') . '</li>' .
                '<li>' . __('Check your error logs for more details', 'gd-claude-chatbot') . '</li>' .
                '<li>' . __('Contact support if the issue persists', 'gd-claude-chatbot') . '</li>' .
                '</ol>' .
                '<p><a href="' . admin_url('plugins.php') . '" class="button button-primary">' . 
                __('Return to Plugins', 'gd-claude-chatbot') . '</a></p>',
                __('Activation Error', 'gd-claude-chatbot'),
                array('back_link' => true)
            );
        }
    }
    
    /**
     * Show load error notice
     * LAYER 3: Graceful Degradation
     */
    public function show_load_error() {
        $missing = GD_Chatbot_Safe_Loader::get_missing_files();
        $errors = GD_Chatbot_Safe_Loader::get_load_errors();
        
        ?>
        <div class="notice notice-error is-dismissible">
            <h3><?php _e('GD Claude Chatbot - Failed to Load', 'gd-claude-chatbot'); ?></h3>
            <p><?php _e('The plugin could not load all required files.', 'gd-claude-chatbot'); ?></p>
            
            <?php if (!empty($missing)) : ?>
                <p><strong><?php _e('Missing files:', 'gd-claude-chatbot'); ?></strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ($missing as $file) : ?>
                        <li>
                            <code><?php echo esc_html($file['path']); ?></code>
                            <?php if ($file['description']) : ?>
                                - <?php echo esc_html($file['description']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (!empty($errors)) : ?>
                <p><strong><?php _e('Load errors:', 'gd-claude-chatbot'); ?></strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ($errors as $error) : ?>
                        <li>
                            <code><?php echo esc_html($error['file']); ?></code>: 
                            <?php echo esc_html($error['error']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <p>
                <strong><?php _e('What to do:', 'gd-claude-chatbot'); ?></strong>
            </p>
            <ol style="margin-left: 20px;">
                <li><?php _e('Download the complete plugin package', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Delete the current plugin folder', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Upload the complete package', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Try activating again', 'gd-claude-chatbot'); ?></li>
            </ol>
            
            <p>
                <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-primary">
                    <?php _e('Return to Plugins', 'gd-claude-chatbot'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Show activation error notice
     * LAYER 3: Graceful Degradation
     */
    public function show_activation_error() {
        $error = get_option('gd_chatbot_activation_error');
        
        if (!$error) {
            return;
        }
        
        ?>
        <div class="notice notice-error is-dismissible">
            <h3><?php _e('GD Claude Chatbot - Activation Failed', 'gd-claude-chatbot'); ?></h3>
            <p><strong><?php _e('The plugin could not be activated:', 'gd-claude-chatbot'); ?></strong></p>
            <p><?php echo esc_html($error['message']); ?></p>
            
            <?php
            // Show requirement errors if any
            $req_errors = get_option('gd_chatbot_requirement_errors');
            if (!empty($req_errors)) :
            ?>
                <p><strong><?php _e('System requirements not met:', 'gd-claude-chatbot'); ?></strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ($req_errors as $req_error) : ?>
                        <li><?php echo esc_html($req_error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <p>
                <strong><?php _e('What to do:', 'gd-claude-chatbot'); ?></strong>
            </p>
            <ol style="margin-left: 20px;">
                <li><?php _e('Verify your server meets the minimum requirements', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Check that all plugin files were uploaded correctly', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Review your error logs for more details', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Try activating again after resolving issues', 'gd-claude-chatbot'); ?></li>
            </ol>
            
            <p>
                <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-primary">
                    <?php _e('Return to Plugins', 'gd-claude-chatbot'); ?>
                </a>
            </p>
        </div>
        <?php
        
        // Clear error after showing
        delete_option('gd_chatbot_activation_error');
    }
    
    /**
     * Show emergency shutdown notice
     * LAYER 3: Graceful Degradation
     */
    public function show_emergency_shutdown_notice() {
        $shutdown_info = get_option('gd_chatbot_emergency_shutdown');
        
        if (!$shutdown_info) {
            return;
        }
        
        ?>
        <div class="notice notice-error">
            <h3><?php _e('GD Claude Chatbot - Emergency Shutdown', 'gd-claude-chatbot'); ?></h3>
            <p><strong><?php _e('The plugin was automatically deactivated due to repeated fatal errors.', 'gd-claude-chatbot'); ?></strong></p>
            <p><?php _e('This is a safety feature to prevent your site from crashing.', 'gd-claude-chatbot'); ?></p>
            
            <p><strong><?php _e('Reason:', 'gd-claude-chatbot'); ?></strong> 
                <?php echo esc_html($shutdown_info['reason']); ?>
            </p>
            
            <?php
            $fatal_error = get_option('gd_chatbot_fatal_error');
            if ($fatal_error) :
            ?>
                <p><strong><?php _e('Last error:', 'gd-claude-chatbot'); ?></strong></p>
                <p>
                    <code><?php echo esc_html($fatal_error['message']); ?></code><br>
                    <small><?php echo esc_html($fatal_error['file']); ?> 
                    (line <?php echo esc_html($fatal_error['line']); ?>)</small>
                </p>
            <?php endif; ?>
            
            <p>
                <strong><?php _e('What to do:', 'gd-claude-chatbot'); ?></strong>
            </p>
            <ol style="margin-left: 20px;">
                <li><?php _e('Check your error logs for detailed information', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Verify all plugin files are intact', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Reinstall the plugin if necessary', 'gd-claude-chatbot'); ?></li>
                <li><?php _e('Contact support if the issue persists', 'gd-claude-chatbot'); ?></li>
            </ol>
            
            <p>
                <a href="#" onclick="jQuery(this).closest('.notice').fadeOut(); return false;" class="button">
                    <?php _e('Dismiss', 'gd-claude-chatbot'); ?>
                </a>
            </p>
        </div>
        <?php
        
        // Don't clear this - let user manually dismiss
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        GD_Chatbot_Diagnostic_Logger::log('Starting database table creation...');
        
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
        
        GD_Chatbot_Diagnostic_Logger::log("Target table: $table_name");
        GD_Chatbot_Diagnostic_Logger::log("Charset collate: $charset_collate");
        
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
        
        GD_Chatbot_Diagnostic_Logger::log('Loading wp-admin/includes/upgrade.php...');
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        GD_Chatbot_Diagnostic_Logger::log('Running dbDelta()...');
        $result = dbDelta($sql);
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        GD_Chatbot_Diagnostic_Logger::log("Table '$table_name' exists: " . ($table_exists ? 'YES' : 'NO'));
        
        if (!empty($result)) {
            GD_Chatbot_Diagnostic_Logger::log('dbDelta result: ' . print_r($result, true));
        }
        
        // Check for database errors
        if ($wpdb->last_error) {
            GD_Chatbot_Diagnostic_Logger::log('Database error: ' . $wpdb->last_error, 'ERROR');
            return false;
        }
        
        GD_Chatbot_Diagnostic_Logger::log('Database table creation completed');
        return true;
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Reset error counter on manual deactivation
        delete_option('gd_chatbot_error_count');
        delete_option('gd_chatbot_emergency_shutdown');
        delete_option('gd_chatbot_fatal_error');
        
        flush_rewrite_rules();
    }
    
    /**
     * Handle shutdown and catch fatal errors
     * LAYER 4: Automatic Recovery
     */
    public function handle_shutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            // Log ALL fatal errors for diagnostics
            GD_Chatbot_Diagnostic_Logger::log(sprintf(
                'FATAL ERROR DETECTED: %s in %s on line %d',
                $error['message'],
                $error['file'],
                $error['line']
            ), 'FATAL');
            
            // Check if error is from our plugin
            if (strpos($error['file'], GD_CHATBOT_PLUGIN_DIR) !== false) {
                $this->handle_fatal_error($error);
            } else {
                // Log that the error is from another source
                GD_Chatbot_Diagnostic_Logger::log('Fatal error is from external source, not from GD Chatbot plugin');
            }
        }
    }
    
    /**
     * Handle fatal error from plugin
     * LAYER 4: Automatic Recovery
     */
    private function handle_fatal_error($error) {
        GD_Chatbot_Diagnostic_Logger::log(sprintf(
            'PLUGIN FATAL ERROR: %s in %s on line %d',
            $error['message'],
            $error['file'],
            $error['line']
        ), 'FATAL');
        
        // Store error
        update_option('gd_chatbot_fatal_error', array(
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'time' => self::safe_current_time()
        ));
        
        // Send email report for fatal errors
        GD_Chatbot_Diagnostic_Logger::send_email_report('FATAL ERROR');
        
        // Increment error counter
        $error_count = get_option('gd_chatbot_error_count', 0);
        update_option('gd_chatbot_error_count', $error_count + 1);
        
        // Auto-disable after 3 errors
        if ($error_count >= 2) { // This is the 3rd error (0, 1, 2)
            deactivate_plugins(plugin_basename(__FILE__));
            
            update_option('gd_chatbot_emergency_shutdown', array(
                'reason' => 'repeated_fatal_errors',
                'time' => self::safe_current_time(),
                'error_count' => $error_count + 1
            ));
            
            error_log('GD Chatbot: Emergency shutdown - plugin auto-disabled after repeated fatal errors');
        }
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
     * Clear Tavily cache
     */
    public function clear_tavily_cache() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        check_ajax_referer('gd_chatbot_admin_nonce', 'nonce');
        
        $tavily = new GD_Tavily_API();
        $result = $tavily->clear_cache();
        
        if ($result) {
            wp_send_json_success(array('message' => 'Cache cleared successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to clear cache'));
        }
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

### CRITICAL: Web Search Results Context
**IMPORTANT:** When web search results are provided:
- ALL search results are SPECIFICALLY about the Grateful Dead band
- NEVER mention or reference other artists/bands from search results
- If search results seem to include other artists, IGNORE that information completely
- ONLY use information that is directly related to the Grateful Dead
- The search system has been configured to ONLY return Grateful Dead-related content
- If you see mentions of other artists in results, it means the search failed - acknowledge this and provide information from your knowledge base instead

### Web Search Behavior
**CRITICAL:** The Tavily search system automatically searches these credible sites ON BEHALF of the user:
- Archive.org (audio files and durations)
- JerryBase.com (setlists and performance notes)
- GratefulStats.com (statistical breakdowns)
- HerbiBot.com (advanced search features)
- Plus 60+ other trusted Grateful Dead sources

**NEVER tell users to:**
- "Check Archive.org" or "Browse Archive.org"
- "Search JerryBase.com" or "Look at JerryBase"
- "Visit GratefulStats" or "Check HerbiBot"

**INSTEAD, say:**
- "Based on the search results from Archive.org..." (if results are provided)
- "The information from JerryBase shows..." (if results are provided)
- "I don't have specific information about that" (if no results)

### Content Priority
**ALWAYS prioritize in this order:**
1. **Knowledge Base** - Our comprehensive GD archive (band members, songs, performances, gear)
2. **Setlist Database** - Our internal 2,388-show database
3. **Web Search** - Only for current info, external resources, or specific data lookups

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
} // End class_exists check for GD_Claude_Chatbot

/**
 * Initialize plugin with error handling
 * LAYER 5: User Notification System
 */
if (!function_exists('gd_claude_chatbot_init')) {
function gd_claude_chatbot_init() {
    // Initialize logger if not already done (for non-activation loads)
    GD_Chatbot_Diagnostic_Logger::init();
    GD_Chatbot_Diagnostic_Logger::log('=== gd_claude_chatbot_init() STARTED (plugins_loaded hook) ===');
    
    try {
        // Check if emergency shutdown occurred
        $shutdown_info = get_option('gd_chatbot_emergency_shutdown');
        if ($shutdown_info) {
            GD_Chatbot_Diagnostic_Logger::log('Emergency shutdown detected from previous run');
            add_action('admin_notices', function() {
                $plugin = GD_Claude_Chatbot::get_instance();
                $plugin->show_emergency_shutdown_notice();
            });
        }
        
        GD_Chatbot_Diagnostic_Logger::log('Creating plugin instance via get_instance()...');
        $instance = GD_Claude_Chatbot::get_instance();
        GD_Chatbot_Diagnostic_Logger::log('Plugin instance created successfully');
        
        return $instance;
    } catch (Exception $e) {
        GD_Chatbot_Diagnostic_Logger::log_error('Initialization Exception in gd_claude_chatbot_init()', $e);
        GD_Chatbot_Diagnostic_Logger::send_email_report('INIT EXCEPTION');
        
        // Show admin notice
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <h3><?php _e('GD Claude Chatbot - Initialization Failed', 'gd-claude-chatbot'); ?></h3>
                <p><strong><?php echo esc_html($e->getMessage()); ?></strong></p>
                <p><?php _e('The plugin could not initialize properly. Please check your error logs.', 'gd-claude-chatbot'); ?></p>
            </div>
            <?php
        });
        
        return null;
    } catch (Error $e) {
        GD_Chatbot_Diagnostic_Logger::log_error('Initialization PHP Error in gd_claude_chatbot_init(): ' . $e->getMessage());
        GD_Chatbot_Diagnostic_Logger::send_email_report('INIT PHP ERROR');
        
        // Show admin notice
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <h3><?php _e('GD Claude Chatbot - PHP Error', 'gd-claude-chatbot'); ?></h3>
                <p><strong><?php echo esc_html($e->getMessage()); ?></strong></p>
                <p><?php _e('A PHP error occurred during initialization. Please check your error logs.', 'gd-claude-chatbot'); ?></p>
            </div>
            <?php
        });
        
        return null;
    }
}
} // End function_exists check for gd_claude_chatbot_init

// Register activation hook BEFORE plugins_loaded
// This must be done at the top level, not inside a hook
register_activation_hook(__FILE__, 'gd_claude_chatbot_activate');
register_deactivation_hook(__FILE__, 'gd_claude_chatbot_deactivate');

/**
 * Plugin activation function (called before plugins_loaded)
 */
if (!function_exists('gd_claude_chatbot_activate')) {
function gd_claude_chatbot_activate() {
    // Initialize diagnostic logger for activation
    GD_Chatbot_Diagnostic_Logger::init();
    GD_Chatbot_Diagnostic_Logger::log('=== PLUGIN ACTIVATION STARTED ===');
    
    try {
        // Create instance and run activation
        GD_Chatbot_Diagnostic_Logger::log('Getting plugin instance...');
        $plugin = GD_Claude_Chatbot::get_instance();
        
        if ($plugin) {
            GD_Chatbot_Diagnostic_Logger::log('Plugin instance created, running activate()...');
            $plugin->activate();
            GD_Chatbot_Diagnostic_Logger::log('activate() completed');
            
            // Flag to send success email after init_components runs
            update_option('gd_chatbot_send_activation_email', true);
        } else {
            GD_Chatbot_Diagnostic_Logger::log('FAILED: Plugin instance is null', 'ERROR');
            GD_Chatbot_Diagnostic_Logger::send_email_report('ACTIVATION FAILED - NULL INSTANCE');
        }
        
        GD_Chatbot_Diagnostic_Logger::log('=== PLUGIN ACTIVATION COMPLETED ===');
        
    } catch (Exception $e) {
        GD_Chatbot_Diagnostic_Logger::log_error('Activation Exception', $e);
        GD_Chatbot_Diagnostic_Logger::send_email_report('ACTIVATION EXCEPTION');
        throw $e; // Re-throw to let WordPress handle it
    } catch (Error $e) {
        GD_Chatbot_Diagnostic_Logger::log_error('Activation PHP Error: ' . $e->getMessage());
        GD_Chatbot_Diagnostic_Logger::send_email_report('ACTIVATION PHP ERROR');
        throw $e; // Re-throw to let WordPress handle it
    }
}
} // End function_exists check for gd_claude_chatbot_activate

/**
 * Plugin deactivation function
 */
if (!function_exists('gd_claude_chatbot_deactivate')) {
function gd_claude_chatbot_deactivate() {
    $plugin = GD_Claude_Chatbot::get_instance();
    if ($plugin) {
        $plugin->deactivate();
    }
}
} // End function_exists check for gd_claude_chatbot_deactivate

// Start the plugin with error handling
add_action('plugins_loaded', 'gd_claude_chatbot_init');

/**
 * Helper function to get plugin instance
 */
if (!function_exists('gd_claude_chatbot')) {
function gd_claude_chatbot() {
    return GD_Claude_Chatbot::get_instance();
}
}