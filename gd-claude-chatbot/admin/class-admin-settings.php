<?php
/**
 * Admin Settings Class
 * 
 * Handles the WordPress admin interface for plugin configuration
 * 
 * @package GD_Claude_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Chatbot_Admin_Settings {
    
    /**
     * Option prefix
     */
    const OPTION_PREFIX = 'gd_chatbot_';
    
    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'gd-claude-chatbot';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            'GD Claude Chatbot',
            'GD Chatbot',
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            self::PAGE_SLUG,
            'Settings',
            'Settings',
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            self::PAGE_SLUG,
            'Analytics',
            'Analytics',
            'manage_options',
            self::PAGE_SLUG . '-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            self::PAGE_SLUG,
            'Conversations',
            'Conversations',
            'manage_options',
            self::PAGE_SLUG . '-conversations',
            array($this, 'render_conversations_page')
        );
    }
    
    /**
     * Register all settings
     */
    public function register_settings() {
        // Claude Settings
        $claude_settings = array(
            'claude_api_key',
            'claude_model',
            'claude_max_tokens',
            'claude_temperature',
            'claude_system_prompt'
        );
        
        foreach ($claude_settings as $setting) {
            register_setting('gd_chatbot_claude', self::OPTION_PREFIX . $setting, array(
                'sanitize_callback' => array($this, 'sanitize_' . $setting)
            ));
        }
        
        // Tavily Settings
        $tavily_settings = array(
            'tavily_enabled',
            'tavily_api_key',
            'tavily_search_depth',
            'tavily_max_results',
            'tavily_include_domains',
            'tavily_exclude_domains',
            'tavily_quota'
        );
        
        foreach ($tavily_settings as $setting) {
            if ($setting === 'tavily_api_key') {
                register_setting('gd_chatbot_tavily', self::OPTION_PREFIX . $setting, array(
                    'sanitize_callback' => array($this, 'sanitize_tavily_api_key')
                ));
            } else {
                register_setting('gd_chatbot_tavily', self::OPTION_PREFIX . $setting);
            }
        }
        
        // Pinecone Settings
        $pinecone_settings = array(
            'pinecone_enabled',
            'pinecone_api_key',
            'pinecone_host',
            'pinecone_index_name',
            'pinecone_namespace',
            'pinecone_top_k',
            'embedding_api_key',
            'embedding_model'
        );
        
        foreach ($pinecone_settings as $setting) {
            register_setting('gd_chatbot_pinecone', self::OPTION_PREFIX . $setting);
        }
        
        // Knowledgebase Loader Settings
        $kb_settings = array(
            'kb_enabled',
            'kb_max_results',
            'kb_min_score'
        );
        
        foreach ($kb_settings as $setting) {
            register_setting('gd_chatbot_kb', self::OPTION_PREFIX . $setting);
        }
        
        // Appearance Settings
        $appearance_settings = array(
            'chatbot_title',
            'chatbot_welcome_message',
            'chatbot_placeholder',
            'chatbot_primary_color',
            'chatbot_position',
            'chatbot_width',
            'chatbot_height'
        );
        
        foreach ($appearance_settings as $setting) {
            register_setting('gd_chatbot_appearance', self::OPTION_PREFIX . $setting);
        }
    }
    
    /**
     * Sanitize API key
     */
    public function sanitize_claude_api_key($value) {
        return sanitize_text_field($value);
    }
    
    /**
     * Sanitize model selection
     */
    public function sanitize_claude_model($value) {
        $valid_models = array_keys(GD_Claude_API::get_available_models());
        return in_array($value, $valid_models) ? $value : 'claude-sonnet-4-20250514';
    }
    
    /**
     * Sanitize max tokens
     */
    public function sanitize_claude_max_tokens($value) {
        $value = (int) $value;
        return max(100, min(100000, $value));
    }
    
    /**
     * Sanitize temperature
     */
    public function sanitize_claude_temperature($value) {
        $value = (float) $value;
        return max(0, min(1, $value));
    }
    
    /**
     * Sanitize system prompt
     */
    public function sanitize_claude_system_prompt($value) {
        return wp_kses_post($value);
    }
    
    /**
     * Sanitize Tavily API key
     */
    public function sanitize_tavily_api_key($value) {
        if (empty($value)) {
            return '';
        }
        
        // If it's already masked, don't update
        if (strpos($value, '****') !== false) {
            return get_option('gd_chatbot_tavily_api_key');
        }
        
        // Encrypt and save
        $tavily = new GD_Tavily_API();
        $tavily->save_api_key($value);
        
        return get_option('gd_chatbot_tavily_api_key');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, self::PAGE_SLUG) === false) {
            return;
        }
        
        wp_enqueue_style(
            'gd-chatbot-admin',
            GD_CHATBOT_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            GD_CHATBOT_VERSION
        );
        
        wp_enqueue_script(
            'gd-chatbot-admin',
            GD_CHATBOT_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery'),
            GD_CHATBOT_VERSION,
            true
        );
        
        wp_localize_script('gd-chatbot-admin', 'gdChatbotAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gd_chatbot_admin_nonce')
        ));
    }
    
    /**
     * Render the main settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'claude';
        ?>
        <div class="wrap gd-chatbot-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=<?php echo self::PAGE_SLUG; ?>&tab=claude" 
                   class="nav-tab <?php echo $active_tab === 'claude' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-cloud"></span> Claude API
                </a>
                <a href="?page=<?php echo self::PAGE_SLUG; ?>&tab=tavily" 
                   class="nav-tab <?php echo $active_tab === 'tavily' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-search"></span> Tavily Search
                </a>
                <a href="?page=<?php echo self::PAGE_SLUG; ?>&tab=pinecone" 
                   class="nav-tab <?php echo $active_tab === 'pinecone' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-database"></span> Pinecone
                </a>
                <a href="?page=<?php echo self::PAGE_SLUG; ?>&tab=knowledgebase" 
                   class="nav-tab <?php echo $active_tab === 'knowledgebase' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-book-alt"></span> Knowledgebase
                </a>
                <a href="?page=<?php echo self::PAGE_SLUG; ?>&tab=appearance" 
                   class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-appearance"></span> Appearance
                </a>
                <a href="?page=<?php echo self::PAGE_SLUG; ?>&tab=shortcode" 
                   class="nav-tab <?php echo $active_tab === 'shortcode' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-shortcode"></span> Shortcode
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'tavily':
                        $this->render_tavily_settings();
                        break;
                    case 'pinecone':
                        $this->render_pinecone_settings();
                        break;
                    case 'knowledgebase':
                        $this->render_knowledgebase_settings();
                        break;
                    case 'appearance':
                        $this->render_appearance_settings();
                        break;
                    case 'shortcode':
                        $this->render_shortcode_info();
                        break;
                    default:
                        $this->render_claude_settings();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Claude settings tab
     */
    private function render_claude_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('gd_chatbot_claude'); ?>
            
            <div class="iti-settings-section">
                <h2><span class="dashicons dashicons-admin-network"></span> Claude API Configuration</h2>
                <p class="description">Configure your Anthropic Claude API settings. You can obtain an API key from 
                    <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="claude_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="claude_api_key" 
                                   name="<?php echo self::OPTION_PREFIX; ?>claude_api_key" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'claude_api_key')); ?>"
                                   class="regular-text"
                                   autocomplete="off">
                            <button type="button" class="button toggle-password" data-target="claude_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" class="button test-connection" data-api="claude">
                                Test Connection
                            </button>
                            <span class="connection-status"></span>
                            <p class="description">Your Anthropic API key (starts with sk-ant-)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="claude_model">Model</label>
                        </th>
                        <td>
                            <?php $current_model = get_option(self::OPTION_PREFIX . 'claude_model', 'claude-sonnet-4-20250514'); ?>
                            <select id="claude_model" name="<?php echo self::OPTION_PREFIX; ?>claude_model" class="model-select">
                                <optgroup label="🚀 Claude 4 (Latest)">
                                    <option value="claude-opus-4-20250514" <?php selected($current_model, 'claude-opus-4-20250514'); ?>>
                                        Claude Opus 4 — Most Capable, Best for Complex Tasks
                                    </option>
                                    <option value="claude-sonnet-4-20250514" <?php selected($current_model, 'claude-sonnet-4-20250514'); ?>>
                                        Claude Sonnet 4 — Balanced Performance (Recommended)
                                    </option>
                                </optgroup>
                                <optgroup label="⚡ Claude 3.5">
                                    <option value="claude-3-5-sonnet-20241022" <?php selected($current_model, 'claude-3-5-sonnet-20241022'); ?>>
                                        Claude 3.5 Sonnet — Strong Performance
                                    </option>
                                    <option value="claude-3-5-haiku-20241022" <?php selected($current_model, 'claude-3-5-haiku-20241022'); ?>>
                                        Claude 3.5 Haiku — Fast & Efficient
                                    </option>
                                </optgroup>
                                <optgroup label="📦 Claude 3 (Legacy)">
                                    <option value="claude-3-opus-20240229" <?php selected($current_model, 'claude-3-opus-20240229'); ?>>
                                        Claude 3 Opus — Previous Gen Most Capable
                                    </option>
                                    <option value="claude-3-sonnet-20240229" <?php selected($current_model, 'claude-3-sonnet-20240229'); ?>>
                                        Claude 3 Sonnet — Previous Gen Balanced
                                    </option>
                                    <option value="claude-3-haiku-20240307" <?php selected($current_model, 'claude-3-haiku-20240307'); ?>>
                                        Claude 3 Haiku — Previous Gen Fast
                                    </option>
                                </optgroup>
                            </select>
                            <div id="model-info" class="model-info-box">
                                <?php 
                                $model_info = GD_Claude_API::get_model_info($current_model);
                                if ($model_info) :
                                    $is_opus = GD_Claude_API::is_opus_model($current_model);
                                ?>
                                    <div class="model-details <?php echo $is_opus ? 'opus-model' : ''; ?>">
                                        <?php if ($is_opus) : ?>
                                            <span class="opus-badge">⭐ OPUS</span>
                                        <?php endif; ?>
                                        <p><strong><?php echo esc_html($model_info['name']); ?></strong></p>
                                        <p><?php echo esc_html($model_info['description']); ?></p>
                                        <p class="model-specs">
                                            Context: <?php echo number_format($model_info['context_window']); ?> tokens | 
                                            Max Output: <?php echo number_format($model_info['max_output']); ?> tokens
                                        </p>
                                        <p class="model-best-for">
                                            <strong>Best for:</strong> <?php echo esc_html(implode(', ', $model_info['best_for'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="description">
                                <strong>Opus models</strong> are the most capable for complex reasoning, research, and analysis.
                                <strong>Sonnet models</strong> offer excellent balance of capability and speed.
                                <strong>Haiku models</strong> are fastest for quick, simple tasks.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="claude_max_tokens">Max Tokens</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="claude_max_tokens" 
                                   name="<?php echo self::OPTION_PREFIX; ?>claude_max_tokens" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'claude_max_tokens', 4096)); ?>"
                                   min="100"
                                   max="100000"
                                   class="small-text">
                            <p class="description">Maximum tokens for Claude's response (100-100,000).</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="claude_temperature">Temperature</label>
                        </th>
                        <td>
                            <input type="range" 
                                   id="claude_temperature" 
                                   name="<?php echo self::OPTION_PREFIX; ?>claude_temperature" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'claude_temperature', 0.7)); ?>"
                                   min="0"
                                   max="1"
                                   step="0.1"
                                   class="temperature-slider">
                            <span class="temperature-value"><?php echo esc_html(get_option(self::OPTION_PREFIX . 'claude_temperature', 0.7)); ?></span>
                            <p class="description">Controls randomness (0 = deterministic, 1 = creative).</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="iti-settings-section">
                <h2><span class="dashicons dashicons-edit"></span> System Prompt</h2>
                <p class="description">Define how Claude should behave and respond. This is sent with every conversation.</p>
                
                <table class="form-table">
                    <tr>
                        <td colspan="2">
                            <textarea id="claude_system_prompt" 
                                      name="<?php echo self::OPTION_PREFIX; ?>claude_system_prompt" 
                                      rows="20" 
                                      class="large-text code"><?php echo esc_textarea(get_option(self::OPTION_PREFIX . 'claude_system_prompt')); ?></textarea>
                            <p class="description">
                                <strong>Tip:</strong> Include persona, tone, expertise areas, and response formatting guidelines.
                                Supports Markdown formatting.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Save Claude Settings'); ?>
        </form>
        <?php
    }
    
    /**
     * Render Tavily settings tab
     */
    private function render_tavily_settings() {
        $tavily = new GD_Tavily_API();
        $usage = $tavily->get_usage();
        $quota = get_option('gd_chatbot_tavily_quota', 1000);
        $percentage = $quota > 0 ? ($usage / $quota) * 100 : 0;
        $cache_stats = $tavily->get_cache_stats();
        
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('gd_chatbot_tavily'); ?>
            
            <div class="iti-settings-section">
                <h2><span class="dashicons dashicons-search"></span> Tavily Search Configuration</h2>
                <p class="description">Configure Tavily for real-time web search capabilities. Get your API key from 
                    <a href="https://tavily.com/" target="_blank">tavily.com</a>.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Tavily</th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" 
                                       name="<?php echo self::OPTION_PREFIX; ?>tavily_enabled" 
                                       value="1"
                                       <?php checked(get_option(self::OPTION_PREFIX . 'tavily_enabled'), 1); ?>>
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label">Enable web search for enhanced responses</span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tavily_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="tavily_api_key" 
                                   name="<?php echo self::OPTION_PREFIX; ?>tavily_api_key" 
                                   value="<?php echo esc_attr($tavily->get_api_key_masked()); ?>"
                                   class="regular-text"
                                   autocomplete="off">
                            <button type="button" class="button toggle-password" data-target="tavily_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" class="button test-connection" data-api="tavily" id="test-tavily-connection">
                                Test Connection
                            </button>
                            <span class="connection-status" id="tavily-test-result"></span>
                            <p class="description">
                                <a href="https://tavily.com/signup" target="_blank">Get your API key</a> | 
                                API keys are encrypted for security
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tavily_search_depth">Search Depth</label>
                        </th>
                        <td>
                            <select id="tavily_search_depth" name="<?php echo self::OPTION_PREFIX; ?>tavily_search_depth">
                                <?php foreach (GD_Tavily_API::get_search_depth_options() as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected(get_option(self::OPTION_PREFIX . 'tavily_search_depth'), $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Advanced search takes longer but provides more comprehensive results.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tavily_max_results">Max Results</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="tavily_max_results" 
                                   name="<?php echo self::OPTION_PREFIX; ?>tavily_max_results" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'tavily_max_results', 5)); ?>"
                                   min="1"
                                   max="20"
                                   class="small-text">
                            <p class="description">Number of search results to include (1-20).</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tavily_include_domains">Include Domains</label>
                        </th>
                        <td>
                            <textarea id="tavily_include_domains" 
                                      name="<?php echo self::OPTION_PREFIX; ?>tavily_include_domains" 
                                      rows="4" 
                                      class="large-text"
                                      placeholder="example.com, trusted-source.org"><?php echo esc_textarea(get_option(self::OPTION_PREFIX . 'tavily_include_domains')); ?></textarea>
                            <p class="description">Comma-separated list of domains to prioritize in searches. Leave empty for all domains.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tavily_exclude_domains">Exclude Domains</label>
                        </th>
                        <td>
                            <textarea id="tavily_exclude_domains" 
                                      name="<?php echo self::OPTION_PREFIX; ?>tavily_exclude_domains" 
                                      rows="4" 
                                      class="large-text"
                                      placeholder="wikipedia.org, reddit.com"><?php echo esc_textarea(get_option(self::OPTION_PREFIX . 'tavily_exclude_domains')); ?></textarea>
                            <p class="description">Comma-separated list of domains to exclude from searches.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tavily_quota">Monthly Quota</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="tavily_quota" 
                                   name="<?php echo self::OPTION_PREFIX; ?>tavily_quota" 
                                   value="<?php echo esc_attr($quota); ?>"
                                   min="100"
                                   class="regular-text">
                            <p class="description">Your Tavily plan limit (Free: 1000, Pro: 10000+)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Usage This Month</th>
                        <td>
                            <div style="background: #f0f0f0; border-radius: 4px; height: 30px; position: relative; margin-bottom: 10px;">
                                <div style="background: <?php echo $percentage > 80 ? '#dc3232' : '#46b450'; ?>; 
                                            height: 100%; 
                                            border-radius: 4px; 
                                            width: <?php echo min(100, $percentage); ?>%; 
                                            transition: width 0.3s ease;"></div>
                            </div>
                            <p>
                                <strong><?php echo number_format($usage); ?></strong> / <?php echo number_format($quota); ?> 
                                (<?php echo number_format($percentage, 1); ?>%)
                            </p>
                            <?php if ($percentage > 80): ?>
                                <p class="description" style="color: #dc3232;">
                                    <span class="dashicons dashicons-warning"></span>
                                    Warning: You've used over 80% of your monthly quota. Consider upgrading your plan.
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Cache Statistics</th>
                        <td>
                            <p>
                                <strong>Cached Queries:</strong> <?php echo number_format($cache_stats['count']); ?><br>
                                <strong>Cache Size:</strong> <?php echo $cache_stats['size_formatted']; ?>
                            </p>
                            <button type="button" class="button" id="clear-tavily-cache">
                                <span class="dashicons dashicons-trash"></span> Clear Cache
                            </button>
                            <span id="cache-clear-result"></span>
                            <p class="description">Clearing cache will force fresh API calls for all queries.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Save Tavily Settings'); ?>
        </form>
        <?php
    }
    
    /**
     * Render Pinecone settings tab
     */
    private function render_pinecone_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('gd_chatbot_pinecone'); ?>
            
            <div class="iti-settings-section">
                <h2><span class="dashicons dashicons-database"></span> Pinecone Vector Database Configuration</h2>
                <p class="description">Configure Pinecone for knowledge base retrieval (RAG). Set up your index at 
                    <a href="https://www.pinecone.io/" target="_blank">pinecone.io</a>.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Pinecone</th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" 
                                       name="<?php echo self::OPTION_PREFIX; ?>pinecone_enabled" 
                                       value="1"
                                       <?php checked(get_option(self::OPTION_PREFIX . 'pinecone_enabled'), 1); ?>>
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label">Enable knowledge base retrieval</span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pinecone_api_key">Pinecone API Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="pinecone_api_key" 
                                   name="<?php echo self::OPTION_PREFIX; ?>pinecone_api_key" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'pinecone_api_key')); ?>"
                                   class="regular-text"
                                   autocomplete="off">
                            <button type="button" class="button toggle-password" data-target="pinecone_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pinecone_host">Index Host URL</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="pinecone_host" 
                                   name="<?php echo self::OPTION_PREFIX; ?>pinecone_host" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'pinecone_host')); ?>"
                                   class="regular-text"
                                   placeholder="https://your-index-xxxxx.svc.environment.pinecone.io">
                            <button type="button" class="button test-connection" data-api="pinecone">
                                Test Connection
                            </button>
                            <span class="connection-status"></span>
                            <p class="description">Your Pinecone index endpoint URL.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pinecone_index_name">Index Name</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="pinecone_index_name" 
                                   name="<?php echo self::OPTION_PREFIX; ?>pinecone_index_name" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'pinecone_index_name')); ?>"
                                   class="regular-text">
                            <p class="description">The name of your Pinecone index.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pinecone_namespace">Namespace</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="pinecone_namespace" 
                                   name="<?php echo self::OPTION_PREFIX; ?>pinecone_namespace" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'pinecone_namespace')); ?>"
                                   class="regular-text"
                                   placeholder="Optional">
                            <p class="description">Optional namespace within the index.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pinecone_top_k">Results Count</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="pinecone_top_k" 
                                   name="<?php echo self::OPTION_PREFIX; ?>pinecone_top_k" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'pinecone_top_k', 5)); ?>"
                                   min="1"
                                   max="20"
                                   class="small-text">
                            <p class="description">Number of relevant documents to retrieve (1-20).</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="iti-settings-section">
                <h2><span class="dashicons dashicons-chart-line"></span> Embedding Configuration</h2>
                <p class="description">Configure the embedding model used to convert text to vectors for Pinecone queries.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="embedding_api_key">OpenAI API Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="embedding_api_key" 
                                   name="<?php echo self::OPTION_PREFIX; ?>embedding_api_key" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'embedding_api_key')); ?>"
                                   class="regular-text"
                                   autocomplete="off">
                            <button type="button" class="button toggle-password" data-target="embedding_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <p class="description">OpenAI API key for generating embeddings. Get yours at 
                                <a href="https://platform.openai.com/" target="_blank">platform.openai.com</a>.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="embedding_model">Embedding Model</label>
                        </th>
                        <td>
                            <select id="embedding_model" name="<?php echo self::OPTION_PREFIX; ?>embedding_model">
                                <?php foreach (GD_Pinecone_API::get_embedding_models() as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected(get_option(self::OPTION_PREFIX . 'embedding_model'), $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Must match the embedding model used when creating your Pinecone index.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Save Pinecone Settings'); ?>
        </form>
        <?php
    }
    
    /**
     * Render Knowledgebase settings tab
     */
    private function render_knowledgebase_settings() {
        // Check if KB Loader plugin is installed
        $kb_available = function_exists('gd_kb_get_api');
        $kb_ready = false;
        $kb_stats = null;
        
        if ($kb_available) {
            $kb_api = gd_kb_get_api();
            $kb_ready = $kb_api->is_ready();
            if ($kb_ready) {
                $kb_stats = $kb_api->get_stats();
            }
        }
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('gd_chatbot_kb'); ?>
            
            <div class="iti-settings-section">
                <h2><span class="dashicons dashicons-book-alt"></span> GD Knowledgebase Loader Integration</h2>
                <p class="description">Integrate with the GD Knowledgebase Loader plugin to provide context from your uploaded documents.</p>
                
                <?php if (!$kb_available): ?>
                    <div class="notice notice-warning inline">
                        <p><strong>GD Knowledgebase Loader Not Installed</strong></p>
                        <p>The GD Knowledgebase Loader plugin is not installed. Install and activate it to use this feature.</p>
                        <p><a href="<?php echo admin_url('plugins.php'); ?>" class="button">Go to Plugins</a></p>
                    </div>
                <?php elseif (!$kb_ready): ?>
                    <div class="notice notice-warning inline">
                        <p><strong>Knowledgebase Not Configured</strong></p>
                        <p>The GD Knowledgebase Loader plugin is installed but not configured. Please configure your API keys.</p>
                        <p><a href="<?php echo admin_url('admin.php?page=gd-kb-loader-settings'); ?>" class="button">Configure Knowledgebase</a></p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-success inline">
                        <p><strong>✓ Knowledgebase Ready</strong></p>
                        <?php if ($kb_stats): ?>
                            <p>
                                <strong><?php echo esc_html($kb_stats['total_documents']); ?></strong> documents | 
                                <strong><?php echo esc_html($kb_stats['total_chunks']); ?></strong> chunks | 
                                <strong><?php echo esc_html($kb_stats['processed_documents']); ?></strong> processed
                            </p>
                        <?php endif; ?>
                        <p><a href="<?php echo admin_url('admin.php?page=gd-kb-loader'); ?>" class="button">Manage Knowledgebase</a></p>
                    </div>
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Knowledgebase</th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" 
                                       name="<?php echo self::OPTION_PREFIX; ?>kb_enabled" 
                                       value="1"
                                       <?php checked(get_option(self::OPTION_PREFIX . 'kb_enabled', true), 1); ?>
                                       <?php disabled(!$kb_ready); ?>>
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label">Use knowledgebase for context</span>
                            <?php if (!$kb_ready): ?>
                                <p class="description" style="color: #d63638;">Knowledgebase must be configured first.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="kb_max_results">Maximum Results</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="kb_max_results" 
                                   name="<?php echo self::OPTION_PREFIX; ?>kb_max_results" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'kb_max_results', 10)); ?>"
                                   min="1"
                                   max="50"
                                   class="small-text">
                            <p class="description">Number of relevant chunks to retrieve (1-50, recommended: 5-15)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="kb_min_score">Minimum Relevance Score</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="kb_min_score" 
                                   name="<?php echo self::OPTION_PREFIX; ?>kb_min_score" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'kb_min_score', 0.35)); ?>"
                                   min="0"
                                   max="1"
                                   step="0.05"
                                   class="small-text">
                            <p class="description">Minimum similarity score (0-1, recommended: 0.30-0.40)</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="iti-settings-section">
                <h3>How It Works</h3>
                <ol>
                    <li><strong>Upload Documents:</strong> Use the KB Loader plugin to upload your knowledge base documents (PDF, DOCX, MD, CSV, JSON, XLSX)</li>
                    <li><strong>Automatic Processing:</strong> Documents are automatically chunked and converted to embeddings</li>
                    <li><strong>Semantic Search:</strong> When users ask questions, relevant chunks are retrieved based on similarity</li>
                    <li><strong>Context to Claude:</strong> Retrieved chunks are added to Claude's context for accurate, informed responses</li>
                </ol>
                
                <h3>Benefits</h3>
                <ul>
                    <li>✓ Provide accurate answers from your own documents</li>
                    <li>✓ No need to manually update system prompts</li>
                    <li>✓ Automatic relevance filtering</li>
                    <li>✓ Source attribution in responses</li>
                    <li>✓ Works alongside Pinecone and Tavily</li>
                </ul>
            </div>
            
            <?php submit_button('Save Knowledgebase Settings'); ?>
        </form>
        <?php
    }
    
    /**
     * Render Appearance settings tab
     */
    private function render_appearance_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('gd_chatbot_appearance'); ?>
            
            <div class="iti-settings-section">
                <h2><span class="dashicons dashicons-admin-appearance"></span> Chatbot Appearance</h2>
                <p class="description">Customize how the chatbot looks and feels on your website.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chatbot_title">Chatbot Title</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="chatbot_title" 
                                   name="<?php echo self::OPTION_PREFIX; ?>chatbot_title" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'chatbot_title', 'GD Assistant')); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chatbot_welcome_message">Welcome Message</label>
                        </th>
                        <td>
                            <textarea id="chatbot_welcome_message" 
                                      name="<?php echo self::OPTION_PREFIX; ?>chatbot_welcome_message" 
                                      rows="3" 
                                      class="large-text"><?php echo esc_textarea(get_option(self::OPTION_PREFIX . 'chatbot_welcome_message', 'Hello! I\'m your AI assistant. How can I help you today?')); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chatbot_placeholder">Input Placeholder</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="chatbot_placeholder" 
                                   name="<?php echo self::OPTION_PREFIX; ?>chatbot_placeholder" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'chatbot_placeholder', 'Type your message...')); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chatbot_primary_color">Primary Color</label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="chatbot_primary_color" 
                                   name="<?php echo self::OPTION_PREFIX; ?>chatbot_primary_color" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'chatbot_primary_color', '#2563eb')); ?>">
                            <span class="color-preview" style="background-color: <?php echo esc_attr(get_option(self::OPTION_PREFIX . 'chatbot_primary_color', '#2563eb')); ?>"></span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chatbot_position">Position</label>
                        </th>
                        <td>
                            <select id="chatbot_position" name="<?php echo self::OPTION_PREFIX; ?>chatbot_position">
                                <option value="bottom-right" <?php selected(get_option(self::OPTION_PREFIX . 'chatbot_position'), 'bottom-right'); ?>>Bottom Right</option>
                                <option value="bottom-left" <?php selected(get_option(self::OPTION_PREFIX . 'chatbot_position'), 'bottom-left'); ?>>Bottom Left</option>
                                <option value="inline" <?php selected(get_option(self::OPTION_PREFIX . 'chatbot_position'), 'inline'); ?>>Inline (use shortcode)</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chatbot_width">Width (px)</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="chatbot_width" 
                                   name="<?php echo self::OPTION_PREFIX; ?>chatbot_width" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'chatbot_width', 400)); ?>"
                                   min="300"
                                   max="800"
                                   class="small-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chatbot_height">Height (px)</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="chatbot_height" 
                                   name="<?php echo self::OPTION_PREFIX; ?>chatbot_height" 
                                   value="<?php echo esc_attr(get_option(self::OPTION_PREFIX . 'chatbot_height', 600)); ?>"
                                   min="400"
                                   max="900"
                                   class="small-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Save Appearance Settings'); ?>
        </form>
        <?php
    }
    
    /**
     * Render Shortcode information tab
     */
    private function render_shortcode_info() {
        ?>
        <div class="iti-settings-section">
            <h2><span class="dashicons dashicons-shortcode"></span> Using the Chatbot</h2>
            
            <div class="shortcode-info-box">
                <h3>Shortcode</h3>
                <p>Add the chatbot to any page or post using this shortcode:</p>
                <code class="shortcode-display">[gd_chatbot]</code>
                <button type="button" class="button copy-shortcode" data-shortcode="[gd_chatbot]">
                    <span class="dashicons dashicons-clipboard"></span> Copy
                </button>
            </div>
            
            <div class="shortcode-info-box">
                <h3>Shortcode Attributes</h3>
                <p>Customize the chatbot using these optional attributes:</p>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Attribute</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>title</code></td>
                            <td><?php echo esc_html(get_option(self::OPTION_PREFIX . 'chatbot_title', 'GD Assistant')); ?></td>
                            <td>Custom title for this instance</td>
                        </tr>
                        <tr>
                            <td><code>welcome</code></td>
                            <td>(from settings)</td>
                            <td>Custom welcome message</td>
                        </tr>
                        <tr>
                            <td><code>width</code></td>
                            <td><?php echo esc_html(get_option(self::OPTION_PREFIX . 'chatbot_width', 400)); ?>px</td>
                            <td>Widget width in pixels</td>
                        </tr>
                        <tr>
                            <td><code>height</code></td>
                            <td><?php echo esc_html(get_option(self::OPTION_PREFIX . 'chatbot_height', 600)); ?>px</td>
                            <td>Widget height in pixels</td>
                        </tr>
                        <tr>
                            <td><code>color</code></td>
                            <td><?php echo esc_html(get_option(self::OPTION_PREFIX . 'chatbot_primary_color', '#2563eb')); ?></td>
                            <td>Primary accent color</td>
                        </tr>
                    </tbody>
                </table>
                
                <h4>Example with attributes:</h4>
                <code class="shortcode-display">[gd_chatbot title="Support Bot" width="450" height="550"]</code>
            </div>
            
            <div class="shortcode-info-box">
                <h3>PHP Function</h3>
                <p>For theme developers, you can also display the chatbot using PHP:</p>
                <pre><code>&lt;?php echo do_shortcode('[gd_chatbot]'); ?&gt;</code></pre>
                
                <p>Or use the function directly:</p>
                <pre><code>&lt;?php 
if (function_exists('gd_render_chatbot')) {
    gd_render_chatbot(array(
        'title' => 'My Assistant',
        'width' => 400,
        'height' => 600
    ));
}
?&gt;</code></pre>
            </div>
            
            <div class="shortcode-info-box">
                <h3>Floating Widget</h3>
                <p>If you've set the position to "Bottom Right" or "Bottom Left" in Appearance settings, 
                   the chatbot will automatically appear as a floating widget on all pages.</p>
                <p>To disable the floating widget and only use shortcodes, set Position to "Inline" in Appearance settings.</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $chat_handler = new GD_Chat_Handler();
        $analytics = $chat_handler->get_analytics();
        ?>
        <div class="wrap gd-chatbot-admin">
            <h1>Chatbot Analytics</h1>
            
            <div class="analytics-cards">
                <div class="analytics-card">
                    <div class="card-icon"><span class="dashicons dashicons-format-chat"></span></div>
                    <div class="card-content">
                        <span class="card-value"><?php echo number_format($analytics['total_messages']); ?></span>
                        <span class="card-label">Total Messages</span>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <div class="card-icon"><span class="dashicons dashicons-groups"></span></div>
                    <div class="card-content">
                        <span class="card-value"><?php echo number_format($analytics['unique_sessions']); ?></span>
                        <span class="card-label">Unique Sessions</span>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <div class="card-icon"><span class="dashicons dashicons-admin-users"></span></div>
                    <div class="card-content">
                        <span class="card-value"><?php echo number_format($analytics['logged_in_users']); ?></span>
                        <span class="card-label">Logged-in Users</span>
                    </div>
                </div>
            </div>
            
            <div class="analytics-chart-section">
                <h2>Daily Activity (Last 30 Days)</h2>
                <div class="daily-chart">
                    <?php if (!empty($analytics['daily_breakdown'])) : ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Messages</th>
                                    <th>Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_count = max(array_column($analytics['daily_breakdown'], 'count'));
                                foreach ($analytics['daily_breakdown'] as $day) : 
                                    $percentage = $max_count > 0 ? ($day['count'] / $max_count) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo esc_html(date('M j, Y', strtotime($day['date']))); ?></td>
                                        <td><?php echo number_format($day['count']); ?></td>
                                        <td>
                                            <div class="activity-bar" style="width: <?php echo $percentage; ?>%"></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p class="no-data">No conversation data yet. Start chatting to see analytics!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render conversations page
     */
    public function render_conversations_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
        
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $conversations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        
        $total_pages = ceil($total / $per_page);
        ?>
        <div class="wrap gd-chatbot-admin">
            <h1>Conversation History</h1>
            
            <?php if (!empty($conversations)) : ?>
                <table class="widefat conversations-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Session</th>
                            <th>User</th>
                            <th>Question</th>
                            <th>Response Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conversations as $conv) : ?>
                            <tr>
                                <td><?php echo esc_html(date('M j, Y g:i a', strtotime($conv->created_at))); ?></td>
                                <td><code><?php echo esc_html(substr($conv->session_id, 0, 8)); ?>...</code></td>
                                <td>
                                    <?php 
                                    if ($conv->user_id) {
                                        $user = get_userdata($conv->user_id);
                                        echo esc_html($user ? $user->display_name : 'User #' . $conv->user_id);
                                    } else {
                                        echo '<em>Guest</em>';
                                    }
                                    ?>
                                </td>
                                <td class="message-preview"><?php echo esc_html(wp_trim_words($conv->user_message, 15)); ?></td>
                                <td class="message-preview"><?php echo esc_html(wp_trim_words($conv->assistant_message, 15)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1) : ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $page
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <p class="no-data">No conversations recorded yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}
