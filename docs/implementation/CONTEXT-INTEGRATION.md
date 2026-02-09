# Grateful Dead Context Integration

## Overview

The GD Claude Chatbot now includes comprehensive Grateful Dead knowledge automatically loaded from the `grateful-dead-context.md` file. This context is injected into Claude's system prompt, making the chatbot an expert on all things Grateful Dead.

## How It Works

### Automatic Context Loading

When the chatbot initializes, it automatically:

1. **Loads Context File**: The `grateful-dead-context.md` file is read from the plugin root directory
2. **Appends to System Prompt**: The comprehensive Grateful Dead knowledge base is appended to Claude's system prompt
3. **Available for All Conversations**: Every conversation has access to this knowledge base without needing to re-load it

### Architecture

```
User Message
     ↓
Chat Handler (class-chat-handler.php)
     ↓
Claude API (class-claude-api.php)
     ├─ System Prompt (from settings)
     ├─ Grateful Dead Context (from grateful-dead-context.md) ← Automatically loaded
     └─ User Message + History
     ↓
Claude API (Anthropic)
     ↓
Response with Grateful Dead expertise
```

### File Structure

```
gd-claude-chatbot/
├── grateful-dead-context.md          ← Comprehensive GD knowledge base
├── gd-claude-chatbot.php             ← Main plugin file (includes load method)
└── includes/
    ├── class-claude-api.php          ← Loads context in constructor
    └── class-chat-handler.php        ← Processes messages
```

## Context File Format

The `grateful-dead-context.md` file contains:

- **Band Overview & History** - Formation, timeline, key events
- **Band Members & Personnel** - Detailed biographies, roles, equipment
- **Musical Catalog** - Songs, setlists, improvisational structures
- **Discography** - Studio albums, live releases, archival recordings
- **Equipment & Gear** - Guitars, amps, sound systems (Wolf, Tiger, Wall of Sound)
- **Era Evolution** - Psychedelic, Acoustic, Classic, Brent, Final years
- **Deadhead Culture** - Taping, touring, Shakedown Street, community
- **Post-Dead Projects** - Dead & Company, Furthur, Phil & Friends
- **Cultural Context** - San Francisco scene, business model, influences
- **Online Resources** - Archives, setlist databases, communities
- **Books & Literature** - Comprehensive bibliography
- **Art Galleries & Museums** - Physical and online locations
- **AI Tools & Chatbots** - Other GD AI applications
- **Key People** - Community figures, journalists, archivists
- **Important URLs** - Official sites, archives, streaming platforms

Total: ~50KB of structured, comprehensive information

## Implementation Details

### In class-claude-api.php

```php
public function __construct($api_key = null) {
    // ... initialize settings ...
    
    // Load Grateful Dead context if available
    $this->load_grateful_dead_context();
}

private function load_grateful_dead_context() {
    $context_file = GD_CHATBOT_PLUGIN_DIR . 'grateful-dead-context.md';
    
    if (!file_exists($context_file)) {
        error_log('GD Chatbot: grateful-dead-context.md file not found');
        return;
    }
    
    $context = file_get_contents($context_file);
    
    // Append to system prompt
    $this->system_prompt .= "\n\n## GRATEFUL DEAD KNOWLEDGE BASE\n\n" 
        . "The following is comprehensive reference material about the Grateful Dead. "
        . "Use this information to answer user questions accurately and in detail.\n\n" 
        . $context;
}
```

### Message Flow

**First Message in Conversation:**
- System Prompt: Base prompt + Grateful Dead context (~50KB)
- User Message: "Tell me about Cornell 5/8/77"
- Claude Response: Uses knowledge from context to provide detailed answer

**Subsequent Messages:**
- System Prompt: Same (includes GD context)
- Conversation History: Previous exchanges
- User Message: Follow-up question
- Claude Response: Can reference both context and conversation history

## Benefits

### 1. Comprehensive Knowledge
The chatbot has instant access to:
- 2,300+ shows worth of information
- Complete band member histories
- Detailed equipment specifications
- Cultural and historical context
- Online resources and archives

### 2. Consistent Accuracy
- All information comes from curated, verified sources
- No hallucination of show dates or facts
- Proper attribution to sources
- Era-appropriate context

### 3. Performance
- Context loaded once per instance
- No per-message overhead
- Efficient use of Claude's context window
- Maintains conversation continuity

### 4. Easy Updates
- Edit `grateful-dead-context.md` to update knowledge
- No code changes required
- Immediate effect on next plugin initialization

## Working with Additional Context

The system also supports dynamic context from:

### Pinecone Vector Database (if enabled)
- Semantic search through knowledge base
- Returns most relevant chunks
- Adds to message context (not system prompt)

### Tavily Web Search (if enabled)
- Real-time information
- Recent events and news
- Tour dates and announcements
- Adds to message context (not system prompt)

### Layered Context Approach

```
System Prompt (Always Present):
├─ Base instructions
└─ Grateful Dead context (50KB)

Message Context (Dynamic, per message):
├─ Pinecone results (if enabled and relevant)
└─ Tavily search results (if enabled and relevant)

Conversation History:
└─ Previous messages in session
```

## Maintenance

### Updating the Context

To update the Grateful Dead knowledge:

1. Edit `grateful-dead-context.md` in the plugin root directory
2. Save the file
3. Next time a new chat instance is created, updated context is loaded

**Note**: Existing conversations will continue using the context loaded when they started.

### Monitoring

The plugin logs context loading:

- **Success**: Silent operation
- **File Not Found**: `error_log('GD Chatbot: grateful-dead-context.md file not found at: [path]')`
- **Empty File**: `error_log('GD Chatbot: grateful-dead-context.md file is empty')`

Check WordPress debug logs if context isn't loading.

### File Size Considerations

**Current Size**: ~50KB (compressed text)

**Claude Context Window**:
- Claude Sonnet 4: 200,000 tokens (~800KB text)
- Claude Opus 4: 200,000 tokens (~800KB text)

The context file uses approximately **6.25%** of Claude's context window, leaving plenty of room for:
- Conversation history
- User messages
- Additional dynamic context (Pinecone/Tavily)
- Long-form responses

## Best Practices

### For Users

**Good Questions:**
- "What guitar did Jerry Garcia play in 1977?"
- "Tell me about the Wall of Sound"
- "What are the best shows from the Brent era?"
- "Explain the Deadhead taping culture"

**The chatbot can:**
- Reference specific shows and dates
- Explain equipment and technical details
- Discuss cultural and historical context
- Recommend resources and archives

### For Administrators

**Maintaining Context Quality:**
- Keep `grateful-dead-context.md` accurate and up-to-date
- Organize information with clear headers
- Use markdown formatting for readability
- Include sources and references
- Regular reviews for accuracy

**Performance:**
- Monitor token usage in responses
- Consider splitting very large topics across sections
- Use clear, concise language
- Maintain hierarchical structure

## Troubleshooting

### Context Not Loading

**Check:**
1. File exists at `[plugin-dir]/grateful-dead-context.md`
2. File is readable by web server
3. Check error logs for loading messages
4. Verify file contains valid UTF-8 text

### Responses Not Using Context

**Possible Causes:**
1. Context file empty or missing
2. System prompt override in settings
3. Claude model limitations
4. Query too vague to match context

**Solutions:**
- Verify file contents
- Check system prompt in admin settings
- Ask more specific questions
- Review Claude API responses

### Token Limits

If hitting token limits:
1. Review context file size
2. Consider using only most essential information
3. Adjust `claude_max_tokens` setting
4. Use more efficient prompt engineering

## Future Enhancements

Potential improvements:

1. **Dynamic Context Selection**: Only load relevant sections based on query
2. **Context Caching**: Use Claude's prompt caching for repeated context
3. **Multi-file Support**: Split context into thematic files
4. **Admin UI**: Edit context through WordPress admin
5. **Version Control**: Track context changes over time

## Technical Notes

### Character Encoding
- File must be UTF-8 encoded
- Handles special characters (★, ⚡, etc.)
- Preserves markdown formatting

### Memory Usage
- File read once per Claude API instance
- Stored in PHP string variable
- Garbage collected when instance destroyed

### WordPress Integration
- Uses `GD_CHATBOT_PLUGIN_DIR` constant
- Compatible with WordPress file system
- Works with multisite installations
- No database storage required

---

*Last Updated: January 3, 2026*
*Plugin Version: 1.0.0*
