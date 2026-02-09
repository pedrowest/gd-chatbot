# GD Chatbot v2.0.4 - Installation Guide

**Version:** 2.0.4  
**Release Date:** January 12, 2026  
**File:** `gd-chatbot-v2.0.4.zip`

---

## 🚀 Quick Start (5 Minutes)

### Step 1: Upload Plugin
1. Log in to your WordPress admin dashboard
2. Go to **Plugins → Add New**
3. Click **Upload Plugin**
4. Choose `gd-chatbot-v2.0.4.zip`
5. Click **Install Now**

### Step 2: Activate
1. Click **Activate Plugin** after installation
2. You'll see "GD Chatbot v2" in your admin menu

### Step 3: Configure API Key
1. Go to **GD Chatbot v2 → Settings** in admin menu
2. Enter your **Anthropic Claude API Key** (required)
3. Click **Save Changes**

### Step 4: Test Connection
1. Scroll down to **Connection Testing**
2. Click **Test Claude Connection**
3. Verify you see "✅ Connection successful!"

### Step 5: Add to Your Site
1. Edit any page or post
2. Add the shortcode: `[gd_chatbot_v2]`
3. Publish and view your page
4. The chatbot will appear!

---

## 📋 Detailed Installation

### Prerequisites

**Required:**
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Anthropic Claude API key ([Get one here](https://console.anthropic.com/))

**Optional:**
- Tavily API key for web search ([Get one here](https://tavily.com/))
- Pinecone account for vector database ([Get one here](https://www.pinecone.io/))

### Method 1: WordPress Admin Upload (Recommended)

1. **Download the Plugin**
   - Ensure you have `gd-chatbot-v2.0.4.zip`
   - Do NOT unzip the file

2. **Upload via WordPress**
   ```
   WordPress Admin → Plugins → Add New → Upload Plugin
   ```
   - Click "Choose File"
   - Select `gd-chatbot-v2.0.4.zip`
   - Click "Install Now"

3. **Activate**
   - Click "Activate Plugin" when installation completes
   - Plugin is now active!

### Method 2: FTP Upload

1. **Unzip the Plugin**
   - Extract `gd-chatbot-v2.0.4.zip`
   - You'll get a `gd-chatbot` folder

2. **Upload via FTP**
   - Connect to your server via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `gd-chatbot` folder

3. **Activate in WordPress**
   - Go to WordPress Admin → Plugins
   - Find "GD Chatbot v2"
   - Click "Activate"

### Method 3: WP-CLI

```bash
wp plugin install /path/to/gd-chatbot-v2.0.4.zip --activate
```

---

## ⚙️ Configuration

### Required Settings

#### 1. Claude API Configuration

Navigate to: **GD Chatbot v2 → Settings → Claude Settings**

**API Key** (Required)
- Get from: https://console.anthropic.com/
- Format: `sk-ant-api03-...`
- Paste into "Claude API Key" field

**Model Selection**
- Default: `claude-sonnet-4-20250514` (recommended)
- Other options available in dropdown

**Max Tokens**
- Default: 4096
- Range: 1024 - 8192
- Higher = longer responses

**Temperature**
- Default: 0.7
- Range: 0.0 - 1.0
- Lower = more focused, Higher = more creative

**System Prompt**
- Optional: Customize the chatbot's behavior
- Leave blank to use default Grateful Dead context

### Optional Settings

#### 2. Tavily Web Search (Optional)

Navigate to: **GD Chatbot v2 → Settings → Tavily Settings**

**Enable Tavily Search**
- Toggle ON to enable real-time web search
- Provides up-to-date information

**API Key**
- Get from: https://tavily.com/
- Free tier available

**Search Depth**
- Basic: Faster, less comprehensive
- Advanced: Slower, more detailed

**Max Results**
- Default: 5
- Range: 1 - 10

#### 3. Pinecone Vector Database (Optional)

Navigate to: **GD Chatbot v2 → Settings → Pinecone Settings**

**Enable Pinecone**
- Toggle ON to enable semantic search

**API Key**
- Get from: https://www.pinecone.io/

**Environment**
- Your Pinecone environment (e.g., "us-west1-gcp")

**Index Name**
- Your Pinecone index name

**Namespace**
- Optional: Organize your vectors

**Top K Results**
- Default: 5
- Number of relevant matches to retrieve

#### 4. Appearance Settings

Navigate to: **GD Chatbot v2 → Settings → Appearance**

**Theme**
- Professional (clean, modern)
- Psychedelic (Grateful Dead themed)

**Position**
- Inline (embedded in page)
- Floating (bottom-right widget)

**Colors**
- Customize primary, secondary, text colors
- Use color picker or hex codes

**Button Text**
- Customize the chat button label

---

## 🎨 Adding to Your Site

### Method 1: Shortcode (Recommended)

Add this shortcode to any page, post, or widget:

```
[gd_chatbot_v2]
```

**With Custom Height:**
```
[gd_chatbot_v2 height="600px"]
```

**With Custom Theme:**
```
[gd_chatbot_v2 theme="psychedelic"]
```

### Method 2: Floating Widget

1. Go to **GD Chatbot v2 → Settings → Appearance**
2. Enable "Floating Widget"
3. Save changes
4. Widget appears on all pages automatically

### Method 3: PHP Template

Add to your theme files:

```php
<?php
if (function_exists('gd_render_chatbot_v2')) {
    gd_render_chatbot_v2();
}
?>
```

### Method 4: Block Editor (Gutenberg)

1. Add a "Shortcode" block
2. Enter: `[gd_chatbot_v2]`
3. Preview and publish

---

## ✅ Testing Your Installation

### 1. Test Claude Connection

**Location:** GD Chatbot v2 → Settings → Connection Testing

1. Click "Test Claude Connection"
2. Expected result: ✅ "Connection successful!"
3. If error: Check API key and internet connection

### 2. Test Tavily (if enabled)

1. Click "Test Tavily Connection"
2. Expected result: ✅ "Connection successful!"
3. If error: Check Tavily API key

### 3. Test Pinecone (if enabled)

1. Click "Test Pinecone Connection"
2. Expected result: ✅ "Connection successful!"
3. If error: Check API key, environment, and index name

### 4. Test Chatbot Interface

1. Add `[gd_chatbot_v2]` to a test page
2. View the page
3. Type a test message: "Who was Jerry Garcia?"
4. Verify you get a response

---

## 🔧 Troubleshooting

### Plugin Won't Activate

**Problem:** "The plugin does not have a valid header"
- **Solution:** Re-download the zip file, ensure it's not corrupted

**Problem:** "Plugin could not be activated because it triggered a fatal error"
- **Solution:** Check PHP version (requires 7.4+)
- Check for conflicting plugins

### Chatbot Not Appearing

**Problem:** Shortcode shows as text
- **Solution:** Ensure plugin is activated
- Check shortcode spelling: `[gd_chatbot_v2]`

**Problem:** Blank space where chatbot should be
- **Solution:** Check browser console for JavaScript errors
- Clear cache and refresh

### API Connection Errors

**Problem:** "Invalid API key"
- **Solution:** Verify API key is correct
- Check for extra spaces before/after key
- Ensure key has proper permissions

**Problem:** "Rate limit exceeded"
- **Solution:** Wait a few minutes
- Check your API usage limits
- Consider upgrading your API plan

### Chatbot Not Responding

**Problem:** Loading spinner never stops
- **Solution:** Check Claude API connection
- Verify API key is valid
- Check server error logs

**Problem:** "Error processing message"
- **Solution:** Check WordPress error log
- Verify all required settings are configured
- Test API connections in settings

---

## 🔄 Upgrading from Previous Versions

### From v2.0.3 or Earlier

1. **Backup First** (recommended)
   - Backup your WordPress database
   - Backup the plugin folder

2. **Deactivate Old Version**
   - Go to Plugins
   - Deactivate "GD Chatbot v2"

3. **Delete Old Version**
   - Click "Delete" on the old plugin
   - Confirm deletion

4. **Install v2.0.4**
   - Upload `gd-chatbot-v2.0.4.zip`
   - Activate

5. **Verify Settings**
   - Go to GD Chatbot v2 → Settings
   - Verify your API keys are still there
   - Test connections

**Note:** Your settings are preserved during upgrade!

### From gd-claude-chatbot v1.7.1

If you're switching from the original gd-claude-chatbot:

1. **Both Can Run Together**
   - gd-chatbot v2 can run side-by-side with gd-claude-chatbot
   - They use separate settings and databases

2. **Manual Settings Migration**
   - Settings are NOT automatically copied
   - You'll need to re-enter your API keys
   - Copy your custom system prompt if you had one

3. **Shortcode Change**
   - Old: `[gd_chatbot]`
   - New: `[gd_chatbot_v2]`
   - Update your pages/posts

---

## 📊 What's Included in v2.0.4

### New in This Version

✅ **14 New Knowledgebase Files**
- Comprehensive online resources guide
- Gallery and museum directory
- Academic research papers collection
- AI tools and chatbots documentation
- UC Santa Cruz archive documentation
- Equipment and gear information
- And more...

✅ **Updated Band Information**
- Phil Lesh death information (Oct 25, 2024)
- Bob Weir death information (Jan 10, 2026)
- Surviving members noted

✅ **Enhanced Context Loading**
- Automatic loading of all knowledgebase files
- Better organization and structure
- Improved accuracy and coverage

### Total Context Files: 31

- Main knowledge base
- Setlists (1965-1995)
- Song catalogs
- Equipment specifications
- Interview transcripts
- Academic resources
- Gallery directories
- And more...

---

## 🆘 Getting Help

### Documentation

- **README.md** - In plugin folder
- **CHANGELOG.md** - Version history
- **RELEASE-NOTES-v2.0.4.md** - Detailed release notes

### Support Channels

- **Developer:** IT Influentials
- **Website:** https://it-influentials.com
- **Email:** Contact through website

### Common Resources

- [Anthropic Claude Documentation](https://docs.anthropic.com/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Tavily API Docs](https://docs.tavily.com/)
- [Pinecone Documentation](https://docs.pinecone.io/)

---

## 🎉 You're All Set!

Your GD Chatbot v2.0.4 is now installed and ready to use!

**Next Steps:**
1. ✅ Test the chatbot with some questions
2. ✅ Customize the appearance to match your site
3. ✅ Add the shortcode to your pages
4. ✅ Explore optional features (Tavily, Pinecone)

**Have fun chatting! 🎸⚡💀🌹**
