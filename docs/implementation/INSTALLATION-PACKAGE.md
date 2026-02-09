# GD Claude Chatbot - Installation Package

**Version:** 1.0.0  
**Package Created:** January 3, 2026  
**Package File:** `gd-claude-chatbot.zip`

---

## 📦 Package Contents

This installable WordPress plugin package includes:

### Core Plugin Files
- ✅ Main plugin file (`gd-claude-chatbot.php`)
- ✅ All PHP classes (5 files in `/includes`)
- ✅ Admin interface (3 files in `/admin`)
- ✅ Public frontend (4 files in `/public`)
- ✅ Uninstall script (`uninstall.php`)

### Knowledge Base & Data
- ✅ Complete Grateful Dead context file (`grateful-dead-context.md` - 50KB)
- ✅ Complete setlist database (31 CSV files, 1965-1995, covering 2,388 shows)

### Assets
- ✅ CSS stylesheets (3 files)
  - Base chatbot styles
  - Admin styles
  - Grateful Dead theme
- ✅ JavaScript files (2 files)
  - Chatbot frontend
  - Admin scripts

### Documentation
- ✅ User Guide (`USER-GUIDE.md`)
- ✅ README with installation instructions
- ✅ Technical documentation (5 files)
- ✅ Documentation index

### Package Statistics
- **Total Files:** 69
- **Functional Files:** 45 (PHP, JS, CSS, CSV)
- **Package Size:** 242 KB (compressed)
- **Uncompressed Size:** ~1.1 MB

---

## 🚀 Installation Instructions

### Method 1: WordPress Admin Upload (Recommended)

1. **Download the zip file** (if you haven't already)
   - File: `gd-claude-chatbot.zip`

2. **Log into WordPress Admin**
   - Go to your WordPress dashboard

3. **Navigate to Plugins**
   - Click **Plugins** → **Add New**

4. **Upload Plugin**
   - Click the **"Upload Plugin"** button at the top
   - Click **"Choose File"**
   - Select `gd-claude-chatbot.zip`
   - Click **"Install Now"**

5. **Activate**
   - After installation completes, click **"Activate Plugin"**

6. **Configure**
   - Go to **GD Chatbot** → **Settings** in the admin menu
   - Enter your Anthropic Claude API key
   - Configure other settings as needed
   - Click **"Save Changes"**

7. **Test**
   - Visit your website
   - Look for the skull button in the bottom-right corner
   - Click it and start chatting!

### Method 2: FTP/SFTP Upload

1. **Extract the zip file** on your computer
   - This will create a `gd-claude-chatbot` folder

2. **Connect via FTP/SFTP**
   - Use FileZilla, Cyberduck, or your preferred FTP client

3. **Upload folder**
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `gd-claude-chatbot` folder

4. **Set permissions** (if needed)
   - Folders: 755
   - Files: 644

5. **Activate in WordPress**
   - Go to **Plugins** in WordPress admin
   - Find "GD Claude Chatbot"
   - Click **"Activate"**

6. **Configure** (same as Method 1, step 6)

### Method 3: WP-CLI

If you have WP-CLI installed:

```bash
# Upload the zip file to your server first, then:
wp plugin install /path/to/gd-claude-chatbot.zip --activate
```

---

## ⚙️ Required Configuration

### 1. Claude API Key (Required)

**You must obtain an API key from Anthropic:**

1. Visit [console.anthropic.com](https://console.anthropic.com/)
2. Sign up or log in
3. Navigate to **API Keys**
4. Create a new API key
5. Copy the key
6. In WordPress: **GD Chatbot** → **Settings** → **Claude API** tab
7. Paste your API key
8. Click **"Test Connection"** to verify
9. Save settings

**Without this API key, the chatbot will not work.**

### 2. Optional Integrations

**Tavily Web Search (Optional):**
- Get API key from [tavily.com](https://tavily.com)
- Enter in **Tavily** tab
- Enables real-time web search

**Pinecone Vector Database (Optional):**
- Get API key from [pinecone.io](https://pinecone.io)
- Also need OpenAI API key for embeddings
- Enter in **Pinecone** tab
- Enables custom knowledge base

---

## ✅ What's Included Out of the Box

### Immediate Features (No Extra Setup)

Once you install and add your Claude API key, you get:

✅ **Full Grateful Dead Knowledge Base**
- Automatically loaded
- 50KB of curated content
- Band history, members, equipment, culture

✅ **Complete Setlist Database**
- All 2,388 shows (1965-1995)
- Search by date, venue, song, or year
- Automatically integrated

✅ **Real-Time Streaming Responses**
- Progressive text display
- Animated cursor
- Immediate feedback

✅ **Psychedelic Grateful Dead Theme**
- Iconic imagery (skulls, roses, bears, lightning)
- Psychedelic colors and gradients
- Custom fonts
- Animations

✅ **Beautiful Chat Interface**
- Floating chat widget
- Mobile responsive
- Conversation history
- Source attribution

### No Additional Files Needed

Everything is included in this package:
- All code files
- All data files
- All stylesheets
- All documentation

Just install, configure API key, and go!

---

## 📋 Post-Installation Checklist

After installing, verify everything works:

- [ ] Plugin activated successfully
- [ ] No errors in WordPress admin
- [ ] Settings page accessible (**GD Chatbot** → **Settings**)
- [ ] Claude API key entered and connection tested
- [ ] Chatbot skull button appears on frontend
- [ ] Chatbot opens when clicked
- [ ] Can send a test message
- [ ] Response streams in successfully
- [ ] Try asking: "Tell me about Cornell 5/8/77"
- [ ] Verify setlist data appears

---

## 🔧 Troubleshooting

### Plugin Won't Activate

**Possible causes:**
- PHP version too old (need 7.4+)
- WordPress version too old (need 5.8+)
- File permissions issue
- Conflicting plugin

**Solution:**
1. Check PHP version in **Tools** → **Site Health**
2. Update WordPress if needed
3. Check error logs
4. Deactivate other chatbot plugins

### Chatbot Doesn't Appear

**Possible causes:**
- JavaScript not loading
- Theme conflict
- Plugin not activated

**Solution:**
1. Clear browser cache
2. Check browser console for errors
3. Try different browser
4. Verify plugin is activated

### No Responses / Error Messages

**Possible causes:**
- Invalid API key
- No API credits
- Network issue
- Server configuration

**Solution:**
1. Verify API key in settings
2. Test connection in settings page
3. Check Anthropic account has credits
4. Check WordPress error logs
5. Ensure server allows outbound HTTPS

### Setlist Data Not Working

**Verify CSV files installed:**
```bash
# Via FTP, check this path exists:
/wp-content/plugins/gd-claude-chatbot/context/Deadshows/deadshows/*.csv
```

**Should see 31 CSV files (1965.csv through 1995.csv)**

If missing, re-upload the plugin.

---

## 📚 Documentation Access

All documentation is included in the plugin folder:

**For End Users:**
- `USER-GUIDE.md` - Complete usage guide

**For Administrators:**
- `README.md` - Installation and configuration

**For Developers:**
- `DOCUMENTATION-INDEX.md` - All docs overview
- `CONTEXT-INTEGRATION.md` - Knowledge base details
- `SETLIST-DATABASE.md` - Setlist system details
- `STREAMING.md` - Streaming implementation
- `GD-THEME.md` - Theme customization
- `QUICK-REFERENCE.md` - Quick technical reference

**Access via FTP or file manager at:**
```
/wp-content/plugins/gd-claude-chatbot/[filename].md
```

---

## 🎨 Customization

### Change Colors

1. Go to **GD Chatbot** → **Settings**
2. **Appearance** tab
3. Modify **Primary Color**
4. Or edit `/public/css/gd-theme.css` for full customization

### Change Welcome Message

1. Go to **GD Chatbot** → **Settings**
2. **Appearance** tab
3. Edit **Welcome Message**
4. Save changes

### Add to Specific Pages

1. Go to **GD Chatbot** → **Settings**
2. **Display** tab
3. Choose where chatbot appears
4. Use shortcode `[gd_chatbot]` for manual placement

---

## 📊 System Requirements

### Minimum Requirements

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **HTTPS:** Recommended (for secure API communication)

### Recommended

- **WordPress:** 6.0+
- **PHP:** 8.0+
- **Memory:** 128MB+ PHP memory limit
- **Anthropic API:** Active account with credits

### Hosting Compatibility

Works with all major WordPress hosts:
- ✅ WP Engine
- ✅ SiteGround
- ✅ Bluehost
- ✅ Kinsta
- ✅ Cloudways
- ✅ Most shared hosting
- ✅ VPS/Dedicated servers

---

## 🔐 Security Notes

### API Key Security

- API keys stored encrypted in WordPress database
- Never exposed to frontend
- Only sent to Anthropic servers
- Use WordPress nonce verification
- Capability checks on all admin actions

### Best Practices

1. **Keep WordPress Updated**
2. **Use HTTPS** (SSL certificate)
3. **Strong Admin Passwords**
4. **Regular Backups**
5. **Monitor API Usage** (in Anthropic console)

---

## 💰 Cost Information

### Plugin Cost

- **Plugin:** Free (included in this package)
- **Installation:** No charge

### API Costs

**You pay Anthropic directly for API usage:**

- **Claude Sonnet 4** (recommended): ~$3 per million input tokens
- **Average conversation:** ~$0.01 - $0.05
- **1,000 conversations:** ~$10 - $50 (varies by length)

**Monitor usage:** [console.anthropic.com](https://console.anthropic.com)

**Optional services:**
- **Tavily Search:** Paid separately if enabled
- **Pinecone Database:** Paid separately if enabled

---

## 🆘 Support

### Documentation

Start with included documentation:
1. `USER-GUIDE.md` for usage questions
2. `README.md` for setup questions
3. Technical docs for development

### Technical Support

**Plugin Issues:**
- Contact: IT Influentials
- Website: [it-influentials.com](https://it-influentials.com)

**API Issues:**
- Anthropic Support: [support.anthropic.com](https://support.anthropic.com)

**WordPress Issues:**
- WordPress Forums: [wordpress.org/support](https://wordpress.org/support)

---

## 📝 License

This plugin is proprietary software developed by IT Influentials.

**Permitted:**
- Install on your WordPress sites
- Customize for your needs
- Use for commercial purposes

**Not Permitted:**
- Redistribute without permission
- Resell as your own product
- Remove copyright notices

---

## 🎸 Credits

**Developed by:** IT Influentials  
**AI Model:** Claude by Anthropic  
**Setlist Data:** gdshowsdb repository  
**Inspired by:** The Grateful Dead community

---

## ✨ Quick Start Summary

1. **Upload** `gd-claude-chatbot.zip` via WordPress admin
2. **Activate** the plugin
3. **Get API key** from console.anthropic.com
4. **Configure** in Settings → Claude API tab
5. **Test** by clicking skull button on frontend
6. **Enjoy** your Grateful Dead chatbot! 🌹⚡☠️

---

**Package Location:**
```
/Users/peterwesterman/Library/CloudStorage/GoogleDrive-peter@it-influentials.com/My Drive/ITI PRODUCTS/it-influentials.com/ITI WP Plugins/gd-claude-chatbot.zip
```

**Ready to install on any WordPress site!**
