# GD Chatbot v2.0.6 - Release Notes

**Release Date:** January 12, 2026  
**Plugin Version:** 2.0.6  
**Stability:** Stable  
**Update Type:** Visual Theme Update

---

## 🎨 What's New - Grateful Dead Psychedelic Theme!

This release switches the default visual theme from the professional "dead.net" inspired design to the **full Grateful Dead psychedelic experience**! 

### Visual Features

**🌹 Grateful Dead Aesthetic:**
- **Steal Your Face** skull icons throughout the interface
- **Dancing Bears** animated typing indicator
- **Roses & Lightning Bolts** decorative elements
- **Psychedelic Color Gradients** inspired by classic GD concert posters
- **Retro Fonts**: Permanent Marker, Righteous, Concert One

**🎨 Color Palette:**
- Roses Red (#DC143C)
- Lightning Blue (#4169E1)
- Psychedelic Purple (#8B008B)
- Sunset Orange (#FF6B35)
- Golden Sun (#FFD700)
- Forest Green (#228B22)

**✨ Animated Effects:**
- Pulsing glow on toggle button
- Rotating Steal Your Face skull
- Dancing bears typing indicator
- Sliding psychedelic background patterns
- Lightning bolt cursor for streaming responses

**🎭 Theme Elements:**
- Header with fire gradient (red → purple)
- Skull emoji (☠️) in header and assistant avatar
- Bear emoji (🐻) for sources section
- Rose emoji (🌹) for welcome message
- Psychedelic border effects
- Custom scrollbar with gradient

---

## 🔄 What Changed

### Files Modified

1. **`public/class-chatbot-public.php`**
   - Line 47-62: Switched from Professional theme to GD theme
   - Line 176: Changed container class to `gd-theme-grateful-dead`

2. **`gd-chatbot.php`**
   - Version updated to 2.0.6

3. **`CHANGELOG.md`**
   - Added v2.0.6 entry

### Theme Files

- **Active**: `public/css/gd-theme.css` (623 lines of psychedelic styling)
- **Available**: `public/css/professional-theme.css` (commented out, still included)
- **Base**: `public/css/chatbot-styles.css` (unchanged)

---

## 🔐 Settings Preservation - GUARANTEED ✅

**ALL YOUR SETTINGS ARE 100% SAFE!**

This is purely a visual/CSS change. Nothing about your configuration changes:

- ✅ **API Keys** - Claude, Tavily, Pinecone all preserved
- ✅ **System Prompt** - Your custom AI instructions intact
- ✅ **All Settings** - Every configuration option preserved
- ✅ **Conversation History** - All logged chats remain
- ✅ **Database** - No schema changes
- ✅ **Functionality** - Everything works exactly the same

### How It Works

The update only changes which CSS file is loaded:
- **Before**: `professional-theme.css`
- **After**: `gd-theme.css`

All WordPress options remain untouched. The activation hook still checks:
```php
if (get_option('gd_chatbot_v2_' . $key) === false) {
    add_option('gd_chatbot_v2_' . $key, $value);
}
```

---

## 📸 Visual Comparison

### Before (Professional Theme)
- Clean, modern design
- Inspired by dead.net
- Professional color scheme
- Minimal animations

### After (Grateful Dead Theme)
- Psychedelic, vibrant design
- Inspired by classic GD posters
- Bold, colorful gradients
- Multiple animations and effects
- Iconic GD imagery (skulls, bears, roses)

---

## 🎯 Who Should Update?

**Update If You Want:**
- ✅ More Grateful Dead themed appearance
- ✅ Psychedelic colors and animations
- ✅ Iconic GD imagery (skulls, bears, roses)
- ✅ Retro concert poster aesthetic
- ✅ More visual personality

**Stay on v2.0.5 If You Prefer:**
- The cleaner, professional look
- Minimal animations
- Subtle, modern design

**Note**: You can always switch themes by editing `public/class-chatbot-public.php` (lines 47-62)

---

## 📦 Installation Instructions

### For New Installations

1. Download `gd-chatbot-v2.0.6.zip`
2. WordPress Admin → Plugins → Add New → Upload Plugin
3. Install and Activate
4. Configure API keys in Settings → GD Chatbot
5. Add `[gd_chatbot_v2]` to any page

### For Upgrades from v2.0.5 or Earlier

**Method 1: WordPress Admin (Recommended)**

1. Download `gd-chatbot-v2.0.6.zip`
2. Go to WordPress Admin → Plugins
3. **Deactivate** GD Chatbot v2 (settings preserved!)
4. **Delete** the old plugin
5. Click "Add New" → "Upload Plugin"
6. Upload `gd-chatbot-v2.0.6.zip`
7. **Activate** the plugin
8. **Clear browser cache** (Ctrl+F5) to see new theme
9. Verify settings in Settings → GD Chatbot

**Method 2: FTP/File Manager**

1. Extract `gd-chatbot-v2.0.6.zip`
2. Delete `/wp-content/plugins/gd-chatbot/`
3. Upload new `gd-chatbot/` folder
4. Activate in WordPress admin
5. **Clear browser cache** (Ctrl+F5)

**Method 3: WP-CLI**

```bash
wp plugin deactivate gd-chatbot
wp plugin delete gd-chatbot
wp plugin install /path/to/gd-chatbot-v2.0.6.zip --activate
```

---

## ✅ Post-Update Checklist

After updating:

- [ ] Plugin shows version 2.0.6 in Plugins list
- [ ] **Clear browser cache** (Ctrl+F5 or Cmd+Shift+R)
- [ ] Chatbot displays with psychedelic colors
- [ ] See skull emoji (☠️) in header
- [ ] See animated effects (pulsing, rotating)
- [ ] All settings still present (Settings → GD Chatbot)
- [ ] Can send/receive messages normally
- [ ] No JavaScript errors in console

**Important**: You MUST clear your browser cache to see the new theme!

---

## 🎨 Theme Customization

### Switching Back to Professional Theme

If you prefer the professional theme:

1. Edit `gd-chatbot/public/class-chatbot-public.php`
2. Find lines 47-62
3. Comment out the GD theme lines
4. Uncomment the Professional theme lines
5. Save and upload

Or just install v2.0.5 which uses the professional theme.

### Custom Colors

The theme respects the color setting from Settings → GD Chatbot → Appearance. However, the GD theme has its own color palette that may override some settings for the full psychedelic effect.

---

## 🐛 Known Issues

**None** - This is a pure CSS change with no functionality changes.

**Note**: If you don't see the new theme after updating:
1. Clear your browser cache (Ctrl+F5)
2. Clear WordPress cache if using a caching plugin
3. Check that v2.0.6 is active in Plugins list

---

## 📱 Responsive Design

The Grateful Dead theme is fully responsive:
- **Desktop**: Full psychedelic experience
- **Tablet**: Optimized layouts
- **Mobile**: Touch-friendly, scaled appropriately
- **Accessibility**: Respects `prefers-reduced-motion` setting

---

## 🌙 Dark Mode

The theme includes dark mode support that activates automatically based on system preferences:
- Dark backgrounds
- Adjusted text colors
- Maintained psychedelic effects
- High contrast maintained

---

## 📊 Version Comparison

| Feature | v2.0.5 | v2.0.6 |
|---------|--------|--------|
| Default Theme | Professional | Grateful Dead |
| Skull Icons | ❌ | ✅ |
| Dancing Bears | ❌ | ✅ |
| Psychedelic Colors | ❌ | ✅ |
| Animations | Minimal | Multiple |
| Retro Fonts | ❌ | ✅ |
| Settings Preserved | ✅ | ✅ |
| Functionality | ✅ | ✅ |

---

## 🔄 Rollback Instructions

To rollback to v2.0.5 (professional theme):

1. Deactivate v2.0.6
2. Delete v2.0.6
3. Install `gd-chatbot-v2.0.5.zip`
4. Activate
5. Clear browser cache

Your settings will be preserved during rollback.

---

## 📞 Support

**Questions?**
- Email: peter@it-influentials.com
- Documentation: See `README.md`
- Theme Details: See `public/css/gd-theme.css`

**Reporting Issues:**
Include:
- WordPress version
- PHP version
- Browser and version
- Screenshot of issue
- Console errors (F12 → Console)

---

## 🎉 Summary

This release brings the **full Grateful Dead experience** to your chatbot with:
- 🌹 Psychedelic colors and gradients
- ☠️ Steal Your Face imagery
- 🐻 Dancing bears animations
- ⚡ Lightning bolt effects
- 🎨 Retro concert poster aesthetic

**All your settings, API keys, and data are completely safe.** This is purely a visual enhancement that makes your chatbot look like it belongs at a Dead show!

---

**What a long, strange trip it's been... 🌹⚡☠️**

---

**Last Updated:** January 12, 2026  
**Plugin Version:** 2.0.6  
**Stability:** Stable  
**Update Priority:** Optional (Visual Enhancement)
