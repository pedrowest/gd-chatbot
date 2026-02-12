<?php
/**
 * Public Chatbot Class
 * 
 * Handles the front-end chatbot interface
 * 
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Chatbot_Public {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode - v2 specific to avoid conflict with gd-claude-chatbot
        add_shortcode('gd_chatbot_v2', array($this, 'render_shortcode'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add floating widget to footer if enabled
        add_action('wp_footer', array($this, 'render_floating_widget'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only load if chatbot might be displayed
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Base styles
        wp_enqueue_style(
            'gd-chatbot-public',
            GD_CHATBOT_PLUGIN_URL . 'public/css/chatbot-styles.css',
            array(),
            GD_CHATBOT_VERSION
        );
        
        // Grateful Dead Psychedelic Theme (default)
        wp_enqueue_style(
            'gd-chatbot-theme',
            GD_CHATBOT_PLUGIN_URL . 'public/css/gd-theme.css',
            array('gd-chatbot-public'),
            GD_CHATBOT_VERSION
        );
        
        // Song Modal Styles
        wp_enqueue_style(
            'gd-chatbot-song-modal',
            GD_CHATBOT_PLUGIN_URL . 'public/css/song-modal.css',
            array('gd-chatbot-public'),
            GD_CHATBOT_VERSION
        );
        
        // Optional: Professional Theme (inspired by dead.net)
        // Uncomment to use the professional theme instead
        // wp_enqueue_style(
        //     'gd-chatbot-theme-professional',
        //     GD_CHATBOT_PLUGIN_URL . 'public/css/professional-theme.css',
        //     array('gd-chatbot-public'),
        //     GD_CHATBOT_VERSION
        // );
        
        wp_enqueue_script(
            'gd-chatbot-public',
            GD_CHATBOT_PLUGIN_URL . 'public/js/chatbot.js',
            array('jquery'),
            GD_CHATBOT_VERSION,
            true
        );
        
        // Song Modal Script
        wp_enqueue_script(
            'gd-chatbot-song-modal',
            GD_CHATBOT_PLUGIN_URL . 'public/js/song-modal.js',
            array('jquery', 'gd-chatbot-public'),
            GD_CHATBOT_VERSION,
            true
        );
        
        // Pass data to song modal script
        wp_localize_script('gd-chatbot-song-modal', 'gdChatbotPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gd_chatbot_nonce'),
            'isLoggedIn' => is_user_logged_in()
        ));
        
        // Marked.js for Markdown rendering
        wp_enqueue_script(
            'marked-js',
            'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            array(),
            '9.1.6',
            true
        );
        
        // Pass configuration to JavaScript
        wp_localize_script('gd-chatbot-public', 'gdChatbot', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gd_chatbot_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'settings' => array(
                'title' => get_option('gd_chatbot_v2_chatbot_title', '🌹 Grateful Dead Guide ⚡'),
                'welcomeMessage' => get_option('gd_chatbot_v2_chatbot_welcome_message', '🎸 Hey there, Deadhead! Ready to explore the music, shows, and culture of the Grateful Dead? Ask me anything!'),
                'placeholder' => get_option('gd_chatbot_v2_chatbot_placeholder', 'Ask about shows, songs, or the Dead...'),
                'primaryColor' => get_option('gd_chatbot_v2_chatbot_primary_color', '#DC143C'),
                'position' => get_option('gd_chatbot_v2_chatbot_position', 'bottom-right'),
                'width' => get_option('gd_chatbot_v2_chatbot_width', 420),
                'height' => get_option('gd_chatbot_v2_chatbot_height', 650),
            ),
            'i18n' => array(
                'send' => __('Send', 'gd-chatbot'),
                'typing' => __('Tuning up...', 'gd-chatbot'),
                'error' => __('Oops! Something went wrong. Please try again.', 'gd-chatbot'),
                'clearChat' => __('Clear conversation', 'gd-chatbot'),
                'minimize' => __('Minimize', 'gd-chatbot'),
                'close' => __('Close', 'gd-chatbot'),
            )
        ));
    }
    
    /**
     * Check if assets should be loaded
     */
    private function should_load_assets() {
        global $post;
        
        // Always load for floating widget
        $position = get_option('gd_chatbot_v2_chatbot_position', 'bottom-right');
        if ($position !== 'inline') {
            return true;
        }
        
        // Check if shortcode is present in content
        if ($post && has_shortcode($post->post_content, 'gd_chatbot_v2')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => get_option('gd_chatbot_v2_chatbot_title', '🌹 Grateful Dead Guide ⚡'),
            'welcome' => get_option('gd_chatbot_v2_chatbot_welcome_message', '🎸 Hey there, Deadhead! Ready to explore the music, shows, and culture of the Grateful Dead? Ask me anything!'),
            'width' => get_option('gd_chatbot_v2_chatbot_width', 420),
            'height' => get_option('gd_chatbot_v2_chatbot_height', 650),
            'color' => get_option('gd_chatbot_v2_chatbot_primary_color', '#DC143C'),
        ), $atts, 'gd_chatbot_v2');
        
        return $this->render_chatbot($atts, 'inline');
    }
    
    /**
     * Render floating widget
     */
    public function render_floating_widget() {
        $position = get_option('gd_chatbot_v2_chatbot_position', 'bottom-right');
        
        // Don't render if inline mode
        if ($position === 'inline') {
            return;
        }
        
        // Check if Claude API is configured
        if (empty(get_option('gd_chatbot_v2_claude_api_key'))) {
            return;
        }
        
        $atts = array(
            'title' => get_option('gd_chatbot_v2_chatbot_title', '🌹 Grateful Dead Guide ⚡'),
            'welcome' => get_option('gd_chatbot_v2_chatbot_welcome_message', '🎸 Hey there, Deadhead! Ready to explore the music, shows, and culture of the Grateful Dead? Ask me anything!'),
            'width' => get_option('gd_chatbot_v2_chatbot_width', 420),
            'height' => get_option('gd_chatbot_v2_chatbot_height', 650),
            'color' => get_option('gd_chatbot_v2_chatbot_primary_color', '#DC143C'),
        );
        
        echo $this->render_chatbot($atts, 'floating', $position);
    }
    
    /**
     * Render the chatbot HTML
     */
    private function render_chatbot($atts, $type = 'inline', $position = 'bottom-right') {
        $session_id = $this->get_session_id();
        $width = intval($atts['width']);
        $height = intval($atts['height']);
        $color = esc_attr($atts['color']);
        
        $container_class = 'gd-chatbot-container gd-theme-grateful-dead';
        if ($type === 'floating') {
            $container_class .= ' gd-chatbot-floating gd-chatbot-' . esc_attr($position);
        }
        
        ob_start();
        ?>
        <div class="<?php echo $container_class; ?>" 
             data-session="<?php echo esc_attr($session_id); ?>"
             style="--iti-chat-primary: <?php echo $color; ?>; --iti-chat-width: <?php echo $width; ?>px; --iti-chat-height: <?php echo $height; ?>px;">
            
            <?php if ($type === 'floating') : ?>
            <!-- Floating Toggle Button -->
            <button class="gd-chatbot-toggle" aria-label="Open chat">
                <svg class="icon-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <svg class="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <?php endif; ?>
            
            <!-- Chat Window -->
            <div class="gd-chatbot-window <?php echo $type === 'floating' ? 'gd-chatbot-hidden' : ''; ?>">
                <!-- Header -->
                <div class="gd-chatbot-header">
                    <div class="gd-chatbot-header-content">
                        <h2 class="gd-chatbot-header-title"><?php echo esc_html($atts['title']); ?></h2>
                        <p class="gd-chatbot-header-subtitle">Your guide to all things Grateful Dead</p>
                    </div>
                    <div class="gd-chatbot-header-actions">
                        <button class="gd-chatbot-clear" title="Clear conversation" aria-label="Clear conversation">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                        <?php if ($type === 'floating') : ?>
                        <button class="gd-chatbot-minimize" title="Minimize" aria-label="Minimize chat">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Messages Area -->
                <div class="gd-chatbot-messages">
                    <!-- Welcome Message -->
                    <div class="gd-chatbot-message gd-chatbot-message-assistant">
                        <div class="message-avatar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                                <line x1="9" y1="9" x2="9.01" y2="9"/>
                                <line x1="15" y1="9" x2="15.01" y2="9"/>
                            </svg>
                        </div>
                        <div class="message-content">
                            <div class="message-text"><?php echo wp_kses_post($atts['welcome']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Typing Indicator -->
                <div class="gd-chatbot-typing gd-chatbot-hidden">
                    <div class="message-avatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                            <line x1="9" y1="9" x2="9.01" y2="9"/>
                            <line x1="15" y1="9" x2="15.01" y2="9"/>
                        </svg>
                    </div>
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
                
                <!-- Input Area -->
                <div class="gd-chatbot-input-area">
                    <form class="gd-chatbot-form">
                        <textarea class="gd-chatbot-input" 
                                  placeholder="<?php echo esc_attr(get_option('gd_chatbot_v2_chatbot_placeholder', 'Type your message...')); ?>"
                                  rows="1"
                                  maxlength="4000"></textarea>
                        <button type="submit" class="gd-chatbot-send" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </form>
                    <div class="gd-chatbot-footer">
                        <span>Powered by Claude AI</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get or create session ID
     */
    private function get_session_id() {
        if (!isset($_COOKIE['gd_chatbot_session'])) {
            $session_id = wp_generate_uuid4();
            setcookie('gd_chatbot_session', $session_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
            return $session_id;
        }
        return sanitize_text_field($_COOKIE['gd_chatbot_session']);
    }
}

/**
 * Helper function to render chatbot v2
 */
function gd_render_chatbot_v2($atts = array()) {
    $defaults = array(
        'title' => get_option('gd_chatbot_v2_chatbot_title', '🌹 Grateful Dead Guide ⚡'),
        'welcome' => get_option('gd_chatbot_v2_chatbot_welcome_message', '🎸 Hey there, Deadhead! Ready to explore the music, shows, and culture of the Grateful Dead? Ask me anything!'),
        'width' => get_option('gd_chatbot_v2_chatbot_width', 420),
        'height' => get_option('gd_chatbot_v2_chatbot_height', 650),
        'color' => get_option('gd_chatbot_v2_chatbot_primary_color', '#DC143C'),
    );
    
    $atts = wp_parse_args($atts, $defaults);
    
    echo do_shortcode('[gd_chatbot_v2 title="' . esc_attr($atts['title']) . '" 
                                     welcome="' . esc_attr($atts['welcome']) . '"
                                     width="' . esc_attr($atts['width']) . '"
                                     height="' . esc_attr($atts['height']) . '"
                                     color="' . esc_attr($atts['color']) . '"]');
}
