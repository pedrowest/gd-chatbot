<?php
/**
 * Admin Streaming Services Settings
 * 
 * Configuration interface for streaming service API credentials
 * 
 * @package GD_Chatbot
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get service manager
$manager = new GD_Streaming_Service_Manager();
$available_services = $manager->get_available_services();

// Get statistics
global $wpdb;
$credentials_manager = new GD_Streaming_Credentials();

// Count connected users per service
$service_stats = array();
foreach (GD_Streaming_Credentials::SERVICES as $service => $label) {
    $meta_key = 'gd_streaming_' . $service;
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s",
        $meta_key
    ));
    $service_stats[$service] = (int) $count;
}

?>

<div class="wrap gd-streaming-services-settings">
    <h2>🎵 Streaming Services Configuration</h2>
    
    <p class="description">
        Configure API credentials for streaming services. Users will be able to connect their accounts and search across multiple platforms.
    </p>
    
    <!-- Service Status Overview -->
    <div class="gd-services-overview">
        <h3>Service Status</h3>
        
        <div class="gd-service-cards">
            <?php foreach (GD_Streaming_Credentials::SERVICES as $service => $label): ?>
                <?php
                $is_configured = isset($available_services[$service]);
                $connected_users = $service_stats[$service];
                $status_class = $is_configured ? 'configured' : 'not-configured';
                $status_icon = $is_configured ? '✅' : '⚠️';
                $status_text = $is_configured ? 'Configured' : 'Not Configured';
                ?>
                
                <div class="gd-service-card <?php echo $status_class; ?>">
                    <div class="service-header">
                        <h4><?php echo esc_html($label); ?></h4>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $status_icon; ?> <?php echo $status_text; ?>
                        </span>
                    </div>
                    <div class="service-stats">
                        <div class="stat">
                            <span class="stat-label">Connected Users:</span>
                            <span class="stat-value"><?php echo $connected_users; ?></span>
                        </div>
                    </div>
                    <div class="service-actions">
                        <a href="#<?php echo $service; ?>-config" class="button button-secondary">
                            <?php echo $is_configured ? 'Edit Configuration' : 'Configure'; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Configuration Forms -->
    <div class="gd-service-configs">
        <h3>API Configuration</h3>
        
        <form method="post" action="options.php">
            <?php settings_fields('gd_chatbot_streaming_services'); ?>
            
            <!-- Spotify Configuration -->
            <div id="spotify-config" class="gd-config-section">
                <h4>🎵 Spotify</h4>
                <p class="description">
                    Get your credentials from the <a href="https://developer.spotify.com/dashboard" target="_blank">Spotify Developer Dashboard</a>.
                </p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="spotify_client_id">Client ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gd_chatbot_v2_spotify_client_id" 
                                   id="spotify_client_id"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_spotify_client_id', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Spotify application Client ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="spotify_client_secret">Client Secret</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="gd_chatbot_v2_spotify_client_secret" 
                                   id="spotify_client_secret"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_spotify_client_secret', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Spotify application Client Secret (keep this private)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td>
                            <code><?php echo admin_url('admin-ajax.php?action=gd_oauth_callback&service=spotify'); ?></code>
                            <p class="description">Add this URL to your Spotify app's Redirect URIs</p>
                        </td>
                    </tr>
                </table>
                
                <button type="button" class="button button-secondary gd-test-service" data-service="spotify">
                    Test Connection
                </button>
            </div>
            
            <!-- Apple Music Configuration -->
            <div id="apple_music-config" class="gd-config-section">
                <h4>🍎 Apple Music</h4>
                <p class="description">
                    Apple Music uses developer tokens. Get yours from <a href="https://developer.apple.com/account" target="_blank">Apple Developer</a>.
                </p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="apple_music_team_id">Team ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gd_chatbot_v2_apple_music_team_id" 
                                   id="apple_music_team_id"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_apple_music_team_id', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Apple Developer Team ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="apple_music_key_id">Key ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gd_chatbot_v2_apple_music_key_id" 
                                   id="apple_music_key_id"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_apple_music_key_id', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your MusicKit Key ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="apple_music_developer_token">Developer Token</label>
                        </th>
                        <td>
                            <textarea name="gd_chatbot_v2_apple_music_developer_token" 
                                      id="apple_music_developer_token"
                                      rows="4"
                                      class="large-text"><?php echo esc_textarea(get_option('gd_chatbot_v2_apple_music_developer_token', '')); ?></textarea>
                            <p class="description">Your MusicKit JWT developer token (valid for 6 months)</p>
                        </td>
                    </tr>
                </table>
                
                <button type="button" class="button button-secondary gd-test-service" data-service="apple_music">
                    Test Connection
                </button>
            </div>
            
            <!-- YouTube Music Configuration -->
            <div id="youtube_music-config" class="gd-config-section">
                <h4>📺 YouTube Music</h4>
                <p class="description">
                    Get your credentials from the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.
                </p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="youtube_music_client_id">Client ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gd_chatbot_v2_youtube_music_client_id" 
                                   id="youtube_music_client_id"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_youtube_music_client_id', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Google OAuth 2.0 Client ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="youtube_music_client_secret">Client Secret</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="gd_chatbot_v2_youtube_music_client_secret" 
                                   id="youtube_music_client_secret"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_youtube_music_client_secret', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Google OAuth 2.0 Client Secret</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td>
                            <code><?php echo admin_url('admin-ajax.php?action=gd_oauth_callback&service=youtube_music'); ?></code>
                            <p class="description">Add this URL to your Google app's Authorized redirect URIs</p>
                        </td>
                    </tr>
                </table>
                
                <button type="button" class="button button-secondary gd-test-service" data-service="youtube_music">
                    Test Connection
                </button>
            </div>
            
            <!-- Amazon Music Configuration -->
            <div id="amazon_music-config" class="gd-config-section">
                <h4>📦 Amazon Music</h4>
                <p class="description">
                    Get your credentials from <a href="https://developer.amazon.com/" target="_blank">Amazon Developer Console</a>.
                </p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="amazon_music_client_id">Client ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gd_chatbot_v2_amazon_music_client_id" 
                                   id="amazon_music_client_id"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_amazon_music_client_id', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Login with Amazon Client ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="amazon_music_client_secret">Client Secret</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="gd_chatbot_v2_amazon_music_client_secret" 
                                   id="amazon_music_client_secret"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_amazon_music_client_secret', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Login with Amazon Client Secret</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td>
                            <code><?php echo admin_url('admin-ajax.php?action=gd_oauth_callback&service=amazon_music'); ?></code>
                            <p class="description">Add this URL to your Amazon app's Allowed Return URLs</p>
                        </td>
                    </tr>
                </table>
                
                <button type="button" class="button button-secondary gd-test-service" data-service="amazon_music">
                    Test Connection
                </button>
            </div>
            
            <!-- Tidal Configuration -->
            <div id="tidal-config" class="gd-config-section">
                <h4>🌊 Tidal</h4>
                <p class="description">
                    Get your credentials from <a href="https://developer.tidal.com/" target="_blank">Tidal Developer Portal</a>.
                </p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="tidal_client_id">Client ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gd_chatbot_v2_tidal_client_id" 
                                   id="tidal_client_id"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_tidal_client_id', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Tidal API Client ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="tidal_client_secret">Client Secret</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="gd_chatbot_v2_tidal_client_secret" 
                                   id="tidal_client_secret"
                                   value="<?php echo esc_attr(get_option('gd_chatbot_v2_tidal_client_secret', '')); ?>"
                                   class="regular-text" />
                            <p class="description">Your Tidal API Client Secret</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td>
                            <code><?php echo admin_url('admin-ajax.php?action=gd_oauth_callback&service=tidal'); ?></code>
                            <p class="description">Add this URL to your Tidal app's Redirect URIs</p>
                        </td>
                    </tr>
                </table>
                
                <button type="button" class="button button-secondary gd-test-service" data-service="tidal">
                    Test Connection
                </button>
            </div>
            
            <?php submit_button('Save All Configurations'); ?>
        </form>
    </div>
    
    <!-- Test Results -->
    <div id="gd-test-results" style="display: none;">
        <h3>Test Results</h3>
        <div class="gd-test-results-content"></div>
    </div>
</div>

<style>
.gd-streaming-services-settings {
    max-width: 1200px;
}

.gd-services-overview {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.gd-service-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gd-service-card {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.2s ease;
}

.gd-service-card.configured {
    border-color: #10b981;
    background: #f0fdf4;
}

.gd-service-card.not-configured {
    border-color: #f59e0b;
    background: #fffbeb;
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.service-header h4 {
    margin: 0;
    font-size: 16px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.configured {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.not-configured {
    background: #fef3c7;
    color: #92400e;
}

.service-stats {
    margin: 15px 0;
}

.stat {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.stat-label {
    color: #6b7280;
    font-size: 14px;
}

.stat-value {
    font-weight: 600;
    color: #111827;
}

.service-actions {
    margin-top: 15px;
}

.gd-service-configs {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.gd-config-section {
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 20px;
    background: #f9fafb;
}

.gd-config-section h4 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}

.gd-config-section code {
    background: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    display: inline-block;
    font-size: 13px;
    word-break: break-all;
}

.gd-test-results-content {
    padding: 15px;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    margin-top: 15px;
}

.gd-test-success {
    color: #065f46;
    background: #d1fae5;
    padding: 12px;
    border-radius: 6px;
    border-left: 4px solid #10b981;
}

.gd-test-error {
    color: #991b1b;
    background: #fee2e2;
    padding: 12px;
    border-radius: 6px;
    border-left: 4px solid #ef4444;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test service connection
    $('.gd-test-service').on('click', function() {
        const $btn = $(this);
        const service = $btn.data('service');
        const originalText = $btn.text();
        
        $btn.text('Testing...').prop('disabled', true);
        
        // Get credentials from form
        const clientId = $('#' + service + '_client_id').val();
        const clientSecret = $('#' + service + '_client_secret').val();
        
        if (!clientId || !clientSecret) {
            alert('Please enter both Client ID and Client Secret before testing.');
            $btn.text(originalText).prop('disabled', false);
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gd_chatbot_test_service_config',
                nonce: '<?php echo wp_create_nonce('gd_chatbot_nonce'); ?>',
                service: service,
                client_id: clientId,
                client_secret: clientSecret
            },
            success: function(response) {
                $('#gd-test-results').show();
                
                if (response.success) {
                    $('.gd-test-results-content').html(
                        '<div class="gd-test-success">✅ ' + response.data.message + '</div>'
                    );
                } else {
                    $('.gd-test-results-content').html(
                        '<div class="gd-test-error">❌ ' + response.data.message + '</div>'
                    );
                }
                
                $btn.text(originalText).prop('disabled', false);
            },
            error: function() {
                $('#gd-test-results').show();
                $('.gd-test-results-content').html(
                    '<div class="gd-test-error">❌ Test failed. Please check your credentials.</div>'
                );
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
