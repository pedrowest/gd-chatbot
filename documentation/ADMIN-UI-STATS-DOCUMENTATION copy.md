# ScubaGPT - Admin UI and Statistics Dashboard Documentation

## Overview

Version 1.1.0 adds comprehensive admin UI and statistics dashboard for AI Power integration, providing site administrators with powerful tools to configure, monitor, and optimize the ScubaGPT chatbot's use of WordPress content.

**Added**: January 2026  
**Version**: ScubaGPT 1.1.0

---

## Table of Contents

1. [AI Power Settings Page](#ai-power-settings-page)
2. [Statistics Dashboard](#statistics-dashboard)
3. [WordPress Dashboard Widget](#wordpress-dashboard-widget)
4. [Statistics Tracking](#statistics-tracking)
5. [Caching System](#caching-system)
6. [AJAX Endpoints](#ajax-endpoints)
7. [Visualizations](#visualizations)
8. [User Guide](#user-guide)
9. [Technical Details](#technical-details)

---

## AI Power Settings Page

### Location
**WordPress Admin > ScubaGPT > AI Power**

### Features

#### 1. Status Box
- **Active/Inactive Status**: Visual indicator showing if AI Power integration is working
- **Real-time Stats**: Total vectors, indexed posts, index name, namespace
- **Quick Actions**: Links to get AI Power plugin or view statistics

#### 2. Enable/Disable Toggle
- **Modern Toggle Switch**: Easy one-click enable/disable
- **Auto-disabled**: If AI Power not configured
- **Visual Feedback**: Clear indication of current state

#### 3. Max Results Slider
- **Range**: 1-50 results
- **Default**: 10
- **Live Value Display**: Shows current value as you slide
- **Description**: Maximum number of results to retrieve from AI Power

#### 4. Minimum Relevance Score Slider
- **Range**: 0-100%
- **Default**: 35%
- **Live Percentage Display**: Shows percentage as you slide
- **Description**: Only include results above this relevance threshold

#### 5. Post Types Selection
- **Checkboxes**: Select which post types to include
- **Auto-detected**: All public post types shown
- **Common Types**: Posts, Pages, Custom Post Types
- **Default**: Posts and Pages selected

#### 6. Post Status Selection
- **Checkboxes**: Select which post statuses to include
- **Options**: Published, Draft, Pending, Private, etc.
- **Default**: Published only
- **Warning**: Typically only "Published" should be checked

#### 7. Indexed Content Preview
- **Table View**: Shows recent posts with index status
- **Columns**:
  - ID
  - Title (clickable to edit)
  - Type
  - Status
  - Indexed (Yes/No with icon)
- **Scrollable**: Max height 400px
- **Real-time**: Shows current index status from AI Power

#### 8. Test Connection Button
- **One-click Test**: Verifies AI Power connectivity
- **Results Display**: Shows vector count and indexed posts
- **Error Handling**: Clear error messages if issues found

#### 9. Quick Tips Box
- **Help Content**: Contextual tips for optimization
- **Links**: Direct links to statistics page
- **Best Practices**: Suggestions for improving results

---

## Statistics Dashboard

### Location
**WordPress Admin > ScubaGPT > AI Power Stats**

### Features

#### 1. Quick Stats Cards (Top Row)

**Total Queries Card**
- Large number display
- Chart icon
- Shows all-time total queries

**AI Power Used Card**
- Large number display
- Checkmark icon
- Shows queries that used AI Power

**Average Relevance Card**
- Percentage display
- Star icon
- Shows average relevance score

**Indexed Posts Card**
- Number display
- Document icon
- Shows total indexed posts

#### 2. Queries Over Time Chart (Line Chart)
- **Time Range**: Last 30 days
- **Two Lines**:
  - Total Queries (teal)
  - AI Power Used (blue)
- **Interactive**: Hover for exact values
- **Responsive**: Adapts to screen size

#### 3. Source Usage Chart (Doughnut Chart)
- **Three Segments**:
  - AI Power (blue)
  - Pinecone Direct (yellow)
  - Tavily (teal)
- **Interactive**: Hover for percentages
- **Legend**: Right-side legend with counts

#### 4. Most Queried Content Table
- **Top 10**: Most frequently retrieved content
- **Columns**:
  - Rank
  - Content title (clickable to edit)
  - Type
  - Query count
  - Average score
- **Sortable**: Click headers to sort
- **Searchable**: Built-in search box

#### 5. Recent Queries Table
- **Last 20**: Most recent queries using AI Power
- **Columns**:
  - Query text (truncated)
  - Results count
  - Average score
  - Sources used
  - Time ago
- **Sortable**: Click headers to sort
- **Searchable**: Filter queries

#### 6. Performance Metrics
- **Average Response Time**: In milliseconds
- **Average Tokens Used**: Per query
- **Success Rate**: Percentage of successful queries
- **Total Vectors**: In Pinecone index

#### 7. Refresh Button
- **Location**: Page title area
- **Icon**: Refresh icon
- **Function**: Clears cache and reloads fresh data
- **Indicator**: Shows when using cached data

#### 8. Cache Notice
- **Shows**: When displaying cached data (15-minute cache)
- **Link**: Quick link to refresh manually
- **Auto-hide**: Disappears after refresh

---

## WordPress Dashboard Widget

### Location
**WordPress Admin > Dashboard** (main dashboard)

### Widget Name
**ScubaGPT - AI Power Statistics**

### Features

#### 1. Status Alert (if AI Power not active)
- Yellow warning box
- Clear message
- Actionable information

#### 2. Quick Stats Grid (2x2)
- **Queries Today**: Large number, blue background
- **AI Power Used**: Large number, blue background
- Centered, easy to read
- Auto-updating

#### 3. Last 7 Days Progress Bar
- Shows query count for past week
- Visual progress indicator
- Number display
- Blue progress fill

#### 4. Integration Status Box (if active)
- Green background
- Checkmark icon
- Indexed posts count
- Average relevance percentage

#### 5. Action Buttons
- **View Full Statistics** (Primary button)
- **Settings** (Secondary button)
- Centered layout
- Direct links to full pages

### Auto-refresh
- Stats cached for 15 minutes
- Fresh on page load
- Lightweight queries

---

## Statistics Tracking

### Database Table
**Table Name**: `wp_scubagpt_query_stats`

### Schema

```sql
CREATE TABLE wp_scubagpt_query_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query TEXT NOT NULL,
    query_hash VARCHAR(64) NOT NULL,
    used_aipower TINYINT(1) DEFAULT 0,
    used_pinecone TINYINT(1) DEFAULT 0,
    used_tavily TINYINT(1) DEFAULT 0,
    aipower_results INT DEFAULT 0,
    aipower_avg_score DECIMAL(5,4) DEFAULT 0,
    pinecone_results INT DEFAULT 0,
    tavily_results INT DEFAULT 0,
    aipower_top_post_id INT DEFAULT NULL,
    response_time_ms INT DEFAULT 0,
    tokens_used INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_query_hash (query_hash),
    INDEX idx_created (created_at),
    INDEX idx_aipower (used_aipower),
    INDEX idx_post (aipower_top_post_id)
);
```

### Tracked Data

**Per Query**:
- Full query text
- Query hash (for deduplication)
- Which sources were used (AI Power, Pinecone, Tavily)
- Number of results from each source
- Average relevance score from AI Power
- Top matching post ID
- Response time in milliseconds
- Total tokens used
- Timestamp

### Tracking Method

Automatic tracking in `ScubaGPT_Chat::process_message()`:
1. Query starts, timestamp recorded
2. Results collected from each source
3. Response generated
4. Statistics calculated and logged
5. No impact on user experience

---

## Caching System

### Cache Keys

**Dashboard Stats**: `scubagpt_dashboard_stats`
- Duration: 15 minutes
- Scope: Per user
- Contains: Quick stats for dashboard widget

**Full Stats**: `scubagpt_aipower_stats_{user_id}`
- Duration: 15 minutes
- Scope: Per user
- Contains: Complete statistics for dashboard page

### Cache Strategy

**When Cached**:
- On first page load
- After manual refresh
- On dashboard widget display

**When Cleared**:
- Manual refresh button click
- 15 minutes expiration
- Settings changes
- AJAX quick stats request

### Benefits
- Fast page loads
- Reduced database queries
- Better server performance
- Real-time option available

### Manual Refresh
- Click "Refresh" button
- Adds `?refresh=1` to URL
- Bypasses cache
- Fetches fresh data

---

## AJAX Endpoints

### 1. Test AI Power Connection

**Action**: `scubagpt_test_aipower`  
**Method**: POST  
**Permission**: `manage_options`

**Request**:
```javascript
{
    action: 'scubagpt_test_aipower',
    nonce: scubagptAdmin.nonce
}
```

**Success Response**:
```javascript
{
    success: true,
    data: {
        message: "Connection successful! Found 1250 vectors and 45 indexed posts.",
        stats: {
            total_vectors: 1250,
            indexed_posts: 45,
            index_name: "scuba-content",
            namespace: ""
        }
    }
}
```

**Error Response**:
```javascript
{
    success: false,
    data: "AI Power integration is not available..."
}
```

### 2. Get Quick Stats

**Action**: `scubagpt_get_quick_stats`  
**Method**: POST  
**Permission**: `manage_options`

**Request**:
```javascript
{
    action: 'scubagpt_get_quick_stats',
    nonce: scubagptAdmin.nonce
}
```

**Success Response**:
```javascript
{
    success: true,
    data: {
        queries_today: 45,
        aipower_today: 32,
        queries_week: 312,
        avg_relevance: 0.78,
        indexed_posts: 45
    }
}
```

### Usage in JavaScript

```javascript
// Test connection
$('#test-aipower-connection').on('click', function() {
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'scubagpt_test_aipower',
            nonce: scubagptAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
            }
        }
    });
});

// Get quick stats
$.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'scubagpt_get_quick_stats',
        nonce: scubagptAdmin.nonce
    },
    success: function(response) {
        if (response.success) {
            updateDashboard(response.data);
        }
    }
});
```

---

## Visualizations

### Chart.js Integration

**Library**: Chart.js 4.4.1  
**CDN**: https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js  
**License**: MIT

### Chart Types Used

#### 1. Line Chart (Queries Over Time)
```javascript
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan 1', 'Jan 2', ...],
        datasets: [
            {
                label: 'Total Queries',
                data: [10, 15, 12, ...],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            },
            {
                label: 'AI Power Used',
                data: [8, 12, 9, ...],
                borderColor: 'rgb(54, 162, 235)',
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
```

#### 2. Doughnut Chart (Source Usage)
```javascript
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['AI Power', 'Pinecone Direct', 'Tavily'],
        datasets: [{
            data: [245, 89, 156],
            backgroundColor: [
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});
```

### Responsive Design
- Charts adapt to container size
- Mobile-friendly breakpoints
- Touch-friendly interactions
- Window resize handler

---

## User Guide

### First Time Setup

1. **Install AI Power Plugin**
   - Go to Plugins > Add New
   - Search for "AI Power"
   - Install and activate

2. **Configure Pinecone**
   - AI Power > Settings
   - Add Pinecone API key
   - Add Pinecone host URL
   - Save settings

3. **Index Content**
   - AI Power > Content > Index
   - Select diving posts/pages
   - Click "Index to Vector Database"
   - Wait for completion

4. **Configure ScubaGPT AI Power**
   - ScubaGPT > AI Power
   - Verify integration is active
   - Adjust max results (default: 10)
   - Adjust min score (default: 35%)
   - Select post types
   - Save settings

5. **Test Connection**
   - Click "Test Connection" button
   - Verify success message
   - Check vector count

6. **View Statistics**
   - ScubaGPT > AI Power Stats
   - Monitor query performance
   - Analyze content usage
   - Optimize settings

### Daily Monitoring

**Dashboard Widget** (Quick Check):
- Check queries today
- Verify AI Power usage
- Monitor trends

**Statistics Page** (Detailed Analysis):
- Review query charts
- Check top content
- Analyze response times
- Identify issues

### Optimization Tips

**Increase Relevance**:
- Index more diving content
- Use descriptive post titles
- Add relevant keywords
- Upload reference files

**Improve Performance**:
- Lower max results if too slow
- Raise min score for quality
- Monitor response times
- Check token usage

**Content Strategy**:
- Review top queried content
- Create content for gaps
- Update popular posts
- Archive outdated content

---

## Technical Details

### File Structure

```
scubagpt-chatbot/
├── includes/
│   ├── class-scubagpt-admin.php (updated)
│   │   ├── render_aipower_page()
│   │   ├── render_aipower_stats_page()
│   │   ├── render_dashboard_widget()
│   │   ├── fetch_aipower_statistics()
│   │   ├── ajax_test_aipower()
│   │   └── ajax_get_quick_stats()
│   ├── class-scubagpt-chat.php (updated)
│   │   └── log_query_stats()
│   └── ...
├── assets/
│   ├── css/
│   │   ├── admin-aipower.css (new)
│   │   └── ...
│   └── js/
│       ├── admin-aipower.js (new)
│       └── ...
└── scubagpt-chatbot.php (updated)
    └── enqueue_admin_assets() (updated)
```

### CSS Classes

**Settings Page**:
- `.scubagpt-aipower-settings` - Main container
- `.scubagpt-status-box` - Status indicator box
- `.scubagpt-toggle` - Toggle switch
- `.scubagpt-range` - Range slider
- `.scubagpt-indexed-preview` - Content preview table
- `.scubagpt-help-box` - Tips box

**Statistics Page**:
- `.scubagpt-aipower-stats` - Main container
- `.scubagpt-stats-cards` - Quick stats grid
- `.scubagpt-stat-card` - Individual stat card
- `.scubagpt-chart-container` - Chart wrapper
- `.scubagpt-top-content` - Top content table
- `.scubagpt-recent-queries` - Recent queries table

**Dashboard Widget**:
- `.scubagpt-dashboard-widget` - Widget container
- `.stat-grid` - Stats grid
- `.stat-box` - Individual stat
- `.progress-bar` - Progress indicator

### JavaScript Events

**Page Load**:
- Range slider initialization
- Chart rendering
- Table initialization
- Animation triggers

**User Interactions**:
- Test connection click
- Settings change
- Refresh button click
- Query row expand/collapse

**Real-time Updates** (optional):
- Auto-refresh every 30 seconds
- AJAX stat updates
- Visual indicators

### Performance Considerations

**Database Queries**:
- Optimized with indexes
- Cached for 15 minutes
- Efficient JOINs
- Limited result sets

**Frontend**:
- Lazy-loaded charts
- Debounced interactions
- Cached AJAX responses
- Minimal DOM manipulation

**Charts**:
- CDN-hosted Chart.js
- Only loaded on stats page
- Responsive rendering
- Memory-efficient

---

## Troubleshooting

### Statistics Not Showing

**Check**:
1. AI Power plugin installed and active?
2. Pinecone configured in AI Power?
3. Content indexed?
4. Queries have been made?

**Solution**:
- Test connection on AI Power settings page
- Verify database table exists
- Check for JavaScript errors in console
- Refresh statistics manually

### Charts Not Rendering

**Check**:
1. Chart.js loading? (Check browser console)
2. Canvas elements present?
3. JavaScript errors?

**Solution**:
- Clear browser cache
- Check internet connection (CDN)
- Verify no JS conflicts
- Try different browser

### Dashboard Widget Missing

**Check**:
1. User has admin permissions?
2. Dashboard screen options enabled?

**Solution**:
- Check Screen Options at top of dashboard
- Ensure "ScubaGPT - AI Power Statistics" is checked
- Refresh page

### Slow Statistics Page

**Symptoms**:
- Long page load time
- Spinning indicators
- Timeout errors

**Solutions**:
- Statistics are cached (15 min)
- Reduce date range
- Archive old data
- Optimize database
- Increase PHP memory limit

---

## Best Practices

### Regular Monitoring
- Check dashboard widget daily
- Review full stats weekly
- Analyze trends monthly
- Archive old data quarterly

### Content Management
- Index new content regularly
- Update high-traffic posts
- Remove outdated content
- Upload reference materials

### Performance Optimization
- Monitor response times
- Adjust result limits
- Optimize relevance scores
- Balance quality vs speed

### Data Privacy
- Statistics don't include user data
- Queries are anonymized
- No PII stored
- GDPR compliant

---

## API Reference

### Functions

#### `fetch_aipower_statistics()`
Returns complete statistics array

**Returns**:
```php
[
    'total_queries' => int,
    'aipower_queries' => int,
    'pinecone_queries' => int,
    'tavily_queries' => int,
    'avg_relevance' => float,
    'avg_response_time' => int,
    'avg_tokens' => int,
    'success_rate' => float,
    'indexed_posts' => int,
    'total_vectors' => int,
    'daily_queries' => array,
    'top_content' => array,
    'recent_queries' => array,
]
```

#### `fetch_dashboard_quick_stats()`
Returns dashboard widget stats

**Returns**:
```php
[
    'queries_today' => int,
    'aipower_today' => int,
    'queries_week' => int,
    'avg_relevance' => float,
    'indexed_posts' => int,
]
```

#### `log_query_stats($query, $aipower_results, $pinecone_results, $tavily_results, $response_time, $usage)`
Logs query statistics to database

**Parameters**:
- `$query` (string): User query text
- `$aipower_results` (array|null): AI Power results
- `$pinecone_results` (array|WP_Error): Pinecone results
- `$tavily_results` (array|WP_Error): Tavily results
- `$response_time` (float): Response time in milliseconds
- `$usage` (array): Token usage data

---

## Changelog

### Version 1.1.0 (January 2026)

**Added**:
- ✅ AI Power settings page with full UI controls
- ✅ Statistics dashboard with charts and metrics
- ✅ WordPress dashboard widget
- ✅ Database statistics tracking
- ✅ Chart.js visualizations
- ✅ AJAX endpoints for real-time updates
- ✅ 15-minute caching system
- ✅ Indexed content preview
- ✅ Test connection functionality
- ✅ Quick tips and help content

**Technical**:
- New CSS file: `admin-aipower.css`
- New JS file: `admin-aipower.js`
- Updated: `class-scubagpt-admin.php`
- Updated: `class-scubagpt-chat.php`
- Updated: `scubagpt-chatbot.php`
- Database table already existed from v1.0

---

## Future Enhancements

**Planned for v1.2**:
- Export statistics to CSV/PDF
- Email digest reports
- Custom date range selection
- A/B testing for settings
- Content recommendations
- Automated optimization suggestions
- Multi-site network support
- REST API endpoints

**Under Consideration**:
- Machine learning insights
- Predictive analytics
- Custom chart types
- Advanced filtering
- Query categorization
- User segmentation
- Cost tracking
- Performance alerts

---

## Support

### Getting Help
- Check troubleshooting section above
- Review WordPress debug logs
- Test AI Power independently
- Verify database tables exist

### Reporting Issues
Include:
- WordPress version
- PHP version
- AI Power version
- ScubaGPT version
- Browser and OS
- Error messages
- Screenshots if relevant

---

**Documentation Version**: 1.0  
**Last Updated**: January 2026  
**Plugin Version**: ScubaGPT 1.1.0  
**Author**: IT Influentials
