# GD Chatbot v2

A WordPress plugin that provides an intelligent chatbot interface powered by Claude AI with advanced features including Tavily web search and Pinecone vector database support.

## Version

2.0.3 (Based on gd-claude-chatbot v1.7.1, fully rebranded with independent settings, menu, and icon)

## Description

GD Chatbot is an AI-powered chatbot plugin that leverages Anthropic's Claude AI to provide intelligent conversational capabilities for WordPress websites. Originally developed for Grateful Dead content, it has been adapted for general use while maintaining all the powerful features of the original.

## Features

- **Claude AI Integration**: Powered by Anthropic's Claude AI for intelligent conversations
- **Tavily Search Integration**: Real-time web search capabilities for up-to-date information
- **Pinecone Vector Database**: Advanced knowledge base with semantic search
- **Knowledge Base Integration**: Supports WordPress Knowledgebase Loader and AI Power plugins
- **Setlist Search**: Built-in CSV-based setlist database search
- **Streaming Responses**: Real-time streaming for responsive user experience
- **Customizable Interface**: Full control over appearance, colors, and positioning
- **Multiple Themes**: Professional and psychedelic theme options
- **Conversation History**: Tracks and stores conversation logs
- **WordPress Integration**: Seamless integration with WordPress via shortcodes and widgets

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active Anthropic Claude API key
- (Optional) Tavily API key for web search
- (Optional) Pinecone API credentials for vector database

## Installation

1. Upload the `gd-chatbot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your API keys in Settings → GD Chatbot
4. Add the chatbot to your site using the shortcode `[gd_chatbot]` or enable the floating widget

## Configuration

### Claude Settings
- API Key (required)
- Model selection (claude-sonnet-4-20250514 recommended)
- Max tokens
- Temperature
- System prompt customization

### Tavily Settings (Optional)
- Enable/disable web search
- API key
- Search depth (basic/advanced)
- Max results
- Domain filtering

### Pinecone Settings (Optional)
- Enable/disable vector database
- API key and host
- Index name and namespace
- Top K results

### Knowledge Base Integration
- Knowledgebase Loader support
- AI Power plugin integration
- Configurable result limits and scoring

### Appearance
- Custom title and welcome message
- Placeholder text
- Primary color
- Position (bottom-right, bottom-left, etc.)
- Width and height

## Usage

### Shortcode
```php
[gd_chatbot_v2]
```

**Note:** This plugin uses `[gd_chatbot_v2]` to avoid conflicts with gd-claude-chatbot which uses `[gd_chatbot]`.

### Floating Widget
Enable in the plugin settings to display a floating chatbot widget on all pages.

### Direct Integration
Use the provided JavaScript API to integrate the chatbot programmatically.

## File Structure

```
gd-chatbot/
├── admin/
│   ├── class-admin-settings.php
│   ├── css/
│   │   └── admin-styles.css
│   └── js/
│       └── admin-scripts.js
├── includes/
│   ├── class-claude-api.php
│   ├── class-tavily-api.php
│   ├── class-pinecone-api.php
│   ├── class-setlist-search.php
│   ├── class-kb-integration.php
│   ├── class-aipower-integration.php
│   └── class-chat-handler.php
├── public/
│   ├── class-chatbot-public.php
│   ├── css/
│   │   ├── chatbot-styles.css
│   │   ├── professional-theme.css
│   │   └── gd-theme.css
│   └── js/
│       └── chatbot.js
├── context/
│   └── [Context and knowledge base files]
├── gd-chatbot.php
├── uninstall.php
└── README.md
```

## Context Files

The plugin includes comprehensive context files for Grateful Dead content:

### Core Context Files
- `grateful-dead-context.md` - Main comprehensive knowledge base (updated Jan 12, 2026)
- `Grateful Dead Competencies` - Structured outline of band knowledge
- `Grateful Dead Context Requirements` - Domain requirements guide

### Specialized Knowledge Files
- `A Comprehensive Guide to Grateful Dead Online Resources.md` - Complete online resource directory
- `A Guide to Regional Music and Rock Art Galleries.md` - Gallery and museum information
- `Comprehensive List of Grateful Dead Academic Research Papers with PDF Downloads.md` - Academic resources
- `GD-THEME.md` - Theme and styling guidelines
- `Grateful Dead Chatbots and AI Tools.md` - AI tools and chatbot information
- `UC Santa Cruz Grateful Dead Archive: Comprehensive Summary of Holdings.md` - Archive documentation
- `dissertations_theses_list.md` - Academic dissertations and theses
- `gds_volume1_articles.md` - Grateful Dead Studies articles
- `grateful_dead_papers_findings.md` - Research paper findings
- `reverb.com_news_the-gear-of-the-grateful-dead.md` - Equipment and gear information
- `ucsc_gd_archive_notes.md` - UC Santa Cruz archive notes
- `the_bahr_gallery.md` - The Bahr Gallery information (Oyster Bay, NY)

### Data Files
- Setlist data (1965-1995) in CSV format
- Song catalog with disambiguation guide
- Equipment specifications (Jerry Garcia guitars, Phil Lesh basses, etc.)
- Interview transcripts collection

### How Context is Loaded
The plugin automatically loads all context files at initialization:
1. Main `grateful-dead-context.md` is loaded as the primary knowledge base
2. Disambiguation guides are loaded to handle song title conflicts
3. Additional knowledgebase files are appended for specialized topics
4. The Bahr Gallery content is specially injected with location verification
5. All content is combined into the Claude system prompt for accurate responses

These can be replaced or supplemented with your own domain-specific content.

## Development

### Class Structure

- `GD_Chatbot`: Main plugin class
- `GD_Claude_API`: Claude API integration
- `GD_Tavily_API`: Tavily search integration
- `GD_Pinecone_API`: Pinecone vector database integration
- `GD_Chat_Handler`: Orchestrates all chat interactions
- `GD_Chatbot_Public`: Frontend interface
- `GD_Chatbot_Admin_Settings`: WordPress admin interface

### Hooks and Filters

The plugin provides various WordPress hooks for customization:
- `wp_ajax_gd_chatbot_send_message`: Handle chat messages
- `wp_ajax_gd_chatbot_stream_message`: Handle streaming responses
- Custom filters for modifying behavior

## Support

For support, feature requests, or bug reports, please contact IT Influentials.

## License

GPL-2.0+

## Credits

- **Developer**: IT Influentials
- **Website**: https://it-influentials.com
- **Based on**: gd-claude-chatbot v1.7.1

## Changelog

### 2.0.0 (2026-01-12)
- **Major release**: Full rebrand for side-by-side installation with gd-claude-chatbot
- Plugin name updated to "GD Chatbot v2"
- Admin menu displays as "GD Chatbot v2"
- All 21 historical zip files rebuilt with correct internal structure
- Main plugin file renamed from gd-claude-chatbot.php to gd-chatbot.php
- Plugin now installs to separate directory (gd-chatbot/)
- No installation conflicts - can run alongside gd-claude-chatbot

### 1.7.1 (2026-01-10)
- Initial release based on gd-claude-chatbot v1.7.1
- Renamed plugin from gd-claude-chatbot to gd-chatbot
- Updated all references and text domains
- Included all historical .zip files from gd-claude-chatbot
- Enhanced context files with current versions where 1.7.1 originals weren't available
- Maintained full compatibility with original 1.7.1 functionality

## Migration from gd-claude-chatbot

This plugin is a direct fork of gd-claude-chatbot v1.7.1. If you're migrating:
1. Export your settings from gd-claude-chatbot
2. Install and activate gd-chatbot
3. Configure with the same settings
4. Test thoroughly before deactivating the old plugin

Note: Both plugins can run simultaneously if needed, but they will maintain separate conversation histories and settings.
