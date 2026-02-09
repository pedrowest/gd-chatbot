# GD Chatbot v2.0.5 - Release Notes

**Release Date:** January 12, 2026  
**Plugin Version:** 2.0.5  
**Stability:** Stable  
**Update Type:** Critical Bug Fix

---

## 🚨 Critical Bug Fix

This release fixes a critical rendering issue that prevented the chatbot from displaying properly on pages using the `[gd_chatbot_v2]` shortcode.

### What Was Fixed

**Shortcode Asset Loading Bug**
- The chatbot HTML was rendering but CSS and JavaScript files were not being loaded
- This caused the chatbot to appear as unstyled, non-functional HTML
- Root cause: Asset loading check was looking for wrong shortcode name (`gd_chatbot` instead of `gd_chatbot_v2`)

### Impact

- ✅ **FIXED**: Chatbot now renders with full styling and functionality
- ✅ **FIXED**: All CSS files load correctly when shortcode is present
- ✅ **FIXED**: All JavaScript functionality works (send messages, clear chat, etc.)
- ✅ **FIXED**: No more blank or broken chatbot displays

---

## 📋 Changes in This Release

### Bug Fixes
1. **Fixed shortcode detection in asset loader** (`class-chatbot-public.php` line 118)
   - Changed from checking `gd_chatbot` to `gd_chatbot_v2`
   - Ensures CSS and JS files load when shortcode is present

2. **Fixed option prefix typo in activation hook** (`gd-chatbot.php` line 162)
   - Corrected `gd_chatbot_` to `gd_chatbot_v2_` for consistency
   - Ensures default options are properly saved with correct prefix

### Documentation
- Added `BUGFIX-SHORTCODE-RENDERING.md` with detailed technical explanation
- Updated `CHANGELOG.md` with v2.0.5 entry

---

## 🔄 Upgrade Information

### Settings Preservation

**✅ ALL SETTINGS ARE PRESERVED DURING UPDATE**

This update will **NOT** affect any of your existing configuration:

- ✅ **API Keys Preserved**
  - Claude API Key
  - Tavily API Key  
  - Pinecone API Key
  - Embedding API Key

- ✅ **System Prompts Preserved**
  - Custom system prompt
  - All Claude model settings
  - Temperature and token limits

- ✅ **Integration Settings Preserved**
  - Tavily search configuration
  - Pinecone vector database settings
  - Knowledge base settings
  - AI Power integration settings

- ✅ **Appearance Settings Preserved**
  - Chatbot title and welcome message
  - Colors and dimensions
  - Position settings (floating vs inline)

- ✅ **Conversation History Preserved**
  - All logged conversations remain intact
  - Database table unchanged

### How Settings Are Protected

The plugin uses WordPress's built-in option system with the `gd_chatbot_v2_` prefix. During updates:

1. **Activation Hook Check** (line 161 in `gd-chatbot.php`):
   ```php
   if (get_option('gd_chatbot_v2_' . $key) === false) {
       add_option('gd_chatbot_v2_' . $key, $value);
   }
   ```
   This only adds options if they don't already exist, preserving your settings.

2. **No Database Changes**: This update doesn't modify the database schema or existing data.

3. **No Option Deletion**: The plugin never deletes options during updates (only on uninstall).

---

## 📦 Installation Instructions

### For New Installations

1. Download `gd-chatbot-v2.0.5.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the zip file and click "Install Now"
5. Click "Activate Plugin"
6. Go to Settings → GD Chatbot to configure

### For Upgrades from v2.0.4 or Earlier

**Method 1: WordPress Admin (Recommended)**

1. Download `gd-chatbot-v2.0.5.zip`
2. Go to WordPress Admin → Plugins
3. **Deactivate** the current GD Chatbot v2 plugin (settings are preserved)
4. Click "Delete" on the old plugin
5. Click "Add New" → "Upload Plugin"
6. Choose `gd-chatbot-v2.0.5.zip` and click "Install Now"
7. Click "Activate Plugin"
8. Verify your settings are intact in Settings → GD Chatbot

**Method 2: FTP/File Manager**

1. Download `gd-chatbot-v2.0.5.zip` and extract it
2. Connect to your server via FTP or use your hosting file manager
3. Navigate to `/wp-content/plugins/`
4. **Backup** the existing `gd-chatbot/` folder (optional but recommended)
5. Delete the existing `gd-chatbot/` folder
6. Upload the new `gd-chatbot/` folder from the extracted zip
7. Go to WordPress Admin → Plugins
8. Click "Activate" on GD Chatbot v2
9. Verify your settings are intact in Settings → GD Chatbot

**Method 3: WP-CLI**

```bash
wp plugin deactivate gd-chatbot
wp plugin delete gd-chatbot
wp plugin install /path/to/gd-chatbot-v2.0.5.zip --activate
```

---

## ✅ Post-Update Checklist

After updating, verify the following:

- [ ] Plugin shows version 2.0.5 in Plugins list
- [ ] All API keys are still configured (Settings → GD Chatbot → API Settings)
- [ ] System prompt is intact (Settings → GD Chatbot → Claude Settings)
- [ ] Chatbot renders properly on pages with `[gd_chatbot_v2]` shortcode
- [ ] Chatbot has proper styling (not plain HTML)
- [ ] Can send and receive messages successfully
- [ ] Clear chat button works
- [ ] Floating widget appears if enabled
- [ ] No JavaScript errors in browser console

---

## 🐛 Known Issues

None at this time.

---

## 📝 Important Notes

### Shortcode Name

This plugin uses `[gd_chatbot_v2]` as the shortcode to avoid conflicts with the `gd-claude-chatbot` plugin (which uses `[gd_chatbot]`).

**Correct Usage:**
```
[gd_chatbot_v2]
```

**With Attributes:**
```
[gd_chatbot_v2 title="Support Bot" width="450" height="600"]
```

### Plugin Compatibility

- **WordPress Version:** 5.8 or higher
- **PHP Version:** 7.4 or higher
- **Conflicts:** None known (safe to run alongside gd-claude-chatbot)

---

## 🆘 Support

If you encounter any issues after updating:

1. **Clear Browser Cache**: Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)
2. **Clear WordPress Cache**: If using a caching plugin, clear all caches
3. **Check Console**: Open browser developer tools and check for JavaScript errors
4. **Verify Settings**: Go to Settings → GD Chatbot and verify all API keys are present
5. **Test Shortcode**: Create a test page with just `[gd_chatbot_v2]` to isolate issues

### Rollback Instructions

If you need to rollback to v2.0.4:

1. Deactivate and delete v2.0.5
2. Install `gd-chatbot-v2.0.4.zip` from the `plugin-installs/` folder
3. Your settings will still be preserved

---

## 📚 Additional Resources

- **Full Documentation**: See `README.md` in the plugin folder
- **Changelog**: See `CHANGELOG.md` for complete version history
- **Bug Fix Details**: See `BUGFIX-SHORTCODE-RENDERING.md` for technical details
- **System Prompt**: See `SYSTEM-PROMPT.md` for AI behavior documentation

---

## 🙏 Thank You

Thank you for using GD Chatbot v2! This critical fix ensures the chatbot works as intended on all pages.

**Questions or feedback?** Contact IT Influentials at peter@it-influentials.com
