<?php
/**
 * Tavily API Integration Class
 * 
 * Handles web search functionality via Tavily API
 * 
 * @package GD_Claude_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Tavily_API {
    
    /**
     * Tavily API endpoint
     */
    const API_ENDPOINT = 'https://api.tavily.com/search';
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Search depth (basic or advanced)
     */
    private $search_depth;
    
    /**
     * Maximum results to return
     */
    private $max_results;
    
    /**
     * Domains to include in search
     */
    private $include_domains;
    
    /**
     * Domains to exclude from search
     */
    private $exclude_domains;
    
    /**
     * Encryption key for API key storage
     */
    private $encryption_key;
    
    /**
     * Encryption IV
     */
    private $encryption_iv;
    
    /**
     * Constructor
     */
    public function __construct($api_key = null) {
        // Use WordPress salts for encryption
        $this->encryption_key = substr(AUTH_KEY, 0, 32);
        $this->encryption_iv = substr(AUTH_SALT, 0, 16);
        
        $this->api_key = $api_key ?: $this->get_api_key();
        $this->search_depth = get_option('gd_chatbot_tavily_search_depth', 'basic');
        $this->max_results = (int) get_option('gd_chatbot_tavily_max_results', 5);
        
        // Parse domain lists
        $include = get_option('gd_chatbot_tavily_include_domains', '');
        $exclude = get_option('gd_chatbot_tavily_exclude_domains', '');
        
        $this->include_domains = $this->parse_domain_list($include);
        $this->exclude_domains = $this->parse_domain_list($exclude);
    }
    
    /**
     * Parse comma-separated domain list
     */
    private function parse_domain_list($domains_string) {
        if (empty($domains_string)) {
            return array();
        }
        
        $domains = explode(',', $domains_string);
        $domains = array_map('trim', $domains);
        $domains = array_filter($domains);
        
        return array_values($domains);
    }
    
    /**
     * Check if Tavily is enabled and configured
     */
    public function is_enabled() {
        return get_option('gd_chatbot_tavily_enabled', false) && !empty($this->api_key);
    }
    
    /**
     * Perform a web search with Grateful Dead context
     * 
     * @param string $query Search query
     * @param array $options Additional options
     * @return array|WP_Error Search results or error
     */
    public function search($query, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Tavily API key is not configured.');
        }
        
        // CRITICAL: Add Grateful Dead context to EVERY query
        // This ensures search results are always about the Grateful Dead
        $contextualized_query = $this->add_grateful_dead_context($query);
        
        // Check cache first (use contextualized query for cache key)
        $cache_key = $this->get_cache_key($contextualized_query, $options);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        // Check rate limit
        $rate_check = $this->check_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
        // Build request body with contextualized query
        $body = array(
            'api_key' => $this->api_key,
            'query' => $contextualized_query, // Use contextualized query
            'search_depth' => isset($options['search_depth']) ? $options['search_depth'] : $this->search_depth,
            'max_results' => isset($options['max_results']) ? $options['max_results'] : $this->max_results,
            'include_answer' => true,
            'include_raw_content' => false,
            'include_images' => false,
        );
        
        // ALWAYS use Grateful Dead trusted domains as include filter
        // This ensures results come from authoritative GD sources
        $gd_trusted_domains = $this->get_trusted_gd_domains();
        
        // Merge with user-specified domains if any
        $include_domains = isset($options['include_domains']) ? $options['include_domains'] : $this->include_domains;
        if (!empty($include_domains)) {
            $include_domains = array_merge($gd_trusted_domains, $include_domains);
        } else {
            $include_domains = $gd_trusted_domains;
        }
        
        // Add exclude domains to filter out non-GD content
        $exclude_domains = isset($options['exclude_domains']) ? $options['exclude_domains'] : $this->exclude_domains;
        $gd_exclude_domains = $this->get_exclude_domains();
        if (!empty($exclude_domains)) {
            $exclude_domains = array_merge($gd_exclude_domains, $exclude_domains);
        } else {
            $exclude_domains = $gd_exclude_domains;
        }
        
        // Apply domain filters
        if (!empty($include_domains)) {
            $body['include_domains'] = array_unique($include_domains);
        }
        
        if (!empty($exclude_domains)) {
            $body['exclude_domains'] = array_unique($exclude_domains);
        }
        
        // Make API request
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body)
        ));
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        // Handle API errors
        if ($response_code !== 200) {
            return $this->handle_error($response_code, $data);
        }
        
        $formatted_results = $this->format_results($data);
        
        // Cache successful response for 24 hours
        set_transient($cache_key, $formatted_results, DAY_IN_SECONDS);
        
        // Track usage
        $this->increment_usage();
        
        return $formatted_results;
    }
    
    /**
     * Format search results for use in chat context
     * 
     * @param array $data Raw API response
     * @return array Formatted results
     */
    private function format_results($data) {
        $formatted = array(
            'answer' => isset($data['answer']) ? $data['answer'] : '',
            'results' => array(),
            'query' => $data['query'] ?? ''
        );
        
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $result) {
                $url = $result['url'] ?? '';
                $credibility = $this->assess_source_credibility($url);
                
                $formatted['results'][] = array(
                    'title' => $result['title'] ?? '',
                    'url' => $url,
                    'content' => $result['content'] ?? '',
                    'score' => $result['score'] ?? 0,
                    'credibility' => $credibility,
                    'published_date' => $result['published_date'] ?? null
                );
            }
        }
        
        // Sort results by credibility tier (tier1 first)
        usort($formatted['results'], function($a, $b) {
            $tier_order = array('tier1' => 1, 'tier2' => 2, 'tier3' => 3, 'tier4' => 4);
            $tier_a = isset($a['credibility']['tier']) ? $a['credibility']['tier'] : 'tier4';
            $tier_b = isset($b['credibility']['tier']) ? $b['credibility']['tier'] : 'tier4';
            return ($tier_order[$tier_a] ?? 5) - ($tier_order[$tier_b] ?? 5);
        });
        
        return $formatted;
    }
    
    /**
     * Convert search results to context string for Claude
     * 
     * @param array $results Search results
     * @return string Formatted context
     */
    public function results_to_context($results) {
        if (empty($results['results'])) {
            return '';
        }
        
        $context = "### Web Search Results (Grateful Dead Sources)\n\n";
        
        // CRITICAL INSTRUCTION: Ensure Claude focuses on Grateful Dead
        $context .= "**IMPORTANT:** These search results are specifically about the GRATEFUL DEAD band. ";
        $context .= "If any result mentions other artists or bands, IGNORE that information. ";
        $context .= "Only use information that is directly related to the Grateful Dead, their music, performances, or culture.\n\n";
        
        // Include Tavily's direct answer if available
        if (!empty($results['answer'])) {
            $context .= "**Summary:** " . $results['answer'] . "\n\n";
        }
        
        $context .= "**Sources (sorted by credibility):**\n\n";
        
        foreach ($results['results'] as $index => $result) {
            $num = $index + 1;
            
            // Get credibility info
            $credibility = isset($result['credibility']) ? $result['credibility'] : $this->assess_source_credibility($result['url']);
            $tier = is_array($credibility) ? $credibility['tier'] : $credibility;
            $tier_label = self::get_tier_label($tier);
            $source_desc = is_array($credibility) && isset($credibility['description']) ? $credibility['description'] : '';
            
            $context .= "**{$num}. {$result['title']}** [{$tier_label}]\n";
            $context .= "Source: {$result['url']}";
            if (!empty($source_desc)) {
                $context .= " ({$source_desc})";
            }
            $context .= "\n";
            if (!empty($result['published_date'])) {
                $context .= "Published: {$result['published_date']}\n";
            }
            $context .= "{$result['content']}\n\n";
        }
        
        // Add credibility legend
        $context .= "---\n";
        $context .= "**Source Credibility Key:**\n";
        $context .= "⭐ Official/Archival = dead.net, archive.org, GDAO, academic sources\n";
        $context .= "✓ Trusted Reference = setlist databases, encyclopedias, major publications\n";
        $context .= "○ Community Source = forums, fan sites, social media\n";
        $context .= "? Unverified = other sources, verify before citing\n";
        
        return $context;
    }
    
    /**
     * Determine if a query would benefit from web search
     * 
     * @param string $query User query
     * @return bool
     */
    public function should_search($query) {
        // General keywords that suggest need for current/web information
        $general_triggers = array(
            'latest',
            'recent',
            'current',
            'today',
            'news',
            'update',
            '2024',
            '2025',
            '2026',
            'what is',
            'who is',
            'where is',
            'when did',
            'how to',
            'search for',
            'find',
            'look up',
            'verify',
            'fact check',
            'source',
            'citation',
        );
        
        // Grateful Dead specific triggers that benefit from web search
        $gd_triggers = array(
            // Setlist & Show queries
            'setlist',
            'set list',
            'what did they play',
            'what songs',
            'show on',
            'concert on',
            'played at',
            'performance at',
            'encore',
            'opener',
            'closer',
            'segue',
            'transition',
            
            // Date/venue queries - Major Venues
            'winterland',
            'fillmore',
            'red rocks',
            'msg',
            'madison square',
            'greek theatre',
            'greek theater',
            'shoreline',
            'alpine valley',
            'deer creek',
            'soldier field',
            'ventura',
            'capitol theatre',
            'the matrix',
            'the warfield',
            'the shrine',
            'the spectrum',
            'the garden',
            'the forum',
            'the palace',
            'barton hall',
            'cornell',
            'hampton',
            'nassau coliseum',
            'oakland coliseum',
            'cal expo',
            'frost amphitheatre',
            
            // Version/recording queries
            'best version',
            'best performance',
            'first time played',
            'last time played',
            'debut',
            'bustout',
            'bust out',
            'how many times',
            'times played',
            'performance history',
            'song history',
            
            // Current events
            'dead and company',
            'dead & company',
            'bobby weir',
            'bob weir',
            'mickey hart',
            'bill kreutzmann',
            'john mayer',
            'oteil burbridge',
            'jeff chimenti',
            'tour',
            'reunion',
            'anniversary',
            '60th',
            'phil lesh',
            'phil & friends',
            'terrapin crossroads',
            
            // Equipment & Gear queries
            'tiger',
            'wolf',
            'rosebud',
            'alligator',
            'lightning bolt',
            'wall of sound',
            'modulus',
            'bass',
            'guitar',
            'gear',
            'equipment',
            'rig',
            
            // Archive/recording queries
            'archive.org',
            'soundboard',
            'sbd',
            'audience recording',
            'aud',
            'matrix',
            'betty boards',
            'dick\'s picks',
            'dave\'s picks',
            'road trips',
            'download series',
            'from the vault',
            'spring 1990',
            'europe \'72',
            'relisten',
            'etree',
            'flac',
            'streaming',
            'tape',
            'taper',
            
            // Historical queries
            'wall of sound',
            'acid test',
            'acid tests',
            'ken kesey',
            'owsley',
            'owsley stanley',
            'bear',
            'fare thee well',
            'pigpen',
            'ron mckernan',
            'keith godchaux',
            'donna godchaux',
            'brent mydland',
            'vince welnick',
            'tom constanten',
            'bruce hornsby',
            
            // Song-specific queries (common searches)
            'dark star',
            'terrapin station',
            'scarlet begonias',
            'fire on the mountain',
            'scarlet fire',
            'uncle john\'s band',
            'truckin',
            'touch of grey',
            'ripple',
            'morning dew',
            'st. stephen',
            'the eleven',
            'china cat sunflower',
            'i know you rider',
            'china rider',
            'shakedown street',
            'casey jones',
            'sugar magnolia',
            'sunshine daydream',
            'friend of the devil',
            'box of rain',
            'brokedown palace',
            
            // Cultural/Historical terms
            'deadhead',
            'miracle ticket',
            'shakedown',
            'parking lot',
            'haight-ashbury',
            'the warlocks',
            'grateful dead archive',
            'ucsc',
            'gdao',
            'special collections',
        );
        
        $query_lower = strtolower($query);
        
        // Check general triggers
        foreach ($general_triggers as $trigger) {
            if (strpos($query_lower, $trigger) !== false) {
                return true;
            }
        }
        
        // Check Grateful Dead specific triggers
        foreach ($gd_triggers as $trigger) {
            if (strpos($query_lower, $trigger) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API key is required.');
        }
        
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'api_key' => $this->api_key,
                'query' => 'test',
                'max_results' => 1
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return true;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $error_message = isset($body['detail']) ? $body['detail'] : 'Connection failed';
        
        return new WP_Error('connection_failed', $error_message);
    }
    
    /**
     * Get search depth options
     */
    public static function get_search_depth_options() {
        return array(
            'basic' => 'Basic (Faster, less comprehensive)',
            'advanced' => 'Advanced (Slower, more comprehensive)'
        );
    }
    
    /**
     * Get API key (decrypted)
     *
     * @return string Decrypted API key
     */
    public function get_api_key() {
        $encrypted = get_option('gd_chatbot_tavily_api_key_encrypted', '');
        
        if (empty($encrypted)) {
            // Check for legacy unencrypted key
            $legacy_key = get_option('gd_chatbot_tavily_api_key', '');
            if (!empty($legacy_key) && strpos($legacy_key, 'tvly-') === 0) {
                // Migrate to encrypted storage
                $this->save_api_key($legacy_key);
                delete_option('gd_chatbot_tavily_api_key');
                return $legacy_key;
            }
            return '';
        }
        
        $decrypted = openssl_decrypt(
            base64_decode($encrypted),
            'AES-256-CBC',
            $this->encryption_key,
            0,
            $this->encryption_iv
        );
        
        return $decrypted !== false ? $decrypted : '';
    }
    
    /**
     * Save API key (encrypted)
     *
     * @param string $api_key Plain text API key
     * @return bool Success status
     */
    public function save_api_key($api_key) {
        if (empty($api_key)) {
            delete_option('gd_chatbot_tavily_api_key_encrypted');
            return true;
        }
        
        $encrypted = openssl_encrypt(
            $api_key,
            'AES-256-CBC',
            $this->encryption_key,
            0,
            $this->encryption_iv
        );
        
        return update_option('gd_chatbot_tavily_api_key_encrypted', base64_encode($encrypted));
    }
    
    /**
     * Get masked API key for display
     *
     * @return string Masked API key
     */
    public function get_api_key_masked() {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            return '';
        }
        
        $length = strlen($api_key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        
        return substr($api_key, 0, 4) . str_repeat('*', $length - 8) . substr($api_key, -4);
    }
    
    /**
     * Generate cache key
     *
     * @param string $query Search query
     * @param array $options Search options
     * @return string Cache key
     */
    private function get_cache_key($query, $options) {
        $key_data = array(
            'query' => $query,
            'depth' => isset($options['search_depth']) ? $options['search_depth'] : $this->search_depth,
            'max_results' => isset($options['max_results']) ? $options['max_results'] : $this->max_results,
        );
        
        return 'gd_chatbot_tavily_' . md5(json_encode($key_data));
    }
    
    /**
     * Check rate limit
     *
     * @return true|WP_Error True if OK, error if exceeded
     */
    private function check_rate_limit() {
        $usage = $this->get_usage();
        $quota = get_option('gd_chatbot_tavily_quota', 1000);
        
        if ($usage >= $quota) {
            return new WP_Error(
                'quota_exceeded',
                __('Monthly Tavily quota exceeded. Using cached results only.', 'gd-claude-chatbot')
            );
        }
        
        return true;
    }
    
    /**
     * Get current month usage
     *
     * @return int Number of API calls this month
     */
    public function get_usage() {
        $current_month = date('Y-m');
        $usage_key = 'gd_chatbot_tavily_usage_' . $current_month;
        return get_option($usage_key, 0);
    }
    
    /**
     * Increment usage counter
     */
    private function increment_usage() {
        $current_month = date('Y-m');
        $usage_key = 'gd_chatbot_tavily_usage_' . $current_month;
        
        $usage = get_option($usage_key, 0);
        update_option($usage_key, $usage + 1);
        
        // Check if approaching limit
        $quota = get_option('gd_chatbot_tavily_quota', 1000);
        if ($usage >= $quota * 0.8 && $usage < $quota * 0.81) {
            $this->send_quota_warning($usage, $quota);
        }
    }
    
    /**
     * Send quota warning email
     *
     * @param int $usage Current usage
     * @param int $quota Total quota
     */
    private function send_quota_warning($usage, $quota) {
        $admin_email = get_option('admin_email');
        $percentage = round(($usage / $quota) * 100, 1);
        
        $subject = __('GD Claude Chatbot: Tavily API Quota Warning', 'gd-claude-chatbot');
        $message = sprintf(
            __('Your Tavily API usage has reached %s%% (%d of %d calls). Consider upgrading your plan or optimizing usage.', 'gd-claude-chatbot'),
            $percentage,
            $usage,
            $quota
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Handle API errors
     *
     * @param int $status_code HTTP status code
     * @param array $data Response data
     * @return WP_Error Error object
     */
    private function handle_error($status_code, $data) {
        $error_message = isset($data['detail']) ? $data['detail'] : 'Unknown API error';
        
        switch ($status_code) {
            case 401:
                return new WP_Error(
                    'tavily_auth_failed',
                    __('Invalid Tavily API key. Please check your settings.', 'gd-claude-chatbot'),
                    array('status' => 401)
                );
                
            case 429:
                return new WP_Error(
                    'tavily_rate_limit',
                    __('Tavily API rate limit exceeded. Using cached results only.', 'gd-claude-chatbot'),
                    array('status' => 429)
                );
                
            case 500:
            case 503:
                return new WP_Error(
                    'tavily_server_error',
                    __('Tavily service temporarily unavailable. Continuing without real-time search.', 'gd-claude-chatbot'),
                    array('status' => $status_code)
                );
                
            default:
                return new WP_Error(
                    'tavily_unknown_error',
                    __('Unexpected error from Tavily API: ' . $error_message, 'gd-claude-chatbot'),
                    array('status' => $status_code, 'data' => $data)
                );
        }
    }
    
    /**
     * Assess source credibility for Grateful Dead information
     *
     * @param string $url Source URL
     * @return array Credibility assessment with tier and details
     */
    public function assess_source_credibility($url) {
        $domain = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        
        if (empty($domain)) {
            return array(
                'tier' => 'tier3',
                'category' => 'unknown',
                'description' => 'Unknown source'
            );
        }
        
        // Remove www. prefix for matching
        $domain = preg_replace('/^www\./', '', $domain);
        
        // ============================================================
        // TIER 1: OFFICIAL & ARCHIVAL SOURCES (Highest Credibility)
        // Primary sources, official archives, and academic institutions
        // ============================================================
        
        $tier1_sources = array(
            // Official Grateful Dead Sources
            'dead.net' => array(
                'category' => 'official',
                'description' => 'Official Grateful Dead website'
            ),
            'gdao.org' => array(
                'category' => 'archive',
                'description' => 'Grateful Dead Archive Online (UC Santa Cruz)'
            ),
            
            // Internet Archive - Live Music Archive
            'archive.org' => array(
                'category' => 'archive',
                'description' => 'Internet Archive / Live Music Archive',
                'path_check' => '/details/GratefulDead'
            ),
            
            // Academic & Scholarly Sources
            'gratefuldeadstudies.org' => array(
                'category' => 'academic',
                'description' => 'Grateful Dead Studies (peer-reviewed journal)'
            ),
            'library.ucsc.edu' => array(
                'category' => 'archive',
                'description' => 'UC Santa Cruz Library (Grateful Dead Archive)'
            ),
            'oac.cdlib.org' => array(
                'category' => 'archive',
                'description' => 'Online Archive of California (UCSC GD Archive)'
            ),
            
            // Band Member Official Sites
            'bobweir.net' => array(
                'category' => 'official',
                'description' => 'Bob Weir official website'
            ),
            'mickeyhart.net' => array(
                'category' => 'official',
                'description' => 'Mickey Hart official website'
            ),
            'billkreutzmann.com' => array(
                'category' => 'official',
                'description' => 'Bill Kreutzmann official website'
            ),
            'philzone.com' => array(
                'category' => 'official',
                'description' => 'Phil Lesh official zone'
            ),
            
            // Major News Outlets (for GD news)
            'apnews.com' => array(
                'category' => 'news',
                'description' => 'Associated Press'
            ),
            'reuters.com' => array(
                'category' => 'news',
                'description' => 'Reuters'
            ),
            'npr.org' => array(
                'category' => 'news',
                'description' => 'National Public Radio'
            ),
        );
        
        // ============================================================
        // TIER 2: TRUSTED COMMUNITY & REFERENCE SOURCES
        // Well-established fan databases, setlist resources, encyclopedias
        // ============================================================
        
        $tier2_sources = array(
            // Setlist & Performance Databases
            'setlist.fm' => array(
                'category' => 'database',
                'description' => 'Setlist.fm concert database'
            ),
            'deadlists.com' => array(
                'category' => 'database',
                'description' => 'DeadLists setlist database'
            ),
            'jerrybase.com' => array(
                'category' => 'database',
                'description' => 'JerryBase - Jerry Garcia performances database'
            ),
            'headyversion.com' => array(
                'category' => 'database',
                'description' => 'HeadyVersion - Best versions voting'
            ),
            'whitegum.com' => array(
                'category' => 'database',
                'description' => 'Whitegum Dead encyclopedic resource'
            ),
            'deaddisc.com' => array(
                'category' => 'database',
                'description' => 'DeadDisc discography database'
            ),
            'deadsources.com' => array(
                'category' => 'database',
                'description' => 'Dead Sources resource site'
            ),
            'relisten.net' => array(
                'category' => 'database',
                'description' => 'Relisten streaming service'
            ),
            'etree.org' => array(
                'category' => 'database',
                'description' => 'etree lossless music community'
            ),
            
            // Reference & Encyclopedia Sources
            'britannica.com' => array(
                'category' => 'encyclopedia',
                'description' => 'Encyclopedia Britannica'
            ),
            'allmusic.com' => array(
                'category' => 'encyclopedia',
                'description' => 'AllMusic database'
            ),
            'discogs.com' => array(
                'category' => 'database',
                'description' => 'Discogs music database'
            ),
            
            // Trusted Music Publications
            'rollingstone.com' => array(
                'category' => 'publication',
                'description' => 'Rolling Stone magazine'
            ),
            'relix.com' => array(
                'category' => 'publication',
                'description' => 'Relix magazine (jam band focus)'
            ),
            'jambands.com' => array(
                'category' => 'publication',
                'description' => 'JamBands.com'
            ),
            'jambase.com' => array(
                'category' => 'publication',
                'description' => 'JamBase concert listings & news'
            ),
            'gratefulweb.com' => array(
                'category' => 'publication',
                'description' => 'Grateful Web news site'
            ),
            'deadcentral.com' => array(
                'category' => 'publication',
                'description' => 'Dead Central website'
            ),
            'gdhour.com' => array(
                'category' => 'publication',
                'description' => 'GD Hour podcast'
            ),
            
            // Wikipedia (well-sourced for GD)
            'wikipedia.org' => array(
                'category' => 'encyclopedia',
                'description' => 'Wikipedia'
            ),
            'en.wikipedia.org' => array(
                'category' => 'encyclopedia',
                'description' => 'Wikipedia (English)'
            ),
            
            // Major News Outlets
            'nytimes.com' => array(
                'category' => 'news',
                'description' => 'New York Times'
            ),
            'sfchronicle.com' => array(
                'category' => 'news',
                'description' => 'San Francisco Chronicle (local GD coverage)'
            ),
            'sfgate.com' => array(
                'category' => 'news',
                'description' => 'SFGate (San Francisco news)'
            ),
            'billboard.com' => array(
                'category' => 'publication',
                'description' => 'Billboard magazine'
            ),
            'cbsnews.com' => array(
                'category' => 'news',
                'description' => 'CBS News'
            ),
            'nbcnews.com' => array(
                'category' => 'news',
                'description' => 'NBC News'
            ),
            
            // Book Publishers (for GD scholarship)
            'bloomsbury.com' => array(
                'category' => 'academic',
                'description' => 'Bloomsbury Publishing (GD academic books)'
            ),
            
            // Streaming & Audio Services
            'nugs.net' => array(
                'category' => 'database',
                'description' => 'Nugs.net streaming service'
            ),
            'spotify.com' => array(
                'category' => 'streaming',
                'description' => 'Spotify (official releases)'
            ),
            'applemusic.com' => array(
                'category' => 'streaming',
                'description' => 'Apple Music (official releases)'
            ),
        );
        
        // ============================================================
        // TIER 3: COMMUNITY & FAN SOURCES
        // Forums, fan sites, social media - useful but verify
        // ============================================================
        
        $tier3_sources = array(
            // Fan Communities & Forums
            'dead.net/forum' => array(
                'category' => 'community',
                'description' => 'Dead.net Forums'
            ),
            'lostliveddead.blogspot.com' => array(
                'category' => 'blog',
                'description' => 'Lost Live Dead blog'
            ),
            'deadessays.blogspot.com' => array(
                'category' => 'blog',
                'description' => 'Dead Essays blog'
            ),
            'thedeadblog.com' => array(
                'category' => 'blog',
                'description' => 'The Dead Blog'
            ),
            
            // Social Media
            'facebook.com' => array(
                'category' => 'social',
                'description' => 'Facebook'
            ),
            'twitter.com' => array(
                'category' => 'social',
                'description' => 'Twitter/X'
            ),
            'x.com' => array(
                'category' => 'social',
                'description' => 'X (formerly Twitter)'
            ),
            'instagram.com' => array(
                'category' => 'social',
                'description' => 'Instagram'
            ),
            'youtube.com' => array(
                'category' => 'video',
                'description' => 'YouTube'
            ),
            
            // General Music Sites
            'genius.com' => array(
                'category' => 'lyrics',
                'description' => 'Genius lyrics'
            ),
            'songfacts.com' => array(
                'category' => 'trivia',
                'description' => 'SongFacts'
            ),
        );
        
        // Check Tier 1 sources
        foreach ($tier1_sources as $source_domain => $info) {
            if (strpos($domain, $source_domain) !== false) {
                // Check path if required
                if (isset($info['path_check']) && !empty($path)) {
                    if (strpos($path, $info['path_check']) === false) {
                        continue; // Path doesn't match, skip
                    }
                }
                return array(
                    'tier' => 'tier1',
                    'category' => $info['category'],
                    'description' => $info['description'],
                    'domain' => $domain
                );
            }
        }
        
        // Check Tier 2 sources
        foreach ($tier2_sources as $source_domain => $info) {
            if (strpos($domain, $source_domain) !== false) {
                return array(
                    'tier' => 'tier2',
                    'category' => $info['category'],
                    'description' => $info['description'],
                    'domain' => $domain
                );
            }
        }
        
        // Check Tier 3 sources
        foreach ($tier3_sources as $source_domain => $info) {
            if (strpos($domain, $source_domain) !== false) {
                // Check path if required
                if (isset($info['path_check']) && !empty($path)) {
                    if (strpos($path, $info['path_check']) === false) {
                        // Path doesn't match, demote to tier4
                        return array(
                            'tier' => 'tier4',
                            'category' => $info['category'],
                            'description' => $info['description'] . ' (non-GD content)',
                            'domain' => $domain
                        );
                    }
                }
                return array(
                    'tier' => 'tier3',
                    'category' => $info['category'],
                    'description' => $info['description'],
                    'domain' => $domain
                );
            }
        }
        
        // Tier 4: Unknown sources - require verification
        return array(
            'tier' => 'tier4',
            'category' => 'unknown',
            'description' => 'Unknown source - verify information',
            'domain' => $domain
        );
    }
    
    /**
     * Get simple tier string (for backward compatibility)
     *
     * @param string $url Source URL
     * @return string Credibility tier (tier1, tier2, tier3, tier4)
     */
    public function get_source_tier($url) {
        $assessment = $this->assess_source_credibility($url);
        return is_array($assessment) ? $assessment['tier'] : $assessment;
    }
    
    /**
     * Get credibility tier label
     *
     * @param string $tier Tier identifier
     * @return string Human-readable label
     */
    public static function get_tier_label($tier) {
        $labels = array(
            'tier1' => '⭐ Official/Archival Source',
            'tier2' => '✓ Trusted Reference',
            'tier3' => '○ Community Source',
            'tier4' => '? Unverified Source'
        );
        return isset($labels[$tier]) ? $labels[$tier] : '? Unknown';
    }
    
    /**
     * Get all trusted Grateful Dead domains for search filtering
     *
     * @return array List of trusted domains
     */
    public static function get_trusted_gd_domains() {
        return array(
            // Official & Archives
            // NOTE: Tavily searches these sites ON BEHALF of the user
            // Users should NOT be directed to search these manually
            'dead.net',
            'gdao.org',
            'archive.org',              // Has actual audio files and durations
            'library.ucsc.edu',
            'oac.cdlib.org',
            
            // Setlist & Performance Databases
            // These sites are searched automatically by Tavily
            'setlist.fm',
            'deadlists.com',
            'jerrybase.com',            // Has setlists and performance notes
            'headyversion.com',         // Best versions and ratings
            'whitegum.com',
            'deaddisc.com',
            'deadsources.com',
            'relisten.net',
            'etree.org',
            'herbibot.com',             // Advanced search and filtering
            'gratefulstats.com',        // Statistical breakdowns by year
            
            // Reference & Encyclopedias
            'allmusic.com',
            'discogs.com',
            'wikipedia.org',
            'britannica.com',
            
            // Publications & News
            'rollingstone.com',
            'relix.com',
            'jambands.com',
            'jambase.com',
            'gratefulweb.com',
            'deadcentral.com',
            'gdhour.com',
            'sfchronicle.com',
            'sfgate.com',
            'billboard.com',
            
            // Academic & Scholarly
            'gratefuldeadstudies.org',
            'bloomsbury.com',
            
            // Streaming Services
            'nugs.net',
            'spotify.com',
            'applemusic.com',
            
            // Band Member Sites
            'bobweir.net',
            'mickeyhart.net',
            'billkreutzmann.com',
            'philzone.com',
            
            // Major News
            'apnews.com',
            'reuters.com',
            'npr.org',
            'nytimes.com',
            'cbsnews.com',
            'nbcnews.com',
        );
    }
    
    /**
     * Clear all Tavily cache
     *
     * @return bool Success status
     */
    public function clear_cache() {
        global $wpdb;
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 OR option_name LIKE %s",
                '_transient_gd_chatbot_tavily_%',
                '_transient_timeout_gd_chatbot_tavily_%'
            )
        );
        
        return $result !== false;
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache stats
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                '_transient_gd_chatbot_tavily_%'
            )
        );
        
        $size = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                '_transient_gd_chatbot_tavily_%'
            )
        );
        
        return array(
            'count' => intval($count),
            'size' => intval($size),
            'size_formatted' => size_format($size, 2),
        );
    }
    
    /**
     * Add Grateful Dead context to search query
     * CRITICAL: This ensures ALL searches are about the Grateful Dead
     * 
     * @param string $query Original query
     * @return string Contextualized query
     */
    private function add_grateful_dead_context($query) {
        $query_lower = strtolower($query);
        
        // Check if query already mentions Grateful Dead or related terms
        $gd_terms = array(
            'grateful dead',
            'the dead',
            'jerry garcia',
            'bob weir',
            'phil lesh',
            'bill kreutzmann',
            'mickey hart',
            'pigpen',
            'brent mydland',
            'keith godchaux',
            'donna godchaux',
            'vince welnick',
            'tom constanten',
            'robert hunter',
            'john perry barlow',
            'deadhead',
            'gdao',
            'dead.net',
        );
        
        $already_has_context = false;
        foreach ($gd_terms as $term) {
            if (strpos($query_lower, $term) !== false) {
                $already_has_context = true;
                break;
            }
        }
        
        // If query already has GD context, return as-is
        if ($already_has_context) {
            return $query;
        }
        
        // Detect query type and add appropriate context
        $query_type = $this->detect_query_type($query_lower);
        
        switch ($query_type) {
            case 'song':
                // For song queries, be very explicit
                return 'Grateful Dead song "' . $query . '" performances versions history';
                
            case 'venue':
                // For venue queries
                return 'Grateful Dead concerts at ' . $query . ' venue shows performances';
                
            case 'equipment':
                // For equipment queries
                return 'Grateful Dead ' . $query . ' guitar bass equipment gear';
                
            case 'person':
                // For person queries (might be band member)
                return $query . ' Grateful Dead band member biography';
                
            case 'recording':
                // For recording queries
                return 'Grateful Dead ' . $query . ' recordings tapes archive';
                
            case 'general':
            default:
                // For general queries, prepend "Grateful Dead"
                return 'Grateful Dead ' . $query;
        }
    }
    
    /**
     * Detect the type of query to add appropriate context
     * 
     * @param string $query_lower Lowercase query
     * @return string Query type
     */
    private function detect_query_type($query_lower) {
        // Song-related keywords
        $song_keywords = array('song', 'version', 'performance', 'played', 'longest', 'best', 'setlist');
        foreach ($song_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'song';
            }
        }
        
        // Venue-related keywords
        $venue_keywords = array('venue', 'shows at', 'concerts at', 'played at', 'theatre', 'theater', 'hall', 'arena', 'stadium', 'coliseum');
        foreach ($venue_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'venue';
            }
        }
        
        // Equipment-related keywords
        $equipment_keywords = array('guitar', 'bass', 'tiger', 'wolf', 'rosebud', 'alligator', 'wall of sound', 'equipment', 'gear', 'instrument');
        foreach ($equipment_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'equipment';
            }
        }
        
        // Recording-related keywords
        $recording_keywords = array('recording', 'tape', 'betty board', 'sbd', 'aud', 'archive', 'stream', 'listen', 'download');
        foreach ($recording_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'recording';
            }
        }
        
        // Person-related keywords (might be asking about band members)
        $person_keywords = array('who is', 'who was', 'biography', 'bio', 'member', 'musician', 'keyboardist', 'drummer', 'bassist', 'guitarist');
        foreach ($person_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'person';
            }
        }
        
        return 'general';
    }
    
    /**
     * Get domains to exclude from search
     * Filters out sites that commonly have non-GD content
     * 
     * @return array Domains to exclude
     */
    private function get_exclude_domains() {
        return array(
            // Generic music sites that might have other artists
            'lyrics.com',
            'azlyrics.com',
            'genius.com',
            'songmeanings.com',
            'metrolyrics.com',
            
            // Sites that commonly confuse searches
            'wikipedia.org', // Too broad, prefer GD-specific sources
            'youtube.com',   // Too many non-GD videos
            'spotify.com',   // Mixed content
            'allmusic.com',  // Covers all artists
            
            // Social media (too much noise)
            'facebook.com',
            'twitter.com',
            'instagram.com',
            'reddit.com', // Removed as credible source in v1.8.1
            
            // Shopping/commercial sites
            'amazon.com',
            'ebay.com',
            'etsy.com',
            
            // Generic news that might have other content
            'cnn.com',
            'bbc.com',
            'nytimes.com', // Unless specifically about GD
        );
    }
}
