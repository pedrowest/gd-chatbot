# Admin Quick Reference: Streaming Services

**GD Chatbot v2.2.0**

---

## Quick Setup (5 Minutes Per Service)

### 1. Get Developer Credentials

| Service | Developer Portal | What You Need |
|---------|-----------------|---------------|
| 🎵 Spotify | [developer.spotify.com](https://developer.spotify.com/dashboard) | Client ID, Client Secret |
| 🍎 Apple Music | [developer.apple.com](https://developer.apple.com/) | Team ID, Key ID, JWT Token |
| 📺 YouTube Music | [console.cloud.google.com](https://console.cloud.google.com/) | Client ID, Client Secret |
| 📦 Amazon Music | [developer.amazon.com](https://developer.amazon.com/) | Client ID, Client Secret |
| 🌊 Tidal | [developer.tidal.com](https://developer.tidal.com/) | Client ID, Client Secret |

### 2. Configure in WordPress

1. Go to **GD Chatbot v2 → Settings → Streaming Services**
2. Enter credentials for each service
3. Copy the Redirect URI
4. Add Redirect URI to service's app settings
5. Click "Test Connection"
6. Click "Save All Configurations"

### 3. Users Can Now Connect

Users go to **Users → Your Profile → Music Streaming Services** to connect their accounts.

---

## Redirect URIs (Copy These)

```
Spotify:
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=spotify

Apple Music:
(Uses JWT tokens, no redirect needed)

YouTube Music:
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=youtube_music

Amazon Music:
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=amazon_music

Tidal:
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=tidal
```

---

## Service-Specific Notes

### Spotify
- **Free to set up**
- Most popular service
- Great search results
- 180 requests/min per user

### Apple Music
- **Requires $99/year Apple Developer Program**
- JWT token expires every 6 months
- Need to regenerate token periodically
- Best for iOS users

### YouTube Music
- **Free to set up**
- Uses Google OAuth
- 10,000 API units/day (search = 100 units)
- Good for video content

### Amazon Music
- **Free to set up**
- Uses Login with Amazon
- Good for Amazon ecosystem users
- Preview URLs available

### Tidal
- **Requires developer approval** (can take days)
- High-quality audio (lossless)
- Great for audiophiles
- 300 requests/min

---

## Monitoring

### View Statistics

**Location:** GD Chatbot v2 → Settings → Streaming Services

**Metrics:**
- Configuration status per service
- Connected user counts
- Service availability

### Check Logs

**Database Table:** `wp_usermeta`

**Query for connected users:**
```sql
SELECT COUNT(DISTINCT user_id) 
FROM wp_usermeta 
WHERE meta_key LIKE 'gd_streaming_%'
```

---

## Troubleshooting

### "Test Connection" Fails

**Check:**
1. Client ID and Secret are correct (no spaces)
2. Redirect URI is added to service's app
3. Service's API is enabled
4. Server can make HTTPS requests

### Users Can't Connect

**Check:**
1. Configuration is saved
2. "Test Connection" succeeds
3. User is logged in
4. Popup blockers are disabled

### Token Refresh Fails

**Check:**
1. Refresh tokens are being stored
2. Service credentials haven't been revoked
3. User's subscription is active

---

## Security Checklist

- [ ] Store credentials in `wp-config.php` (production)
- [ ] Use HTTPS (required for OAuth)
- [ ] Rotate credentials every 6-12 months
- [ ] Monitor for unusual API usage
- [ ] Restrict admin access to trusted users

---

## Maintenance Schedule

### Monthly
- Check service status
- Review connected user counts
- Verify test connections work

### Quarterly
- Review API usage
- Check for service updates
- Update documentation

### Annually
- Rotate credentials
- Renew Apple Developer Program
- Review caching strategies

---

## Common Commands

### Clear All User Connections (SQL)
```sql
DELETE FROM wp_usermeta 
WHERE meta_key LIKE 'gd_streaming_%'
```

### Count Connections Per Service (SQL)
```sql
SELECT meta_key, COUNT(*) as count
FROM wp_usermeta
WHERE meta_key LIKE 'gd_streaming_%'
GROUP BY meta_key
```

### Check User's Connections (SQL)
```sql
SELECT meta_key, meta_value
FROM wp_usermeta
WHERE user_id = 1 
AND meta_key LIKE 'gd_streaming_%'
```

---

## Support Resources

### Documentation
- **User Guide:** `USER-GUIDE-STREAMING-SERVICES.md`
- **Admin Guide:** `ADMIN-GUIDE-STREAMING-SERVICES.md`
- **Technical Docs:** `PHASE-4-COMPLETE.md`

### Service Documentation
- [Spotify Web API](https://developer.spotify.com/documentation/web-api)
- [Apple MusicKit](https://developer.apple.com/documentation/applemusicapi)
- [YouTube Data API](https://developers.google.com/youtube/v3)
- [Amazon Music API](https://developer.amazon.com/docs/login-with-amazon/documentation-overview.html)
- [Tidal API](https://developer.tidal.com/)

---

## Quick Wins

### Enable Just Spotify (Easiest)
1. Create Spotify app (5 min)
2. Copy Client ID/Secret
3. Configure in WordPress
4. Done! Most popular service enabled

### Enable All Free Services
1. Spotify (5 min)
2. YouTube Music (10 min)
3. Amazon Music (10 min)
4. Tidal (5 min + wait for approval)
5. Total: ~30 min + approval time

### Skip Apple Music Initially
- Requires $99/year
- JWT token management
- Can add later if needed

---

## Success Metrics

### Week 1
- At least 1 service configured
- 5+ users connected
- 0 critical errors

### Month 1
- 3+ services configured
- 25+ users connected
- 100+ searches/day

### Month 3
- All desired services configured
- 50+ users connected
- 500+ searches/day

---

**Need Help?** Check the full admin guide or contact support.

**Last Updated:** February 12, 2026  
**Version:** 2.2.0
