# Hybrid Approach Implementation - Complete

**Date**: February 12, 2026  
**Status**: ✅ Phase 1 Complete - Database Schema & Archive.org Sync  
**Next**: Phase 2 - Frontend Integration

---

## What Was Implemented

### ✅ 1. Database Schema (`class-streaming-database.php`)

Created 4 new database tables for the hybrid approach:

#### `wp_gd_show_recordings`
Stores Archive.org performance metadata linked to show dates from CSV files.

**Columns**:
- `archive_identifier` - Unique Archive.org ID
- `show_date` - Date (links to CSV setlist data)
- `venue_name`, `venue_location`, `venue_city`, `venue_state`
- `downloads`, `avg_rating`, `num_reviews` - Popularity metrics
- `thumbnail_url`, `stream_url_mp3`, `archive_url`
- `metadata_json` - Full Archive.org metadata
- `last_synced` - Track when data was updated

**Indexes**:
- Primary key on `id`
- Unique key on `archive_identifier`
- Indexes on `show_date`, `downloads`, `avg_rating`
- Full-text index on venue fields

#### `wp_gd_song_recordings`
Individual song tracks within performances for clickable song links.

**Columns**:
- `recording_id` - Foreign key to show recordings
- `song_title`, `song_slug`
- `track_number`, `set_number`, `position_in_set`
- `duration_seconds`
- `stream_url` - Direct MP3 link for this track
- `file_format`

**Indexes**:
- Primary key on `id`
- Foreign key on `recording_id`
- Indexes on `song_title`, `song_slug`

#### `wp_gd_user_show_favorites`
User favorites for future features (playlists, ratings).

**Columns**:
- `user_id` - WordPress user ID
- `show_date` - Date of favorited show
- `recording_id` - Specific recording (if multiple exist)
- `notes` - User notes
- `added_at`

**Indexes**:
- Unique key on `(user_id, show_date)`
- Indexes on `user_id`, `show_date`

#### `wp_gd_archive_sync_log`
Tracks sync progress and errors for debugging.

**Columns**:
- `sync_type` - incremental, full, year, date
- `year`, `song_title` - What was synced
- `records_found`, `records_added`, `records_updated`
- `status` - pending, running, completed, failed
- `error_message`
- `started_at`, `completed_at`

---

### ✅ 2. Archive.org API Integration (`class-archive-api.php`)

Complete API wrapper for Internet Archive searches.

**Key Methods**:

```php
// Search performances
$archive_api->search_performances(array(
    'song_title' => 'Dark Star',
    'date' => '1977-05-08',
    'year' => 1977,
    'venue' => 'Cornell',
    'sort_by' => 'downloads', // or 'date', 'rating'
    'limit' => 50
));

// Get metadata for specific show
$archive_api->get_metadata('gd1977-05-08.sbd.hicks.4982.sbeok.shnf');

// Get streaming URL
$archive_api->get_streaming_url('identifier', 'mp3');

// Get all MP3 files (for setlist parsing)
$archive_api->get_mp3_files('identifier');

// Convenience methods
$archive_api->search_by_date('1977-05-08');
$archive_api->search_by_year(1977);
$archive_api->search_by_song('Dark Star');
```

**Features**:
- Automatic caching (24 hours for searches, 7 days for metadata)
- Error handling with WP_Error
- Parses Archive.org metadata into clean format
- Extracts date, venue, location from various fields
- Finds best streaming file (VBR MP3 preferred)

**Cache Management**:
```php
$archive_api->clear_cache(); // Clear all caches
$archive_api->get_cache_stats(); // Get cache statistics
```

---

### ✅ 3. Archive.org Sync Service (`class-archive-sync.php`)

Background service to populate database from Archive.org.

**Sync Types**:

1. **Incremental Sync** (default, runs daily via WP-Cron):
   - Syncs shows not updated in 30 days
   - Syncs popular shows (>10k downloads) not updated in 7 days
   - If no existing data, syncs popular years (1977, 1972, 1973, etc.)

2. **Full Sync**:
   - Syncs all years (1965-1995)
   - ~2,340 shows
   - Takes ~2 hours (with rate limiting)

3. **Year Sync**:
   - Sync specific year (e.g., 1977)
   - Up to 200 shows per year

4. **Date Sync**:
   - Sync specific date (e.g., 1977-05-08)
   - Multiple recordings per date possible

**Key Methods**:

```php
$archive_sync = new GD_Archive_Sync();

// Run incremental sync (automatic via cron)
$results = $archive_sync->run_sync();

// Sync specific year
$results = $archive_sync->run_sync(array(
    'sync_type' => 'year',
    'year' => 1977,
    'force' => true
));

// Sync specific date
$results = $archive_sync->run_sync(array(
    'sync_type' => 'date',
    'date' => '1977-05-08',
    'force' => true
));

// Get sync status
$status = $archive_sync->get_sync_status();
```

**Features**:
- WP-Cron scheduled daily sync
- Rate limiting (sleeps between requests)
- Logs all sync operations to database
- Handles errors gracefully
- Syncs both show metadata AND individual song tracks
- Parses song titles from MP3 filenames
- Updates existing records if forced

---

### ✅ 4. Plugin Integration

**Modified Files**:
- `plugin/gd-chatbot.php` - Added new class loading, activation hooks, AJAX endpoints

**Changes Made**:

1. **Load Dependencies**:
```php
require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-streaming-database.php';
require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-archive-api.php';
require_once GD_CHATBOT_PLUGIN_DIR . 'includes/class-archive-sync.php';
```

2. **Activation Hook**:
```php
public function activate() {
    // ... existing code ...
    
    // Create streaming database tables
    $streaming_db = new GD_Streaming_Database();
    $streaming_db->maybe_create_tables();
    
    // Trigger activation hook for other components
    do_action('gd_chatbot_activate');
}
```

3. **Initialize Components**:
```php
public function init_components() {
    // ... existing code ...
    
    // Initialize streaming database and sync service
    new GD_Streaming_Database();
    new GD_Archive_Sync();
}
```

4. **AJAX Endpoints**:
```php
// Archive.org search (public)
add_action('wp_ajax_gd_chatbot_archive_search', array($this, 'handle_archive_search'));
add_action('wp_ajax_nopriv_gd_chatbot_archive_search', array($this, 'handle_archive_search'));

// Get recordings from database (public)
add_action('wp_ajax_gd_chatbot_get_recordings', array($this, 'handle_get_recordings'));
add_action('wp_ajax_nopriv_gd_chatbot_get_recordings', array($this, 'handle_get_recordings'));

// Trigger manual sync (admin only)
add_action('wp_ajax_gd_chatbot_trigger_sync', array($this, 'handle_trigger_sync'));
```

---

## How It Works (Hybrid Approach)

### Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│  Step 1: User Asks About Show                                │
│  "Tell me about the Cornell '77 show"                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  Step 2: CSV Setlist Search (Existing)                      │
│  class-setlist-search.php finds show in 1977.csv            │
│  Returns: Date, Venue, Location, Setlist                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  Step 3: Database Lookup (New)                              │
│  Query wp_gd_show_recordings WHERE show_date = '1977-05-08' │
│  Returns: Archive.org metadata (downloads, rating, URLs)    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  Step 4: Chatbot Response                                    │
│  Combines CSV setlist + Archive.org metadata                │
│  Song titles are clickable (links to recordings)            │
└─────────────────────────────────────────────────────────────┘
```

### Song Link Flow

```
┌─────────────────────────────────────────────────────────────┐
│  User Clicks "Dark Star" in Chatbot Response                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  Frontend: AJAX Request                                      │
│  POST /wp-admin/admin-ajax.php                              │
│  action: gd_chatbot_get_recordings                          │
│  song_title: "Dark Star"                                    │
│  sort_by: "downloads"                                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  Backend: Query Database                                     │
│  SELECT r.* FROM wp_gd_show_recordings r                    │
│  JOIN wp_gd_song_recordings s ON r.id = s.recording_id      │
│  WHERE s.song_title LIKE '%Dark Star%'                      │
│  ORDER BY r.downloads DESC LIMIT 50                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│  Frontend: Display Modal                                     │
│  - List of 50 performances (sorted by popularity)           │
│  - Each with: Date, Venue, Downloads, Rating                │
│  - Click "Play" → Stream MP3 from Archive.org              │
└─────────────────────────────────────────────────────────────┘
```

---

## Testing the Implementation

### 1. Activate Plugin

When you activate the plugin, it will:
1. Create the 4 new database tables
2. Schedule daily WP-Cron sync
3. Initialize Archive.org sync service

**Verify Tables Created**:
```sql
SHOW TABLES LIKE 'wp_gd_%';

-- Should show:
-- wp_gd_show_recordings
-- wp_gd_song_recordings
-- wp_gd_user_show_favorites
-- wp_gd_archive_sync_log
```

### 2. Trigger Initial Sync

**Option A: Via WP-CLI** (recommended):
```bash
wp eval 'do_action("gd_chatbot_archive_sync");'
```

**Option B: Via PHP**:
```php
$archive_sync = new GD_Archive_Sync();
$results = $archive_sync->run_sync(array(
    'sync_type' => 'incremental'
));
print_r($results);
```

**Option C: Via AJAX** (admin panel, coming in Phase 2):
```javascript
fetch(ajaxurl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'gd_chatbot_trigger_sync',
        nonce: gdChatbot.nonce,
        sync_type: 'incremental'
    })
});
```

### 3. Verify Sync Results

**Check sync log**:
```sql
SELECT * FROM wp_gd_archive_sync_log ORDER BY started_at DESC LIMIT 10;
```

**Check recordings**:
```sql
SELECT COUNT(*) as total_shows FROM wp_gd_show_recordings;
SELECT COUNT(*) as total_songs FROM wp_gd_song_recordings;

-- Get most popular shows
SELECT show_date, venue_name, downloads, avg_rating 
FROM wp_gd_show_recordings 
ORDER BY downloads DESC 
LIMIT 10;
```

### 4. Test Archive.org Search

**Direct API Test**:
```php
$archive_api = new GD_Archive_API();

// Search for Dark Star performances
$results = $archive_api->search_by_song('Dark Star', 'downloads', 10);
print_r($results);

// Search for Cornell '77
$results = $archive_api->search_by_date('1977-05-08');
print_r($results);
```

**AJAX Test** (from browser console):
```javascript
fetch(ajaxurl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
        action: 'gd_chatbot_archive_search',
        nonce: gdChatbot.nonce,
        song_title: 'Dark Star',
        sort_by: 'downloads'
    })
})
.then(r => r.json())
.then(data => console.log(data));
```

### 5. Test Database Query

**Test get_recordings endpoint**:
```javascript
fetch(ajaxurl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
        action: 'gd_chatbot_get_recordings',
        nonce: gdChatbot.nonce,
        song_title: 'Dark Star',
        sort_by: 'downloads'
    })
})
.then(r => r.json())
.then(data => console.log(data));
```

---

## Performance Benchmarks

### Expected Performance

| Operation | Time | Notes |
|-----------|------|-------|
| **CSV Setlist Search** | 20-50ms | Unchanged (existing functionality) |
| **Database Recording Lookup** | 5-15ms | With proper indexes |
| **Archive.org API Search** | 1-3s | First request (uncached) |
| **Archive.org API Search** | 50-100ms | Cached (24 hours) |
| **Full Sync (1965-1995)** | 2-3 hours | ~2,340 shows with rate limiting |
| **Year Sync (e.g., 1977)** | 3-5 minutes | ~60 shows |
| **Date Sync** | 10-20s | 1-5 recordings per date |

### Cache Strategy

**Archive.org API Caches** (WordPress transients):
- Search results: 24 hours
- Metadata: 7 days
- Streaming URLs: 1 hour

**Database Caches**:
- No caching needed (database queries are fast)
- Indexes provide sub-10ms query times

---

## What's Next: Phase 2 - Frontend Integration

### Remaining Tasks

1. **Song Detection in Responses** (`class-song-detector.php`):
   - Parse chatbot responses for song titles
   - Match against songs.csv
   - Insert clickable `<span>` tags with data attributes

2. **Song Modal UI** (`public/js/song-modal.js`):
   - Modal component for displaying recordings
   - Performance list with sorting
   - Audio player integration
   - Source picker (Archive.org + streaming services)

3. **Admin Interface** (admin panel):
   - Sync status dashboard
   - Manual sync triggers
   - Database statistics
   - Cache management

4. **CSS Styling** (`public/css/song-modal.css`):
   - Modal design
   - Performance cards
   - Audio player controls
   - Responsive layout

### Estimated Timeline

- Song Detection: 4 hours
- Modal UI: 8 hours
- Admin Interface: 4 hours
- CSS/Polish: 4 hours
- **Total: ~20 hours (2-3 days)**

---

## Database Maintenance

### Routine Tasks

**Daily** (automatic via WP-Cron):
- Incremental sync runs
- Updates popular shows

**Weekly** (manual or scheduled):
- Clear old transient caches
- Verify database integrity

**Monthly** (manual):
- Full sync to catch new Archive.org uploads
- Cleanup orphaned records

### Maintenance Commands

```php
// Clear Archive.org caches
$archive_api = new GD_Archive_API();
$archive_api->clear_cache();

// Verify database integrity
$streaming_db = new GD_Streaming_Database();
$issues = $streaming_db->verify_integrity();
print_r($issues);

// Cleanup orphaned records
$results = $streaming_db->cleanup_orphaned_records();
print_r($results);

// Get table statistics
$stats = $streaming_db->get_table_stats();
print_r($stats);
```

---

## Troubleshooting

### Common Issues

**1. Tables Not Created**
```sql
-- Manually create tables
$streaming_db = new GD_Streaming_Database();
$streaming_db->maybe_create_tables();
```

**2. Sync Not Running**
```php
// Check if cron is scheduled
$next_run = wp_next_scheduled('gd_chatbot_archive_sync');
echo date('Y-m-d H:i:s', $next_run);

// Manually trigger sync
do_action('gd_chatbot_archive_sync');
```

**3. Archive.org API Errors**
- Check internet connection
- Verify Archive.org is accessible
- Check error logs: `wp_gd_archive_sync_log` table

**4. No Recordings Found**
- Run initial sync (incremental or full)
- Check sync log for errors
- Verify Archive.org API is returning results

---

## Summary

✅ **Phase 1 Complete**: Database schema and Archive.org sync functionality fully implemented

**What Works Now**:
- Database tables created on plugin activation
- Archive.org API integration (search, metadata, streaming URLs)
- Background sync service (WP-Cron scheduled)
- AJAX endpoints for frontend integration
- CSV setlist search unchanged (existing functionality preserved)

**What's Next**:
- Phase 2: Frontend integration (song detection, modal UI, audio player)
- Phase 3: Admin dashboard (sync management, statistics)
- Phase 4: Streaming service integration (Spotify, Apple Music, etc.)

**Key Achievement**: Zero disruption to existing setlist search functionality while adding powerful new music streaming capabilities via the hybrid approach! 🎸

---

**Files Created**:
1. `/plugin/includes/class-streaming-database.php` (267 lines)
2. `/plugin/includes/class-archive-api.php` (424 lines)
3. `/plugin/includes/class-archive-sync.php` (577 lines)

**Files Modified**:
1. `/plugin/gd-chatbot.php` (added class loading, activation hooks, AJAX endpoints)

**Total New Code**: ~1,400 lines of production-ready PHP

Ready to proceed to Phase 2! 🚀
