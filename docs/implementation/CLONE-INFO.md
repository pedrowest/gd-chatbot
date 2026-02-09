# GD Claude Chatbot - Clone Information

## Overview
This plugin is a cloned version of the **ITI Claude Chatbot** plugin, renamed for GD use.

**Original Plugin:** ITI Claude Chatbot  
**Cloned Plugin:** GD Claude Chatbot  
**Clone Date:** January 3, 2025  
**Version:** 1.0.0  

## Changes Made

All references to "ITI" have been systematically replaced with "GD" throughout the entire plugin:

### Class Names
- `ITI_Claude_Chatbot` → `GD_Claude_Chatbot`
- `ITI_Claude_API` → `GD_Claude_API`
- `ITI_Tavily_API` → `GD_Tavily_API`
- `ITI_Pinecone_API` → `GD_Pinecone_API`
- `ITI_Chat_Handler` → `GD_Chat_Handler`
- `ITI_Chatbot_Admin_Settings` → `GD_Chatbot_Admin_Settings`
- `ITI_Chatbot_Public` → `GD_Chatbot_Public`

### Constants
- `ITI_CHATBOT_VERSION` → `GD_CHATBOT_VERSION`
- `ITI_CHATBOT_PLUGIN_DIR` → `GD_CHATBOT_PLUGIN_DIR`
- `ITI_CHATBOT_PLUGIN_URL` → `GD_CHATBOT_PLUGIN_URL`
- `ITI_CHATBOT_PLUGIN_BASENAME` → `GD_CHATBOT_PLUGIN_BASENAME`
- `ITI_CHATBOT_REMOVE_DATA` → `GD_CHATBOT_REMOVE_DATA`

### Database
- Table name: `wp_gd_chatbot_conversations` (was `wp_iti_chatbot_conversations`)
- All option names prefixed with `gd_chatbot_` (was `iti_chatbot_`)

### WordPress Integration
- **Plugin Name:** GD Claude Chatbot
- **Text Domain:** gd-claude-chatbot
- **Menu Title:** GD Chatbot
- **Shortcode:** `[gd_chatbot]` (was `[iti_chatbot]`)
- **Function:** `gd_render_chatbot()` (was `iti_render_chatbot()`)
- **AJAX Actions:**
  - `gd_chatbot_send_message`
  - `gd_test_claude_connection`
  - `gd_test_tavily_connection`
  - `gd_test_pinecone_connection`

### JavaScript
- Object names: `gdChatbot`, `gdChatbotAdmin` (was `itiChatbot`, `itiChatbotAdmin`)
- Class names: `GDChatbot` (was `ITIChatbot`)

### CSS Classes
- All CSS classes prefixed with `.gd-chatbot-` (was `.iti-chatbot-`)
- CSS variables prefixed with `--gd-` or `--gd-chat-` (was `--iti-`)

### Cookie Names
- Cookie: `gd_chatbot_session` (was `iti_chatbot_session`)

### Default Settings
- Default chatbot title: "GD Assistant" (was "ITI Assistant")
- All other settings remain the same

## File Structure

```
gd-claude-chatbot/
├── gd-claude-chatbot.php      # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── README.md                   # Documentation
├── CLONE-INFO.md              # This file
├── includes/
│   ├── class-claude-api.php    # Claude API integration
│   ├── class-tavily-api.php    # Tavily search integration
│   ├── class-pinecone-api.php  # Pinecone vector DB integration
│   └── class-chat-handler.php  # Chat orchestration
├── admin/
│   ├── class-admin-settings.php # WordPress admin pages
│   ├── css/admin-styles.css     # Admin styling
│   └── js/admin-scripts.js      # Admin JavaScript
└── public/
    ├── class-chatbot-public.php # Frontend display
    ├── css/chatbot-styles.css   # Chat widget styles
    └── js/chatbot.js            # Chat widget JavaScript
```

## Installation

1. Upload the `gd-claude-chatbot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin at **GD Chatbot → Settings**

## Important Notes

1. **Separate Configuration:** This plugin maintains its own separate settings and database tables from the ITI version
2. **Can Run Alongside:** Both ITI and GD versions can be installed simultaneously without conflicts
3. **Different Shortcodes:** Use `[gd_chatbot]` for this version vs `[iti_chatbot]` for ITI version
4. **Independent Data:** Conversations and settings are completely separate between the two plugins

## Migration from ITI Version

If you want to migrate settings from the ITI version:

1. Export settings from ITI Chatbot (if export feature exists)
2. Manually copy API keys and configuration to GD Chatbot settings
3. Note: Conversation history is NOT automatically migrated

## Support

For questions or issues with this plugin, contact IT Influentials.

---

**Note:** This is a functional clone with renamed identifiers. All core functionality remains identical to the ITI Claude Chatbot plugin.
