# ScubaGPT - AI Power Integration Quick Start

## ✅ What's New

ScubaGPT now integrates with **AI Power (gpt-ai-content-generator-premium)** to use your WordPress posts, pages, and uploaded files as diving knowledge!

## 🚀 Quick Setup (5 Minutes)

### Step 1: Install AI Power
1. Install and activate the AI Power plugin
2. Navigate to AI Power settings in WordPress admin

### Step 2: Configure Pinecone
1. Sign up for Pinecone at https://www.pinecone.io/ (free tier available)
2. Create a new index in Pinecone
3. In AI Power settings:
   - Go to **Vector Database** settings
   - Enter your **Pinecone API Key**
   - Enter your **Pinecone Host URL**
   - Enter your **Index Name**
   - Save settings

### Step 3: Configure OpenAI
1. Get OpenAI API key from https://platform.openai.com/
2. In AI Power settings:
   - Go to **AI Provider** settings
   - Enter your **OpenAI API Key**
   - Select embedding model: `text-embedding-3-small`
   - Save settings

### Step 4: Index Your Content
1. Go to **AI Power > Content > Index Content**
2. Select diving-related posts and pages
3. Click **Index to Vector Database**
4. Wait for indexing to complete

### Step 5: Done! 🎉
ScubaGPT will automatically use your indexed content. No additional configuration needed!

## 📊 How It Works

```
User asks: "Best dive sites in Bali?"
    ↓
ScubaGPT queries AI Power indexed content
    ↓
Finds your post: "Bali Diving Guide" (92% relevance)
    ↓
Finds uploaded file: "Indonesia-Dive-Sites.pdf" (85% relevance)
    ↓
Also searches Tavily for recent dive reports
    ↓
Combines all sources and sends to Claude
    ↓
Returns comprehensive answer with source links
```

## 🎯 Content Sources

The integration retrieves from AI Power:

### 1. WordPress Posts/Pages
- Blog posts about diving destinations
- Destination guides
- Dive site reviews
- Equipment reviews
- Safety guidelines

### 2. Uploaded Files
- PADI dive manuals (PDF)
- Marine life guides (PDF)
- Dive site maps and guides (PDF, TXT)
- Safety documentation (PDF, TXT)

**Supported Formats**: PDF, TXT

## 💡 Usage Examples

### Example 1: Destination Research
**Query**: "Tell me about diving in the Maldives"

**Retrieved**:
- 📝 Your post: "Maldives Diving Paradise" (95%)
- 📄 Uploaded: "Maldives-Guide-2026.pdf" (88%)
- 🌐 Web: Recent Maldives diving conditions

### Example 2: Technical Questions
**Query**: "What is the maximum depth for Open Water divers?"

**Retrieved**:
- 📄 Uploaded: "PADI-Open-Water-Manual.pdf" (94%)
- 📝 Your page: "Certification Levels Explained" (82%)

### Example 3: Marine Life
**Query**: "Where can I see whale sharks?"

**Retrieved**:
- 📝 Your posts about whale shark dive sites (91%)
- 📄 Uploaded: "Marine-Encounters-Guide.pdf" (87%)
- 🌐 Web: Current whale shark migration patterns

## ⚙️ Default Settings

```php
'scubagpt_aipower_enabled' => true,      // Auto-enabled
'scubagpt_aipower_max_results' => 10,    // Up to 10 results
'scubagpt_aipower_min_score' => 0.35,    // 35% minimum relevance
```

**Automatically applied** - no configuration needed!

## 🔧 Customization (Optional)

Want to adjust settings? Add to your theme's `functions.php`:

```php
// Get more results
update_option('scubagpt_aipower_max_results', 15);

// Higher relevance threshold (more selective)
update_option('scubagpt_aipower_min_score', 0.50); // 50%

// Lower threshold (more results)
update_option('scubagpt_aipower_min_score', 0.25); // 25%

// Disable integration
update_option('scubagpt_aipower_enabled', false);
```

## 📋 Content Indexing Tips

### What to Index First

✅ **High Priority**:
- Top dive destination guides
- Most popular dive sites
- Safety and certification information
- Equipment guides

✅ **Medium Priority**:
- Marine life guides
- Travel tips
- Dive trip reports
- Photography guides

✅ **Upload as Files**:
- PADI/SSI certification manuals
- Comprehensive dive site databases
- Technical diving references
- Marine species identification guides

### Content Best Practices

1. **Use Clear Titles** - They appear in chatbot responses
2. **Keep Content Current** - Re-index when updating
3. **Add Relevant Keywords** - Helps with relevance matching
4. **Organize by Topic** - Use categories and tags
5. **Upload References** - PDFs of dive manuals and guides

## 🐛 Troubleshooting

### Not Working?

**Check 1: AI Power Active?**
```
WordPress Admin > Plugins > Look for "AI Power"
Status should be "Active"
```

**Check 2: Pinecone Configured?**
```
AI Power > Settings > Vector Database
- API Key entered? ✅
- Host URL entered? ✅
- Index name entered? ✅
```

**Check 3: Content Indexed?**
```
AI Power > Content > Index Content
Posts should show "Indexed" status
```

**Check 4: OpenAI Configured?**
```
AI Power > Settings > AI Provider
OpenAI API key entered? ✅
```

### No Results?

**Solution 1: Index More Content**
- Add more diving-related posts
- Upload reference files
- Ensure content is about diving topics

**Solution 2: Lower Threshold**
```php
update_option('scubagpt_aipower_min_score', 0.25);
```

**Solution 3: Increase Results**
```php
update_option('scubagpt_aipower_max_results', 15);
```

## 📈 Monitoring

### Check Integration Status

Look for this in WordPress debug log:
```
ScubaGPT: AI Power Pinecone integration active
```

### View Statistics

```php
$aipower = new ScubaGPT_AIPower_Integration();
$stats = $aipower->get_stats();

echo "Total vectors: " . $stats['total_vectors'];
echo "Indexed posts: " . $stats['indexed_posts'];
```

## ✨ Features

✅ **Automatic Detection** - Works when AI Power is configured  
✅ **No Extra Setup** - Uses AI Power's Pinecone configuration  
✅ **Post Type Filtering** - Can filter by post/page type  
✅ **Full Content** - Retrieves complete post content (up to 2000 chars)  
✅ **Rich Metadata** - Includes titles, URLs, relevance scores  
✅ **File Support** - PDF and TXT file uploads  
✅ **Backward Compatible** - Works with or without AI Power  

## 🎁 Benefits

### For Site Visitors
- More accurate answers using YOUR diving content
- Source links back to your posts
- Comprehensive information from multiple sources
- Up-to-date diving knowledge

### For Site Owners
- Leverage existing WordPress content
- No duplicate content management
- Automatic updates when posts change
- Track which content is most relevant
- Better engagement and conversions

## 📚 Content Ideas

### Posts to Create/Index

1. **Dive Destinations**
   - Country/region diving guides
   - Top dive sites lists
   - Best time to visit guides
   - Budget vs luxury destinations

2. **Marine Life**
   - Species identification guides
   - Where to see specific animals
   - Marine conservation info
   - Photography tips

3. **Technical Info**
   - Certification levels explained
   - Equipment buying guides
   - Safety procedures
   - Dive planning tips

4. **Trip Planning**
   - Packing lists
   - Travel insurance tips
   - Booking recommendations
   - Liveaboard reviews

### Files to Upload

1. **PADI Manuals** - Open Water, Advanced, etc.
2. **Dive Tables** - NDL tables, safety stops
3. **Marine Guides** - Species identification books
4. **Site Databases** - Comprehensive dive site info
5. **Safety Docs** - DAN safety guidelines

## 🔄 Update Workflow

When you update diving content:

1. **Edit WordPress Post** - Update your diving guide
2. **Re-index in AI Power** - AI Power > Index Content
3. **Done!** - ScubaGPT automatically uses updated content

No need to:
- ❌ Re-upload files
- ❌ Update chatbot settings
- ❌ Clear caches
- ❌ Restart services

## 🎯 Testing

### Test Query Examples

Try these queries to test the integration:

```
"What are the best dive sites in Thailand?"
"Explain the buddy system in scuba diving"
"What certification do I need for deep diving?"
"Tell me about manta ray dive sites"
"What should I pack for a dive trip?"
```

### Expected Behavior

✅ **Good Response**:
- Cites your WordPress posts
- Shows relevance scores
- Includes source URLs
- Combines with web search

❌ **Needs Work**:
- No sources shown → Check content is indexed
- Low relevance → Index more relevant content
- No results → Lower min_score threshold

## 💰 Cost Considerations

### Pinecone
- **Free Tier**: 100K vectors, 1 index
- **Paid**: $0.096/hr for larger indexes
- Most sites fit in free tier

### OpenAI Embeddings
- **text-embedding-3-small**: $0.02 / 1M tokens
- Very cost-effective for most sites
- ~1000 posts = $0.20-0.50

### Claude API (ScubaGPT)
- Separate cost for chatbot responses
- Not affected by AI Power integration

## 🚦 Next Steps

1. ✅ Verify AI Power integration is active (check logs)
2. 📝 Index your best diving content
3. 📄 Upload key reference files (dive manuals, guides)
4. 🧪 Test with diving queries
5. 📊 Monitor which content is most relevant
6. 🔄 Keep content updated and re-index regularly

## 📖 Full Documentation

For detailed information, see:
- **AIPOWER-INTEGRATION.md** - Complete technical documentation
- **AI Power Plugin Docs** - AI Power setup and configuration
- **ScubaGPT README** - General chatbot documentation

## 🎉 You're Ready!

Your ScubaGPT chatbot is now powered by YOUR diving expertise!

The chatbot will:
- ✅ Use your WordPress posts and pages
- ✅ Reference uploaded diving manuals
- ✅ Combine with real-time web search
- ✅ Provide accurate, sourced answers
- ✅ Link back to your content

**Happy diving! 🤿🐠🌊**

---

**Version**: ScubaGPT 1.0.0 with AI Power Integration  
**Updated**: January 2026  
**Support**: Check WordPress debug logs for integration status
