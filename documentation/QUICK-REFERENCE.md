# Grateful Dead Chatbot - Quick Reference

## File Locations

```
gd-claude-chatbot/
├── grateful-dead-context.md          ← Main knowledge base (50KB)
├── gd-claude-chatbot.php             ← Main plugin file
├── README.md                         ← User documentation
├── CONTEXT-INTEGRATION.md            ← Technical documentation
├── CHANGES.md                        ← Change summary
└── includes/
    ├── class-claude-api.php          ← Loads context automatically
    └── class-chat-handler.php        ← Processes messages
```

## How Context is Loaded

```
┌─────────────────────────────────────┐
│  WordPress Plugin Initialization   │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   new GD_Claude_API()               │
│   (constructor called)              │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   load_grateful_dead_context()      │
│   - Reads grateful-dead-context.md  │
│   - Appends to $this->system_prompt │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Context Ready for All Messages    │
│   (stored in system prompt)         │
└─────────────────────────────────────┘
```

## Message Flow

```
User Message
    ↓
┌───────────────────────────────────────────┐
│ Frontend: Streaming Request               │
│ ├─ Opens EventSource connection          │
│ └─ Creates message placeholder           │
└───────────────┬───────────────────────────┘
                │
                ▼
┌───────────────────────────────────────────┐
│ Chat Handler: process_message_stream()   │
│ ├─ Query Pinecone (optional)              │
│ └─ Search Tavily (optional)               │
└───────────────┬───────────────────────────┘
                │
                ▼
┌───────────────────────────────────────────┐
│ Claude API: send_message_stream()        │
│                                           │
│ System Prompt:                            │
│ ├─ Base instructions                      │
│ └─ Grateful Dead context (50KB) ←────────│
│                                           │
│ Messages:                                 │
│ ├─ Conversation history                   │
│ ├─ Dynamic context (Pinecone/Tavily)     │
│ └─ Current user message                   │
└───────────────┬───────────────────────────┘
                │
                ▼
┌───────────────────────────────────────────┐
│ Anthropic Claude API (Streaming)         │
│ Sends chunks as they're generated        │
└───────────────┬───────────────────────────┘
                │
                ▼
┌───────────────────────────────────────────┐
│ Response Streams Back to Frontend        │
│ ├─ Text appears progressively             │
│ ├─ Animated cursor shows progress (▋)    │
│ └─ Cursor removed when complete           │
└───────────────────────────────────────────┘
```

## Context Content Overview

### `grateful-dead-context.md` Contains:

1. **Band Overview & History** - Formation, timeline, shows (~2,300)
2. **Band Members** - Jerry, Bob, Phil, Bill, Mickey, + keyboards
3. **Musical Catalog** - 300+ songs, setlists, jam structures
4. **Discography** - Studio albums, Dick's Picks, Dave's Picks
5. **Equipment & Gear** - Wolf, Tiger, Lightning Bolt, Wall of Sound
6. **Eras** - Psychedelic → Acoustic → Classic → Brent → Final
7. **Deadhead Culture** - Taping, touring, Shakedown Street
8. **Post-Dead Projects** - Dead & Co, Furthur, Phil & Friends
9. **Cultural Context** - SF scene, Acid Tests, business model
10. **Resources** - Archives, setlists, books, galleries, URLs

**Total**: ~50KB of structured markdown (~1,000 lines)

## Key Capabilities

### What the Chatbot Can Do:

✅ Answer questions about band history  
✅ Discuss specific shows and performances  
✅ Explain equipment and technical details  
✅ Recommend recordings and resources  
✅ Discuss Deadhead culture and community  
✅ Reference books, archives, and websites  
✅ Provide context-appropriate recommendations  
✅ Handle follow-up questions with context  
✅ **Stream responses in real-time** with visual feedback  

### Example Questions:

- "What guitar did Jerry play at Cornell 5/8/77?"
- "Tell me about the Wall of Sound"
- "What are the essential Grateful Dead books?"
- "Explain the Deadhead taping culture"
- "What happened during the Europe '72 tour?"
- "How did the band's sound change in the 1980s?"

## Technical Specs

### Context Window:
- **Claude Sonnet 4**: 200K tokens (~800KB text)
- **Context Usage**: ~12.5K tokens (~50KB)
- **Percentage Used**: ~6.25%
- **Remaining**: ~187.5K tokens

### Performance:
- **Load Time**: < 0.1 seconds
- **Memory**: ~50KB per instance
- **Latency**: Negligible
- **Overhead**: None per message

### Error Handling:
- Missing file → Logs error, continues
- Empty file → Logs error, continues
- Read error → Logs error, continues
- Plugin remains functional

## Maintenance

### To Update Context:

1. Open `grateful-dead-context.md` in text editor
2. Make changes (add info, correct errors, etc.)
3. Save file
4. New chat sessions will use updated context

**Note**: Active conversations keep original context until restarted.

### To Check Context is Loading:

1. Enable WordPress debug logging:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Check `/wp-content/debug.log` for:
   - No messages = successful load
   - Error messages = file missing or empty

3. Test with a question:
   ```
   User: "What guitar did Jerry play in 1979?"
   Expected: "Tiger, built by Doug Irwin..."
   ```

### File Requirements:

- **Location**: Plugin root directory
- **Name**: `grateful-dead-context.md` (exact)
- **Format**: UTF-8 encoded text
- **Size**: ~50KB (flexible)
- **Permissions**: Readable by web server

## Configuration Settings

### In WordPress Admin → GD Chatbot → Settings:

**Claude API Tab:**
- API Key: Required
- Model: claude-sonnet-4-20250514 (recommended)
- Max Tokens: 4096 (default)
- Temperature: 0.7 (default)
- System Prompt: Base instructions (context auto-appends)

**Tavily Tab** (Optional):
- Enable for real-time web search
- Adds current information to responses

**Pinecone Tab** (Optional):
- Enable for vector database search
- Requires embedding API setup

## Troubleshooting

### Context Not Working:

**Symptom**: Generic responses, no GD knowledge

**Check:**
1. File exists: `[plugin-dir]/grateful-dead-context.md`
2. File has content: `ls -lh grateful-dead-context.md` (should show ~50KB)
3. Check logs: Look for error messages in debug.log
4. Test specific query: Ask about "Cornell 5/8/77"

**Fix:**
- If missing: Copy from backup or context/ folder
- If empty: Re-download or restore
- If permission issue: `chmod 644 grateful-dead-context.md`

### Responses Too Long:

**Symptom**: Token limit errors

**Solution:**
- Increase `claude_max_tokens` in settings
- Or simplify questions
- Or edit context file to be more concise

### Outdated Information:

**Symptom**: Old or incorrect information in responses

**Solution:**
- Edit `grateful-dead-context.md` directly
- Update the specific section
- Save file
- New chats will use updated info

## Resources

### Documentation:
- `USER-GUIDE.md` - Complete end-user guide for chatbot users
- `README.md` - Setup, installation, and admin guide
- `CONTEXT-INTEGRATION.md` - Technical details on GD knowledge base
- `SETLIST-DATABASE.md` - Technical details on setlist database
- `STREAMING.md` - Technical details on streaming implementation
- `GD-THEME.md` - Technical details on Grateful Dead theme
- `CHANGES.md` - Implementation summary
- `QUICK-REFERENCE.md` - This file

### External:
- [dead.net](https://www.dead.net) - Official site
- [archive.org/details/GratefulDead](https://archive.org/details/GratefulDead) - Live recordings
- [jerrybase.com](https://jerrybase.com) - Setlist database

### Support:
- IT Influentials: [it-influentials.com](https://it-influentials.com)
- WordPress Forums
- GitHub Issues (if applicable)

## Best Practices

### For Users:
1. **Be Specific**: "1977 shows" better than "best shows"
2. **Follow Up**: Build on previous questions
3. **Ask for Sources**: Request archive links, book references
4. **Explore Topics**: Equipment, eras, culture, etc.

### For Admins:
1. **Keep Updated**: Review and update context quarterly
2. **Monitor Logs**: Check for errors periodically
3. **Test Changes**: Verify after updating context
4. **Backup**: Keep backup of context file

### For Developers:
1. **Document Changes**: Update CHANGES.md when modifying
2. **Test Thoroughly**: Check all context loading paths
3. **Handle Errors**: Always gracefully degrade
4. **Maintain Structure**: Keep markdown organized

## Version Info

- **Plugin Version**: 1.0.0
- **Context Version**: January 3, 2026
- **Claude Model**: claude-sonnet-4-20250514
- **WordPress**: 5.8+ required
- **PHP**: 7.4+ required

---

**Quick Start**: Install plugin → Add Claude API key → Chat works with full GD knowledge automatically!
