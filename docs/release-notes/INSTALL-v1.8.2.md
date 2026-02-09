# Quick Installation Guide - GD Claude Chatbot v1.8.2

**Version:** 1.8.2  
**Release Date:** January 9, 2026

---

## 📦 Package Contents

**File:** `gd-claude-chatbot-1.8.2.zip` (560KB)

**What's Included:**
- Core plugin files
- Tavily integration with 60+ trusted sources
- 140+ search triggers
- Complete setlist database (1965-1995)
- Knowledge base and context files
- Admin interface
- Documentation

---

## 🚀 New Installation

### Step 1: Download

Download `gd-claude-chatbot-1.8.2.zip` to your computer.

### Step 2: Upload to WordPress

1. Log in to WordPress Admin
2. Navigate to **Plugins → Add New**
3. Click **Upload Plugin** button
4. Click **Choose File** and select `gd-claude-chatbot-1.8.2.zip`
5. Click **Install Now**

### Step 3: Activate

1. After installation completes, click **Activate Plugin**
2. You'll see "GD Chatbot" in your admin menu

### Step 4: Configure API Keys

1. Go to **GD Chatbot → Settings**
2. Navigate to **Claude** tab
3. Enter your Anthropic Claude API key
4. Click **Test Connection** to verify
5. Click **Save Claude Settings**

### Step 5: Enable Tavily (Optional but Recommended)

1. Go to **Tavily** tab
2. Check **Enable Tavily**
3. Enter your Tavily API key
4. Set **Search Depth** to "Advanced"
5. Set **Max Results** to 5-10
6. Set **Monthly Quota** (e.g., 1000 for free plan)
7. Click **Test Connection**
8. Click **Save Tavily Settings**

### Step 6: Test the Chatbot

1. Visit any page on your site
2. Look for the chatbot icon (bottom right)
3. Click to open the chat
4. Try: "What did they play at Cornell 5/8/77?"
5. Verify you get accurate results

---

## 🔄 Upgrade from Previous Version

### Automatic Upgrade (Recommended)

1. WordPress will notify you of the update
2. Click **Update Now**
3. Wait for completion
4. All settings automatically preserved

### Manual Upgrade

1. Go to **Plugins → Installed Plugins**
2. Find "GD Claude Chatbot"
3. Click **Deactivate** (settings are preserved)
4. Click **Delete**
5. Follow "New Installation" steps above
6. All settings will be automatically restored

**Note:** Your API keys, settings, and configurations are stored in the WordPress database and will not be lost during upgrade.

---

## ⚙️ Configuration

### Minimum Configuration

**Required:**
- ✅ Claude API key

**Recommended:**
- ✅ Tavily API key (for web search)
- ✅ Enable Tavily
- ✅ Set search depth to "Advanced"

**Optional:**
- Pinecone API key (for vector search)
- OpenAI Embeddings API key (for Pinecone)
- Custom appearance settings

### Tavily Configuration

**Recommended Settings:**
```
Enable Tavily: ✅ Checked
Search Depth: Advanced
Max Results: 5-10
Monthly Quota: 1000 (or your plan limit)
Include Domains: (leave empty for all trusted sources)
Exclude Domains: (leave empty)
```

---

## 🔑 Getting API Keys

### Anthropic Claude API (Required)

1. Visit: https://console.anthropic.com
2. Sign up or log in
3. Go to API Keys section
4. Create new key
5. Copy and paste into plugin settings

**Cost:** Pay-as-you-go, ~$0.003 per message

### Tavily API (Recommended)

1. Visit: https://tavily.com
2. Sign up for account
3. Go to API section
4. Copy your API key
5. Paste into plugin settings

**Cost:** Free tier: 1,000 searches/month

### Pinecone API (Optional)

1. Visit: https://www.pinecone.io
2. Sign up for account
3. Create an index
4. Get API key and host URL
5. Configure in plugin settings

**Cost:** Free tier available

---

## ✅ Verification Checklist

After installation, verify:

- [ ] Plugin activated successfully
- [ ] Claude API key configured and tested
- [ ] Tavily API key configured and tested (if using)
- [ ] Chatbot appears on frontend
- [ ] Test query returns accurate results
- [ ] Search triggers working (try equipment/venue queries)
- [ ] Source credibility labels showing
- [ ] Cache statistics visible in admin

---

## 🧪 Test Queries

Try these to verify everything is working:

### Basic Query
```
"Who was Jerry Garcia?"
```
**Expected:** Biographical information from knowledge base

### Setlist Query
```
"What did they play at Cornell 5/8/77?"
```
**Expected:** Complete setlist from database

### Equipment Query (NEW in v1.8.2)
```
"Tell me about Jerry's Tiger guitar"
```
**Expected:** Equipment details with web search results

### Venue Query (ENHANCED in v1.8.2)
```
"Shows at Winterland"
```
**Expected:** Venue information and show list

### Song Version Query (ENHANCED in v1.8.2)
```
"Best Dark Star versions"
```
**Expected:** HeadyVersion and Relisten results

---

## 🐛 Troubleshooting

### Chatbot Not Appearing

**Check:**
- Plugin is activated
- No JavaScript errors in browser console
- Theme compatibility (try default WordPress theme)

**Fix:**
- Clear browser cache
- Disable other chatbot plugins
- Check for JavaScript conflicts

### "API Key Invalid" Error

**Check:**
- API key copied correctly (no extra spaces)
- API key is active in provider dashboard
- Account has available credits

**Fix:**
- Re-enter API key
- Test connection
- Check provider account status

### Slow Responses

**Check:**
- Search depth setting (Basic is faster)
- Max results setting (lower = faster)
- Server performance

**Fix:**
- Use "Basic" search depth
- Reduce max results to 3-5
- Enable caching (automatic in v1.8.2)

### No Web Search Results

**Check:**
- Tavily is enabled
- Tavily API key is valid
- Monthly quota not exceeded

**Fix:**
- Enable Tavily in settings
- Test Tavily connection
- Check usage statistics
- Clear cache if needed

---

## 📊 Post-Installation

### Monitor Usage

1. Go to **GD Chatbot → Settings → Tavily**
2. Check **Usage This Month**
3. Monitor progress bar
4. Watch for 80% warning

### Review Cache

1. Check **Cache Statistics**
2. Monitor cache hit rate
3. Clear cache if needed (rare)

### Adjust Settings

Based on usage:
- Increase/decrease max results
- Adjust search depth
- Update monthly quota
- Configure domain filtering

---

## 🆘 Need Help?

### Documentation
- **RELEASE-NOTES-1.8.2.md** - What's new
- **TAVILY-ENHANCEMENTS-v1.8.2.md** - Detailed enhancements
- **ACCURACY-SYSTEMS.md** - How accuracy works
- **USER-GUIDE.md** - Complete user guide

### Support
- **Email:** peter@it-influentials.com
- **Website:** https://it-influentials.com

### Common Issues
- Check WordPress debug log: `wp-content/debug.log`
- Enable WordPress debugging in `wp-config.php`
- Check PHP error log
- Verify server requirements met

---

## 📋 Server Requirements

### Minimum
- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+
- 64MB PHP memory

### Recommended
- WordPress 6.4+
- PHP 8.1+
- MySQL 8.0+
- 128MB PHP memory

### PHP Extensions
- curl
- json
- mbstring
- mysqli
- openssl (for API key encryption)

---

## 🎉 You're Ready!

Your GD Claude Chatbot v1.8.2 is now installed with:

✅ 60+ trusted Grateful Dead sources  
✅ 140+ search triggers  
✅ Enhanced equipment, venue, and song queries  
✅ Encrypted API key storage  
✅ Automatic caching  
✅ Usage tracking  
✅ Source credibility assessment  

**Enjoy the most accurate Grateful Dead chatbot available!**

---

**Version:** 1.8.2  
**File:** gd-claude-chatbot-1.8.2.zip (560KB)  
**Release Date:** January 9, 2026  
**Maintained By:** IT Influentials
