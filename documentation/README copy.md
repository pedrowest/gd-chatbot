# ScubaGPT - AI-Powered Diving Chatbot for WordPress

## Overview

ScubaGPT is a WordPress plugin that provides an AI-powered chatbot specifically designed for recreational scuba divers. Built on Claude AI, Pinecone vector database, and Tavily web search, it offers comprehensive diving advice, destination recommendations, and technical information.

## Latest Updates

### Version 1.1.0 (January 2026) - Safety Guardrails, Enhanced System Prompt & Admin UI 🛡️

#### 🛡️ Plugin Safety Guardrails (NEW)
✅ **5-Layer Protection System** - Plugin can never crash your WordPress site  
✅ **Safe File Loading** - Validates all files before loading  
✅ **Activation Protection** - Comprehensive error handling during activation  
✅ **Fatal Error Recovery** - Auto-disables after 3 fatal errors  
✅ **Health Check System** - Hourly checks with automatic repair  
✅ **Emergency Shutdown** - Prevents infinite error loops  
✅ **Clear Error Messages** - Step-by-step recovery instructions  
✅ **One-Click Recovery** - Admin tools to clear errors and reset  

#### 🤖 Enhanced System Prompt (NEW)
✅ **9 Safety Rules** - Comprehensive guidelines for accuracy and safety  
✅ **Species ID Confidence** - Required confidence levels for identifications  
✅ **News Integration** - Relevant recent news included in responses  
✅ **Links Organization** - Structured links section with up to 10 homepage URLs  
✅ **Conversation Memory** - Explicit follow-up question handling  
✅ **Source Citations** - Up to 3 sources cited per dive site/operator  
✅ **Enhanced Formatting** - Emoji bullets, bold headers, consistent structure  
✅ **Google Maps Integration** - Automatic coordinate links when available  

#### 📊 Admin UI & Statistics Dashboard
✅ **AI Power Settings Page** - Full UI controls for AI Power integration  
✅ **Statistics Dashboard** - Comprehensive metrics and analytics  
✅ **Dashboard Widget** - Quick stats on WordPress main dashboard  
✅ **Chart Visualizations** - Interactive charts powered by Chart.js  
✅ **Real-time Monitoring** - Track queries, relevance scores, and performance  
✅ **Content Analytics** - See which content is most queried  

### Version 1.0.0 (January 2026) - AI Power Integration 🎉

✅ **WordPress Content Integration** - Use your blog posts and pages as chatbot knowledge  
✅ **File Upload Support** - Upload dive manuals, guides, and reference documents (PDF, TXT)  
✅ **Automatic Sync** - Re-index posts in AI Power to update chatbot instantly  
✅ **Source Attribution** - Clear indication of where information came from  
✅ **Multi-Source Context** - Combines AI Power, Pinecone, and Tavily for comprehensive answers  

## Project Structure

```
Scuba GPT/
├── scubagpt-chatbot/              # WordPress plugin files
│   ├── includes/
│   │   ├── class-scubagpt-admin.php
│   │   ├── class-scubagpt-api.php
│   │   ├── class-scubagpt-chat.php
│   │   ├── class-scubagpt-rest.php
│   │   ├── class-scubagpt-pinecone-api.php        # NEW
│   │   └── class-scubagpt-aipower-integration.php # NEW
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   ├── templates/
│   ├── scubagpt-chatbot.php       # Main plugin file (updated)
│   └── readme.txt
├── Scuba GPT Training Data/       # Training datasets
│   ├── Scuba_Sites_Parsed-*.csv   # Dive site databases
│   ├── *.pdf                      # Reference materials
│   └── ScubaGPT-SearchSites.txt   # Website seed list
├── Fine Tunings/                  # ML training data
├── Scuba GPT Agents/              # AI agent configs
├── Scuba GPT Prompts/             # Prompt templates
├── AIPOWER-INTEGRATION.md                      # Full technical docs
├── AIPOWER-QUICK-START.md                      # 5-minute setup guide
├── AIPOWER-FILE-UPLOAD-SUPPORT.md              # File upload docs
├── AIPOWER-INTEGRATION-SUMMARY.md              # Implementation summary
├── PLUGIN-SAFETY-GUARDRAILS.md                 # Safety system docs
├── SAFETY-GUARDRAILS-IMPLEMENTATION-SUMMARY.md # Safety implementation
├── SAFETY-GUARDRAILS-QUICK-REFERENCE.md        # Quick safety guide
├── SYSTEM-PROMPT-UPDATE.md                     # System prompt docs (NEW)
├── CLAUDE.md                                   # Repository guidance
└── README.md                                   # This file
```

## Plugin Features

### Core Features

- **Claude AI Integration** - Powered by Claude Sonnet 4
- **Pinecone Vector Database** - Semantic search for diving knowledge
- **Tavily Web Search** - Real-time diving news and conditions
- **AI Power Integration** - Use WordPress content as knowledge base
- **Plugin Safety Guardrails** ⭐ NEW - Never crashes your WordPress site
- **Conversation History** - Maintains context across messages
- **Streaming Responses** - Real-time response generation
- **Rate Limiting** - Prevents abuse
- **Source Attribution** - Shows where information came from
- **Admin Statistics** - Track usage and performance metrics

### Diving-Specific Features

- Dive site recommendations
- Marine life information
- Certification guidance
- Equipment advice
- Safety information
- Trip planning assistance
- Destination research
- Technical diving knowledge

### 🛡️ Safety Guardrails (Version 1.1.0)

ScubaGPT implements a comprehensive 5-layer safety system that ensures the plugin can **never crash your WordPress site**:

#### Layer 1: Pre-Installation Validation
- Validates all files before loading
- Checks for missing or corrupted files
- Distinguishes critical vs optional files

#### Layer 2: Safe Activation
- Verifies PHP 8.0+ and WordPress 6.0+
- Checks required PHP extensions
- Validates database table creation
- Wraps activation in try-catch

#### Layer 3: Graceful Degradation
- Plugin stops safely on errors
- Provides clear, actionable error messages
- Shows step-by-step recovery instructions
- Admin notices with one-click tools

#### Layer 4: Automatic Recovery
- Health checks run hourly
- Missing tables auto-recreated
- Silent recovery from common issues
- Logs all repair attempts

#### Layer 5: Emergency Shutdown
- Detects fatal errors automatically
- Auto-disables after 3 fatal errors
- Prevents infinite error loops
- Stores error details for debugging

**Benefits:**
- ✅ Your site always remains accessible
- ✅ Clear error messages, no technical jargon
- ✅ One-click error recovery tools
- ✅ Automatic repair of common issues
- ✅ No manual SSH/FTP access needed

**Documentation:**
- Full Guide: `PLUGIN-SAFETY-GUARDRAILS.md`
- Quick Reference: `SAFETY-GUARDRAILS-QUICK-REFERENCE.md`
- Implementation: `SAFETY-GUARDRAILS-IMPLEMENTATION-SUMMARY.md`

## Quick Start

### Prerequisites

1. WordPress 6.0+ with PHP 8.0+
2. Claude API key from Anthropic
3. (Optional) Pinecone account for vector search
4. (Optional) Tavily API key for web search
5. (Optional) AI Power plugin for WordPress content integration

### Installation

1. **Upload Plugin**
   ```bash
   cd wp-content/plugins
   unzip scubagpt-chatbot.zip
   ```

2. **Activate Plugin**
   - Go to WordPress Admin > Plugins
   - Find "ScubaGPT Chatbot"
   - Click "Activate"

3. **Configure Claude API**
   - Go to ScubaGPT Settings
   - Enter your Claude API key
   - Select model (default: claude-sonnet-4-20250514)
   - Save settings

4. **Configure Optional Services**
   - Pinecone: For semantic search
   - Tavily: For real-time web search
   - AI Power: For WordPress content integration

### AI Power Integration Setup

For the NEW AI Power integration, follow these steps:

1. **Install AI Power Plugin**
   - Install and activate AI Power plugin
   - Get it from: https://aipower.org/

2. **Configure Pinecone in AI Power**
   - Sign up at https://www.pinecone.io/
   - Create a new index
   - In AI Power settings:
     - Enter Pinecone API key
     - Enter Pinecone host URL
     - Enter index name
     - Save settings

3. **Configure OpenAI in AI Power**
   - Get API key from https://platform.openai.com/
   - In AI Power settings:
     - Enter OpenAI API key
     - Select embedding model: text-embedding-3-small
     - Save settings

4. **Index Your Diving Content**
   - Go to AI Power > Content > Index Content
   - Select diving-related posts and pages
   - Click "Index to Vector Database"
   - Upload dive manuals and guides (PDF, TXT)

5. **Done!**
   - ScubaGPT automatically uses indexed content
   - No additional configuration needed

**See `AIPOWER-QUICK-START.md` for detailed instructions**

## Usage

### Display Chatbot

**Via Widget (Automatic)**:
- Configure display mode in settings
- Appears on all pages (or specific pages)
- Bottom-right corner by default

**Via Shortcode**:
```php
[scubagpt_chat]
```

**Inline Mode**:
```php
[scubagpt_chat inline="true" height="600px"]
```

### Example Queries

**Destination Research**:
- "What are the best dive sites in the Maldives?"
- "When is the best time to dive in Indonesia?"
- "Compare diving in the Caribbean vs Red Sea"

**Marine Life**:
- "Where can I see whale sharks?"
- "Tell me about manta ray encounters"
- "What fish species live on coral reefs?"

**Technical Questions**:
- "What is the maximum depth for Open Water divers?"
- "Explain decompression stops"
- "What equipment do I need for cold water diving?"

**Trip Planning**:
- "Plan a 7-day liveaboard trip to the Maldives"
- "What should I pack for a dive trip to Bali?"
- "Recommend budget-friendly dive destinations"

## Documentation

### Plugin Documentation

- **Main README**: This file - Overview and quick start
- **Plugin Help**: In WordPress admin under ScubaGPT Settings

### AI Power Integration Documentation

- **`AIPOWER-INTEGRATION.md`** - Complete technical documentation
  - How integration works
  - Setup instructions
  - Configuration options
  - Usage examples
  - Troubleshooting guide
  - Best practices
  - Technical implementation details

- **`AIPOWER-QUICK-START.md`** - 5-minute setup guide
  - Quick setup steps
  - Common use cases
  - Default settings
  - Quick troubleshooting

- **`AIPOWER-FILE-UPLOAD-SUPPORT.md`** - File upload guide
  - Supported file formats
  - Upload instructions
  - Use cases for dive manuals
  - Best practices

- **`AIPOWER-INTEGRATION-SUMMARY.md`** - Implementation summary
  - Files created/modified
  - Technical details
  - Testing results
  - Version history

### Development Documentation

- **`CLAUDE.md`** - Repository guidance for AI assistants
- Training data documentation in respective folders

## Configuration

### Claude Settings

```php
'model' => 'claude-sonnet-4-20250514',
'max_tokens' => 4096,
'temperature' => 0.7,
'enable_streaming' => true,
```

### Pinecone Settings

```php
'top_k' => 5,
'similarity_threshold' => 0.7,
'enabled' => false, // Enable after configuration
```

### Tavily Settings

```php
'search_depth' => 'basic',
'max_results' => 5,
'enabled' => false, // Enable after configuration
'include_domains' => 'padi.com, dan.org, scubadiving.com',
```

### AI Power Settings (NEW)

```php
'scubagpt_aipower_enabled' => true,
'scubagpt_aipower_max_results' => 10,
'scubagpt_aipower_min_score' => 0.35, // 35% relevance threshold
```

## Training Data

### Dive Site Database

- **`Scuba_Sites_Parsed-*.csv`** - 1000+ dive sites worldwide
- Includes coordinates, descriptions, and characteristics
- Google Maps integration data

### Reference Materials

- Marine biology guides
- PADI course information
- Diving safety documents
- Regional diving guides

### Content Sources

- **`ScubaGPT-SearchSites.txt`** - 200+ diving websites
- Authoritative sources: PADI, DAN, Scuba Diving Magazine
- Tourism boards and dive operators
- Conservation organizations

## API Requirements

### Required

- **Claude API** (Anthropic) - For AI responses
  - Pricing: Pay per token
  - Free tier: Limited
  - Sign up: https://www.anthropic.com/

### Optional but Recommended

- **Pinecone** - For semantic search
  - Free tier: 100K vectors, 1 index
  - Sign up: https://www.pinecone.io/

- **OpenAI** - For embeddings (if using Pinecone or AI Power)
  - Pricing: $0.02 / 1M tokens
  - Sign up: https://platform.openai.com/

- **Tavily** - For web search
  - Free tier: 1000 searches/month
  - Sign up: https://tavily.com/

### For AI Power Integration

- **AI Power Plugin** - WordPress plugin for content indexing
  - Required for AI Power integration
  - Get it: https://aipower.org/

- **Pinecone** (via AI Power) - Vector storage
- **OpenAI** (via AI Power) - Embedding generation

## Development

### File Structure

**Core Classes**:
- `class-scubagpt-admin.php` - Admin interface and settings
- `class-scubagpt-api.php` - External API integrations
- `class-scubagpt-chat.php` - Chat logic and conversation management
- `class-scubagpt-rest.php` - REST API endpoints

**Integration Classes** (NEW):
- `class-scubagpt-pinecone-api.php` - Pinecone API wrapper
- `class-scubagpt-aipower-integration.php` - AI Power integration

**Frontend**:
- `assets/css/chatbot.css` - Widget styling
- `assets/js/chatbot.js` - Chat interface and streaming
- `templates/chatbot-widget.php` - Widget template

### Database Tables

**Conversations**:
```sql
wp_scubagpt_conversations
- id, session_id, user_id, user_ip
- created_at, updated_at
```

**Messages**:
```sql
wp_scubagpt_messages
- id, conversation_id, role, content
- tokens_used, sources_used, created_at
```

### Hooks and Filters

```php
// Filter system prompt
add_filter('scubagpt_system_prompt', function($prompt) {
    return $prompt . "\nAdditional instructions...";
});

// Filter context sources
add_filter('scubagpt_context_sources', function($sources) {
    // Modify or add sources
    return $sources;
});

// Action after response
add_action('scubagpt_after_response', function($message, $response) {
    // Log, track, or process response
}, 10, 2);
```

## Troubleshooting

### Common Issues

**1. Chatbot Not Appearing**
- Check plugin is activated
- Verify display settings
- Check for JavaScript errors in console

**2. API Errors**
- Verify API keys are correct
- Check API service status
- Review WordPress debug log

**3. AI Power Integration Not Working**
- Verify AI Power plugin is active
- Check Pinecone configuration in AI Power
- Ensure content is indexed
- Look for integration log: "ScubaGPT: AI Power Pinecone integration active"

**4. No Results from AI Power**
- Index more diving content
- Lower relevance threshold: `update_option('scubagpt_aipower_min_score', 0.25);`
- Increase max results: `update_option('scubagpt_aipower_max_results', 15);`

**5. Rate Limiting**
- Adjust rate limit in settings
- Check session handling
- Clear browser cookies

### Debug Mode

Enable WordPress debug mode:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `wp-content/debug.log`

## Support

### Resources

- Plugin settings page: WordPress Admin > ScubaGPT
- Documentation: See markdown files in this folder
- WordPress debug log: `wp-content/debug.log`

### External Resources

- Claude API: https://docs.anthropic.com/
- Pinecone: https://docs.pinecone.io/
- Tavily: https://docs.tavily.com/
- AI Power: https://aipower.org/documentation/

## Version History

### Version 1.0.0 (January 2026)
- ✅ Initial release
- ✅ Claude AI integration
- ✅ Pinecone vector search
- ✅ Tavily web search
- ✅ AI Power WordPress content integration
- ✅ Streaming responses
- ✅ Conversation history
- ✅ Rate limiting
- ✅ Source attribution

## Roadmap

### Planned Features

**v1.1.0**:
- Admin settings UI for AI Power integration
- Statistics dashboard
- Content recommendation engine
- Enhanced file upload (more formats)

**v1.2.0**:
- Multi-language support
- Custom dive site database integration
- Trip planning module
- Equipment compatibility checker

**v2.0.0**:
- Mobile app integration
- Voice chat support
- Image recognition (dive photos)
- Personalized recommendations

## License

GPL v2 or later

## Credits

**Developed by**: IT Influentials  
**AI Integration**: Based on GD Claude Chatbot AI Power integration  
**Built with**: Claude AI, Pinecone, Tavily, AI Power  

## Contributing

This is a commercial plugin. For feature requests or bug reports, contact IT Influentials.

## Related Projects

- **GD Claude Chatbot** - Generic chatbot for any topic
- **GD Knowledgebase Loader** - Alternative knowledge base solution
- **AI Power** - WordPress AI content generation and indexing

---

**Ready to dive in? 🤿🐠🌊**

For quick setup with AI Power integration, see **AIPOWER-QUICK-START.md**

For comprehensive documentation, see **AIPOWER-INTEGRATION.md**
