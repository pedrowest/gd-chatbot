# GD Claude Chatbot - Version 1.4.0 Release Notes

**Release Date**: January 4, 2026  
**Type**: Feature Update - AI Power Integration

## 🎉 What's New

### AI Power Plugin Integration
The chatbot now seamlessly integrates with the **AI Power (gpt-ai-content-generator-premium)** plugin to use your WordPress posts, pages, and uploaded files as context.

## ✨ New Features

### 1. Automatic AI Power Detection
- Detects when AI Power plugin is installed and configured
- Automatically uses AI Power's Pinecone embeddings
- No additional configuration required

### 2. WordPress Content Integration
- **Posts**: Uses all WordPress posts indexed by AI Power
- **Pages**: Uses all WordPress pages indexed by AI Power
- **Uploaded Files**: Uses PDF and TXT files uploaded via AI Power

### 3. Dual Integration Support
- Works **alongside** GD Knowledgebase Loader
- Works **alongside** direct Pinecone integration
- Works **alongside** Tavily web search
- All context sources are merged for comprehensive responses

## 📋 What's Included

### New Files
- `includes/class-aipower-integration.php` - AI Power integration class

### Modified Files
- `includes/class-chat-handler.php` - Added AI Power context retrieval
- `gd-claude-chatbot.php` - Added integration loading and default settings

### Version Updates
- Updated from 1.3.0 to 1.4.0
- Updated plugin constant `GD_CHATBOT_VERSION`

## 🔧 How It Works

### Content Sources (in order)
1. **Setlist Database** - Grateful Dead shows (if query matches)
2. **GD Knowledgebase Loader** - Uploaded documents (if plugin active)
3. **AI Power Content** ← NEW!
   - WordPress posts indexed via AI Power
   - WordPress pages indexed via AI Power
   - PDF files uploaded via AI Power
   - TXT files uploaded via AI Power
4. **Direct Pinecone** - Direct Pinecone queries (if configured)
5. **Tavily Web Search** - Real-time web results (if enabled)

All sources are combined and sent to Claude for comprehensive responses.

## 📊 Supported Content Types

### Via AI Power
- ✅ WordPress Posts (any post type)
- ✅ WordPress Pages
- ✅ PDF files (uploaded via AI Power chat)
- ✅ TXT files (uploaded via AI Power chat)
- ❌ CSV files (not supported by AI Power - use KB Loader instead)

### Via GD Knowledgebase Loader
- ✅ CSV files
- ✅ JSON files
- ✅ Markdown files
- ✅ PDF files
- ✅ DOCX files
- ✅ XLSX files

## ⚙️ Configuration

### Default Settings (Auto-configured)
```php
'aipower_enabled' => true,          // Enabled by default
'aipower_max_results' => 10,        // Max results to retrieve
'aipower_min_score' => 0.35,        // Minimum relevance score
```

### Requirements
- AI Power plugin installed and active
- Pinecone configured in AI Power
- WordPress posts/pages indexed via AI Power

### No Configuration Needed!
If AI Power is installed with Pinecone configured, the integration works automatically.

## 🎯 Use Cases

### Blog Content
- Answer questions about blog posts
- Cite specific articles
- Reference published content

### Documentation
- Technical documentation
- How-to guides
- Product information

### Uploaded Files
- PDF documentation
- Text files with data
- Reference materials

## 🔄 Upgrade Instructions

### From 1.3.0 to 1.4.0

**Safe to upgrade** - No breaking changes!

1. **Deactivate** version 1.3.0
2. **Delete** old plugin files
3. **Upload** version 1.4.0 zip
4. **Activate** the plugin
5. All settings are preserved

Or simply update via WordPress plugin updater.

### What's Preserved
- ✅ All settings
- ✅ Conversation history
- ✅ API keys
- ✅ Appearance settings
- ✅ Integration configurations

## 🧪 Testing

### Verify Integration
1. Ensure AI Power is installed and Pinecone is configured
2. Index some WordPress posts via AI Power
3. Ask the chatbot a question about your content
4. Check if it references your posts

### Expected Behavior
**User**: "Tell me about [your blog post topic]"

**Chatbot**: 
```
Based on your WordPress content:

### Post Title (Type: post, Relevance: 92%)
URL: https://yoursite.com/post

[Content from your WordPress post...]
```

## 📝 Context Format

The chatbot now sends this to Claude:

```markdown
## WORDPRESS & UPLOADED CONTENT CONTEXT

The following information is from WordPress posts/pages and uploaded files indexed with AI Power:

### Post Title (Type: post, Relevance: 85%)
URL: https://example.com/post
[Full post content...]

---

### document.pdf (Uploaded File, Relevance: 92%)
Uploaded: 2026-01-04 10:30:00
[Content from uploaded file...]
```

## 🐛 Bug Fixes

None - This is a feature-only release.

## 🔒 Security

- ✅ Read-only access to AI Power configuration
- ✅ No modifications to AI Power plugin
- ✅ Secure API key handling
- ✅ Proper nonce verification
- ✅ User permission checks

## 📚 Documentation

New documentation files (not in plugin zip):
- `AIPOWER-INTEGRATION.md` - Complete technical documentation
- `AIPOWER-QUICK-START.md` - Quick reference guide
- `AIPOWER-FILE-UPLOAD-SUPPORT.md` - File upload details
- `CSV-SUPPORT-ANSWER.md` - CSV file support info

## 🎁 Benefits

✅ **No Manual Upload** - Uses existing WordPress content  
✅ **Always Current** - Reflects latest post updates  
✅ **Rich Metadata** - Titles, URLs, post types included  
✅ **WordPress Native** - Direct database access  
✅ **Scalable** - Handles large content libraries  
✅ **Complementary** - Works with existing integrations  

## ⚠️ Known Limitations

### CSV Files Not Supported
AI Power chat uploads do not support CSV files. Use GD Knowledgebase Loader for CSV files instead.

### File Content Display
For uploaded files, the chatbot indicates the file matched but doesn't display full extracted content in context preview (Claude still has access through embeddings).

## 🔮 Future Enhancements

Potential additions in future versions:
- Admin UI for AI Power integration settings
- Statistics dashboard for indexed content
- Post type filtering options
- Caching for improved performance
- Support for additional file types

## 📦 Package Details

**File**: `gd-claude-chatbot-1.4.0.zip`  
**Size**: 55KB  
**Files**: 24 files (core plugin only, no documentation)

### Included Files
- Main plugin file
- Admin classes and assets
- Integration classes (Claude, Tavily, Pinecone, KB Loader, AI Power)
- Chat handler
- Public interface
- Styles and scripts

### Not Included (Development Only)
- Documentation files (*.md)
- Context files
- Test scripts
- Development notes

## 🔗 Compatibility

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **AI Power**: 2.3.50 or higher (optional)
- **GD KB Loader**: Any version (optional)

## 📞 Support

If you encounter issues:
1. Check that AI Power is installed and Pinecone is configured
2. Verify posts/pages are indexed in AI Power
3. Check WordPress debug log for errors
4. Ensure chatbot has proper API keys configured

## 🎊 Summary

Version 1.4.0 adds seamless integration with AI Power, allowing the chatbot to use your WordPress posts, pages, and uploaded files as context without any additional configuration. This provides richer, more current information from your own WordPress content to enhance chatbot responses.

The integration works alongside existing features (KB Loader, Pinecone, Tavily) to provide the most comprehensive and relevant context possible!

---

**Upgrade Today**: Upload `gd-claude-chatbot-1.4.0.zip` to WordPress!

**Previous Version**: 1.3.0  
**Current Version**: 1.4.0  
**Release Date**: January 4, 2026
