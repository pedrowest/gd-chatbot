<?php
/**
 * Admin Streaming Dashboard
 * 
 * Displays sync status, database statistics, and management tools
 * 
 * @package GD_Chatbot
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get instances
$streaming_db = new GD_Streaming_Database();
$archive_sync = new GD_Archive_Sync();
$archive_api = new GD_Archive_API();

// Get statistics
$db_stats = $streaming_db->get_table_stats();
$sync_status = $archive_sync->get_sync_status();
$cache_stats = $archive_api->get_cache_stats();

// Check for integrity issues
$integrity_issues = $streaming_db->verify_integrity();

// Get recent sync logs
global $wpdb;
$recent_syncs = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}gd_archive_sync_log 
     ORDER BY started_at DESC 
     LIMIT 10",
    ARRAY_A
);

?>

<div class="wrap gd-streaming-dashboard">
    <h1>
        🎸 Music Streaming Dashboard
        <span class="gd-version-badge">v2.1.0</span>
    </h1>
    
    <p class="description">
        Manage Archive.org sync, view database statistics, and test song detection.
    </p>
    
    <!-- Status Cards -->
    <div class="gd-dashboard-cards">
        <!-- Database Status -->
        <div class="gd-card gd-card-primary">
            <div class="gd-card-icon">📊</div>
            <div class="gd-card-content">
                <h3>Database Status</h3>
                <div class="gd-stat-row">
                    <span class="gd-stat-label">Total Recordings:</span>
                    <span class="gd-stat-value"><?php echo number_format($db_stats['recordings']); ?></span>
                </div>
                <div class="gd-stat-row">
                    <span class="gd-stat-label">Song Recordings:</span>
                    <span class="gd-stat-value"><?php echo number_format($db_stats['song_recordings']); ?></span>
                </div>
                <div class="gd-stat-row">
                    <span class="gd-stat-label">User Favorites:</span>
                    <span class="gd-stat-value"><?php echo number_format($db_stats['favorites']); ?></span>
                </div>
                <?php if ($db_stats['earliest_show'] && $db_stats['latest_show']): ?>
                <div class="gd-stat-row">
                    <span class="gd-stat-label">Date Range:</span>
                    <span class="gd-stat-value">
                        <?php echo date('Y', strtotime($db_stats['earliest_show'])); ?> - 
                        <?php echo date('Y', strtotime($db_stats['latest_show'])); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sync Status -->
        <div class="gd-card gd-card-success">
            <div class="gd-card-icon">🔄</div>
            <div class="gd-card-content">
                <h3>Sync Status</h3>
                <?php if ($sync_status['last_sync']): ?>
                    <div class="gd-stat-row">
                        <span class="gd-stat-label">Last Sync:</span>
                        <span class="gd-stat-value">
                            <?php echo $sync_status['last_sync']->sync_type; ?>
                        </span>
                    </div>
                    <div class="gd-stat-row">
                        <span class="gd-stat-label">Status:</span>
                        <span class="gd-stat-badge gd-badge-<?php echo esc_attr($sync_status['last_sync']->status); ?>">
                            <?php echo esc_html(ucfirst($sync_status['last_sync']->status)); ?>
                        </span>
                    </div>
                    <div class="gd-stat-row">
                        <span class="gd-stat-label">Completed:</span>
                        <span class="gd-stat-value">
                            <?php echo human_time_diff(strtotime($sync_status['last_sync']->completed_at), current_time('timestamp')); ?> ago
                        </span>
                    </div>
                    <div class="gd-stat-row">
                        <span class="gd-stat-label">Records:</span>
                        <span class="gd-stat-value">
                            <?php echo $sync_status['last_sync']->records_added; ?> added, 
                            <?php echo $sync_status['last_sync']->records_updated; ?> updated
                        </span>
                    </div>
                <?php else: ?>
                    <p class="gd-no-data">No sync history yet. Run your first sync below.</p>
                <?php endif; ?>
                
                <?php if ($sync_status['next_scheduled']): ?>
                    <div class="gd-stat-row">
                        <span class="gd-stat-label">Next Scheduled:</span>
                        <span class="gd-stat-value">
                            <?php echo human_time_diff($sync_status['next_scheduled'], current_time('timestamp')); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Cache Status -->
        <div class="gd-card gd-card-info">
            <div class="gd-card-icon">💾</div>
            <div class="gd-card-content">
                <h3>Cache Status</h3>
                <div class="gd-stat-row">
                    <span class="gd-stat-label">Search Caches:</span>
                    <span class="gd-stat-value"><?php echo number_format($cache_stats['search_cache_count']); ?></span>
                </div>
                <div class="gd-stat-row">
                    <span class="gd-stat-label">Metadata Caches:</span>
                    <span class="gd-stat-value"><?php echo number_format($cache_stats['metadata_cache_count']); ?></span>
                </div>
                <div class="gd-stat-row">
                    <span class="gd-stat-label">Total Size:</span>
                    <span class="gd-stat-value"><?php echo size_format($cache_stats['total_cache_size']); ?></span>
                </div>
                <button type="button" class="button button-secondary gd-clear-cache" style="margin-top: 10px;">
                    Clear All Caches
                </button>
            </div>
        </div>
        
        <!-- Health Status -->
        <div class="gd-card <?php echo empty($integrity_issues) ? 'gd-card-success' : 'gd-card-warning'; ?>">
            <div class="gd-card-icon"><?php echo empty($integrity_issues) ? '✅' : '⚠️'; ?></div>
            <div class="gd-card-content">
                <h3>Database Health</h3>
                <?php if (empty($integrity_issues)): ?>
                    <p class="gd-success-message">✓ All checks passed</p>
                    <p class="gd-stat-value">No integrity issues found</p>
                <?php else: ?>
                    <p class="gd-warning-message">Issues detected:</p>
                    <ul class="gd-issue-list">
                        <?php foreach ($integrity_issues as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="button button-secondary gd-cleanup-db" style="margin-top: 10px;">
                        Clean Up Database
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sync Management -->
    <div class="gd-section">
        <h2>🔄 Sync Management</h2>
        
        <div class="gd-sync-controls">
            <div class="gd-sync-option">
                <h3>Incremental Sync</h3>
                <p>Sync recent shows and update popular performances (recommended for daily use).</p>
                <button type="button" class="button button-primary gd-trigger-sync" data-sync-type="incremental">
                    Run Incremental Sync
                </button>
            </div>
            
            <div class="gd-sync-option">
                <h3>Year Sync</h3>
                <p>Sync all shows from a specific year.</p>
                <div class="gd-sync-year-input">
                    <input type="number" id="sync-year" min="1965" max="1995" value="1977" placeholder="Year (1965-1995)" />
                    <button type="button" class="button button-primary gd-trigger-sync" data-sync-type="year">
                        Sync Year
                    </button>
                </div>
            </div>
            
            <div class="gd-sync-option">
                <h3>Date Sync</h3>
                <p>Sync all recordings from a specific date.</p>
                <div class="gd-sync-date-input">
                    <input type="date" id="sync-date" placeholder="YYYY-MM-DD" />
                    <button type="button" class="button button-primary gd-trigger-sync" data-sync-type="date">
                        Sync Date
                    </button>
                </div>
            </div>
            
            <div class="gd-sync-option gd-sync-warning">
                <h3>Full Sync</h3>
                <p><strong>Warning:</strong> This will sync all years (1965-1995) and may take 2-3 hours.</p>
                <button type="button" class="button button-secondary gd-trigger-sync" data-sync-type="full">
                    Run Full Sync (All Years)
                </button>
            </div>
        </div>
        
        <!-- Sync Progress -->
        <div id="gd-sync-progress" style="display: none;">
            <div class="gd-progress-bar">
                <div class="gd-progress-fill"></div>
            </div>
            <p class="gd-progress-text">Syncing...</p>
        </div>
        
        <!-- Sync Results -->
        <div id="gd-sync-results" style="display: none;">
            <h3>Sync Results</h3>
            <div class="gd-results-content"></div>
        </div>
    </div>
    
    <!-- Recent Sync Logs -->
    <div class="gd-section">
        <h2>📋 Recent Sync History</h2>
        
        <?php if (!empty($recent_syncs)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Sync Type</th>
                        <th>Year/Song</th>
                        <th>Status</th>
                        <th>Found</th>
                        <th>Added</th>
                        <th>Updated</th>
                        <th>Started</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_syncs as $sync): ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst($sync['sync_type'])); ?></td>
                            <td>
                                <?php 
                                if ($sync['year']) {
                                    echo esc_html($sync['year']);
                                } elseif ($sync['song_title']) {
                                    echo esc_html($sync['song_title']);
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="gd-stat-badge gd-badge-<?php echo esc_attr($sync['status']); ?>">
                                    <?php echo esc_html(ucfirst($sync['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($sync['records_found']); ?></td>
                            <td><?php echo number_format($sync['records_added']); ?></td>
                            <td><?php echo number_format($sync['records_updated']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($sync['started_at'])); ?></td>
                            <td>
                                <?php 
                                if ($sync['completed_at']) {
                                    $duration = strtotime($sync['completed_at']) - strtotime($sync['started_at']);
                                    echo $duration > 60 ? round($duration / 60) . ' min' : $duration . ' sec';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="gd-no-data">No sync history yet. Run your first sync above.</p>
        <?php endif; ?>
    </div>
    
    <!-- Song Detection Testing -->
    <div class="gd-section">
        <h2>🎵 Song Detection Testing</h2>
        
        <p>Test the song detection system with sample text.</p>
        
        <div class="gd-test-tool">
            <textarea id="gd-test-text" rows="6" placeholder="Enter text to test song detection... (e.g., 'They played Dark Star at Cornell in 1977')"></textarea>
            <button type="button" class="button button-primary gd-test-detection">
                Test Detection
            </button>
        </div>
        
        <div id="gd-test-results" style="display: none;">
            <h3>Detection Results</h3>
            <div class="gd-test-results-content"></div>
        </div>
    </div>
    
    <!-- Database Management -->
    <div class="gd-section gd-danger-zone">
        <h2>⚠️ Danger Zone</h2>
        
        <div class="gd-danger-actions">
            <div class="gd-danger-action">
                <h3>Clear All Data</h3>
                <p>Remove all recordings, songs, and sync logs from the database. This cannot be undone.</p>
                <button type="button" class="button button-secondary gd-clear-all-data">
                    Clear All Data
                </button>
            </div>
            
            <div class="gd-danger-action">
                <h3>Reset Tables</h3>
                <p>Drop and recreate all streaming tables. This will delete all data.</p>
                <button type="button" class="button button-secondary gd-reset-tables">
                    Reset Tables
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Styles */
.gd-streaming-dashboard {
    max-width: 1400px;
}

.gd-version-badge {
    font-size: 14px;
    color: #666;
    font-weight: normal;
    margin-left: 10px;
}

.gd-dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.gd-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #ddd;
}

.gd-card-primary { border-left-color: #667eea; }
.gd-card-success { border-left-color: #10b981; }
.gd-card-info { border-left-color: #3b82f6; }
.gd-card-warning { border-left-color: #f59e0b; }

.gd-card-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.gd-card h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #374151;
}

.gd-stat-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
}

.gd-stat-row:last-child {
    border-bottom: none;
}

.gd-stat-label {
    color: #6b7280;
    font-size: 14px;
}

.gd-stat-value {
    font-weight: 600;
    color: #111827;
    font-size: 14px;
}

.gd-stat-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.gd-badge-completed { background: #d1fae5; color: #065f46; }
.gd-badge-running { background: #dbeafe; color: #1e40af; }
.gd-badge-failed { background: #fee2e2; color: #991b1b; }
.gd-badge-pending { background: #f3f4f6; color: #6b7280; }

.gd-section {
    background: white;
    border-radius: 8px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.gd-section h2 {
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 2px solid #f3f4f6;
}

.gd-sync-controls {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gd-sync-option {
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
}

.gd-sync-option h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.gd-sync-option p {
    margin: 0 0 15px 0;
    color: #6b7280;
    font-size: 14px;
}

.gd-sync-warning {
    border-color: #f59e0b;
    background: #fffbeb;
}

.gd-sync-year-input,
.gd-sync-date-input {
    display: flex;
    gap: 10px;
}

.gd-sync-year-input input,
.gd-sync-date-input input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}

.gd-progress-bar {
    width: 100%;
    height: 30px;
    background: #f3f4f6;
    border-radius: 15px;
    overflow: hidden;
    margin: 20px 0 10px 0;
}

.gd-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    width: 0%;
    transition: width 0.3s ease;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.gd-progress-text {
    text-align: center;
    color: #6b7280;
    font-weight: 500;
}

.gd-results-content {
    padding: 15px;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.gd-test-tool textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-family: monospace;
    margin-bottom: 10px;
}

.gd-test-results-content {
    padding: 15px;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    margin-top: 15px;
}

.gd-danger-zone {
    border: 2px solid #ef4444;
    background: #fef2f2;
}

.gd-danger-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gd-danger-action {
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 1px solid #fecaca;
}

.gd-danger-action h3 {
    margin: 0 0 10px 0;
    color: #dc2626;
}

.gd-danger-action p {
    margin: 0 0 15px 0;
    color: #6b7280;
    font-size: 14px;
}

.gd-no-data {
    padding: 40px;
    text-align: center;
    color: #9ca3af;
    font-style: italic;
}

.gd-success-message {
    color: #059669;
    font-weight: 600;
    margin: 0 0 10px 0;
}

.gd-warning-message {
    color: #d97706;
    font-weight: 600;
    margin: 0 0 10px 0;
}

.gd-issue-list {
    margin: 10px 0;
    padding-left: 20px;
    color: #6b7280;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Trigger sync
    $('.gd-trigger-sync').on('click', function() {
        const syncType = $(this).data('sync-type');
        let year = null;
        let date = null;
        
        if (syncType === 'year') {
            year = $('#sync-year').val();
            if (!year || year < 1965 || year > 1995) {
                alert('Please enter a valid year between 1965 and 1995');
                return;
            }
        } else if (syncType === 'date') {
            date = $('#sync-date').val();
            if (!date) {
                alert('Please select a date');
                return;
            }
        } else if (syncType === 'full') {
            if (!confirm('This will sync all years (1965-1995) and may take 2-3 hours. Continue?')) {
                return;
            }
        }
        
        $('#gd-sync-progress').show();
        $('#gd-sync-results').hide();
        $('.gd-progress-fill').css('width', '50%');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gd_chatbot_trigger_sync',
                nonce: '<?php echo wp_create_nonce('gd_chatbot_nonce'); ?>',
                sync_type: syncType,
                year: year,
                date: date
            },
            success: function(response) {
                $('#gd-sync-progress').hide();
                $('.gd-progress-fill').css('width', '0%');
                
                if (response.success) {
                    const results = response.data;
                    $('#gd-sync-results').show();
                    $('.gd-results-content').html(`
                        <p><strong>Sync completed successfully!</strong></p>
                        <ul>
                            <li>Records found: ${results.records_found}</li>
                            <li>Records added: ${results.records_added}</li>
                            <li>Records updated: ${results.records_updated}</li>
                            ${results.errors.length > 0 ? '<li>Errors: ' + results.errors.length + '</li>' : ''}
                        </ul>
                    `);
                    
                    // Reload page after 3 seconds to show updated stats
                    setTimeout(() => location.reload(), 3000);
                } else {
                    alert('Sync failed: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                $('#gd-sync-progress').hide();
                $('.gd-progress-fill').css('width', '0%');
                alert('Sync request failed. Please try again.');
            }
        });
    });
    
    // Clear cache
    $('.gd-clear-cache').on('click', function() {
        if (!confirm('Clear all Archive.org caches?')) return;
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gd_chatbot_clear_archive_cache',
                nonce: '<?php echo wp_create_nonce('gd_chatbot_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Cache cleared successfully!');
                    location.reload();
                } else {
                    alert('Failed to clear cache: ' + (response.data.message || 'Unknown error'));
                }
            }
        });
    });
    
    // Cleanup database
    $('.gd-cleanup-db').on('click', function() {
        if (!confirm('Clean up orphaned database records?')) return;
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gd_chatbot_cleanup_database',
                nonce: '<?php echo wp_create_nonce('gd_chatbot_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Database cleaned successfully!');
                    location.reload();
                } else {
                    alert('Failed to clean database: ' + (response.data.message || 'Unknown error'));
                }
            }
        });
    });
    
    // Test song detection
    $('.gd-test-detection').on('click', function() {
        const text = $('#gd-test-text').val();
        if (!text) {
            alert('Please enter some text to test');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gd_chatbot_test_detection',
                nonce: '<?php echo wp_create_nonce('gd_chatbot_nonce'); ?>',
                text: text
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = `<p><strong>Songs detected: ${data.detected_songs.length}</strong></p>`;
                    
                    if (data.detected_songs.length > 0) {
                        html += '<ul>';
                        data.detected_songs.forEach(song => {
                            html += `<li><strong>${song.title}</strong> (${song.author})</li>`;
                        });
                        html += '</ul>';
                        html += '<h4>Enriched Output:</h4>';
                        html += `<div style="padding: 15px; background: white; border: 1px solid #ddd; border-radius: 4px;">${data.enriched}</div>`;
                    } else {
                        html += '<p>No songs detected in the text.</p>';
                    }
                    
                    $('#gd-test-results').show();
                    $('.gd-test-results-content').html(html);
                } else {
                    alert('Test failed: ' + (response.data.message || 'Unknown error'));
                }
            }
        });
    });
    
    // Clear all data
    $('.gd-clear-all-data').on('click', function() {
        if (!confirm('This will delete ALL recordings, songs, and sync logs. This cannot be undone. Are you sure?')) return;
        if (!confirm('Are you REALLY sure? This will permanently delete all data.')) return;
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gd_chatbot_clear_all_data',
                nonce: '<?php echo wp_create_nonce('gd_chatbot_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('All data cleared successfully!');
                    location.reload();
                } else {
                    alert('Failed to clear data: ' + (response.data.message || 'Unknown error'));
                }
            }
        });
    });
    
    // Reset tables
    $('.gd-reset-tables').on('click', function() {
        if (!confirm('This will drop and recreate all tables, deleting ALL data. Are you sure?')) return;
        if (!confirm('Are you REALLY sure? This cannot be undone.')) return;
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gd_chatbot_reset_tables',
                nonce: '<?php echo wp_create_nonce('gd_chatbot_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Tables reset successfully!');
                    location.reload();
                } else {
                    alert('Failed to reset tables: ' + (response.data.message || 'Unknown error'));
                }
            }
        });
    });
});
</script>
