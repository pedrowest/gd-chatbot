<?php
/**
 * Fired when the plugin is uninstalled.
 * 
 * @package GD_Claude_Chatbot
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we should remove all data
$remove_data = defined('GD_CHATBOT_REMOVE_DATA') && GD_CHATBOT_REMOVE_DATA;

if ($remove_data) {
    global $wpdb;
    
    // Delete all plugin options
    $options = array(
        'gd_chatbot_claude_api_key',
        'gd_chatbot_claude_model',
        'gd_chatbot_claude_max_tokens',
        'gd_chatbot_claude_temperature',
        'gd_chatbot_claude_system_prompt',
        'gd_chatbot_tavily_enabled',
        'gd_chatbot_tavily_api_key',
        'gd_chatbot_tavily_search_depth',
        'gd_chatbot_tavily_max_results',
        'gd_chatbot_tavily_include_domains',
        'gd_chatbot_tavily_exclude_domains',
        'gd_chatbot_pinecone_enabled',
        'gd_chatbot_pinecone_api_key',
        'gd_chatbot_pinecone_host',
        'gd_chatbot_pinecone_index_name',
        'gd_chatbot_pinecone_namespace',
        'gd_chatbot_pinecone_top_k',
        'gd_chatbot_embedding_api_key',
        'gd_chatbot_embedding_model',
        'gd_chatbot_chatbot_title',
        'gd_chatbot_chatbot_welcome_message',
        'gd_chatbot_chatbot_placeholder',
        'gd_chatbot_chatbot_primary_color',
        'gd_chatbot_chatbot_position',
        'gd_chatbot_chatbot_width',
        'gd_chatbot_chatbot_height',
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Delete the conversations table
    $table_name = $wpdb->prefix . 'gd_chatbot_conversations';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Clear any transients
    delete_transient('gd_chatbot_cache');
}
