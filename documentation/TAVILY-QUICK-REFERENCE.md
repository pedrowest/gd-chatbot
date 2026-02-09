# Tavily Integration - Quick Reference Guide

**GD Claude Chatbot Plugin**  
**Updated:** January 9, 2026

---

## Quick Start

### Basic Search

```php
$tavily = new GD_Tavily_API();
$results = $tavily->search('your search query');

if (!is_wp_error($results)) {
    foreach ($results['results'] as $result) {
        echo $result['title'] . ': ' . $result['url'];
    }
}
```

---

## New Features Overview

| Feature | Method | Description |
|---------|--------|-------------|
| **Encrypted Storage** | `save_api_key($key)` | Securely store API key |
| **Caching** | Automatic | 24-hour cache for all searches |
| **Usage Tracking** | `get_usage()` | Monthly API call counter |
| **Credibility Check** | `assess_source_credibility($url)` | Tier-based source rating |
| **Cache Management** | `clear_cache()` | Clear all cached results |
| **Rate Limiting** | Automatic | Quota enforcement |

---

## API Key Management

### Save Encrypted Key

```php
$tavily = new GD_Tavily_API();
$tavily->save_api_key('tvly-your-api-key-here');
```

### Get Decrypted Key

```php
$tavily = new GD_Tavily_API();
$api_key = $tavily->get_api_key();
```

### Display Masked Key

```php
$tavily = new GD_Tavily_API();
$masked = $tavily->get_api_key_masked();
// Returns: "tvly-****-1234"
```

---

## Caching

### Automatic Caching

All searches are automatically cached for 24 hours. No code changes needed!

```php
// First call - hits API
$results = $tavily->search('climate change');

// Second call (within 24 hours) - uses cache
$results = $tavily->search('climate change'); // Fast!
```

### Clear Cache

```php
$tavily = new GD_Tavily_API();
$success = $tavily->clear_cache();
```

### Get Cache Statistics

```php
$tavily = new GD_Tavily_API();
$stats = $tavily->get_cache_stats();

echo "Cached queries: " . $stats['count'];
echo "Cache size: " . $stats['size_formatted'];
```

---

## Usage Tracking

### Get Current Usage

```php
$tavily = new GD_Tavily_API();
$usage = $tavily->get_usage(); // Returns: 47 (calls this month)
```

### Set Quota

```php
update_option('gd_chatbot_tavily_quota', 1000);
```

### Check Usage Percentage

```php
$tavily = new GD_Tavily_API();
$usage = $tavily->get_usage();
$quota = get_option('gd_chatbot_tavily_quota', 1000);
$percentage = ($usage / $quota) * 100;

if ($percentage > 80) {
    echo "Warning: High usage!";
}
```

---

## Source Credibility Assessment (Grateful Dead Focus)

### Check Single Source

```php
$tavily = new GD_Tavily_API();
$assessment = $tavily->assess_source_credibility('https://www.dead.net/show/1977-05-08');

// Returns array with tier, category, description, domain
// Example: ['tier' => 'tier1', 'category' => 'official', 'description' => 'Official Grateful Dead website']
```

### Get Simple Tier String

```php
$tavily = new GD_Tavily_API();
$tier = $tavily->get_source_tier('https://archive.org/details/gd1977-05-08');

// Returns: 'tier1', 'tier2', 'tier3', or 'tier4'
```

### Assess All Results

```php
$tavily = new GD_Tavily_API();
$results = $tavily->search('Grateful Dead Cornell 1977');

foreach ($results['results'] as $result) {
    $credibility = $result['credibility']; // Already included in results
    $tier = $credibility['tier'];
    $label = GD_Tavily_API::get_tier_label($tier);
    
    echo "{$label}: {$result['title']}\n";
    echo "Source: {$credibility['description']}\n\n";
}
```

### Credibility Tiers (Grateful Dead Specific)

**Tier 1 - Official/Archival Sources (⭐):**
- `dead.net` - Official Grateful Dead website
- `gdao.org` - Grateful Dead Archive Online (UC Santa Cruz)
- `archive.org` - Internet Archive / Live Music Archive
- `gratefuldeadstudies.org` - Peer-reviewed academic journal
- `library.ucsc.edu` - UC Santa Cruz Library
- Band member sites: `bobweir.net`, `mickeyhart.net`, `philzone.com`
- Major news: AP, Reuters, NPR

**Tier 2 - Trusted Reference Sources (✓):**
- Setlist databases: `setlist.fm`, `deadlists.com`, `jerrybase.com`
- Performance databases: `headyversion.com`, `whitegum.com`, `deaddisc.com`
- Encyclopedias: `britannica.com`, `allmusic.com`, `discogs.com`, `wikipedia.org`
- Music publications: `rollingstone.com`, `relix.com`, `jambands.com`, `jambase.com`
- Bay Area news: `sfchronicle.com`, `sfgate.com`
- Academic publishers: `bloomsbury.com`

**Tier 3 - Community Sources (○):**
- Dead.net Forums
- Fan blogs: `lostliveddead.blogspot.com`, `deadessays.blogspot.com`
- Social media: Facebook, Twitter/X, Instagram, YouTube
- Lyrics/trivia: `genius.com`, `songfacts.com`

**Tier 4 - Unverified Sources (?):**
- All other domains - verify before citing

### Get Trusted Domains for Search Filtering

```php
$trusted_domains = GD_Tavily_API::get_trusted_gd_domains();
// Returns array of trusted GD-related domains for include_domains filter
```

### Get Tier Label

```php
$label = GD_Tavily_API::get_tier_label('tier1');
// Returns: "⭐ Official/Archival Source"
```

---

## Error Handling

### Check for Errors

```php
$tavily = new GD_Tavily_API();
$results = $tavily->search('query');

if (is_wp_error($results)) {
    $error_code = $results->get_error_code();
    $error_message = $results->get_error_message();
    
    switch ($error_code) {
        case 'quota_exceeded':
            echo "Monthly quota reached. Using cached results.";
            break;
        case 'tavily_auth_failed':
            echo "Invalid API key. Check settings.";
            break;
        case 'tavily_rate_limit':
            echo "Rate limit exceeded. Try again later.";
            break;
        default:
            echo "Error: " . $error_message;
    }
}
```

### Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| `no_api_key` | API key not configured | Add key in settings |
| `quota_exceeded` | Monthly limit reached | Increase quota or use cache |
| `tavily_auth_failed` | Invalid API key | Check key in settings |
| `tavily_rate_limit` | Too many requests | Wait or use cached results |
| `tavily_server_error` | Tavily service down | Try again later |

---

## Advanced Search Options

### Custom Search Parameters

```php
$tavily = new GD_Tavily_API();

$results = $tavily->search('query', array(
    'search_depth' => 'advanced',  // 'basic' or 'advanced'
    'max_results' => 10,            // 1-20
    'include_domains' => array('reuters.com', 'bbc.com'),
    'exclude_domains' => array('wikipedia.org')
));
```

### Search Depth Comparison

| Depth | Speed | Quality | Cost | Best For |
|-------|-------|---------|------|----------|
| **basic** | Fast (~1-2s) | Good | 1 credit | Quick queries, common facts |
| **advanced** | Slower (~2-4s) | Excellent | 2 credits | Critical info, research |

---

## Admin Settings

### Programmatic Configuration

```php
// Enable Tavily
update_option('gd_chatbot_tavily_enabled', 1);

// Set search depth
update_option('gd_chatbot_tavily_search_depth', 'advanced');

// Set max results
update_option('gd_chatbot_tavily_max_results', 5);

// Set quota
update_option('gd_chatbot_tavily_quota', 1000);

// Set trusted domains
update_option('gd_chatbot_tavily_include_domains', 'reuters.com, bbc.com, .gov');

// Set blocked domains
update_option('gd_chatbot_tavily_exclude_domains', 'wikipedia.org, reddit.com');
```

---

## AJAX Endpoints

### Test Connection

```javascript
jQuery.post(ajaxurl, {
    action: 'gd_test_tavily_connection',
    nonce: gdChatbotAdmin.nonce,
    api_key: 'tvly-your-key'
}, function(response) {
    if (response.success) {
        console.log('Connected!');
    }
});
```

### Clear Cache

```javascript
jQuery.post(ajaxurl, {
    action: 'gd_clear_tavily_cache',
    nonce: gdChatbotAdmin.nonce
}, function(response) {
    if (response.success) {
        console.log('Cache cleared!');
    }
});
```

---

## Performance Tips

### 1. Use Caching Effectively

```php
// ✅ Good - Let cache work
$results = $tavily->search('common query');

// ❌ Bad - Bypassing cache
$tavily->clear_cache();
$results = $tavily->search('common query');
```

### 2. Choose Appropriate Search Depth

```php
// ✅ Good - Use basic for simple queries
$results = $tavily->search('weather today', array(
    'search_depth' => 'basic'
));

// ✅ Good - Use advanced for critical info
$results = $tavily->search('medical research', array(
    'search_depth' => 'advanced'
));
```

### 3. Limit Results Appropriately

```php
// ✅ Good - Request only what you need
$results = $tavily->search('query', array(
    'max_results' => 3
));

// ❌ Bad - Requesting unnecessary results
$results = $tavily->search('query', array(
    'max_results' => 20
));
```

### 4. Monitor Usage

```php
// Check usage regularly
$usage = $tavily->get_usage();
$quota = get_option('gd_chatbot_tavily_quota', 1000);

if ($usage > $quota * 0.8) {
    // Consider optimizing or upgrading
    error_log("Tavily usage at 80%: $usage / $quota");
}
```

---

## Integration Examples

### In Chat Handler

```php
class GD_Chat_Handler {
    public function process_message($message) {
        $tavily = new GD_Tavily_API();
        
        // Check if search is needed
        if ($tavily->should_search($message)) {
            $results = $tavily->search($message);
            
            if (!is_wp_error($results)) {
                $context = $tavily->results_to_context($results);
                // Add to Claude prompt
            }
        }
    }
}
```

### Custom Search Widget

```php
function my_custom_search_widget() {
    $tavily = new GD_Tavily_API();
    
    if (isset($_GET['q'])) {
        $query = sanitize_text_field($_GET['q']);
        $results = $tavily->search($query);
        
        if (!is_wp_error($results)) {
            foreach ($results['results'] as $result) {
                $tier = $tavily->assess_source_credibility($result['url']);
                
                echo '<div class="search-result ' . $tier . '">';
                echo '<h3>' . esc_html($result['title']) . '</h3>';
                echo '<p>' . esc_html($result['content']) . '</p>';
                echo '<a href="' . esc_url($result['url']) . '">Read more</a>';
                echo '</div>';
            }
        }
    }
}
```

---

## Troubleshooting

### Issue: API Key Not Working

```php
// Check if key is saved
$tavily = new GD_Tavily_API();
$key = $tavily->get_api_key();

if (empty($key)) {
    echo "API key not configured";
} else {
    echo "API key found: " . $tavily->get_api_key_masked();
}

// Test connection
$result = $tavily->test_connection();
if (is_wp_error($result)) {
    echo "Connection failed: " . $result->get_error_message();
}
```

### Issue: High API Usage

```php
// Check cache effectiveness
$stats = $tavily->get_cache_stats();
$usage = $tavily->get_usage();

echo "Cached queries: " . $stats['count'];
echo "API calls this month: " . $usage;

// Calculate cache hit rate estimate
$total_searches = $usage + ($stats['count'] * 2); // Rough estimate
$cache_hit_rate = ($stats['count'] / $total_searches) * 100;
echo "Estimated cache hit rate: " . round($cache_hit_rate) . "%";
```

### Issue: Slow Searches

```php
// Switch to basic depth
update_option('gd_chatbot_tavily_search_depth', 'basic');

// Reduce max results
update_option('gd_chatbot_tavily_max_results', 3);

// Check if caching is working
$start = microtime(true);
$results = $tavily->search('test query');
$duration = microtime(true) - $start;

if ($duration < 0.1) {
    echo "Using cache (fast)";
} else {
    echo "API call (slow)";
}
```

---

## Best Practices

### ✅ Do

- Use caching for repeated queries
- Monitor usage regularly
- Set appropriate quotas
- Use basic depth for simple queries
- Assess source credibility
- Handle errors gracefully
- Test API connection after setup

### ❌ Don't

- Clear cache unnecessarily
- Always use advanced depth
- Request more results than needed
- Ignore quota warnings
- Store API keys unencrypted
- Skip error handling
- Make redundant API calls

---

## Database Schema

### Options

```sql
-- Encrypted API key
gd_chatbot_tavily_api_key_encrypted

-- Configuration
gd_chatbot_tavily_enabled (0 or 1)
gd_chatbot_tavily_search_depth ('basic' or 'advanced')
gd_chatbot_tavily_max_results (1-20)
gd_chatbot_tavily_quota (integer)
gd_chatbot_tavily_include_domains (comma-separated)
gd_chatbot_tavily_exclude_domains (comma-separated)

-- Usage tracking
gd_chatbot_tavily_usage_2026-01 (integer, auto-created monthly)
```

### Transients

```sql
-- Cached searches (24-hour TTL)
_transient_gd_chatbot_tavily_{md5_hash}
_transient_timeout_gd_chatbot_tavily_{md5_hash}
```

---

## Support

**Documentation:** See TAVILY-ENHANCEMENT-SUMMARY.md  
**Tavily API Docs:** https://docs.tavily.com  
**Settings:** WordPress Admin → GD Chatbot → Tavily

---

*Quick Reference Guide - GD Claude Chatbot Tavily Integration*
