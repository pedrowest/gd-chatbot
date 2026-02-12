<?php
/**
 * Streaming Database Schema and Migration
 * 
 * Manages database tables for Archive.org recording metadata
 * Part of the hybrid approach: CSV for setlists, DB for streaming
 * 
 * @package GD_Chatbot
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Streaming_Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0';
    
    /**
     * Database version option name
     */
    const DB_VERSION_OPTION = 'gd_chatbot_streaming_db_version';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into activation
        add_action('gd_chatbot_activate', array($this, 'maybe_create_tables'));
        
        // Check version on admin init
        if (is_admin()) {
            add_action('admin_init', array($this, 'check_database_version'));
        }
    }
    
    /**
     * Check if database needs updating
     */
    public function check_database_version() {
        $current_version = get_option(self::DB_VERSION_OPTION, '0');
        
        if (version_compare($current_version, self::DB_VERSION, '<')) {
            $this->maybe_create_tables();
        }
    }
    
    /**
     * Create or update database tables
     * 
     * @return bool Success
     */
    public function maybe_create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Table 1: Archive.org show recordings
        $table_recordings = $wpdb->prefix . 'gd_show_recordings';
        $sql_recordings = "CREATE TABLE $table_recordings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            archive_identifier varchar(255) NOT NULL,
            show_date date NOT NULL,
            venue_name varchar(255) DEFAULT NULL,
            venue_location varchar(255) DEFAULT NULL,
            venue_city varchar(100) DEFAULT NULL,
            venue_state varchar(50) DEFAULT NULL,
            downloads int(11) DEFAULT 0,
            avg_rating decimal(3,1) DEFAULT NULL,
            num_reviews int(11) DEFAULT 0,
            thumbnail_url varchar(500) DEFAULT NULL,
            stream_url_mp3 varchar(500) DEFAULT NULL,
            archive_url varchar(500) DEFAULT NULL,
            metadata_json longtext DEFAULT NULL,
            last_synced datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_identifier (archive_identifier),
            KEY idx_date (show_date),
            KEY idx_downloads (downloads DESC),
            KEY idx_rating (avg_rating DESC),
            KEY idx_location (venue_city, venue_state),
            FULLTEXT KEY idx_venue (venue_name, venue_location)
        ) $charset_collate;";
        
        dbDelta($sql_recordings);
        
        // Table 2: Individual song recordings within shows
        $table_songs = $wpdb->prefix . 'gd_song_recordings';
        $sql_songs = "CREATE TABLE $table_songs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            recording_id bigint(20) unsigned NOT NULL,
            song_title varchar(255) NOT NULL,
            song_slug varchar(255) NOT NULL,
            track_number tinyint(4) DEFAULT NULL,
            set_number tinyint(4) DEFAULT NULL,
            position_in_set tinyint(4) DEFAULT NULL,
            duration_seconds int(11) DEFAULT NULL,
            stream_url varchar(500) DEFAULT NULL,
            file_format varchar(20) DEFAULT 'mp3',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_recording (recording_id),
            KEY idx_song_title (song_title),
            KEY idx_song_slug (song_slug),
            KEY fk_recording (recording_id)
        ) $charset_collate;";
        
        dbDelta($sql_songs);
        
        // Table 3: User favorites
        $table_favorites = $wpdb->prefix . 'gd_user_show_favorites';
        $sql_favorites = "CREATE TABLE $table_favorites (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            show_date date NOT NULL,
            recording_id bigint(20) unsigned DEFAULT NULL,
            notes text DEFAULT NULL,
            added_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_favorite (user_id, show_date),
            KEY idx_user (user_id),
            KEY idx_date (show_date),
            KEY fk_recording (recording_id)
        ) $charset_collate;";
        
        dbDelta($sql_favorites);
        
        // Table 4: Sync log (track Archive.org sync progress)
        $table_sync_log = $wpdb->prefix . 'gd_archive_sync_log';
        $sql_sync_log = "CREATE TABLE $table_sync_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sync_type varchar(50) NOT NULL,
            year int(4) DEFAULT NULL,
            song_title varchar(255) DEFAULT NULL,
            records_found int(11) DEFAULT 0,
            records_added int(11) DEFAULT 0,
            records_updated int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            error_message text DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY idx_sync_type (sync_type),
            KEY idx_status (status),
            KEY idx_started (started_at)
        ) $charset_collate;";
        
        dbDelta($sql_sync_log);
        
        // Update version
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        
        return true;
    }
    
    /**
     * Drop all streaming tables (for uninstall)
     * 
     * @return bool Success
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'gd_show_recordings',
            $wpdb->prefix . 'gd_song_recordings',
            $wpdb->prefix . 'gd_user_show_favorites',
            $wpdb->prefix . 'gd_archive_sync_log'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option(self::DB_VERSION_OPTION);
        
        return true;
    }
    
    /**
     * Get table statistics
     * 
     * @return array Table row counts
     */
    public function get_table_stats() {
        global $wpdb;
        
        $stats = array();
        
        $stats['recordings'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_show_recordings"
        );
        
        $stats['song_recordings'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_song_recordings"
        );
        
        $stats['favorites'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_user_show_favorites"
        );
        
        $stats['sync_logs'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_archive_sync_log"
        );
        
        // Get last sync time
        $stats['last_sync'] = $wpdb->get_var(
            "SELECT MAX(completed_at) FROM {$wpdb->prefix}gd_archive_sync_log 
             WHERE status = 'completed'"
        );
        
        // Get date range of recordings
        $date_range = $wpdb->get_row(
            "SELECT MIN(show_date) as earliest, MAX(show_date) as latest 
             FROM {$wpdb->prefix}gd_show_recordings"
        );
        
        $stats['earliest_show'] = $date_range->earliest ?? null;
        $stats['latest_show'] = $date_range->latest ?? null;
        
        return $stats;
    }
    
    /**
     * Verify database integrity
     * 
     * @return array Issues found
     */
    public function verify_integrity() {
        global $wpdb;
        
        $issues = array();
        
        // Check for orphaned song recordings
        $orphaned_songs = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_song_recordings sr
             LEFT JOIN {$wpdb->prefix}gd_show_recordings r ON sr.recording_id = r.id
             WHERE r.id IS NULL"
        );
        
        if ($orphaned_songs > 0) {
            $issues[] = "$orphaned_songs orphaned song recordings found";
        }
        
        // Check for orphaned favorites
        $orphaned_favorites = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_user_show_favorites f
             LEFT JOIN {$wpdb->prefix}gd_show_recordings r ON f.recording_id = r.id
             WHERE f.recording_id IS NOT NULL AND r.id IS NULL"
        );
        
        if ($orphaned_favorites > 0) {
            $issues[] = "$orphaned_favorites orphaned favorites found";
        }
        
        // Check for recordings without dates
        $missing_dates = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_show_recordings
             WHERE show_date IS NULL OR show_date = '0000-00-00'"
        );
        
        if ($missing_dates > 0) {
            $issues[] = "$missing_dates recordings missing dates";
        }
        
        return $issues;
    }
    
    /**
     * Clean up orphaned records
     * 
     * @return array Cleanup results
     */
    public function cleanup_orphaned_records() {
        global $wpdb;
        
        $results = array();
        
        // Delete orphaned song recordings
        $deleted_songs = $wpdb->query(
            "DELETE sr FROM {$wpdb->prefix}gd_song_recordings sr
             LEFT JOIN {$wpdb->prefix}gd_show_recordings r ON sr.recording_id = r.id
             WHERE r.id IS NULL"
        );
        
        $results['deleted_songs'] = $deleted_songs;
        
        // Fix orphaned favorites (set recording_id to NULL)
        $fixed_favorites = $wpdb->query(
            "UPDATE {$wpdb->prefix}gd_user_show_favorites f
             LEFT JOIN {$wpdb->prefix}gd_show_recordings r ON f.recording_id = r.id
             SET f.recording_id = NULL
             WHERE f.recording_id IS NOT NULL AND r.id IS NULL"
        );
        
        $results['fixed_favorites'] = $fixed_favorites;
        
        return $results;
    }
}
