# Deployment Guide: Music Streaming Integration

**GD Chatbot v2.2.0**  
**Feature:** Multi-Platform Music Streaming  
**Deployment Type:** WordPress Plugin Update

---

## Pre-Deployment Checklist

### 1. Environment Verification

- [ ] WordPress version 5.0 or higher
- [ ] PHP version 7.2 or higher
- [ ] MySQL version 5.6 or higher
- [ ] HTTPS enabled (required for OAuth)
- [ ] Server can make outbound HTTPS requests
- [ ] Sufficient disk space (50MB+ recommended)

### 2. Backup

- [ ] Full database backup
- [ ] Plugin files backup
- [ ] wp-config.php backup
- [ ] .htaccess backup (if applicable)

### 3. Staging Test

- [ ] Test on staging environment first
- [ ] Verify all features work
- [ ] Check for conflicts with other plugins
- [ ] Test on mobile devices

---

## Deployment Steps

### Step 1: Update Plugin Files

**Option A: Via WordPress Admin (Recommended)**

1. Download `gd-chatbot-2.2.0.zip`
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file
4. Click "Install Now"
5. Click "Activate Plugin"

**Option B: Via FTP/SFTP**

1. Connect to your server
2. Navigate to `/wp-content/plugins/`
3. Backup existing `gd-chatbot/` folder
4. Upload new plugin files
5. Overwrite when prompted

**Option C: Via SSH**

```bash
cd /path/to/wordpress/wp-content/plugins/
mv gd-chatbot gd-chatbot-backup
unzip gd-chatbot-2.2.0.zip
chown -R www-data:www-data gd-chatbot
```

### Step 2: Run Database Migrations

The plugin will automatically create new tables on activation. To verify:

1. Go to **GD Chatbot v2 → Music Streaming**
2. Check the "Database Status" card
3. Should show 4 tables with 0 records initially

**Manual Verification (SQL):**

```sql
SHOW TABLES LIKE 'wp_gd_archive_%';
```

Should return:
- `wp_gd_archive_metadata`
- `wp_gd_archive_recordings`
- `wp_gd_archive_sync_log`
- `wp_gd_archive_cache`

### Step 3: Initial Configuration

#### Basic Settings

1. Go to **GD Chatbot v2 → Settings → Music Streaming**
2. Enable music streaming: ✅
3. Set default sort order: "Most Popular"
4. Set result limit: 50
5. Set cache duration: 24 hours
6. Click "Save Streaming Settings"

#### Archive.org Sync

1. Go to **GD Chatbot v2 → Music Streaming**
2. Click "Sync All Years" (this will take 5-10 minutes)
3. Monitor progress bar
4. Verify success message
5. Check "Database Status" shows 2,340+ shows

### Step 4: Test Basic Functionality

1. Open chatbot on frontend
2. Ask: "Tell me about Dark Star"
3. Verify "Dark Star" appears as a blue link
4. Click the link
5. Modal should open with Archive.org results
6. Click "Play" on a performance
7. Audio should start playing

**If this works, core functionality is operational!** ✅

---

## Optional: Configure Streaming Services

### Prerequisites

- Developer accounts on desired services
- API credentials (Client ID, Client Secret)
- Time to set up (5-10 min per service)

### Quick Setup (Spotify Example)

1. **Get Credentials:**
   - Go to [developer.spotify.com](https://developer.spotify.com/dashboard)
   - Create an app
   - Copy Client ID and Client Secret

2. **Configure in WordPress:**
   - Go to **GD Chatbot v2 → Settings → Streaming Services**
   - Scroll to "Spotify" section
   - Paste Client ID and Client Secret
   - Copy the Redirect URI shown

3. **Add Redirect URI:**
   - Go back to Spotify Developer Dashboard
   - Click "Edit Settings"
   - Add the Redirect URI
   - Click "Save"

4. **Test:**
   - Click "Test Connection" in WordPress
   - Should show "✅ Configuration is valid!"
   - Click "Save All Configurations"

5. **User Test:**
   - Go to **Users → Your Profile**
   - Scroll to "Music Streaming Services"
   - Click "Connect Spotify"
   - Authorize in popup
   - Should show "✅ Connected"

6. **Verify in Modal:**
   - Open chatbot
   - Click a song link
   - Should see tabs: 🎸 Archive.org | 🎵 Spotify
   - Click Spotify tab
   - Should see Spotify results

**Repeat for other services as desired.**

---

## Post-Deployment Verification

### Functional Tests

#### Song Detection
- [ ] Song titles are detected in responses
- [ ] Links are blue and underlined
- [ ] Hover shows "Click to listen" tooltip

#### Modal Functionality
- [ ] Modal opens on link click
- [ ] Archive.org results load
- [ ] Sort dropdown works
- [ ] Performance cards display correctly
- [ ] Thumbnails load

#### Audio Playback
- [ ] Click "Play" opens audio player
- [ ] Audio starts playing (or shows error)
- [ ] Player controls work (play, pause, seek)
- [ ] Player close button works

#### Streaming Services (if configured)
- [ ] Source tabs appear for connected services
- [ ] Tab switching works
- [ ] Streaming results load
- [ ] Album art displays
- [ ] "Play" opens service in new tab

#### Admin Dashboard
- [ ] Music Streaming page loads
- [ ] Status cards display correctly
- [ ] Sync buttons work
- [ ] Recent sync history shows

#### User Profile
- [ ] "Music Streaming Services" section appears
- [ ] Service status displays correctly
- [ ] Connect buttons work
- [ ] OAuth popup flow works

### Performance Tests

- [ ] Modal opens in < 500ms
- [ ] Archive.org search completes in < 3s
- [ ] Page load time unchanged
- [ ] No JavaScript errors in console
- [ ] No PHP errors in logs

### Mobile Tests

- [ ] Modal is responsive
- [ ] Tabs scroll horizontally
- [ ] Touch interactions work
- [ ] Audio player works
- [ ] No layout issues

### Security Tests

- [ ] Non-logged-in users can't connect services
- [ ] Non-admins can't access admin pages
- [ ] Credentials are encrypted in database
- [ ] OAuth state tokens validated

---

## Troubleshooting

### Issue: Database Tables Not Created

**Solution:**
1. Deactivate plugin
2. Reactivate plugin
3. Check for PHP errors in logs
4. Manually run schema creation:

```php
// In wp-admin/admin.php or via plugin
$db = new GD_Streaming_Database();
$db->create_tables();
```

### Issue: Song Links Not Appearing

**Check:**
1. Music streaming is enabled in settings
2. Song cache is populated (wait 24h or clear cache)
3. JavaScript is loading (check browser console)
4. No JavaScript conflicts with other plugins

**Fix:**
1. Go to **Settings → Music Streaming**
2. Click "Clear Song Cache"
3. Refresh chatbot page
4. Try again

### Issue: Modal Not Opening

**Check:**
1. Browser console for JavaScript errors
2. `song-modal.js` is loading
3. No CSS conflicts
4. jQuery is loaded

**Fix:**
1. Clear browser cache
2. Disable other plugins temporarily
3. Check for JavaScript conflicts
4. Re-enqueue scripts

### Issue: Archive.org Results Not Loading

**Check:**
1. Sync has been run (check dashboard)
2. Database has records
3. Archive.org is accessible from server
4. No firewall blocking requests

**Fix:**
1. Run manual sync from dashboard
2. Check sync logs for errors
3. Verify server can reach archive.org:

```bash
curl https://archive.org/advancedsearch.php
```

### Issue: Streaming Service Won't Connect

**Check:**
1. Service is configured by admin
2. Credentials are correct
3. Redirect URI is added to service app
4. User is logged in
5. Popup blockers are disabled

**Fix:**
1. Test connection in admin settings
2. Verify redirect URI matches exactly
3. Check OAuth error logs
4. Try different browser

### Issue: Token Expired

**Solution:**
1. User goes to profile
2. Clicks "Reconnect" for expired service
3. Authorizes again
4. Token is refreshed

---

## Rollback Procedure

If deployment fails and you need to rollback:

### Step 1: Restore Plugin Files

**Via FTP:**
```bash
cd /wp-content/plugins/
rm -rf gd-chatbot
mv gd-chatbot-backup gd-chatbot
```

**Via WordPress:**
1. Deactivate current plugin
2. Delete plugin
3. Upload previous version
4. Activate

### Step 2: Restore Database (if needed)

**Full Restore:**
```bash
mysql -u username -p database_name < backup.sql
```

**Selective Restore (just new tables):**
```sql
DROP TABLE IF EXISTS wp_gd_archive_metadata;
DROP TABLE IF EXISTS wp_gd_archive_recordings;
DROP TABLE IF EXISTS wp_gd_archive_sync_log;
DROP TABLE IF EXISTS wp_gd_archive_cache;
```

### Step 3: Clear Caches

1. Clear WordPress object cache
2. Clear page cache (if using caching plugin)
3. Clear browser cache
4. Clear CDN cache (if applicable)

---

## Monitoring

### First 24 Hours

**Check every 2-4 hours:**
- Error logs for PHP errors
- Browser console for JavaScript errors
- Sync logs for failed syncs
- User reports of issues

**Key Metrics:**
- Song links clicked
- Modals opened
- Audio plays started
- Errors encountered

### First Week

**Check daily:**
- Database table sizes
- Sync success rate
- User connections (if services configured)
- Performance metrics

### Ongoing

**Check weekly:**
- Archive.org sync status
- Streaming service connections
- API rate limit usage
- User feedback

---

## Optimization

### Performance Tuning

**If modal is slow:**
1. Increase cache duration
2. Reduce result limit
3. Enable object caching (Redis/Memcached)
4. Optimize database indexes

**If sync is slow:**
1. Increase PHP max_execution_time
2. Increase PHP memory_limit
3. Run sync during off-peak hours
4. Use incremental sync instead of full

### Caching Strategy

**Recommended Settings:**
- Song cache: 24 hours
- Archive.org search: 1 hour
- Streaming search: 1 hour
- Database queries: Use object cache

**Clear Caches When:**
- After sync completes
- After configuration changes
- After plugin updates
- When testing changes

---

## Security Hardening

### Production Recommendations

1. **Store credentials in wp-config.php:**

```php
// Spotify
define('GD_SPOTIFY_CLIENT_ID', 'your_id');
define('GD_SPOTIFY_CLIENT_SECRET', 'your_secret');
```

2. **Enforce HTTPS:**

```php
// In wp-config.php
define('FORCE_SSL_ADMIN', true);
```

3. **Restrict admin access:**
- Use strong passwords
- Enable 2FA for admin accounts
- Limit admin user count

4. **Monitor for suspicious activity:**
- Failed OAuth attempts
- Unusual API usage
- Multiple connection attempts

5. **Regular updates:**
- Keep WordPress updated
- Keep PHP updated
- Rotate API credentials annually

---

## Support Resources

### Documentation
- **Implementation Summary:** `IMPLEMENTATION-SUMMARY.md`
- **User Guide:** `USER-GUIDE-STREAMING-SERVICES.md`
- **Admin Guide:** `ADMIN-GUIDE-STREAMING-SERVICES.md`
- **Quick Reference:** `ADMIN-QUICK-REFERENCE.md`

### Logs
- **PHP Errors:** `wp-content/debug.log` (if WP_DEBUG_LOG enabled)
- **Sync Logs:** Database table `wp_gd_archive_sync_log`
- **Browser Console:** F12 → Console tab

### Getting Help
1. Check documentation first
2. Review error logs
3. Search WordPress forums
4. Contact IT Influentials support

---

## Success Criteria

### Deployment is successful when:

- [x] Plugin activates without errors
- [x] Database tables created successfully
- [x] Song links appear in chatbot responses
- [x] Modal opens and displays Archive.org results
- [x] Audio playback works
- [x] Admin dashboard accessible
- [x] No critical errors in logs
- [x] Performance is acceptable (< 3s for searches)
- [x] Mobile experience is good

### Optional success criteria (if configured):

- [ ] At least one streaming service configured
- [ ] Users can connect services
- [ ] Streaming tabs appear in modal
- [ ] Streaming results load correctly
- [ ] OAuth flow works smoothly

---

## Deployment Timeline

### Recommended Schedule

**Staging Deployment:**
- Day 1: Deploy to staging
- Day 2-3: Test all features
- Day 4: Fix any issues
- Day 5: Final staging verification

**Production Deployment:**
- Day 6: Deploy to production (off-peak hours)
- Day 6-7: Monitor closely
- Day 8-10: Gather user feedback
- Day 11-14: Make adjustments as needed

**Service Configuration (Optional):**
- Week 2: Configure first streaming service (Spotify)
- Week 3: Add additional services as desired
- Week 4: Monitor usage and optimize

---

## Final Checklist

Before marking deployment complete:

- [ ] All functional tests passed
- [ ] Performance is acceptable
- [ ] Mobile experience verified
- [ ] Security checks completed
- [ ] Documentation updated
- [ ] Team trained on new features
- [ ] Users notified of new functionality
- [ ] Monitoring in place
- [ ] Rollback plan ready (just in case)
- [ ] Support team briefed

---

**Deployment Status:** Ready to Deploy ✅

**Estimated Deployment Time:** 1-2 hours (basic) + optional time for streaming services

**Risk Level:** Low (backwards compatible, no breaking changes)

---

**Good luck with your deployment! 🚀**

---

**Last Updated:** February 12, 2026  
**Version:** 2.2.0  
**Document Version:** 1.0
