# Quick Start - AI Power Integration

## ✅ What Was Done

Modified **GD Claude Chatbot** to use embeddings from **AI Power (gpt-ai-content-generator-premium)** plugin.

## 📋 Files Created/Modified

### New Files
- `includes/class-aipower-integration.php` - Integration class for AI Power

### Modified Files
- `includes/class-chat-handler.php` - Added AI Power context retrieval
- `gd-claude-chatbot.php` - Added integration loading and settings

## 🚀 How to Use

### 1. Install AI Power Plugin
Make sure **AI Power** plugin is installed and active.

### 2. Configure Pinecone in AI Power
- Go to AI Power settings
- Enter your Pinecone API key and host
- Configure your Pinecone index

### 3. Index Your Content
Use AI Power to index your WordPress posts/pages to Pinecone.

### 4. Done!
The chatbot will automatically use AI Power's indexed content when available.

## 🔍 How It Works

```
User asks question
    ↓
Chatbot queries Pinecone for relevant posts (via AI Power's embeddings)
    ↓
Retrieves full post content from WordPress  
    ↓
Formats as context for Claude
    ↓
Claude responds with information from your posts
```

## 📊 Context Sources (in order)

1. **Setlist Database** - Grateful Dead shows (if query matches)
2. **KB Loader** - Uploaded documents (if plugin active)
3. **AI Power Content** ← NEW! WordPress posts indexed via AI Power
4. **Direct Pinecone** - Direct Pinecone queries (if configured)
5. **Tavily Web Search** - Real-time web results (if enabled)

All sources are combined and sent to Claude.

## ⚙️ Default Settings

```php
'aipower_enabled' => true,          // Enabled by default
'aipower_max_results' => 10,        // Max 10 results
'aipower_min_score' => 0.35,        // 35% minimum relevance
```

## ✨ Features

- ✅ **Automatic Detection** - Works if AI Power is installed
- ✅ **No Configuration** - Uses AI Power's Pinecone settings
- ✅ **Post Type Filtering** - Can filter by post/page/custom types
- ✅ **Full Content** - Retrieves complete post content
- ✅ **Metadata Rich** - Includes titles, URLs, relevance scores
- ✅ **Backward Compatible** - Works with or without AI Power

## 🎯 Use Cases

### Blog Content
- Answer questions about blog posts
- Cite specific articles
- Reference published content

### Documentation
- Technical documentation
- How-to guides
- Product information

### Pages
- About page
- Services
- Product descriptions

## 🧪 Testing

### Check if Active
```php
$aipower = new GD_AIPower_Integration();
if ($aipower->is_available()) {
    echo "AI Power integration is active!";
}
```

### Test Search
Ask the chatbot a question related to your WordPress content.

## 📝 Example Interaction

**User**: "Tell me about your latest blog post"

**Chatbot**: 
```
Based on your WordPress content:

### Latest Product Update (Type: post, Relevance: 92.3%)
URL: https://yoursite.com/latest-update

We're excited to announce new features including...
[full post content]
```

## 🔧 Troubleshooting

### Integration Not Working?

1. **Check AI Power is Active**
   ```bash
   wp plugin list | grep gpt3-ai-content-generator
   ```

2. **Verify Pinecone Configuration**
   - Check AI Power settings
   - Verify Pinecone API key
   - Confirm index name

3. **Check Indexed Posts**
   - Go to AI Power
   - Verify posts are indexed
   - Check for indexing errors

4. **Check Chatbot Logs**
   Look for: `GD Chatbot: AI Power Pinecone integration active`

### No Results?

- **Index more content** in AI Power
- **Lower min_score** (currently 0.35)
- **Increase max_results** (currently 10)

## 💡 Tips

1. **Index Key Content First** - Start with important posts/pages
2. **Update Regularly** - Re-index when content changes
3. **Monitor Relevance** - Check which posts are being used
4. **Combine Sources** - Use alongside KB Loader for best results

## 📚 Documentation

- Full details: `AIPOWER-INTEGRATION.md`
- AI Power docs: Check AI Power plugin documentation
- Pinecone docs: https://docs.pinecone.io/

## 🎉 Benefits

✅ **No Manual Upload** - Uses existing WordPress content  
✅ **Always Current** - Reflects latest post updates  
✅ **Rich Metadata** - Titles, URLs, post types included  
✅ **WordPress Native** - Direct database access  
✅ **Scalable** - Handles large content libraries  

---

**Version**: Added in GD Claude Chatbot 1.3.0  
**Date**: January 4, 2026
