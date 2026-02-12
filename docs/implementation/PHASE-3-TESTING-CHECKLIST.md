# Phase 3: Admin Dashboard - Testing Checklist

**Version:** 2.1.0  
**Date:** February 11, 2026  
**Tester:** _________________  
**Environment:** _________________

---

## Pre-Testing Setup

- [ ] WordPress 5.0+ installed
- [ ] GD Chatbot v2.1.0 activated
- [ ] Admin account with `manage_options` capability
- [ ] Test user account without admin privileges
- [ ] Browser console open for error monitoring
- [ ] Network tab open for AJAX monitoring

---

## 1. Dashboard Access

### Menu Item
- [ ] "Music Streaming" menu item appears under "GD Chatbot v2"
- [ ] Menu item has music note icon
- [ ] Menu item is visible to admins only
- [ ] Menu item is hidden from non-admins
- [ ] Clicking menu item loads dashboard

### Dashboard Page
- [ ] Dashboard loads without errors
- [ ] Page title is "🎸 Music Streaming Dashboard"
- [ ] Version badge displays "v2.1.0"
- [ ] All sections render correctly
- [ ] No PHP warnings/errors
- [ ] No JavaScript console errors

---

## 2. Status Cards

### Database Status Card
- [ ] Card displays with blue border
- [ ] Shows "Total Recordings" count
- [ ] Shows "Song Recordings" count
- [ ] Shows "User Favorites" count
- [ ] Shows date range (if data exists)
- [ ] Numbers are formatted with commas
- [ ] Card updates after sync

### Sync Status Card
- [ ] Card displays with green border
- [ ] Shows last sync type (if exists)
- [ ] Shows sync status badge (completed/running/failed)
- [ ] Shows completion time ("X ago")
- [ ] Shows records added/updated
- [ ] Shows next scheduled sync (if configured)
- [ ] Status badge colors correctly (green=completed, blue=running, red=failed)

### Cache Status Card
- [ ] Card displays with blue border
- [ ] Shows search cache count
- [ ] Shows metadata cache count
- [ ] Shows total cache size (formatted)
- [ ] "Clear All Caches" button present
- [ ] Button triggers confirmation dialog
- [ ] Cache clears successfully
- [ ] Page reloads after clearing

### Health Status Card
- [ ] Card displays (green if healthy, orange if issues)
- [ ] Shows checkmark if no issues
- [ ] Shows warning icon if issues found
- [ ] Lists specific integrity issues
- [ ] "Clean Up Database" button appears if issues
- [ ] Cleanup removes orphaned records
- [ ] Page reloads after cleanup

---

## 3. Sync Management

### Incremental Sync
- [ ] "Incremental Sync" section displays
- [ ] Description text is clear
- [ ] "Run Incremental Sync" button present
- [ ] Clicking button shows progress bar
- [ ] Progress bar animates
- [ ] Progress text displays "Syncing..."
- [ ] Results display after completion
- [ ] Shows records found/added/updated
- [ ] Page reloads after 3 seconds
- [ ] Sync history updates

### Year Sync
- [ ] "Year Sync" section displays
- [ ] Year input field present (number type)
- [ ] Input has min=1965, max=1995
- [ ] Default value is 1977
- [ ] "Sync Year" button present
- [ ] Validation: Rejects year < 1965
- [ ] Validation: Rejects year > 1995
- [ ] Validation: Requires year input
- [ ] Valid year triggers sync
- [ ] Progress and results display correctly

### Date Sync
- [ ] "Date Sync" section displays
- [ ] Date picker input present
- [ ] "Sync Date" button present
- [ ] Validation: Requires date input
- [ ] Valid date triggers sync
- [ ] Progress and results display correctly

### Full Sync
- [ ] "Full Sync" section displays
- [ ] Section has warning styling (orange border)
- [ ] Warning text mentions "2-3 hours"
- [ ] "Run Full Sync" button present
- [ ] Clicking button shows confirmation dialog
- [ ] Confirmation mentions time estimate
- [ ] Canceling confirmation stops sync
- [ ] Confirming starts sync
- [ ] Progress and results display correctly

### Progress Indicators
- [ ] Progress bar appears when sync starts
- [ ] Progress bar has gradient animation
- [ ] Progress bar pulses
- [ ] Progress text updates
- [ ] Progress hides when complete
- [ ] Results section appears
- [ ] Results show accurate counts
- [ ] Error handling works (if sync fails)

---

## 4. Recent Sync History

### Table Display
- [ ] Table renders correctly
- [ ] Headers: Sync Type, Year/Song, Status, Found, Added, Updated, Started, Duration
- [ ] Shows last 10 sync operations
- [ ] Rows are striped (alternating colors)
- [ ] Data is accurate

### Table Data
- [ ] Sync type displays correctly (Incremental, Year, Date, Full)
- [ ] Year/song displays for applicable syncs
- [ ] Status badge displays with correct color
- [ ] Numbers are formatted with commas
- [ ] Dates are formatted (e.g., "Feb 11, 2026 3:45 PM")
- [ ] Duration is calculated correctly (minutes or seconds)
- [ ] Empty state message if no history

---

## 5. Song Detection Testing

### Test Interface
- [ ] "Song Detection Testing" section displays
- [ ] Textarea is present and functional
- [ ] Placeholder text is helpful
- [ ] "Test Detection" button present
- [ ] Button is styled correctly

### Testing Functionality
- [ ] Enter text: "They played Dark Star at Cornell"
- [ ] Click "Test Detection"
- [ ] Results section appears
- [ ] Shows "Songs detected: 1"
- [ ] Lists "Dark Star" with author
- [ ] Shows enriched HTML output
- [ ] Song link is clickable in preview
- [ ] Link has correct styling (blue, underlined, icon)

### Edge Cases
- [ ] Test with no songs: Shows "No songs detected"
- [ ] Test with multiple songs: Detects all
- [ ] Test with partial matches: Handles correctly
- [ ] Test with punctuation: Works correctly
- [ ] Test with case variations: Works correctly

---

## 6. Danger Zone

### Clear All Data
- [ ] "Danger Zone" section displays
- [ ] Section has red border
- [ ] "Clear All Data" option present
- [ ] Description mentions "cannot be undone"
- [ ] "Clear All Data" button present
- [ ] First confirmation dialog appears
- [ ] Second confirmation dialog appears
- [ ] Canceling stops operation
- [ ] Confirming deletes all data
- [ ] Success message displays
- [ ] Page reloads
- [ ] Database is empty after operation

### Reset Tables
- [ ] "Reset Tables" option present
- [ ] Description mentions "delete all data"
- [ ] "Reset Tables" button present
- [ ] First confirmation dialog appears
- [ ] Second confirmation dialog appears
- [ ] Canceling stops operation
- [ ] Confirming drops and recreates tables
- [ ] Success message displays
- [ ] Page reloads
- [ ] Tables are reset (empty with fresh schema)

---

## 7. Settings Tab

### Tab Access
- [ ] Go to `GD Chatbot v2 → Settings`
- [ ] "Music Streaming" tab appears in nav
- [ ] Tab has music note icon
- [ ] Clicking tab loads settings

### Settings Form
- [ ] Form renders correctly
- [ ] "Enable Music Streaming" checkbox present
- [ ] "Default Sort Order" dropdown present
- [ ] "Result Limit" number input present
- [ ] "Cache Duration" number input present
- [ ] "Autoplay Behavior" checkbox present
- [ ] All fields have descriptions
- [ ] "Save Streaming Settings" button present

### Settings Functionality
- [ ] Check "Enable Music Streaming"
- [ ] Select sort order: "Most Popular"
- [ ] Set result limit: 50
- [ ] Set cache duration: 24
- [ ] Check "Autoplay Behavior"
- [ ] Click "Save Streaming Settings"
- [ ] Success message displays
- [ ] Settings are saved
- [ ] Reload page: Settings persist

### Song Detection Info
- [ ] "Song Detection" section displays
- [ ] Shows total song count
- [ ] Shows path to songs.csv
- [ ] Shows cache status (Cached or Not cached)
- [ ] "Clear Song Cache" button present (if cached)
- [ ] Clicking button shows confirmation
- [ ] Cache clears successfully
- [ ] Page reloads

### Quick Links
- [ ] "View Streaming Dashboard" button present
- [ ] Button links to dashboard correctly
- [ ] "Archive.org Collection" button present
- [ ] Button opens Archive.org in new tab

---

## 8. AJAX Security

### Nonce Verification
- [ ] Open browser network tab
- [ ] Trigger any AJAX request
- [ ] Verify `nonce` parameter is sent
- [ ] Modify nonce value in request
- [ ] Request fails with error
- [ ] Error message mentions security

### Capability Checks
- [ ] Log out of admin account
- [ ] Log in as non-admin user
- [ ] Try to access dashboard directly (URL)
- [ ] Access is denied
- [ ] Try to trigger AJAX endpoint (console)
- [ ] Request fails with "Unauthorized"

### Input Sanitization
- [ ] Year sync: Enter "1977'; DROP TABLE wp_users; --"
- [ ] Verify SQL injection is prevented
- [ ] Date sync: Enter malicious date string
- [ ] Verify sanitization works
- [ ] Test detection: Enter `<script>alert('XSS')</script>`
- [ ] Verify script is escaped/removed

---

## 9. Performance

### Dashboard Load Time
- [ ] Clear browser cache
- [ ] Load dashboard
- [ ] Measure load time (< 500ms expected)
- [ ] Check number of database queries (< 10 expected)
- [ ] Verify no slow queries (> 100ms)

### AJAX Response Times
- [ ] Trigger incremental sync
- [ ] Measure response time (< 100ms for request initiation)
- [ ] Clear cache
- [ ] Measure response time (< 100ms)
- [ ] Test detection
- [ ] Measure response time (< 50ms)

### Memory Usage
- [ ] Monitor PHP memory usage
- [ ] Run full sync
- [ ] Verify memory doesn't exceed limits
- [ ] Check for memory leaks (run multiple syncs)

---

## 10. Browser Compatibility

### Chrome
- [ ] Dashboard loads correctly
- [ ] All features work
- [ ] No console errors
- [ ] Animations smooth

### Firefox
- [ ] Dashboard loads correctly
- [ ] All features work
- [ ] No console errors
- [ ] Animations smooth

### Safari
- [ ] Dashboard loads correctly
- [ ] All features work
- [ ] No console errors
- [ ] Animations smooth

### Edge
- [ ] Dashboard loads correctly
- [ ] All features work
- [ ] No console errors
- [ ] Animations smooth

### Mobile (Responsive)
- [ ] Dashboard is mobile-friendly
- [ ] Cards stack vertically
- [ ] Buttons are touch-friendly
- [ ] Text is readable
- [ ] No horizontal scrolling

---

## 11. Error Handling

### Network Errors
- [ ] Disable internet connection
- [ ] Try to run sync
- [ ] Verify error message displays
- [ ] Error is user-friendly

### Archive.org Unavailable
- [ ] Simulate Archive.org being down (modify API endpoint)
- [ ] Try to run sync
- [ ] Verify error is caught
- [ ] Error message is helpful

### Database Errors
- [ ] Simulate database error (temporarily rename table)
- [ ] Try to load dashboard
- [ ] Verify error is caught
- [ ] Error message is helpful

### PHP Errors
- [ ] Check PHP error log
- [ ] Verify no warnings/notices
- [ ] Verify no fatal errors

---

## 12. Integration Testing

### With Phase 1 (Database)
- [ ] Sync creates database records
- [ ] Records have correct schema
- [ ] Indexes are created
- [ ] Foreign keys work

### With Phase 2 (Frontend)
- [ ] Song links work after sync
- [ ] Modal displays synced performances
- [ ] Audio player works with synced URLs
- [ ] Cache is used correctly

### With Existing Features
- [ ] Chatbot still works
- [ ] Setlist search still works
- [ ] Settings page still works
- [ ] No conflicts with other plugins

---

## 13. Documentation

### Admin Quick Start
- [ ] `ADMIN-QUICK-START.md` is accurate
- [ ] Instructions are clear
- [ ] Examples work as described
- [ ] Troubleshooting tips are helpful

### Phase 3 Complete
- [ ] `PHASE-3-COMPLETE.md` is comprehensive
- [ ] Testing checklist is complete
- [ ] Technical details are accurate
- [ ] User flows are documented

### Status Report
- [ ] `MUSIC-STREAMING-STATUS.md` is updated
- [ ] Phase 3 is marked complete
- [ ] Progress percentage is correct
- [ ] Next steps are clear

---

## 14. Regression Testing

### Phase 1 Features
- [ ] Database tables still exist
- [ ] Sync functionality still works
- [ ] Archive.org API still works
- [ ] Caching still works

### Phase 2 Features
- [ ] Song detection still works
- [ ] Response enrichment still works
- [ ] Modal still opens
- [ ] Audio player still works

### Core Chatbot
- [ ] Chat interface still works
- [ ] Messages send/receive correctly
- [ ] Streaming responses work
- [ ] Context is maintained

---

## Test Results Summary

**Total Tests:** _____ / _____  
**Passed:** _____  
**Failed:** _____  
**Blocked:** _____

### Critical Issues
1. _________________
2. _________________
3. _________________

### Minor Issues
1. _________________
2. _________________
3. _________________

### Notes
_________________
_________________
_________________

---

## Sign-Off

**Tester Name:** _________________  
**Date:** _________________  
**Signature:** _________________

**Status:** [ ] Approved for Production [ ] Needs Fixes

---

**Testing Completed:** _________________  
**Ready for Phase 4:** [ ] Yes [ ] No
