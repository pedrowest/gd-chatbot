# ScubaGPT - AI Power Integration

## Overview

ScubaGPT Chatbot now integrates seamlessly with the **AI Power (gpt-ai-content-generator-premium)** plugin to use embeddings from your indexed WordPress posts, pages, and uploaded files as context for diving-related queries.

## What Was Added

### New Files

1. **`includes/class-scubagpt-pinecone-api.php`** - Pinecone API wrapper
   - Handles low-level Pinecone API communication
   - Generates embeddings using OpenAI
   - Queries vector database with metadata filters

2. **`includes/class-scubagpt-aipower-integration.php`** - AI Power integration class
   - Detects AI Power plugin availability
   - Reads Pinecone configuration from AI Power
   - Searches indexed content with relevance filtering
   - Formats context for Claude API

### Modified Files

1. **`includes/class-scubagpt-chat.php`**
   - Added AI Power integration property
   - Query AI Power content before Pinecone/Tavily (step 0.5)
   - Merge AI Power sources with other context sources
   - Applied to both standard and streaming message processing

2. **`scubagpt-chatbot.php`**
   - Include new integration classes
   - Added AI Power default settings
   - Auto-enable integration when AI Power is available

## How It Works

### Content Flow

```
User asks diving question
    ↓
0.5. AI Power Content ← NEW! WordPress posts/pages & uploaded files
    ↓
1. Pinecone Direct Query (if configured separately)
    ↓
2. Tavily Web Search (if enabled)
    ↓
3. Combine all context sources
    ↓
4. Send to Claude with combined diving knowledge
    ↓
5. Return response with source attribution
```

### AI Power Content Types

The integration retrieves **two types** of content from AI Power:

#### 1. WordPress Posts/Pages
**Metadata Structure**:
```php
'source' => 'wordpress_post',
'post_id' => '123',
'title' => 'Great Barrier Reef Diving Guide',
'type' => 'post',  // or 'page'
'url' => 'https://yoursite.com/great-barrier-reef',
'vector_id' => 'wp_post_123'
```

**Context Format**:
```markdown
### Great Barrier Reef Diving Guide (Type: post, Relevance: 92.3%)
URL: https://yoursite.com/great-barrier-reef

[Full post content about diving the Great Barrier Reef...]
```

#### 2. Uploaded Files (PDF, TXT)
**Metadata Structure**:
```php
'source' => 'chat_file_upload',
'original_filename' => 'padi-dive-manual.pdf',
'file_upload_context_id' => 'pinecone_chat_file_...',
'user_id' => '1',
'session_id' => 'abc123',
'timestamp' => 1704398400
```

**Context Format**:
```markdown
### padi-dive-manual.pdf (Uploaded File, Relevance: 88.7%)
Uploaded: 2026-01-04 15:30:00

[Content from uploaded file matched your query about scuba diving with 88.7% relevance]
```

## Setup Instructions

### Prerequisites

1. ✅ **AI Power Plugin** - Install and activate AI Power (gpt-ai-content-generator-premium)
2. ✅ **Pinecone Account** - Sign up at https://www.pinecone.io/
3. ✅ **OpenAI API Key** - For generating embeddings
4. ✅ **Claude API Key** - For chatbot responses (ScubaGPT requirement)

### Step 1: Configure AI Power

1. **Install AI Power Plugin**
   - Upload and activate the AI Power plugin in WordPress
   - Navigate to AI Power settings

2. **Configure Pinecone in AI Power**
   - Go to **AI Power > Settings > Vector Database**
   - Select **Pinecone** as your vector database
   - Enter your **Pinecone API Key**
   - Enter your **Pinecone Host URL** (e.g., `https://your-index-abc123.svc.environment.pinecone.io`)
   - Set your **Pinecone Index Name**
   - Optionally set a **Namespace**
   - Save settings

3. **Configure OpenAI in AI Power**
   - Go to **AI Power > Settings > AI Provider**
   - Select **OpenAI** as embedding provider
   - Enter your **OpenAI API Key**
   - Choose embedding model (default: `text-embedding-3-small`)
   - Save settings

### Step 2: Index Your Diving Content

1. **Index Posts and Pages**
   - Go to **AI Power > Content > Index Content**
   - Select posts/pages about diving topics to index
   - Click **Index to Vector Database**
   - Wait for indexing to complete
   - AI Power creates embeddings and stores them in Pinecone

2. **Upload Reference Files** (Optional)
   - Use AI Power's chat interface to upload diving reference files
   - Supported formats: PDF, TXT
   - Examples: PADI manuals, dive site guides, safety documents
   - Files are automatically indexed to Pinecone

### Step 3: Configure ScubaGPT (Optional)

The integration is **automatic** - no configuration required! But you can customize:

**Default Settings** (automatically applied):
```php
'scubagpt_aipower_enabled' => true,         // Enable AI Power integration
'scubagpt_aipower_max_results' => 10,       // Max results to retrieve
'scubagpt_aipower_min_score' => 0.35,       // Minimum relevance score (35%)
```

**To Customize** (add to your theme's functions.php or custom plugin):
```php
// Disable AI Power integration
update_option('scubagpt_aipower_enabled', false);

// Increase max results
update_option('scubagpt_aipower_max_results', 15);

// Raise relevance threshold
update_option('scubagpt_aipower_min_score', 0.50); // 50%
```

## Usage Examples

### Example 1: Diving Destination Query

**User**: "What are the best dive sites in the Maldives?"

**ScubaGPT Retrieves**:
- 📝 Blog post: "Top 10 Maldives Dive Sites" (95% relevance)
- 📄 Uploaded PDF: "Maldives Diving Guide 2026.pdf" (88% relevance)
- 🌐 Tavily web search: Recent articles about Maldives diving

**Response**: Uses information from your WordPress content, uploaded files, AND web search to provide comprehensive, up-to-date diving recommendations.

### Example 2: Technical Diving Question

**User**: "What is the no-decompression limit at 30 meters?"

**ScubaGPT Retrieves**:
- 📄 Uploaded file: "PADI-Open-Water-Manual.pdf" (92% relevance)
- 📝 Page: "Diving Safety Guidelines" (78% relevance)
- 🗂️ Pinecone: Dive tables and safety information

**Response**: Accurate technical information from your indexed diving manuals and safety documentation.

### Example 3: Marine Life Inquiry

**User**: "Tell me about manta ray encounters"

**ScubaGPT Retrieves**:
- 📝 Post: "Swimming with Manta Rays" (91% relevance)
- 📄 File: "Marine-Life-Guide.pdf" (85% relevance)
- 🌐 Tavily: Recent manta ray conservation news

**Response**: Rich content combining your articles, reference materials, and current information.

## Context Format for Claude

When AI Power content is found, ScubaGPT sends this format to Claude:

```markdown
## WORDPRESS & UPLOADED CONTENT CONTEXT

The following information is from WordPress posts/pages and uploaded files indexed with AI Power:

### Great Barrier Reef Diving Guide (Type: post, Relevance: 92.3%)
URL: https://yoursite.com/great-barrier-reef

The Great Barrier Reef is the world's largest coral reef system...
[Full post content, up to 2000 characters]

---

### PADI-Dive-Manual.pdf (Uploaded File, Relevance: 88.7%)
Uploaded: 2026-01-04 10:30:00

[Content from uploaded file matched your query about scuba diving with 88.7% relevance]

---

### Dive Safety Tips (Type: page, Relevance: 76.2%)
URL: https://yoursite.com/dive-safety

Always check your equipment before diving...
[Page content]
```

## Source Attribution

Sources are tracked and displayed with clear attribution:

**WordPress Post**:
```json
{
    "title": "Great Barrier Reef Diving Guide",
    "type": "aipower_post",
    "post_type": "post",
    "score": 92.3,
    "url": "https://yoursite.com/great-barrier-reef",
    "post_id": 123
}
```

**Uploaded File**:
```json
{
    "title": "PADI-Dive-Manual.pdf",
    "type": "aipower_file",
    "post_type": "file",
    "score": 88.7,
    "uploaded": "2026-01-04 10:30:00"
}
```

## Benefits

### ✅ **No Manual Upload**
Use existing WordPress posts and pages - no need to upload separately to ScubaGPT.

### ✅ **Automatic Updates**
When you update posts in WordPress and re-index with AI Power, the chatbot automatically uses the latest content.

### ✅ **File Upload Support**
Upload diving manuals, guides, and reference documents via AI Power chat interface.

### ✅ **Rich Metadata**
Full URLs, post types, relevance scores, and upload timestamps included.

### ✅ **WordPress Native**
Direct database access to post content for complete, accurate information.

### ✅ **Multiple Sources**
Combines AI Power content with Pinecone queries and Tavily web search for comprehensive answers.

### ✅ **Relevance Filtering**
Only includes content above 35% relevance threshold (configurable).

## Advantages Over Other Methods

| Feature | AI Power Integration | Manual Upload | Direct Pinecone |
|---------|---------------------|---------------|-----------------|
| **Auto-sync with WP** | ✅ Yes | ❌ No | ❌ No |
| **File uploads** | ✅ PDF, TXT | ⚠️ Limited | ❌ No |
| **Post type filtering** | ✅ Yes | ❌ No | ⚠️ Manual |
| **URL attribution** | ✅ Automatic | ❌ Manual | ⚠️ Manual |
| **Content updates** | ✅ Automatic | ❌ Re-upload | ❌ Re-index |
| **Setup complexity** | ⭐ Low | ⭐⭐ Medium | ⭐⭐⭐ High |

## Troubleshooting

### Integration Not Working?

**1. Check AI Power is Active**
```bash
# Via WP-CLI
wp plugin list | grep gpt3-ai-content-generator

# Via WordPress Admin
Plugins > Installed Plugins > Look for "AI Power"
```

**2. Verify Pinecone Configuration**
- Go to AI Power settings
- Check Pinecone API key is entered
- Verify Pinecone host URL is correct
- Confirm index name matches

**3. Check Content is Indexed**
- Go to AI Power > Content > Index Content
- Verify posts/pages show as "Indexed"
- Check for any indexing errors in AI Power logs

**4. Check ScubaGPT Logs**
Look for: `ScubaGPT: AI Power Pinecone integration active`

### No Results from AI Power?

**Increase Context Window**:
```php
update_option('scubagpt_aipower_max_results', 15);
```

**Lower Relevance Threshold**:
```php
update_option('scubagpt_aipower_min_score', 0.25); // 25%
```

**Index More Content**:
- Index more diving-related posts
- Upload more reference files
- Ensure content is relevant to diving topics

### Sources Not Showing?

The integration extracts sources automatically. If sources aren't showing:

1. Check that `log_conversations` is enabled in ScubaGPT settings
2. Verify JavaScript console for errors
3. Ensure chat handler is returning source data

## File Upload Support

### Supported Formats

AI Power currently supports:
- ✅ **PDF files** (`.pdf`) - Text extracted via PDF parser
- ✅ **Text files** (`.txt`) - Plain text content
- ❌ **CSV files** (`.csv`) - NOT supported for AI Power chat uploads

### Alternative for CSV Files

If you need to use CSV data:
1. **Convert to TXT** - Save CSV as tab-delimited or pipe-delimited text
2. **Create WordPress Post** - Import CSV data into a post and index it
3. **Use Alternative Plugin** - Consider plugins specifically for CSV knowledge bases

## Statistics and Monitoring

### Check Integration Status

```php
$aipower = new ScubaGPT_AIPower_Integration();

if ($aipower->is_available()) {
    echo "AI Power integration is active!";
    
    $stats = $aipower->get_stats();
    echo "Total vectors: " . $stats['total_vectors'];
    echo "Indexed posts: " . $stats['indexed_posts'];
    echo "Index name: " . $stats['index_name'];
}
```

### Test Search

```php
$aipower = new ScubaGPT_AIPower_Integration();
$results = $aipower->search('manta rays');

foreach ($results['matches'] as $match) {
    echo $match['metadata']['title'] . " - " . $match['score'];
}
```

## Best Practices

### 1. **Index Relevant Content First**
Start with your most important diving posts, guides, and reference materials.

### 2. **Use Clear Titles**
Ensure WordPress posts have descriptive titles - they're used in context formatting.

### 3. **Keep Content Updated**
When updating posts, re-index them in AI Power to keep chatbot knowledge current.

### 4. **Upload Key References**
Upload important diving manuals, safety guides, and technical documents as PDFs.

### 5. **Monitor Relevance Scores**
Check which content is being retrieved - adjust min_score if needed.

### 6. **Combine with Tavily**
Enable Tavily for real-time diving news and current conditions alongside your evergreen content.

### 7. **Test Regularly**
Ask test questions to ensure the chatbot is retrieving relevant content from AI Power.

## Technical Details

### Metadata Filter

The integration uses this Pinecone filter to retrieve AI Power content:

```php
$filter = array(
    'source' => array('$in' => array('wordpress_post', 'chat_file_upload'))
);

// Optional: Filter by post type
if (!empty($post_types)) {
    $filter['type'] = array('$in' => array('post', 'page'));
}
```

### Embedding Generation

Embeddings are generated using OpenAI's API:
- Model: `text-embedding-3-small` (default)
- Dimensions: 1536
- Generates vector representation of query text
- Compared against indexed content vectors

### Content Retrieval

For WordPress posts:
1. Pinecone returns post_id from metadata
2. Integration queries WordPress database for full content
3. Content is stripped of HTML tags
4. Limited to 2000 characters per post
5. Formatted with title, URL, and relevance score

## Version Information

- **ScubaGPT Version**: 1.0.0 with AI Power Integration
- **Integration Added**: January 2026
- **Compatible with**: AI Power 2.x and above
- **Requires**: WordPress 6.0+, PHP 8.0+

## Support and Resources

### Documentation
- AI Power docs: Check AI Power plugin documentation
- Pinecone docs: https://docs.pinecone.io/
- OpenAI embeddings: https://platform.openai.com/docs/guides/embeddings

### Getting Help
- Review integration logs in WordPress debug.log
- Check AI Power support for indexing issues
- Test Pinecone connection independently
- Verify OpenAI API key has embeddings access

## Summary

✅ **Automatic Integration** - Works when AI Power is configured  
✅ **WordPress Content** - Uses your posts and pages as chatbot knowledge  
✅ **File Uploads** - PDF and TXT diving manuals indexed seamlessly  
✅ **Relevance Filtering** - Only returns content above threshold  
✅ **Source Attribution** - Clear indication of where information came from  
✅ **Combined Context** - Merges with Pinecone and Tavily for comprehensive answers  

The AI Power integration transforms ScubaGPT into a comprehensive diving assistant powered by YOUR content, providing accurate, relevant, and well-sourced information to help divers plan their next underwater adventure! 🤿🐠🌊

---

**Updated**: January 2026  
**Plugin**: ScubaGPT Chatbot 1.0.0
