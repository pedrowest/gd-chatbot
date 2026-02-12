# Quick Installation Guide - GD Chatbot v2.2.0

**5-Minute Setup** | **No Configuration Required** | **Works Immediately**

---

## 📦 What You're Installing

**GD Chatbot v2.2.0** - AI-powered Grateful Dead chatbot with music streaming integration

**New in v2.2.0:**
- 🎸 Click any song in responses to hear it
- 🎵 Access 2,340+ Archive.org live shows
- 🔗 Optional: Connect Spotify, Apple Music, YouTube Music, Amazon Music, Tidal
- 📱 Beautiful mobile-responsive interface

---

## ⚡ Quick Install (3 Steps)

### Step 1: Upload Plugin

**Via WordPress Admin (Recommended):**

1. Log in to WordPress admin
2. Go to **Plugins → Add New → Upload Plugin**
3. Click "Choose File"
4. Select `gd-chatbot-2.2.0.zip`
5. Click "Install Now"
6. Click "Activate Plugin"

**Via FTP/SFTP:**

1. Unzip `gd-chatbot-2.2.0.zip`
2. Upload `gd-chatbot` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin
4. Click "Activate" under "GD Chatbot v2"

### Step 2: Sync Archive.org Data

1. Go to **GD Chatbot v2 → Music Streaming**
2. Click **"Sync All Years"** button
3. Wait 5-10 minutes (progress bar shows status)
4. See "✅ Sync completed successfully!"

### Step 3: Test It!

1. Open your site's chatbot
2. Ask: **"Tell me about Dark Star"**
3. Click the blue "Dark Star" link
4. Modal opens with Archive.org performances
5. Click "Play" to listen

**Done! Music streaming is now active.** 🎉

---

## ⚙️ Basic Configuration

### Required Settings (Already Configured)

The plugin works immediately with these defaults:
- ✅ Music streaming enabled
- ✅ Sort by most popular
- ✅ Show 50 results per song
- ✅ Cache results for 24 hours

### Optional: Adjust Settings

Go to **GD Chatbot v2 → Settings → Music Streaming** to customize:

- **Enable/Disable** - Turn music streaming on/off
- **Default Sort** - Most Popular, Date, or Rating
- **Result Limit** - 10-100 performances per song
- **Cache Duration** - 1-168 hours
- **Autoplay** - Auto-start audio on play click

---

## 🎵 Optional: Add Streaming Services

**Want Spotify, Apple Music, etc.?** Follow these steps:

### For Administrators

1. **Get API Credentials** (5-10 min per service)
   - Create developer account on desired service
   - Create an app
   - Copy Client ID and Client Secret

2. **Configure in WordPress**
   - Go to **GD Chatbot v2 → Settings → Streaming Services**
   - Enter credentials for each service
   - Copy the Redirect URI shown
   - Add Redirect URI to service's app settings
   - Click "Test Connection"
   - Click "Save All Configurations"

**See `ADMIN-GUIDE-STREAMING-SERVICES.md` for detailed instructions.**

### For Users

Once admin configures services:

1. Go to **Users → Your Profile**
2. Scroll to "Music Streaming Services"
3. Click "Connect [Service]"
4. Authorize in popup
5. Done! Service appears in song modal tabs

---

## ✅ Verification Checklist

After installation, verify these work:

### Basic Functionality
- [ ] Song titles are blue links in chatbot
- [ ] Clicking link opens modal
- [ ] Archive.org results load
- [ ] Audio player works
- [ ] Sort dropdown works
- [ ] Mobile layout looks good

### Admin Dashboard
- [ ] Can access Music Streaming page
- [ ] Database shows 2,340+ shows
- [ ] Sync buttons work
- [ ] No errors in status cards

### Optional (If Streaming Services Configured)
- [ ] Can connect service from profile
- [ ] OAuth popup works
- [ ] Source tabs appear in modal
- [ ] Streaming results load

---

## 🔧 Troubleshooting

### Issue: Song Links Don't Appear

**Fix:**
1. Go to **Settings → Music Streaming**
2. Check "Enable Music Streaming" is ✅
3. Click "Clear Song Cache"
4. Refresh chatbot page

### Issue: Modal Opens But No Results

**Fix:**
1. Go to **Music Streaming** dashboard
2. Check "Database Status" card
3. If shows 0 shows, click "Sync All Years"
4. Wait for sync to complete

### Issue: Audio Won't Play

**Possible Causes:**
- Archive.org is temporarily down (try again later)
- Browser blocks autoplay (click play manually)
- Network issues (check internet connection)

### Issue: Sync Fails

**Fix:**
1. Check server can reach archive.org
2. Increase PHP `max_execution_time` to 300
3. Try syncing one year at a time
4. Check error logs for details

---

## 📊 System Requirements

### Minimum Requirements
- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- 50MB disk space
- HTTPS (for OAuth - optional)

### Recommended
- WordPress 6.0+
- PHP 8.0+
- MySQL 8.0+
- HTTPS enabled
- Object caching (Redis/Memcached)

---

## 🔒 Security Notes

### What's Secure
- ✅ All streaming credentials encrypted (AES-256)
- ✅ OAuth 2.0 with CSRF protection
- ✅ Nonce verification on all AJAX
- ✅ Capability checks for admin functions

### Best Practices
1. **Use HTTPS** - Required for streaming services
2. **Strong Passwords** - For admin accounts
3. **Regular Updates** - Keep WordPress and PHP updated
4. **Backup Regularly** - Before major changes

---

## 📚 Documentation

### Included Documentation

**User Guides:**
- `USER-GUIDE-STREAMING-SERVICES.md` - How to use streaming
- `QUICK-START-STREAMING.md` - Quick reference

**Admin Guides:**
- `ADMIN-GUIDE-STREAMING-SERVICES.md` - Configuration guide
- `ADMIN-QUICK-START.md` - Quick setup
- `ADMIN-QUICK-REFERENCE.md` - Command reference

**Technical Docs:**
- `IMPLEMENTATION-SUMMARY.md` - Technical overview
- `DEPLOYMENT-GUIDE.md` - Production deployment
- `PHASE-1-COMPLETE.md` through `PHASE-4-COMPLETE.md` - Implementation details

### Where to Find Docs

All documentation is in the `/docs/` folder of the plugin directory:
```
/wp-content/plugins/gd-chatbot/docs/
```

Or view online at: [Your documentation URL]

---

## 🎯 What's Next?

### Immediate Actions
1. ✅ Install plugin
2. ✅ Run sync
3. ✅ Test song links

### Optional Enhancements
1. Configure streaming services
2. Customize settings
3. Train users on new features
4. Monitor usage and optimize

### Future Features (Phase 5)
- Favorite performances
- Create playlists
- Listening history
- Recommendations

---

## 📞 Getting Help

### Self-Service
1. Check documentation (10+ guides included)
2. Review error logs
3. Search WordPress forums
4. Check browser console for errors

### Support Channels
- **Email:** support@it-influentials.com
- **Documentation:** See `/docs/` folder
- **WordPress Forums:** [Your forum URL]

### Reporting Bugs

Include:
- WordPress version
- PHP version
- Steps to reproduce
- Error messages
- Screenshots

---

## 📄 License & Credits

**License:** GPL-2.0+  
**Developer:** IT Influentials  
**Version:** 2.2.0  
**Release Date:** February 12, 2026

**APIs Used:**
- Archive.org (live recordings)
- Anthropic Claude (AI chatbot)
- Spotify, Apple Music, YouTube Music, Amazon Music, Tidal (optional)

---

## 🎸 Enjoy!

You're all set! Your chatbot now has powerful music streaming capabilities.

**Ask about any Grateful Dead song and start listening!**

---

**Questions?** Check the docs or contact support.

**Happy listening!** 🎵🚀
