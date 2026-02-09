# GD Claude Chatbot v1.8.3 - Installation Guide

**Version:** 1.8.3  
**Release Date:** January 9, 2026  
**Quick Install Time:** 5-10 minutes

---

## 📦 What's in This Release

- **Bug Fix:** Shortcode `[gd_chatbot]` now works correctly
- **Enhanced Documentation:** Updated user guides with all v1.8.2/1.8.3 features
- **New File:** QUICKSTART-GUIDE.md (markdown version)
- **All v1.8.2 Features:** 60+ trusted sources, 140+ search triggers, enhanced accuracy

---

## 🚀 Quick Installation

### Step 1: Upload Plugin

1. Download `gd-claude-chatbot-1.8.3.zip`
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the zip file
5. Click **Install Now**
6. Click **Activate Plugin**

### Step 2: Configure API Keys

1. Go to **Settings → GD Chatbot**
2. Navigate to **Claude API** tab
3. Enter your Claude API key
4. Click **Save Changes**

### Step 3: Test the Chatbot

**Test Floating Widget:**
1. Visit your website
2. Look for the chat button in the corner
3. Click to open
4. Send a test message

**Test Shortcode (NEW):**
1. Create a new page
2. Add shortcode: `[gd_chatbot]`
3. Publish and view the page
4. Chat should appear inline

---

## 🔧 Detailed Configuration

### Required Settings

#### Claude API (Required)
- **API Key:** Get from console.anthropic.com
- **Model:** claude-3-5-sonnet-20241022 (recommended)
- **Max Tokens:** 4096 (default)
- **Temperature:** 0.7 (default)

### Optional Settings

#### Tavily Web Search (Recommended)
- **Enable:** Check to activate web search
- **API Key:** Get from tavily.com
- **Search Depth:** Advanced (recommended)
- **Max Results:** 5 (default)
- **Quota:** 1000 (default)

**Benefits:**
- Real-time web information
- 60+ trusted Grateful Dead sources
- Source credibility ratings
- Enhanced accuracy

#### Pinecone Vector Database (Optional)
- **Enable:** Check to activate
- **API Key:** Get from pinecone.io
- **Environment:** Your Pinecone environment
- **Index Name:** Your index name

**Benefits:**
- Semantic search
- Context retrieval
- Enhanced responses

#### Knowledge Base (Optional)
- **Enable:** Check to activate
- **API Key:** Get from your KB provider

#### Appearance
- **Title:** Customize chat title
- **Welcome Message:** Customize greeting
- **Placeholder:** Customize input placeholder
- **Primary Color:** Choose your color
- **Position:** Choose widget position
- **Width:** Set chat width (420px default)
- **Height:** Set chat height (650px default)

---

## 🎨 Using the Shortcode

### Basic Usage

Add to any page or post:
```
[gd_chatbot]
```

### Custom Title and Size
```
[gd_chatbot title="Ask About the Dead" width="500" height="700"]
```

### Full Customization
```
[gd_chatbot 
    title="Grateful Dead Expert" 
    welcome="Hey Deadhead! What's on your mind?"
    width="600" 
    height="800" 
    color="#DC143C"]
```

### Parameters
- `title` - Chat window title
- `welcome` - Welcome message
- `width` - Width in pixels
- `height` - Height in pixels
- `color` - Primary color (hex code)

### Use Cases
- Dedicated chat page
- Help section
- FAQ page
- Knowledge base
- Support page

---

## ✅ Post-Installation Checklist

### Basic Functionality
- [ ] Plugin activated successfully
- [ ] Claude API key configured
- [ ] Floating widget appears on site
- [ ] Chat opens when clicked
- [ ] Messages send and receive
- [ ] Responses stream correctly

### Shortcode (NEW in v1.8.3)
- [ ] Shortcode renders on page
- [ ] Chat interface loads
- [ ] Messages work correctly
- [ ] Custom parameters work

### Tavily Search (if enabled)
- [ ] API key configured
- [ ] Search triggers work (try "Tell me about Tiger guitar")
- [ ] Source credibility ratings display
- [ ] Trusted sources appear first

### Appearance
- [ ] Custom title displays
- [ ] Welcome message shows
- [ ] Colors match your theme
- [ ] Size is appropriate
- [ ] Position is correct

---

## 🔍 Testing Queries

Try these to test functionality:

### Basic Query
```
Tell me about the Grateful Dead
```

### Show Query
```
What did they play at Cornell 5/8/77?
```

### Equipment Query (Tests Tavily v1.8.2)
```
Tell me about Jerry's Tiger guitar
```

### Venue Query (Tests Tavily v1.8.2)
```
Shows at the Capitol Theatre
```

### Recording Query (Tests Tavily v1.8.2)
```
Where can I find Betty Boards?
```

---

## 🐛 Troubleshooting

### Shortcode Not Working
**Issue:** Shortcode displays as text  
**Solution:** 
1. Ensure plugin is activated
2. Check for conflicting plugins
3. Try in a different page/post
4. Clear cache

### Chat Not Appearing
**Issue:** No floating widget or shortcode chat  
**Solution:**
1. Verify plugin is activated
2. Check Claude API key is set
3. Check browser console for errors
4. Try different theme

### No Responses
**Issue:** Messages send but no response  
**Solution:**
1. Verify Claude API key is correct
2. Check API key has credits
3. Check error logs in WordPress
4. Test API connection in settings

### Tavily Not Working
**Issue:** Equipment/venue queries don't search web  
**Solution:**
1. Enable Tavily in settings
2. Add Tavily API key
3. Check quota hasn't been exceeded
4. Clear Tavily cache

---

## 📊 Performance Tips

### Optimize Caching
- Keep default 1-hour cache duration
- Clear cache after major updates
- Monitor cache hit rate

### Rate Limiting
- Default 100 requests/hour is good for most sites
- Increase for high-traffic sites
- Monitor usage in settings

### API Costs
- Claude: ~$0.003 per conversation
- Tavily: Free tier available (1000/month)
- Pinecone: Free tier available

---

## 🔄 Upgrading from Previous Versions

### From v1.8.2
1. Deactivate current plugin
2. Delete old files (settings preserved)
3. Upload v1.8.3
4. Activate
5. Test shortcode

### From v1.8.0 or v1.8.1
1. Back up database
2. Note current settings
3. Follow upgrade steps above
4. Verify all settings
5. Test all features

### From v1.7.x or Earlier
1. **Important:** Back up database
2. Export current settings (screenshot)
3. Deactivate and delete old plugin
4. Install v1.8.3
5. Reconfigure all settings
6. Test thoroughly

---

## 📚 Documentation

### User Documentation
- **USER-GUIDE.md** - Complete user guide
- **QUICKSTART-GUIDE.md** - Quick reference (NEW)
- **QUICKSTART-GUIDE.html** - HTML version

### Technical Documentation
- **CHANGELOG.md** - Version history
- **RELEASE-NOTES-1.8.3.md** - This release details
- **TAVILY-QUICK-REFERENCE.md** - Tavily integration
- **TAVILY-ENHANCEMENTS-v1.8.2.md** - v1.8.2 enhancements
- **ACCURACY-SYSTEMS.md** - Accuracy architecture

---

## 🎯 What's New in v1.8.3

### Bug Fixes
- ✅ Fixed `[gd_chatbot]` shortcode rendering
- ✅ Improved asset loading detection
- ✅ Better shortcode compatibility

### Documentation
- ✅ Updated USER-GUIDE.md
- ✅ Created QUICKSTART-GUIDE.md
- ✅ Updated QUICKSTART-GUIDE.html
- ✅ Added shortcode examples

### Inherited from v1.8.2
- ✅ 60+ trusted sources
- ✅ 140+ search triggers
- ✅ 100% equipment query coverage
- ✅ 95% venue query coverage
- ✅ Source credibility ratings

---

## 🆘 Getting Help

### Self-Help
1. Read USER-GUIDE.md
2. Check CHANGELOG.md
3. Review QUICKSTART-GUIDE.md
4. Test with default settings

### Support Channels
1. Contact site administrator
2. Check plugin settings diagnostics
3. Review WordPress error logs
4. Test API connections in settings

---

## 🎵 Enjoy!

Your Grateful Dead chatbot is ready to help users explore the band's music, history, and culture. The shortcode now works perfectly for embedding the chat anywhere on your site!

**What a long, strange trip it's been!** 🌹⚡

---

*Installation time: ~5-10 minutes | Configuration time: ~10-15 minutes | Total setup: ~20-25 minutes*
