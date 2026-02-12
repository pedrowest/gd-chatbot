# Music Streaming Admin Quick Start Guide

**For GD Chatbot v2.1.0+**

## Getting Started

### First-Time Setup

1. **Navigate to Settings**
   - Go to `GD Chatbot v2 → Settings`
   - Click the "Music Streaming" tab

2. **Enable Music Streaming**
   - Check "Enable Music Streaming"
   - Configure default sort order (recommended: "Most Popular")
   - Set result limit (recommended: 50)
   - Set cache duration (recommended: 24 hours)
   - Click "Save Streaming Settings"

3. **Run Initial Sync**
   - Go to `GD Chatbot v2 → Music Streaming` (dashboard)
   - Click "Run Incremental Sync" to get started
   - Or sync specific years (e.g., 1977, 1972, 1989)

---

## Common Tasks

### Running a Sync

**Incremental Sync** (Recommended for daily use)
- Updates recent shows and popular performances
- Takes 5-15 minutes
- Safe to run multiple times per day

**Year Sync** (For specific years)
- Enter year: 1965-1995
- Takes 5-10 minutes per year
- Use for popular years like 1977, 1972, 1989

**Date Sync** (For specific dates)
- Select a date from the picker
- Takes 1-2 minutes
- Use when users ask about a specific show

**Full Sync** (Use sparingly)
- Syncs all years (1965-1995)
- Takes 2-3 hours
- Only needed once or after major issues

### Checking System Health

1. Go to `GD Chatbot v2 → Music Streaming`
2. View the four status cards:
   - **Database Status**: See recording counts
   - **Sync Status**: Check last sync time
   - **Cache Status**: Monitor cache size
   - **Health Status**: Look for integrity issues

3. If issues are found:
   - Click "Clean Up Database" to fix orphaned records
   - Check "Recent Sync History" for errors

### Testing Song Detection

1. Go to dashboard → "Song Detection Testing"
2. Enter text like: "They played Dark Star at Cornell in 1977"
3. Click "Test Detection"
4. Verify songs are detected and links work

### Managing Caches

**Clear Archive.org Cache** (when needed)
- Dashboard → "Cache Status" card → "Clear All Caches"
- Use when Archive.org data seems stale
- Cache rebuilds automatically

**Clear Song Cache** (rarely needed)
- Settings → Music Streaming tab → "Clear Song Cache"
- Use after updating `songs.csv` file
- Cache rebuilds on next use

---

## Troubleshooting

### Songs Not Detected

1. Check if song is in `plugin/context/reference/songs.csv`
2. Clear song cache (Settings → Music Streaming)
3. Test detection in dashboard
4. Check for typos or alternate titles

### No Performances Found

1. Check if sync has run recently
2. Run incremental sync
3. Check "Recent Sync History" for errors
4. Verify Archive.org is accessible

### Modal Not Opening

1. Check browser console for JavaScript errors
2. Verify streaming is enabled (Settings → Music Streaming)
3. Clear browser cache
4. Test in incognito mode

### Sync Failures

1. Check "Recent Sync History" for error details
2. Verify Archive.org API is accessible
3. Check PHP error logs
4. Try a smaller sync (single year or date)
5. Increase PHP `max_execution_time` if needed

### Database Issues

1. Dashboard → "Database Health" card
2. If issues found, click "Clean Up Database"
3. If problems persist, use "Danger Zone" → "Reset Tables"
4. Re-run full sync after reset

---

## Maintenance Schedule

### Daily
- Run incremental sync (optional, if high traffic)
- Check dashboard for errors

### Weekly
- Review "Recent Sync History"
- Check cache sizes
- Run incremental sync

### Monthly
- Clear Archive.org cache
- Review database health
- Sync any missing years

### As Needed
- Sync specific dates when users request them
- Test song detection after CSV updates
- Clean up database if integrity issues arise

---

## Performance Tips

1. **Cache Duration**: Keep at 24 hours for most sites
2. **Result Limit**: 50 is optimal (balance between variety and speed)
3. **Sync Strategy**: Incremental syncs are faster than full syncs
4. **Popular Years**: Pre-sync 1977, 1972, 1989, 1974 for best user experience
5. **Database Cleanup**: Run monthly to maintain performance

---

## Security Notes

- Only administrators can access the dashboard
- All AJAX requests are nonce-protected
- Destructive operations require double confirmation
- API keys are not stored in the database (Archive.org is public)

---

## Quick Reference

### Dashboard Location
`WordPress Admin → GD Chatbot v2 → Music Streaming`

### Settings Location
`WordPress Admin → GD Chatbot v2 → Settings → Music Streaming tab`

### Key Files
- Songs CSV: `plugin/context/reference/songs.csv`
- Setlist CSVs: `plugin/context/setlists/*.csv`

### Database Tables
- `wp_gd_show_recordings`: Archive.org performance metadata
- `wp_gd_song_recordings`: Individual song tracks
- `wp_gd_user_show_favorites`: User favorites (future)
- `wp_gd_archive_sync_log`: Sync operation history

### AJAX Endpoints (for developers)
- `gd_chatbot_trigger_sync`: Run sync operations
- `gd_chatbot_archive_search`: Search Archive.org
- `gd_chatbot_get_recordings`: Get recordings from DB
- `gd_chatbot_get_stream_url`: Get MP3 stream URL
- `gd_chatbot_clear_archive_cache`: Clear Archive.org cache
- `gd_chatbot_cleanup_database`: Clean orphaned records
- `gd_chatbot_test_detection`: Test song detection
- `gd_chatbot_clear_all_data`: Delete all streaming data
- `gd_chatbot_reset_tables`: Drop and recreate tables
- `gd_chatbot_clear_song_cache`: Clear song detection cache

---

## Getting Help

### Documentation
- Full requirements: `docs/music-streaming-requirements.md`
- Phase 1 details: `docs/implementation/HYBRID-APPROACH-IMPLEMENTATION.md`
- Phase 2 details: `docs/implementation/PHASE-2-COMPLETE.md`
- Phase 3 details: `docs/implementation/PHASE-3-COMPLETE.md`

### Common Questions

**Q: How often should I run syncs?**  
A: Incremental sync weekly is sufficient for most sites. Daily if high traffic.

**Q: Can I sync while users are using the chatbot?**  
A: Yes, syncs run in the background and don't affect existing data.

**Q: What if a sync times out?**  
A: Use WP-CLI for large syncs: `wp gd-chatbot sync --type=full`

**Q: How much database space does this use?**  
A: Approximately 50-100 MB for full sync (all years, all shows).

**Q: Can I customize which songs are detected?**  
A: Yes, edit `plugin/context/reference/songs.csv` and clear song cache.

---

## Next Steps

After mastering the basics:

1. **Explore Analytics**: Check which songs are most popular
2. **User Favorites**: Monitor user favorite counts (when implemented)
3. **Custom Syncs**: Create scheduled syncs via WP-Cron
4. **Advanced Testing**: Use song detection testing for quality assurance

---

**Last Updated:** February 11, 2026  
**Plugin Version:** 2.1.0  
**Phase:** 3 (Admin Dashboard)
