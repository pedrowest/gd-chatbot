# User Guide: Connecting Streaming Services

**GD Chatbot v2.2.0+**

---

## Overview

Connect your streaming service accounts to search and play Grateful Dead music across multiple platforms directly from the chatbot. When you click on a song title in the chat, you'll see results from Archive.org AND your connected streaming services.

---

## Supported Services

- 🎵 **Spotify** - 70+ million songs
- 🍎 **Apple Music** - 100+ million songs
- 📺 **YouTube Music** - Official videos and audio
- 📦 **Amazon Music** - High-quality streaming
- 🌊 **Tidal** - Lossless audio quality

---

## How to Connect a Service

### Step 1: Access Your Profile

1. Log in to your WordPress account
2. Go to **Users → Your Profile** (or click your name in the admin bar)
3. Scroll down to the **"Music Streaming Services"** section

### Step 2: Connect a Service

1. Find the service you want to connect (e.g., Spotify)
2. Click the **"Connect [Service]"** button
3. A popup window will open with the service's login page
4. Log in with your service account credentials
5. Authorize the GD Chatbot to access your account
6. The popup will close automatically
7. Your profile page will refresh showing "✅ Connected"

### Step 3: Use in the Chatbot

1. Open the chatbot and ask about a song
2. Click on any song title in the response
3. You'll now see **tabs** at the top of the modal:
   - 🎸 Archive.org (live recordings)
   - 🎵 Spotify (if connected)
   - 🍎 Apple Music (if connected)
   - etc.
4. Click a tab to see results from that service
5. Click "Play" to listen

---

## Using Multiple Services

You can connect as many services as you like! Each connected service will appear as a tab in the song modal.

**Example:**
- Connected: Spotify, Apple Music, Tidal
- Click "Dark Star" in chat
- See tabs: Archive.org | Spotify | Apple Music | Tidal
- Switch between tabs to compare versions
- Play from your preferred service

---

## Managing Connections

### Disconnect a Service

1. Go to **Users → Your Profile**
2. Find the service in the "Music Streaming Services" section
3. Click **"Disconnect"**
4. Confirm the action
5. The service will be removed

### Reconnect a Service

If your connection expires:
1. You'll see "⚠️ Token Expired" next to the service
2. Click **"Reconnect"**
3. Follow the same authorization flow
4. Your connection will be refreshed

---

## Troubleshooting

### "Not configured by administrator"

**Problem:** The service shows a warning that it's not configured.

**Solution:** Ask your site administrator to configure the API credentials in **GD Chatbot v2 → Settings → Streaming Services**.

### Connection Fails

**Problem:** The popup closes but the service doesn't connect.

**Solution:**
1. Make sure you completed the authorization
2. Check that you didn't deny permission
3. Try again with a different browser
4. Clear your browser cache and cookies
5. Contact your administrator if the problem persists

### No Tabs Appear in Modal

**Problem:** You connected a service but don't see tabs in the song modal.

**Solution:**
1. Make sure you're logged in
2. Refresh the page
3. Check that the service shows "✅ Connected" in your profile
4. Try disconnecting and reconnecting

### Popup Blocked

**Problem:** The authorization popup is blocked by your browser.

**Solution:**
1. Allow popups for your WordPress site
2. Look for the popup blocker icon in your address bar
3. Click "Always allow popups from this site"
4. Try connecting again

### Token Expired

**Problem:** Service shows "⚠️ Token Expired".

**Solution:**
1. Click **"Reconnect"** in your profile
2. Authorize again
3. Your token will be refreshed

---

## Privacy & Security

### What Data is Stored?

- Your access tokens (encrypted with AES-256)
- Token expiration dates
- Connection timestamps

### What Data is NOT Stored?

- Your streaming service passwords
- Your listening history
- Your playlists
- Any personal data from streaming services

### How is Data Protected?

- All credentials are encrypted using AES-256-CBC
- Tokens are stored securely in WordPress user meta
- Only you can access your own credentials
- Administrators cannot see your tokens
- Tokens are automatically refreshed when expired

### Can I Revoke Access?

Yes! You can disconnect any service at any time from your profile. This will:
- Delete your stored credentials
- Revoke the chatbot's access to your account
- Remove the service from your song modal tabs

You can also revoke access directly on the service's website:
- **Spotify:** Account → Apps → Remove GD Chatbot
- **Apple Music:** Apple ID → Security → Apps → Remove GD Chatbot
- **YouTube Music:** Google Account → Security → Third-party apps → Remove GD Chatbot
- **Amazon Music:** Account → Apps and Services → Remove GD Chatbot
- **Tidal:** Account → Apps → Remove GD Chatbot

---

## Benefits of Connecting Services

### 1. More Options
- Access studio recordings, live albums, and official releases
- Compare different versions of the same song
- Find songs not available on Archive.org

### 2. Better Quality
- High-quality audio (Tidal offers lossless)
- Official mastering and production
- No audience noise or recording artifacts

### 3. Convenience
- Play directly in your streaming app
- Add to your playlists
- Download for offline listening (if supported)

### 4. Discovery
- See popularity scores (Spotify)
- View related artists and albums
- Explore curated playlists

---

## Frequently Asked Questions

**Q: Do I need to connect streaming services?**  
A: No, it's optional. Archive.org works without any connections.

**Q: Can I use the chatbot without logging in?**  
A: Yes, but you'll only see Archive.org results. Streaming services require login.

**Q: How many services can I connect?**  
A: All 5 services if you want! There's no limit.

**Q: Will this cost me anything?**  
A: You need an active subscription to each streaming service you want to use. The chatbot integration itself is free.

**Q: Can I connect a service I don't have a subscription for?**  
A: No, you need an active subscription to authorize the connection.

**Q: What happens if my subscription expires?**  
A: The service will show as expired in your profile. You can disconnect it or renew your subscription and reconnect.

**Q: Can I switch my default service?**  
A: The modal always opens to Archive.org first, but you can click any tab to switch. Your last selection is remembered during your session.

**Q: Does this work on mobile?**  
A: Yes! The modal is fully responsive and works on phones and tablets.

---

## Getting Help

### Documentation
- Admin guide: How administrators configure services
- Technical guide: API documentation and OAuth flows

### Support
- Contact your site administrator for configuration issues
- Check the streaming service's help center for account issues
- Report bugs or feature requests to the plugin developer

---

## Tips & Best Practices

1. **Connect Your Favorite Service First** - Start with the service you use most
2. **Try Different Services** - Compare audio quality and availability
3. **Keep Tokens Fresh** - Reconnect if you see expiration warnings
4. **Use Archive.org for Live Shows** - It has the best collection of live recordings
5. **Use Streaming Services for Studio Albums** - Better quality for official releases

---

**Last Updated:** February 12, 2026  
**Plugin Version:** 2.2.0  
**Phase:** 4 (Streaming Services Integration)
