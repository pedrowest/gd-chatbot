# GD Claude Chatbot - AI Power Integration

## Overview

Modified the GD Claude Chatbot plugin to use embeddings created by the **AI Power (gpt-ai-content-generator-premium)** plugin instead of (or in addition to) the GD Knowledgebase Loader.

## Changes Made

### 1. New Integration Class

**File**: `includes/class-aipower-integration.php`

Created a new integration class that:
- Detects when AI Power plugin is installed and active
- Reads Pinecone configuration from AI Power's settings
- Queries Pinecone for WordPress posts indexed by AI Power
- Understands AI Power's metadata structure:
  ```php
  'source' => 'wordpress_post',
  'post_id' => (string)$post_id,
  'title' => $post_title,
  'type' => get_post_type($post_id),
  'url' => get_permalink($post_id),
  'vector_id' => 'wp_post_' . $post_id
  ```
- Retrieves full post content from WordPress based on post IDs
- Formats context for Claude API
- Provides filtering by post type and relevance score

### 2. Modified Chat Handler

**File**: `includes/class-chat-handler.php`

**Changes**:
- Added `$aipower_integration` property
- Instantiated `GD_AIPower_Integration` in constructor
- Added step 0.6 to query AI Power indexed content
- Results are merged with other context sources (setlists, KB Loader, Pinecone, Tavily)

**Flow**:
```
User Query
    ↓
0. Setlist Database (if applicable)
    ↓
0.5. GD Knowledgebase Loader (if available)
    ↓
0.6. AI Power Indexed Content (NEW!) ← Posts/Pages indexed via AI Power
    ↓
1. Direct Pinecone Query (if enabled)
    ↓
2. Tavily Web Search (if enabled)
    ↓
3. Combine all context
    ↓
4. Send to Claude with combined context
```

### 3. Modified Main Plugin File

**File**: `gd-claude-chatbot.php`

**Changes**:
- Added `require_once` for `class-aipower-integration.php`
- Added default options for AI Power integration:
  - `aipower_enabled`: true
  - `aipower_max_results`: 10
  - `aipower_min_score`: 0.35

## How It Works

### AI Power Integration

1. **Detection**: Checks if `WPAICG\WP_AI_Content_Generator` class exists
2. **Configuration**: Reads Pinecone API key and host from AI Power's `AIPKit_Providers` class
3. **Querying**: Uses the chatbot's existing `GD_Pinecone_API` class to query Pinecone
4. **Filtering**: Filters results by metadata (`source` = `wordpress_post`)
5. **Content Retrieval**: Gets full post content from WordPress using `post_id` from metadata
6. **Context Formatting**: Formats as "WORDPRESS CONTENT CONTEXT" for Claude

### Metadata Structure

AI Power stores vectors with this metadata:
```php
[
    'source' => 'wordpress_post',        // Identifies AI Power content
    'post_id' => '123',                  // WordPress post ID
    'title' => 'Post Title',             // Post title
    'type' => 'post',                    // Post type (post, page, etc.)
    'url' => 'https://...',              // Permalink
    'vector_id' => 'wp_post_123'         // Unique vector ID
]
```

The integration:
- Filters by `source` = `wordpress_post` to get only AI Power content
- Optionally filters by `type` to limit to specific post types
- Uses `post_id` to retrieve full content from WordPress
- Includes URL and title in context for Claude

### Context Format

```markdown
## WORDPRESS CONTENT CONTEXT

The following information is from your WordPress posts/pages indexed with AI Power:

### Post Title (Type: post, Relevance: 85.5%)
URL: https://example.com/post-title

[Full post content here, limited to 2000 characters]

---

### Another Post (Type: page, Relevance: 78.3%)
URL: https://example.com/another-post

[Full post content here]
```

## Advantages Over KB Loader

1. **No Additional Upload**: Uses posts/pages already in WordPress
2. **Automatic Updates**: When posts are updated in AI Power, embeddings update automatically
3. **Post Type Filtering**: Can filter by post type (posts, pages, custom types)
4. **Metadata Rich**: Includes URLs, post types, titles automatically
5. **WordPress Integration**: Direct access to full WordPress post data

## Configuration

### AI Power Setup

1. Install and activate **AI Power (gpt-ai-content-generator-premium)**
2. Configure Pinecone in AI Power:
   - Go to AI Power settings
   - Enter Pinecone API key
   - Set Pinecone host/index
3. Index your WordPress posts:
   - Use AI Power's content indexing feature
   - Select posts/pages to index
   - AI Power creates embeddings and stores in Pinecone

### Chatbot Setup

1. The integration is **automatic** - no configuration needed!
2. If AI Power is installed and has Pinecone configured, the chatbot will use it
3. Optional: Disable in chatbot settings (future enhancement)

### Settings (Default)

```php
'aipower_enabled' => true,          // Use AI Power content
'aipower_max_results' => 10,        // Max results to retrieve
'aipower_min_score' => 0.35,        // Minimum relevance score (0-1)
```

## Compatibility

- Works **alongside** GD Knowledgebase Loader (both can be active)
- Works **alongside** direct Pinecone integration
- Works **alongside** Tavily web search
- All context sources are merged and sent to Claude

## Requirements

- **AI Power plugin** installed and active
- **Pinecone** configured in AI Power
- **WordPress posts/pages** indexed via AI Power

## Testing

### Verify Integration

```php
// Check if AI Power is detected
$aipower = new GD_AIPower_Integration();
var_dump($aipower->is_available());  // Should return true if configured

// Test search
$results = $aipower->search('test query');
var_dump($results);

// Test context generation
$context = $aipower->get_context('test query');
echo $context;
```

### Expected Behavior

1. **AI Power Active + Configured**: Chatbot uses AI Power indexed content
2. **AI Power Not Active**: Integration silently inactive (no errors)
3. **AI Power Active but Not Configured**: Integration inactive (no errors)

## Future Enhancements

### Potential Additions

1. **Admin Settings UI**:
   - Enable/disable AI Power integration
   - Set max results and min score
   - Choose which post types to include

2. **Statistics Dashboard**:
   - Show number of indexed posts
   - Display Pinecone usage
   - Show most queried content

3. **Post Type Filters**:
   - Allow filtering by specific post types in settings
   - Exclude certain categories or tags

4. **Caching**:
   - Cache Pinecone query results
   - Cache retrieved post content
   - Reduce API calls

5. **Fallback Handling**:
   - If Pinecone fails, fallback to WordPress search
   - Graceful degradation

## Files Modified

1. ✅ `includes/class-aipower-integration.php` - NEW
2. ✅ `includes/class-chat-handler.php` - Modified
3. ✅ `gd-claude-chatbot.php` - Modified

## Version

- **Current Plugin Version**: 1.3.0
- **Integration Added**: 2026-01-04

## Notes

- The integration is **non-destructive** - doesn't modify AI Power plugin
- Uses **read-only** access to AI Power's Pinecone configuration
- **Backward compatible** - works with or without AI Power
- **Error handling** - gracefully handles missing AI Power or misconfiguration

## Summary

The GD Claude Chatbot now seamlessly integrates with AI Power's Pinecone embeddings, allowing it to use your WordPress posts and pages as context without requiring a separate upload process. This provides richer, more current information from your own WordPress content to enhance chatbot responses.
