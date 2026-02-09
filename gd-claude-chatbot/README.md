# GD Claude Chatbot

A powerful WordPress plugin that integrates Anthropic's Claude AI with Tavily web search and Pinecone vector database support for intelligent, context-aware chatbot experiences.

**Note:** This is a cloned version of the ITI Claude Chatbot, renamed for GD use.

## Features

- 🤖 **Claude AI Integration** - Powered by Anthropic's latest Claude models
- ⚡ **Streaming Responses** - Real-time text generation for immediate feedback
- 🎸 **Grateful Dead Expert** - Comprehensive knowledge base automatically loaded from curated context file
- 📅 **Setlist Database** - Direct access to 2,388 shows (1965-1995) with complete setlists
- 🎨 **Psychedelic Theme** - Authentic Grateful Dead design with iconic colors, fonts, and imagery
- 🔍 **Tavily Web Search** - Real-time web search for up-to-date information
- 📚 **Pinecone RAG** - Retrieval-Augmented Generation from your knowledge base
- 💬 **Modern Chat Interface** - Beautiful, responsive chat widget
- 🎨 **Customizable Appearance** - Colors, size, and position
- 📊 **Analytics Dashboard** - Track conversations and usage
- 🔒 **Secure** - API keys encrypted, nonce verification, capability checks

### Streaming Responses

The chatbot features **real-time streaming responses** that display Claude's answers as they're generated, providing:

- **Immediate feedback** - See responses start appearing within 1-2 seconds
- **Better perceived performance** - Text appears progressively, not all at once
- **Visual progress indicator** - Animated cursor shows generation in progress
- **Smooth experience** - Especially valuable for long, detailed responses

**See [STREAMING.md](STREAMING.md) for complete technical details.**

### Grateful Dead Context Integration

This chatbot includes a comprehensive Grateful Dead knowledge base that is automatically loaded into Claude's system prompt. The `grateful-dead-context.md` file (50KB) contains detailed information about:

- Band history, members, and personnel
- Complete discography and live recordings
- Equipment and gear (guitars, amps, Wall of Sound)
- Eras and musical evolution
- Deadhead culture and community
- Online resources and archives
- Books, galleries, and AI tools
- Key people and important URLs

**See [CONTEXT-INTEGRATION.md](CONTEXT-INTEGRATION.md) for complete details on how the context system works.**

### Setlist Database

The chatbot has **direct access to complete setlist data** for all Grateful Dead performances:

- **2,388 shows** from May 5, 1965 to July 9, 1995
- Complete setlists with song-by-song details
- Venue names and locations
- Segue information (e.g., "Scarlet > Fire")
- Set organization (Set 1, Set 2, Encore, etc.)

Users can ask specific questions like:
- "What did they play at Cornell 5/8/77?"
- "Shows at Winterland in 1974"
- "When did they play Dark Star in 1973?"
- "How many shows in 1989?"

**See [SETLIST-DATABASE.md](SETLIST-DATABASE.md) for complete details on the setlist system.**

### Grateful Dead Theme

The chatbot features an **authentic Grateful Dead psychedelic design**:

- **Iconic Colors** - Roses red, lightning blue, psychedelic purple
- **Classic Iconography** - Steal Your Face (☠️), Roses (🌹), Dancing Bears (🐻)
- **Psychedelic Fonts** - Concert One, Permanent Marker, Righteous
- **Animated Effects** - Pulsing skull, dancing bears, lightning bolts
- **Fire Gradients** - Red → Orange → Purple color flows
- **Complete Isolation** - No WordPress theme conflicts
- **Responsive** - Beautiful on all devices
- **Accessible** - WCAG compliant, reduced motion support
- **Dark Mode** - Automatic detection and adaptation

The theme creates an immersive Deadhead experience while maintaining professional functionality.

**See [GD-THEME.md](GD-THEME.md) for complete theme documentation.**

---

## Documentation

**📚 [DOCUMENTATION-INDEX.md](DOCUMENTATION-INDEX.md) - Start here for documentation navigation!**

### For End Users

- **[USER-GUIDE.md](USER-GUIDE.md)** - Complete user guide for chatting with the Grateful Dead bot
  - What you can ask
  - Understanding responses
  - Example conversations
  - Tips and troubleshooting
  - FAQ and quick reference

### For Developers & Administrators

- **[CONTEXT-INTEGRATION.md](CONTEXT-INTEGRATION.md)** - How the GD knowledge base works
- **[SETLIST-DATABASE.md](SETLIST-DATABASE.md)** - Setlist database integration details
- **[STREAMING.md](STREAMING.md)** - Real-time streaming implementation
- **[GD-THEME.md](GD-THEME.md)** - Psychedelic theme documentation
- **[QUICK-REFERENCE.md](QUICK-REFERENCE.md)** - Quick technical reference
- **[CHANGES.md](CHANGES.md)** - Implementation changelog

---

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- SSL certificate (HTTPS) recommended
- Anthropic API key (required)
- Tavily API key (optional, for web search)
- Pinecone account (optional, for knowledge base)
- OpenAI API key (optional, for embeddings with Pinecone)

## Installation

### Method 1: Direct Upload

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the zip file
4. Click "Install Now" and then "Activate"

### Method 2: FTP/SFTP

1. Extract the plugin folder
2. Upload `gd-claude-chatbot` to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "GD Claude Chatbot" and click "Activate"

## Configuration

### 1. Claude API Setup (Required)

1. Get your API key from [console.anthropic.com](https://console.anthropic.com/)
2. Go to WordPress Admin → GD Chatbot → Settings
3. In the "Claude API" tab:
   - Enter your API key
   - Select a model (see model guide below)
   - Adjust max tokens and temperature as needed
   - Customize the system prompt for your use case
4. Click "Test Connection" to verify
5. Save settings

#### Available Claude Models

| Model | Tier | Best For | Max Output |
|-------|------|----------|------------|
| **Claude Opus 4** | ⭐ Most Capable | Complex reasoning, research, analysis, code generation | 32,000 tokens |
| **Claude Sonnet 4** | Recommended | General assistance, content creation, customer support | 16,000 tokens |
| **Claude 3.5 Sonnet** | Strong | General tasks, coding, balanced performance | 8,192 tokens |
| **Claude 3.5 Haiku** | Fast | Quick queries, simple tasks, high volume | 8,192 tokens |
| **Claude 3 Opus** | Legacy | Complex tasks (previous generation) | 4,096 tokens |
| **Claude 3 Sonnet** | Legacy | General tasks (previous generation) | 4,096 tokens |
| **Claude 3 Haiku** | Legacy | Quick responses (previous generation) | 4,096 tokens |

**Opus models** are recommended for:
- Complex analysis and reasoning
- Research and in-depth exploration
- Advanced code generation
- Creative writing and brainstorming
- Tasks requiring nuanced understanding

**Sonnet models** are recommended for:
- General customer support
- Content creation
- Data analysis
- Day-to-day assistance

**Haiku models** are recommended for:
- High-volume, simple queries
- Quick factual lookups
- Cost-sensitive applications

### 2. Tavily Setup (Optional)

Enable web search to provide current information:

1. Get your API key from [tavily.com](https://tavily.com/)
2. Go to the "Tavily Search" tab
3. Enable Tavily and enter your API key
4. Configure search settings:
   - **Search Depth**: Basic (faster) or Advanced (more comprehensive)
   - **Max Results**: Number of web results to include
   - **Include/Exclude Domains**: Filter search results
5. Test connection and save

### 3. Pinecone Setup (Optional)

Add knowledge base retrieval for domain-specific answers:

1. Create an account at [pinecone.io](https://www.pinecone.io/)
2. Create an index with appropriate dimensions for your embedding model:
   - `text-embedding-3-small`: 1536 dimensions
   - `text-embedding-3-large`: 3072 dimensions
   - `text-embedding-ada-002`: 1536 dimensions
3. Go to the "Pinecone" tab
4. Enable Pinecone and enter:
   - **API Key**: Your Pinecone API key
   - **Index Host URL**: Your index endpoint (e.g., `https://your-index-xxxxx.svc.environment.pinecone.io`)
   - **Index Name**: Name of your index
   - **Namespace**: Optional namespace within the index
   - **Results Count**: Number of documents to retrieve (1-20)
5. Configure embeddings:
   - Get an OpenAI API key from [platform.openai.com](https://platform.openai.com/)
   - Select the embedding model matching your index
6. Test connection and save

### 4. Appearance Settings

Customize the chatbot appearance:

- **Title**: Display name in the chat header
- **Welcome Message**: Initial greeting
- **Input Placeholder**: Hint text in the input field
- **Primary Color**: Accent color for buttons and highlights
- **Position**: Bottom-right, bottom-left, or inline (shortcode only)
- **Width/Height**: Chat window dimensions

## Usage

### Floating Widget

By default, the chatbot appears as a floating widget on all pages. Configure position in Appearance settings.

### Shortcode

Add the chatbot to specific pages/posts:

```
[gd_chatbot]
```

With custom attributes:

```
[gd_chatbot title="Support Bot" width="450" height="550" color="#10b981"]
```

### PHP Function

For theme integration:

```php
<?php 
if (function_exists('gd_render_chatbot')) {
    gd_render_chatbot(array(
        'title' => 'My Assistant',
        'width' => 400,
        'height' => 600
    ));
}
?>
```

## System Prompt Best Practices

The system prompt defines how Claude behaves. Tips for effective prompts:

1. **Define the persona**: Who is the assistant? What expertise does it have?
2. **Set the tone**: Professional, friendly, technical, etc.
3. **Specify the audience**: Who will be asking questions?
4. **Include constraints**: What should the assistant avoid?
5. **Format guidelines**: How should responses be structured?

### Example System Prompt

```
You are a helpful customer support assistant for [Company Name].

## Expertise
- Product knowledge for [Product Line]
- Troubleshooting common issues
- Pricing and availability information

## Guidelines
- Be friendly and professional
- Provide concise, accurate answers
- If unsure, suggest contacting human support
- Never share sensitive customer information
- Use bullet points for step-by-step instructions

## Response Format
- Keep responses under 300 words when possible
- Use **bold** for important points
- Include relevant links when helpful
```

## Analytics

View chatbot usage statistics:

1. Go to GD Chatbot → Analytics
2. See total messages, unique sessions, and logged-in users
3. View daily activity chart for the last 30 days

## Conversation History

Review past conversations:

1. Go to GD Chatbot → Conversations
2. Browse conversations by date, session, and user
3. View question and response previews

## Troubleshooting

### "Claude API key is not configured"

Ensure you've entered your API key in the Claude API tab and saved settings.

### "Connection failed" errors

- Verify your API keys are correct
- Check that your server can make outbound HTTPS requests
- For Pinecone, ensure the host URL is complete (includes https://)

### Chatbot not appearing

- Check if Position is set to "Inline" (requires shortcode)
- Verify the plugin is activated
- Check for JavaScript errors in browser console
- Ensure no caching plugins are blocking the scripts

### Slow responses

- Claude Sonnet models are faster than Opus
- Reduce max tokens for shorter responses
- Use "Basic" search depth for Tavily
- Reduce Pinecone results count

## Hooks & Filters

### Filters

```php
// Modify the system prompt dynamically
add_filter('gd_chatbot_system_prompt', function($prompt) {
    return $prompt . "\n\nCurrent page: " . get_the_title();
});

// Filter Claude API response
add_filter('gd_chatbot_claude_response', function($response, $message) {
    // Modify response
    return $response;
}, 10, 2);
```

### Actions

```php
// After a message is processed
add_action('gd_chatbot_message_sent', function($session_id, $message, $response) {
    // Log or process
}, 10, 3);
```

## Security

- API keys are stored encrypted in WordPress options
- All AJAX requests use nonce verification
- Admin settings require `manage_options` capability
- User input is sanitized and validated
- Conversation logs are stored in a secure database table

## Data Storage

The plugin creates one database table:

- `{prefix}_gd_chatbot_conversations` - Stores conversation history

To remove all data on uninstall, add to `wp-config.php`:

```php
define('GD_CHATBOT_REMOVE_DATA', true);
```

## Support

For issues and feature requests, please contact IT Influentials at [it-influentials.com](https://it-influentials.com).

## License

GPL-2.0+ - See LICENSE file for details.

## Changelog

### 1.0.0
- Initial release
- Claude API integration with multiple model support
- Tavily web search integration
- Pinecone vector database support
- Modern responsive chat interface
- WordPress admin settings panel
- Conversation analytics
- Shortcode support

---

Built with ❤️ by [IT Influentials](https://it-influentials.com)
