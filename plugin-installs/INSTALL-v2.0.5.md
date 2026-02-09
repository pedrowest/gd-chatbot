# GD Chatbot v2.0.5 - Quick Installation Guide

## 🚀 Quick Start

### Step 1: Upload Plugin
1. Download `gd-chatbot-v2.0.5.zip`
2. WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file → Install Now → Activate

### Step 2: Configure API Keys
Go to **Settings → GD Chatbot → API Settings**

**Required:**
- **Claude API Key**: Get from [Anthropic Console](https://console.anthropic.com/)

**Optional:**
- **Tavily API Key**: For web search - [Get Key](https://tavily.com/)
- **Pinecone API Key**: For vector search - [Get Key](https://www.pinecone.io/)

### Step 3: Add to Your Page
Use the shortcode in any page or post:
```
[gd_chatbot_v2]
```

Or enable the floating widget in **Settings → GD Chatbot → Appearance**

---

## 🔄 Upgrading from v2.0.4

### Your Settings Are Safe! ✅

All your configuration will be preserved:
- API Keys (Claude, Tavily, Pinecone)
- System Prompts
- Appearance Settings
- Conversation History

### Upgrade Steps

1. **Deactivate** current GD Chatbot v2 (Plugins page)
2. **Delete** the old plugin
3. **Upload** gd-chatbot-v2.0.5.zip (Add New → Upload)
4. **Activate** the new version
5. **Verify** settings in Settings → GD Chatbot

**That's it!** Your chatbot will now work properly with full styling.

---

## 🎨 Customization

### Shortcode Attributes
```
[gd_chatbot_v2 
  title="Your Title" 
  welcome="Your welcome message"
  width="500" 
  height="700"
  color="#FF0000"]
```

### Floating Widget
1. Settings → GD Chatbot → Appearance
2. Set **Position** to "Bottom Right" or "Bottom Left"
3. Customize colors and dimensions
4. Save changes

---

## ✅ Verification

After installation, check:

1. **Version**: Plugins page shows "2.0.5"
2. **Styling**: Chatbot has colors and proper layout
3. **Functionality**: Can send/receive messages
4. **Console**: No JavaScript errors (F12 → Console)

---

## 🆘 Troubleshooting

### Chatbot Not Showing
- Clear browser cache (Ctrl+F5)
- Check shortcode spelling: `[gd_chatbot_v2]` (not `[gd_chatbot]`)
- Verify plugin is activated

### No Styling
- This was the bug in v2.0.4 - v2.0.5 fixes it!
- Clear WordPress cache if using caching plugin
- Hard refresh browser (Ctrl+Shift+R)

### Can't Send Messages
- Verify Claude API key in Settings → GD Chatbot
- Check browser console for errors (F12)
- Test with a simple message like "Hello"

---

## 📞 Support

**Need Help?**
- Email: peter@it-influentials.com
- Check `README.md` for full documentation
- See `RELEASE-NOTES-v2.0.5.md` for detailed changes

---

**🎉 Enjoy your fully functional Grateful Dead chatbot!**
