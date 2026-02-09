# gd-chatbot - Context for Claude

## Overview
The gd-chatbot is a WordPress plugin that provides an intelligent chatbot interface powered by Claude AI. It is based on version 1.7.1 of gd-claude-chatbot, adapted for general use while maintaining the original functionality.

## Directory Structure
- **plugin-installs/**: WordPress installation .zip files (includes all historical versions from gd-claude-chatbot)
- **gd-chatbot/**: Main plugin source code
  - **admin/**: WordPress admin interface
  - **includes/**: Core PHP classes
  - **public/**: Frontend interface (CSS, JS, templates)
  - **context/**: Domain-specific knowledge files

## Key Features
- Claude API integration for conversational AI (Anthropic)
- Tavily search integration for real-time web information
- Pinecone vector database support for semantic search
- Knowledge base integration (Knowledgebase Loader, AI Power)
- Customizable chatbot interface with multiple themes
- WordPress shortcode and widget support
- Conversation logging and analytics
- Streaming response support

## Version Information
- **Current Version**: 1.7.1
- **Based On**: gd-claude-chatbot v1.7.1 (last stable version)
- **Release Date**: 2026-01-10

## Technical Architecture

### Core Classes
- `GD_Chatbot`: Main plugin orchestrator
- `GD_Claude_API`: Claude AI API wrapper
- `GD_Tavily_API`: Tavily search integration
- `GD_Pinecone_API`: Vector database integration
- `GD_Chat_Handler`: Message processing and routing
- `GD_Setlist_Search`: CSV-based setlist search
- `GD_KB_Integration`: Knowledgebase Loader integration
- `GD_AIPower_Integration`: AI Power plugin integration
- `GD_Chatbot_Public`: Frontend rendering
- `GD_Chatbot_Admin_Settings`: Admin interface

### API Integrations
1. **Anthropic Claude**
   - Model: claude-sonnet-4-20250514 (default)
   - Supports streaming responses
   - Configurable temperature and token limits

2. **Tavily Search**
   - Real-time web search
   - Configurable search depth (basic/advanced)
   - Domain filtering support

3. **Pinecone Vector Database**
   - Semantic search capabilities
   - Configurable top-K results
   - Namespace support

### WordPress Integration
- Custom database table: `wp_gd_chatbot_conversations`
- Settings stored as WordPress options with prefix `gd_chatbot_`
- AJAX endpoints for chat and streaming
- Nonce security for all requests

## Context Files
The plugin includes comprehensive Grateful Dead context files:
- Setlist data (CSV format, 1965-1995)
- Song catalog with disambiguation guide
- Equipment specifications
- Interview transcripts
- Historical documentation

These files can be replaced or supplemented for other domains.

## Development Notes

### Changes from gd-claude-chatbot
- Plugin name changed from "GD Claude Chatbot" to "GD Chatbot"
- Text domain changed from `gd-claude-chatbot` to `gd-chatbot`
- Package references updated from `GD_Claude_Chatbot` to `GD_Chatbot`
- Main class renamed from `GD_Claude_Chatbot` to `GD_Chatbot`
- All function references updated accordingly

### Preserved Elements
- All PHP class files from v1.7.1
- All CSS and JavaScript assets
- Complete context file collection (enhanced with current versions where 1.7.1 didn't have them)
- All historical .zip installation files
- Core functionality and API integrations
- Database schema and option names

### File Path Structure
```
gd-chatbot/
├── gd-chatbot/              # Main plugin directory
│   ├── gd-chatbot.php       # Main plugin file
│   ├── admin/               # Admin interface
│   ├── includes/            # Core classes
│   ├── public/              # Frontend assets
│   ├── context/             # Knowledge files
│   ├── uninstall.php        # Cleanup script
│   └── README.md            # Documentation
├── plugin-installs/         # Historical versions
└── CLAUDE.md               # This file
```

## Usage Guidelines

### For Developers
1. The plugin follows WordPress coding standards
2. All API keys should be stored securely in WordPress options
3. The plugin uses WordPress AJAX for frontend communication
4. Streaming is implemented using Server-Sent Events (SSE)
5. Context is managed through the chat handler class

### For Content Creators
1. Context files in the `context/` directory control domain knowledge
2. The system prompt can be customized in admin settings
3. Disambiguation files help clarify ambiguous terms
4. CSV files are used for structured data (setlists, songs, etc.)

### For Site Administrators
1. Configure API keys in Settings → GD Chatbot
2. Enable/disable features as needed (Tavily, Pinecone, etc.)
3. Customize appearance to match site branding
4. Monitor conversation logs for quality assurance
5. Use shortcode `[gd_chatbot]` or enable floating widget

## Related Projects
- **Original**: gd-claude-chatbot (v1.7.1 as base)
- **Derivatives**: ITI Chatbot, AI News Cafe chatbot, Scuba GPT chatbot
- **Shared Libraries**: ITI Shared Libraries (for common components)

## Important Notes
1. This is a production-ready plugin based on stable v1.7.1
2. All .zip files from gd-claude-chatbot are preserved for version history
3. Context files have been enhanced with current versions where appropriate
4. The plugin maintains backward compatibility with v1.7.1 functionality
5. Database table and option names use `gd_chatbot_` prefix (not `gd_claude_chatbot_`)

## Migration Path
If migrating from gd-claude-chatbot:
1. Both plugins use the same database structure
2. Settings can be manually copied
3. Plugins can run side-by-side during transition
4. Context files are compatible between versions

## Security Considerations
1. All AJAX requests use WordPress nonces
2. User capabilities checked for admin functions
3. Input sanitization on all user data
4. API keys stored in WordPress options (consider using constants for production)
5. SQL queries use WordPress $wpdb with proper escaping

## Performance Notes
1. Streaming responses improve perceived performance
2. Context files loaded on-demand
3. CSS/JS assets only loaded when chatbot is active
4. Conversation history stored in custom table for efficiency
5. Transient caching can be enabled for repeated queries

## Future Considerations
- Consider extracting common components to shared library
- Potential for multi-model support beyond Claude
- Enhanced analytics and reporting features
- Integration with additional knowledge base systems
- Support for multiple languages and domains
