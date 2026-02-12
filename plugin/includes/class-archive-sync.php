<?php
/**
 * Archive.org Sync Service
 * 
 * Background service to sync Archive.org recording metadata to database
 * Runs via WP-Cron and can be triggered manually from admin
 * 
 * @package GD_Chatbot
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Archive_Sync {
    
    /**
     * Archive API instance
     */
    private $archive_api;
    
    /**
     * Cron hook name
     */
    const CRON_HOOK = 'gd_chatbot_archive_sync';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->archive_api = new GD_Archive_API();
        
        // Register cron hook
        add_action(self::CRON_HOOK, array($this, 'run_sync'));
        
        // Schedule cron if not already scheduled
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }
    }
    
    /**
     * Run sync process
     * 
     * @param array $args Sync arguments
     * @return array Sync results
     */
    public function run_sync($args = array()) {
        $defaults = array(
            'sync_type' => 'incremental', // incremental, full, year, date
            'year' => null,
            'date' => null,
            'force' => false // Force re-sync even if already synced
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Log sync start
        $log_id = $this->log_sync_start($args);
        
        $results = array(
            'records_found' => 0,
            'records_added' => 0,
            'records_updated' => 0,
            'errors' => array()
        );
        
        try {
            switch ($args['sync_type']) {
                case 'full':
                    $results = $this->sync_all_years();
                    break;
                    
                case 'year':
                    if (!empty($args['year'])) {
                        $results = $this->sync_year($args['year'], $args['force']);
                    }
                    break;
                    
                case 'date':
                    if (!empty($args['date'])) {
                        $results = $this->sync_date($args['date'], $args['force']);
                    }
                    break;
                    
                case 'incremental':
                default:
                    $results = $this->sync_incremental();
                    break;
            }
            
            // Log sync completion
            $this->log_sync_complete($log_id, $results, 'completed');
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            $this->log_sync_complete($log_id, $results, 'failed', $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Sync all years (1965-1995)
     * 
     * @return array Sync results
     */
    private function sync_all_years() {
        $results = array(
            'records_found' => 0,
            'records_added' => 0,
            'records_updated' => 0,
            'errors' => array()
        );
        
        for ($year = 1965; $year <= 1995; $year++) {
            $year_results = $this->sync_year($year, false);
            
            $results['records_found'] += $year_results['records_found'];
            $results['records_added'] += $year_results['records_added'];
            $results['records_updated'] += $year_results['records_updated'];
            $results['errors'] = array_merge($results['errors'], $year_results['errors']);
            
            // Sleep to avoid hammering Archive.org
            sleep(2);
        }
        
        return $results;
    }
    
    /**
     * Sync a specific year
     * 
     * @param int $year Year (1965-1995)
     * @param bool $force Force re-sync
     * @return array Sync results
     */
    private function sync_year($year, $force = false) {
        global $wpdb;
        
        $results = array(
            'records_found' => 0,
            'records_added' => 0,
            'records_updated' => 0,
            'errors' => array()
        );
        
        // Search Archive.org for this year
        $performances = $this->archive_api->search_by_year($year, 'date', 200);
        
        if (is_wp_error($performances)) {
            $results['errors'][] = "Year $year: " . $performances->get_error_message();
            return $results;
        }
        
        $results['records_found'] = count($performances);
        
        // Process each performance
        foreach ($performances as $performance) {
            $result = $this->save_recording($performance, $force);
            
            if ($result['action'] === 'added') {
                $results['records_added']++;
            } elseif ($result['action'] === 'updated') {
                $results['records_updated']++;
            } elseif ($result['action'] === 'error') {
                $results['errors'][] = $result['error'];
            }
        }
        
        return $results;
    }
    
    /**
     * Sync a specific date
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @param bool $force Force re-sync
     * @return array Sync results
     */
    private function sync_date($date, $force = false) {
        $results = array(
            'records_found' => 0,
            'records_added' => 0,
            'records_updated' => 0,
            'errors' => array()
        );
        
        // Search Archive.org for this date
        $performances = $this->archive_api->search_by_date($date, 'downloads');
        
        if (is_wp_error($performances)) {
            $results['errors'][] = "Date $date: " . $performances->get_error_message();
            return $results;
        }
        
        $results['records_found'] = count($performances);
        
        // Process each performance
        foreach ($performances as $performance) {
            $result = $this->save_recording($performance, $force);
            
            if ($result['action'] === 'added') {
                $results['records_added']++;
            } elseif ($result['action'] === 'updated') {
                $results['records_updated']++;
            } elseif ($result['action'] === 'error') {
                $results['errors'][] = $result['error'];
            }
        }
        
        return $results;
    }
    
    /**
     * Incremental sync (sync recent or missing data)
     * 
     * @return array Sync results
     */
    private function sync_incremental() {
        global $wpdb;
        
        $results = array(
            'records_found' => 0,
            'records_added' => 0,
            'records_updated' => 0,
            'errors' => array()
        );
        
        // Strategy: Sync shows that haven't been synced in 30 days
        // or shows with high download counts that might have updated ratings
        
        // Get dates that need syncing
        $dates_to_sync = $wpdb->get_col(
            "SELECT DISTINCT show_date 
             FROM {$wpdb->prefix}gd_show_recordings 
             WHERE last_synced IS NULL 
             OR last_synced < DATE_SUB(NOW(), INTERVAL 30 DAY)
             OR (downloads > 10000 AND last_synced < DATE_SUB(NOW(), INTERVAL 7 DAY))
             LIMIT 50"
        );
        
        // If no existing records, sync a sample of popular years
        if (empty($dates_to_sync)) {
            $popular_years = array(1977, 1972, 1973, 1974, 1989, 1990);
            
            foreach ($popular_years as $year) {
                $year_results = $this->sync_year($year, false);
                
                $results['records_found'] += $year_results['records_found'];
                $results['records_added'] += $year_results['records_added'];
                $results['records_updated'] += $year_results['records_updated'];
                $results['errors'] = array_merge($results['errors'], $year_results['errors']);
                
                sleep(2);
            }
        } else {
            // Sync specific dates
            foreach ($dates_to_sync as $date) {
                $date_results = $this->sync_date($date, true);
                
                $results['records_found'] += $date_results['records_found'];
                $results['records_added'] += $date_results['records_added'];
                $results['records_updated'] += $date_results['records_updated'];
                $results['errors'] = array_merge($results['errors'], $date_results['errors']);
                
                sleep(1);
            }
        }
        
        return $results;
    }
    
    /**
     * Save a recording to database
     * 
     * @param array $performance Performance data from Archive.org
     * @param bool $force Force update even if exists
     * @return array Result with action taken
     */
    private function save_recording($performance, $force = false) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gd_show_recordings';
        $identifier = $performance['identifier'];
        
        // Check if already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, last_synced FROM $table WHERE archive_identifier = %s",
            $identifier
        ));
        
        // Skip if exists and not forcing
        if ($existing && !$force) {
            return array('action' => 'skipped', 'id' => $existing->id);
        }
        
        // Parse location into city and state
        $location_parts = $this->parse_location($performance['location']);
        
        // Prepare data
        $data = array(
            'archive_identifier' => $identifier,
            'show_date' => $performance['date'],
            'venue_name' => $performance['venue'],
            'venue_location' => $performance['location'],
            'venue_city' => $location_parts['city'],
            'venue_state' => $location_parts['state'],
            'downloads' => $performance['downloads'],
            'avg_rating' => $performance['rating'],
            'num_reviews' => $performance['num_reviews'],
            'thumbnail_url' => $performance['thumbnail'],
            'stream_url_mp3' => $performance['stream_url'],
            'archive_url' => $performance['archive_url'],
            'metadata_json' => json_encode($performance['metadata']),
            'last_synced' => current_time('mysql')
        );
        
        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%s', '%s', '%s', '%s', '%s');
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table,
                $data,
                array('id' => $existing->id),
                $format,
                array('%d')
            );
            
            if (false === $result) {
                return array('action' => 'error', 'error' => $wpdb->last_error);
            }
            
            return array('action' => 'updated', 'id' => $existing->id);
        } else {
            // Insert new record
            $result = $wpdb->insert($table, $data, $format);
            
            if (false === $result) {
                return array('action' => 'error', 'error' => $wpdb->last_error);
            }
            
            $recording_id = $wpdb->insert_id;
            
            // Sync song recordings for this show
            $this->sync_song_recordings($recording_id, $identifier);
            
            return array('action' => 'added', 'id' => $recording_id);
        }
    }
    
    /**
     * Sync individual song recordings for a show
     * 
     * @param int $recording_id Database recording ID
     * @param string $identifier Archive.org identifier
     * @return int Number of songs added
     */
    private function sync_song_recordings($recording_id, $identifier) {
        global $wpdb;
        
        // Get MP3 files from Archive.org
        $mp3_files = $this->archive_api->get_mp3_files($identifier);
        
        if (empty($mp3_files)) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'gd_song_recordings';
        $added = 0;
        
        foreach ($mp3_files as $file) {
            // Extract song title from filename or title
            $song_title = $this->extract_song_title($file['title'], $file['filename']);
            
            if (empty($song_title)) {
                continue;
            }
            
            $data = array(
                'recording_id' => $recording_id,
                'song_title' => $song_title,
                'song_slug' => sanitize_title($song_title),
                'track_number' => intval($file['track']),
                'duration_seconds' => $this->parse_duration($file['length']),
                'stream_url' => $file['url'],
                'file_format' => 'mp3'
            );
            
            $result = $wpdb->insert($table, $data, array('%d', '%s', '%s', '%d', '%d', '%s', '%s'));
            
            if ($result) {
                $added++;
            }
        }
        
        return $added;
    }
    
    /**
     * Parse location into city and state
     * 
     * @param string $location Location string (e.g., "San Francisco, CA")
     * @return array City and state
     */
    private function parse_location($location) {
        $parts = array(
            'city' => null,
            'state' => null
        );
        
        if (empty($location)) {
            return $parts;
        }
        
        // Try to split by comma
        $location_parts = array_map('trim', explode(',', $location));
        
        if (count($location_parts) >= 2) {
            $parts['city'] = $location_parts[0];
            $parts['state'] = $location_parts[1];
        } else {
            $parts['city'] = $location;
        }
        
        return $parts;
    }
    
    /**
     * Extract song title from file metadata
     * 
     * @param string $title File title
     * @param string $filename Filename
     * @return string Song title
     */
    private function extract_song_title($title, $filename) {
        // If title is provided, use it
        if (!empty($title) && $title !== 'Grateful Dead') {
            return $title;
        }
        
        // Try to extract from filename
        // Format: gd1977-05-08d1t01.mp3 or similar
        $filename = basename($filename, '.mp3');
        
        // Remove technical prefixes
        $filename = preg_replace('/^gd\d{4}-\d{2}-\d{2}[a-z]\d+t\d+/', '', $filename);
        $filename = preg_replace('/^[_\-]+/', '', $filename);
        
        // Convert underscores/hyphens to spaces
        $filename = str_replace(array('_', '-'), ' ', $filename);
        
        // Capitalize words
        $filename = ucwords(strtolower($filename));
        
        return trim($filename);
    }
    
    /**
     * Parse duration string to seconds
     * 
     * @param string $duration Duration string (e.g., "5:23", "1:23:45")
     * @return int Duration in seconds
     */
    private function parse_duration($duration) {
        if (empty($duration)) {
            return 0;
        }
        
        $parts = explode(':', $duration);
        $seconds = 0;
        
        if (count($parts) === 3) {
            // H:M:S
            $seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        } elseif (count($parts) === 2) {
            // M:S
            $seconds = ($parts[0] * 60) + $parts[1];
        } else {
            $seconds = intval($duration);
        }
        
        return $seconds;
    }
    
    /**
     * Log sync start
     * 
     * @param array $args Sync arguments
     * @return int Log ID
     */
    private function log_sync_start($args) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gd_archive_sync_log';
        
        $wpdb->insert($table, array(
            'sync_type' => $args['sync_type'],
            'year' => $args['year'],
            'status' => 'running',
            'started_at' => current_time('mysql')
        ), array('%s', '%d', '%s', '%s'));
        
        return $wpdb->insert_id;
    }
    
    /**
     * Log sync completion
     * 
     * @param int $log_id Log ID
     * @param array $results Sync results
     * @param string $status Status (completed, failed)
     * @param string $error_message Error message if failed
     */
    private function log_sync_complete($log_id, $results, $status, $error_message = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gd_archive_sync_log';
        
        $wpdb->update(
            $table,
            array(
                'records_found' => $results['records_found'],
                'records_added' => $results['records_added'],
                'records_updated' => $results['records_updated'],
                'status' => $status,
                'error_message' => $error_message,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $log_id),
            array('%d', '%d', '%d', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get sync status
     * 
     * @return array Sync status information
     */
    public function get_sync_status() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gd_archive_sync_log';
        
        // Get last sync
        $last_sync = $wpdb->get_row(
            "SELECT * FROM $table ORDER BY started_at DESC LIMIT 1"
        );
        
        // Get total synced recordings
        $total_recordings = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gd_show_recordings"
        );
        
        // Get date range
        $date_range = $wpdb->get_row(
            "SELECT MIN(show_date) as earliest, MAX(show_date) as latest 
             FROM {$wpdb->prefix}gd_show_recordings"
        );
        
        return array(
            'last_sync' => $last_sync,
            'total_recordings' => $total_recordings,
            'earliest_show' => $date_range->earliest ?? null,
            'latest_show' => $date_range->latest ?? null,
            'next_scheduled' => wp_next_scheduled(self::CRON_HOOK)
        );
    }
    
    /**
     * Unschedule cron job
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }
}
