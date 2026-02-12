<?php
/**
 * User Profile Integration
 * 
 * Adds streaming service connection management to WordPress user profiles
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_User_Profile_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('show_user_profile', array($this, 'render_profile_fields'));
        add_action('edit_user_profile', array($this, 'render_profile_fields'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_profile_scripts'));
    }
    
    /**
     * Enqueue scripts for profile page
     */
    public function enqueue_profile_scripts() {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen && ($screen->id === 'profile' || $screen->id === 'user-edit')) {
            wp_enqueue_style(
                'gd-chatbot-profile',
                GD_CHATBOT_PLUGIN_URL . 'admin/css/profile-styles.css',
                array(),
                GD_CHATBOT_VERSION
            );
            
            wp_enqueue_script(
                'gd-chatbot-profile',
                GD_CHATBOT_PLUGIN_URL . 'admin/js/profile-scripts.js',
                array('jquery'),
                GD_CHATBOT_VERSION,
                true
            );
            
            wp_localize_script('gd-chatbot-profile', 'gdChatbotProfile', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gd_chatbot_nonce')
            ));
        }
    }
    
    /**
     * Render profile fields
     */
    public function render_profile_fields($user) {
        $manager = new GD_Streaming_Service_Manager();
        $status = $manager->get_connection_status($user->ID);
        
        ?>
        <h2 id="gd-streaming-services">🎵 Music Streaming Services</h2>
        
        <p class="description">
            Connect your streaming service accounts to search and play music across multiple platforms in the chatbot.
        </p>
        
        <table class="form-table" id="gd-streaming-connections">
            <?php foreach ($status as $service => $info): ?>
                <tr class="gd-service-row" data-service="<?php echo esc_attr($service); ?>">
                    <th scope="row">
                        <?php echo esc_html($info['label']); ?>
                    </th>
                    <td>
                        <?php if (!$info['configured']): ?>
                            <p class="gd-not-configured">
                                ⚠️ Not configured by administrator. 
                                <a href="<?php echo admin_url('admin.php?page=gd-chatbot-v2&tab=streaming_services'); ?>">
                                    Configure API credentials
                                </a>
                            </p>
                        <?php elseif ($info['connected']): ?>
                            <div class="gd-connected-status">
                                <span class="gd-status-badge gd-connected">
                                    ✅ Connected
                                </span>
                                
                                <?php if ($info['expired']): ?>
                                    <span class="gd-status-badge gd-expired">
                                        ⚠️ Token Expired
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($info['stored_at']): ?>
                                    <p class="description">
                                        Connected <?php echo human_time_diff(strtotime($info['stored_at']), current_time('timestamp')); ?> ago
                                    </p>
                                <?php endif; ?>
                                
                                <button type="button" 
                                        class="button button-secondary gd-disconnect-service" 
                                        data-service="<?php echo esc_attr($service); ?>">
                                    Disconnect
                                </button>
                                
                                <?php if ($info['expired']): ?>
                                    <button type="button" 
                                            class="button button-primary gd-reconnect-service" 
                                            data-service="<?php echo esc_attr($service); ?>">
                                        Reconnect
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="gd-disconnected-status">
                                <span class="gd-status-badge gd-disconnected">
                                    ⭕ Not Connected
                                </span>
                                
                                <button type="button" 
                                        class="button button-primary gd-connect-service" 
                                        data-service="<?php echo esc_attr($service); ?>">
                                    Connect <?php echo esc_html($info['label']); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <div id="gd-connection-message" style="display: none; margin-top: 15px;"></div>
        
        <style>
        .gd-service-row {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .gd-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .gd-status-badge.gd-connected {
            background: #d1fae5;
            color: #065f46;
        }
        
        .gd-status-badge.gd-disconnected {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .gd-status-badge.gd-expired {
            background: #fef3c7;
            color: #92400e;
        }
        
        .gd-not-configured {
            color: #d97706;
            font-weight: 500;
        }
        
        .gd-connected-status,
        .gd-disconnected-status {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        
        #gd-connection-message {
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        #gd-connection-message.success {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        
        #gd-connection-message.error {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Connect service
            $('.gd-connect-service, .gd-reconnect-service').on('click', function() {
                const $btn = $(this);
                const service = $btn.data('service');
                const originalText = $btn.text();
                
                $btn.text('Connecting...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'gd_chatbot_connect_service',
                        nonce: gdChatbotProfile.nonce,
                        service: service
                    },
                    success: function(response) {
                        if (response.success) {
                            // Open OAuth URL in popup
                            const width = 600;
                            const height = 700;
                            const left = (screen.width - width) / 2;
                            const top = (screen.height - height) / 2;
                            
                            const popup = window.open(
                                response.data.auth_url,
                                'oauth_popup',
                                `width=${width},height=${height},left=${left},top=${top}`
                            );
                            
                            // Poll for popup close
                            const pollTimer = setInterval(function() {
                                if (popup.closed) {
                                    clearInterval(pollTimer);
                                    // Reload page to show updated status
                                    location.reload();
                                }
                            }, 500);
                        } else {
                            showMessage('error', response.data.message || 'Failed to connect');
                            $btn.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        showMessage('error', 'Connection failed. Please try again.');
                        $btn.text(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Disconnect service
            $('.gd-disconnect-service').on('click', function() {
                const $btn = $(this);
                const service = $btn.data('service');
                
                if (!confirm('Disconnect this service? You can reconnect at any time.')) {
                    return;
                }
                
                const originalText = $btn.text();
                $btn.text('Disconnecting...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'gd_chatbot_disconnect_service',
                        nonce: gdChatbotProfile.nonce,
                        service: service
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', 'Service disconnected successfully!');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showMessage('error', response.data.message || 'Failed to disconnect');
                            $btn.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        showMessage('error', 'Disconnection failed. Please try again.');
                        $btn.text(originalText).prop('disabled', false);
                    }
                });
            });
            
            function showMessage(type, message) {
                const $msg = $('#gd-connection-message');
                $msg.removeClass('success error')
                    .addClass(type)
                    .html(message)
                    .slideDown();
                
                setTimeout(() => $msg.slideUp(), 5000);
            }
        });
        </script>
        <?php
    }
}
