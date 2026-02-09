<?php
/**
 * Setlist Search Class
 * 
 * Searches through Grateful Dead setlist CSV files to find shows by date, venue, location, or song
 * 
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Setlist_Search {
    
    /**
     * Path to setlist files
     */
    private $setlist_dir;
    
    /**
     * Years available
     */
    private $years = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->setlist_dir = GD_CHATBOT_PLUGIN_DIR . 'context/Deadshows/deadshows/';
        
        // Years 1965-1995
        for ($year = 1965; $year <= 1995; $year++) {
            $this->years[] = $year;
        }
    }
    
    /**
     * Search for shows based on query
     * 
     * @param string $query User's search query
     * @return string Formatted search results or empty if nothing found
     */
    public function search($query) {
        $query_lower = strtolower($query);
        
        // Detect query type
        $search_type = $this->detect_search_type($query_lower);
        
        switch ($search_type) {
            case 'specific_date':
                return $this->search_by_specific_date($query);
            case 'year':
                return $this->search_by_year($query);
            case 'venue':
                return $this->search_by_venue($query);
            case 'location':
                return $this->search_by_location($query);
            case 'song':
                return $this->search_by_song($query);
            case 'general':
            default:
                return $this->general_search($query);
        }
    }
    
    /**
     * Detect what type of search the user is asking for
     */
    private function detect_search_type($query_lower) {
        // Check for specific date patterns (Cornell 5/8/77, May 8 1977, 5/8/1977, etc.)
        if (preg_match('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b/', $query_lower)) {
            return 'specific_date';
        }
        
        // Check for month and year patterns
        if (preg_match('/\b(january|february|march|april|may|june|july|august|september|october|november|december)\s+\d{1,2},?\s+\d{4}\b/', $query_lower)) {
            return 'specific_date';
        }
        
        // Check for year-only queries
        if (preg_match('/\b(19\d{2})\b/', $query_lower) && 
            (strpos($query_lower, 'shows') !== false || 
             strpos($query_lower, 'year') !== false ||
             strpos($query_lower, 'played') !== false)) {
            return 'year';
        }
        
        // Check for venue-specific queries
        if (strpos($query_lower, 'venue') !== false ||
            strpos($query_lower, 'winterland') !== false ||
            strpos($query_lower, 'fillmore') !== false ||
            strpos($query_lower, 'barton hall') !== false ||
            strpos($query_lower, 'cornell') !== false ||
            strpos($query_lower, 'garden') !== false ||
            strpos($query_lower, 'theater') !== false ||
            strpos($query_lower, 'theatre') !== false) {
            return 'venue';
        }
        
        // Check for location-specific queries
        if (preg_match('/\bin\s+(san francisco|new york|chicago|boston|philadelphia)/i', $query_lower)) {
            return 'location';
        }
        
        // Check for song-specific queries
        if (strpos($query_lower, 'played') !== false ||
            strpos($query_lower, 'performed') !== false ||
            strpos($query_lower, 'setlist') !== false) {
            return 'song';
        }
        
        return 'general';
    }
    
    /**
     * Search for a specific date
     */
    private function search_by_specific_date($query) {
        // Extract date from query
        $date = $this->extract_date($query);
        
        if (!$date) {
            return '';
        }
        
        $year = date('Y', strtotime($date));
        $month_day = date('m/d/Y', strtotime($date));
        
        $show = $this->find_show_by_date($year, $month_day);
        
        if ($show) {
            return $this->format_show_detail($show);
        }
        
        return '';
    }
    
    /**
     * Search shows in a specific year
     */
    private function search_by_year($query) {
        preg_match('/\b(19\d{2})\b/', $query, $matches);
        
        if (empty($matches[1])) {
            return '';
        }
        
        $year = $matches[1];
        $shows = $this->get_all_shows_for_year($year);
        
        if (empty($shows)) {
            return '';
        }
        
        // Return summary of year
        return $this->format_year_summary($year, $shows);
    }
    
    /**
     * Search by venue name
     */
    private function search_by_venue($query) {
        $results = array();
        $search_term = strtolower($query);
        
        foreach ($this->years as $year) {
            $shows = $this->get_all_shows_for_year($year);
            
            foreach ($shows as $show) {
                if (stripos($show['Venue Name'], $query) !== false) {
                    $results[] = $show;
                    
                    if (count($results) >= 20) {
                        break 2; // Limit results
                    }
                }
            }
        }
        
        if (empty($results)) {
            return '';
        }
        
        return $this->format_venue_results($results, $query);
    }
    
    /**
     * Search by location (city/state)
     */
    private function search_by_location($query) {
        $results = array();
        
        foreach ($this->years as $year) {
            $shows = $this->get_all_shows_for_year($year);
            
            foreach ($shows as $show) {
                if (stripos($show['Venue Location'], $query) !== false) {
                    $results[] = $show;
                    
                    if (count($results) >= 20) {
                        break 2;
                    }
                }
            }
        }
        
        if (empty($results)) {
            return '';
        }
        
        return $this->format_location_results($results, $query);
    }
    
    /**
     * Search for shows with a specific song
     */
    private function search_by_song($query) {
        // Extract song name from query
        $song = $this->extract_song_name($query);
        
        if (!$song) {
            return '';
        }
        
        $results = array();
        
        foreach ($this->years as $year) {
            $shows = $this->get_all_shows_for_year($year);
            
            foreach ($shows as $show) {
                if (stripos($show['Set List'], $song) !== false) {
                    $results[] = $show;
                    
                    if (count($results) >= 15) {
                        break 2;
                    }
                }
            }
        }
        
        if (empty($results)) {
            return '';
        }
        
        return $this->format_song_results($results, $song);
    }
    
    /**
     * General search across all fields
     */
    private function general_search($query) {
        $results = array();
        
        foreach ($this->years as $year) {
            $shows = $this->get_all_shows_for_year($year);
            
            foreach ($shows as $show) {
                $combined = implode(' ', $show);
                
                if (stripos($combined, $query) !== false) {
                    $results[] = $show;
                    
                    if (count($results) >= 10) {
                        break 2;
                    }
                }
            }
        }
        
        if (empty($results)) {
            return '';
        }
        
        return $this->format_general_results($results);
    }
    
    /**
     * Get all shows for a specific year
     */
    private function get_all_shows_for_year($year) {
        $file_path = $this->setlist_dir . $year . '.csv';
        
        if (!file_exists($file_path)) {
            return array();
        }
        
        $shows = array();
        $file = fopen($file_path, 'r');
        
        if ($file === false) {
            return array();
        }
        
        // Read header
        $header = fgetcsv($file);
        
        // Read all rows
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) === count($header)) {
                $shows[] = array_combine($header, $row);
            }
        }
        
        fclose($file);
        
        return $shows;
    }
    
    /**
     * Find a specific show by date
     */
    private function find_show_by_date($year, $date_string) {
        $shows = $this->get_all_shows_for_year($year);
        
        foreach ($shows as $show) {
            if ($show['Date'] === $date_string) {
                return $show;
            }
        }
        
        return null;
    }
    
    /**
     * Extract date from query string
     */
    private function extract_date($query) {
        // Try various date formats
        
        // MM/DD/YYYY or M/D/YY
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})\b/', $query, $matches)) {
            $month = $matches[1];
            $day = $matches[2];
            $year = $matches[3];
            
            // Handle 2-digit years
            if (strlen($year) === 2) {
                $year = '19' . $year;
            }
            
            return "$month/$day/$year";
        }
        
        // Month DD, YYYY
        if (preg_match('/\b(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{1,2}),?\s+(\d{4})\b/i', $query, $matches)) {
            $months = array(
                'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
                'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
                'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12
            );
            
            $month = $months[strtolower($matches[1])];
            $day = $matches[2];
            $year = $matches[3];
            
            return "$month/$day/$year";
        }
        
        return null;
    }
    
    /**
     * Extract song name from query
     */
    private function extract_song_name($query) {
        // Try to find quoted song name
        if (preg_match('/"([^"]+)"/', $query, $matches)) {
            return $matches[1];
        }
        
        // Try common song name patterns
        $common_songs = array(
            'Dark Star', 'Playing in the Band', 'Scarlet Begonias', 'Fire on the Mountain',
            'Terrapin Station', 'Sugar Magnolia', 'Casey Jones', 'Truckin', 'Touch of Grey',
            'Uncle John\'s Band', 'Friend of the Devil', 'Box of Rain', 'Ripple',
            'Not Fade Away', 'The Other One', 'Saint Stephen', 'Morning Dew'
        );
        
        foreach ($common_songs as $song) {
            if (stripos($query, $song) !== false) {
                return $song;
            }
        }
        
        return null;
    }
    
    /**
     * Format detailed show information
     */
    private function format_show_detail($show) {
        $output = "\n## Show: " . $show['Date'] . "\n\n";
        $output .= "**Venue:** " . $show['Venue Name'] . "\n";
        $output .= "**Location:** " . $show['Venue Location'] . "\n\n";
        $output .= "**Setlist:**\n\n";
        
        // Parse and format setlist
        $sets = explode(';', $show['Set List']);
        foreach ($sets as $set) {
            $set = trim($set);
            if (!empty($set)) {
                $output .= "- " . $set . "\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Format year summary
     */
    private function format_year_summary($year, $shows) {
        $output = "\n## Grateful Dead Shows in $year\n\n";
        $output .= "**Total Shows:** " . count($shows) . "\n\n";
        
        // Group by month
        $by_month = array();
        foreach ($shows as $show) {
            $month = date('F', strtotime($show['Date']));
            if (!isset($by_month[$month])) {
                $by_month[$month] = array();
            }
            $by_month[$month][] = $show;
        }
        
        $output .= "**Monthly Breakdown:**\n\n";
        foreach ($by_month as $month => $month_shows) {
            $output .= "- **$month:** " . count($month_shows) . " shows\n";
        }
        
        // List first 10 shows
        $output .= "\n**Shows (showing first 10):**\n\n";
        foreach (array_slice($shows, 0, 10) as $show) {
            $output .= "- **" . $show['Date'] . "** - " . $show['Venue Name'] . ", " . $show['Venue Location'] . "\n";
        }
        
        if (count($shows) > 10) {
            $output .= "\n*(" . (count($shows) - 10) . " more shows not displayed)*\n";
        }
        
        return $output;
    }
    
    /**
     * Format venue search results
     */
    private function format_venue_results($results, $venue_name) {
        $output = "\n## Shows at venues matching \"$venue_name\"\n\n";
        $output .= "**Found " . count($results) . " shows:**\n\n";
        
        foreach ($results as $show) {
            $output .= "- **" . $show['Date'] . "** - " . $show['Venue Name'] . ", " . $show['Venue Location'] . "\n";
        }
        
        return $output;
    }
    
    /**
     * Format location search results
     */
    private function format_location_results($results, $location) {
        $output = "\n## Shows in locations matching \"$location\"\n\n";
        $output .= "**Found " . count($results) . " shows:**\n\n";
        
        foreach ($results as $show) {
            $output .= "- **" . $show['Date'] . "** - " . $show['Venue Name'] . ", " . $show['Venue Location'] . "\n";
        }
        
        return $output;
    }
    
    /**
     * Format song search results
     */
    private function format_song_results($results, $song) {
        $output = "\n## Shows featuring \"$song\"\n\n";
        $output .= "**Found " . count($results) . " shows (showing first 15):**\n\n";
        
        foreach ($results as $show) {
            $output .= "- **" . $show['Date'] . "** - " . $show['Venue Name'] . ", " . $show['Venue Location'] . "\n";
        }
        
        return $output;
    }
    
    /**
     * Format general search results
     */
    private function format_general_results($results) {
        $output = "\n## Search Results\n\n";
        $output .= "**Found " . count($results) . " matching shows:**\n\n";
        
        foreach ($results as $show) {
            $output .= "- **" . $show['Date'] . "** - " . $show['Venue Name'] . ", " . $show['Venue Location'] . "\n";
        }
        
        return $output;
    }
    
    /**
     * Check if query is likely about setlists
     */
    public function is_setlist_query($query) {
        $query_lower = strtolower($query);
        
        $keywords = array(
            'setlist', 'set list', 'show', 'concert', 'performance', 'played',
            'venue', 'date', 'when did', 'what songs', 'cornell', 'winterland',
            'barton hall', 'fillmore', '19', 'song', 'tour'
        );
        
        foreach ($keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return true;
            }
        }
        
        // Check for date patterns
        if (preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $query)) {
            return true;
        }
        
        return false;
    }
}
