<?php
/**
 * Context Builder Class
 *
 * Builds minimal, token-efficient AI context based on query intent.
 * Uses TokenBudgetManager to enforce hard limits.
 *
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Context_Builder {

    /**
     * Query optimizer instance
     */
    private $optimizer;

    /**
     * Cache service instance
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->optimizer = new GD_Query_Optimizer();
        $this->cache = new GD_Context_Cache();
    }

    /**
     * Build optimized context for a query.
     *
     * @param string $query User query
     * @param array $options Additional options (setlist_results, pinecone_results, tavily_results, etc.)
     * @return array Array with 'context', 'debug_info', 'intent', 'sources_used'
     */
    public function build_context($query, $options = array()) {
        $intent = $this->optimizer->detect_intent($query);
        $required_sources = $this->optimizer->get_required_sources($intent);

        $token_budget = (int) get_option('gd_chatbot_v2_token_budget', 500);
        $budget = new GD_Token_Budget_Manager($token_budget);

        // 1. Base context (CRITICAL — always included)
        $base_context = $this->build_base_context();
        $budget->add('base', $base_context, GD_Token_Budget_Manager::PRIORITY_CRITICAL);

        // 2. Add query-specific context based on intent
        foreach ($required_sources as $source) {
            if ($source === GD_Query_Optimizer::SOURCE_BASE) {
                continue; // Already added
            }

            $source_context = $this->build_source_context($source, $query, $options);

            if (!empty($source_context)) {
                $priority = $this->get_source_priority($source, $intent);
                $budget->add($source, $source_context, $priority);
            }
        }

        // 3. Add knowledge base if needed and space allows
        if ($this->optimizer->needs_knowledge_base($query) &&
            isset($options['pinecone_results']) &&
            !empty($options['pinecone_results'])) {

            $kb_context = $this->build_knowledge_base_context($options['pinecone_results'], $budget->get_remaining_tokens());

            if (!empty($kb_context)) {
                $budget->add_truncated('knowledge_base', $kb_context, GD_Token_Budget_Manager::PRIORITY_MEDIUM);
            }
        }

        // 4. Add Pinecone results if provided and not already added via knowledge_base
        if (!$this->optimizer->needs_knowledge_base($query) &&
            isset($options['pinecone_results']) &&
            !empty($options['pinecone_results']) &&
            in_array(GD_Query_Optimizer::SOURCE_KNOWLEDGE_BASE, $required_sources)) {

            $kb_context = $this->build_knowledge_base_context($options['pinecone_results'], $budget->get_remaining_tokens());

            if (!empty($kb_context)) {
                $budget->add_truncated('pinecone_kb', $kb_context, GD_Token_Budget_Manager::PRIORITY_MEDIUM);
            }
        }

        // 5. Add web search if needed and space allows
        if ($this->optimizer->needs_web_search($query) &&
            isset($options['tavily_results']) &&
            !empty($options['tavily_results'])) {

            $web_context = $this->build_web_search_context($options['tavily_results'], $budget->get_remaining_tokens());

            if (!empty($web_context)) {
                $budget->add_truncated('web_search', $web_context, GD_Token_Budget_Manager::PRIORITY_LOW);
            }
        }

        // 6. Add Tavily results even if not flagged by needs_web_search, if they were provided
        if (!$this->optimizer->needs_web_search($query) &&
            isset($options['tavily_results']) &&
            !empty($options['tavily_results'])) {

            $web_context = $this->build_web_search_context($options['tavily_results'], $budget->get_remaining_tokens());

            if (!empty($web_context)) {
                $budget->add_truncated('tavily_results', $web_context, GD_Token_Budget_Manager::PRIORITY_LOW);
            }
        }

        $debug_info = $budget->get_debug_info();

        return array(
            'context' => $budget->build(),
            'debug_info' => $debug_info,
            'intent' => $intent,
            'sources_used' => array_column($debug_info['fragments'], 'label')
        );
    }

    /**
     * Build minimal base context with condensed accuracy guardrails.
     *
     * @return string Base context
     */
    private function build_base_context() {
        $cache_key = 'base_context_v2';
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $context = "You are an expert on the Grateful Dead (1965-1995). ";
        $context .= "You have access to complete setlist data, song information, and band history. ";
        $context .= "Provide accurate, helpful responses based on the context provided.\n\n";

        // Condensed accuracy guardrails (critical for response quality)
        $context .= "RULES: ";
        $context .= "Never mention knowledge base, database, Pinecone, Tavily, context files, system prompt, or technical retrieval details. ";
        $context .= "Respond naturally as if you simply know the information. ";
        $context .= "Context and web search results override training data 100%. ";
        $context .= "Only use location info from provided context or web results — never infer locations from training data. ";
        $context .= "Acknowledge uncertainty rather than provide incorrect information.\n\n";

        $context .= "BAHR GALLERY: Located in Oyster Bay, Long Island, NY (ONLY location). ";
        $context .= "Never state any other location. If web search says different, use Oyster Bay.";

        $this->cache->set($cache_key, $context, 86400); // 24 hours

        return $context;
    }

    /**
     * Build context for a specific source.
     *
     * @param string $source Source type
     * @param string $query User query
     * @param array $options Additional options
     * @return string Source context
     */
    private function build_source_context($source, $query, $options) {
        switch ($source) {
            case GD_Query_Optimizer::SOURCE_SETLIST_DB:
                return $this->build_setlist_context($query, $options);

            case GD_Query_Optimizer::SOURCE_SONG_GUIDE:
                return $this->build_song_guide_context($query);

            case GD_Query_Optimizer::SOURCE_EQUIPMENT:
                return $this->build_equipment_context();

            case GD_Query_Optimizer::SOURCE_BAND_INFO:
                return $this->build_band_info_context();

            case GD_Query_Optimizer::SOURCE_VENUES:
                return $this->build_venues_context();

            default:
                return '';
        }
    }

    /**
     * Build setlist context. Handles both markdown strings and structured arrays.
     *
     * @param string $query User query
     * @param array $options Additional options
     * @return string Setlist context
     */
    private function build_setlist_context($query, $options) {
        if (!isset($options['setlist_results']) || empty($options['setlist_results'])) {
            return '';
        }

        $data = $options['setlist_results'];

        // Handle markdown string from existing search() or search_compact()
        if (is_string($data)) {
            return GD_Token_Estimator::truncate($data, 200);
        }

        // Handle structured array
        if (is_array($data)) {
            $context = "## Setlist Data\n\n";
            $shows = array_slice($data, 0, 3);

            foreach ($shows as $show) {
                $date = isset($show['date']) ? $show['date'] : (isset($show['Date']) ? $show['Date'] : '');
                $venue = isset($show['venue']) ? $show['venue'] : (isset($show['Venue Name']) ? $show['Venue Name'] : '');
                $location = isset($show['location']) ? $show['location'] : (isset($show['Venue Location']) ? $show['Venue Location'] : '');
                $songs = isset($show['songs']) ? $show['songs'] : array();

                if (is_string($songs)) {
                    $songs = array_map('trim', explode(',', $songs));
                }

                $context .= "**{$date}** - {$venue}";
                if (!empty($location)) {
                    $context .= ", {$location}";
                }
                $context .= ': ';
                $context .= implode(', ', array_slice($songs, 0, 10));
                if (count($songs) > 10) {
                    $context .= '...';
                }
                $context .= "\n";
            }

            return $context;
        }

        return '';
    }

    /**
     * Build compact song guide context.
     *
     * @param string $query User query
     * @return string Song guide context
     */
    private function build_song_guide_context($query) {
        $cache_key = 'song_guide_v1';
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Load minimal song guide (top 50 songs)
        $song_guide_file = GD_CHATBOT_PLUGIN_DIR . 'context/reference/songs.csv';

        if (!file_exists($song_guide_file)) {
            return '';
        }

        $songs = array();
        $handle = fopen($song_guide_file, 'r');
        if ($handle === false) {
            return '';
        }

        $header = fgetcsv($handle);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false && $count < 50) {
            if (!empty($row[0])) {
                $songs[] = $row[0]; // Song name only
            }
            $count++;
        }

        fclose($handle);

        if (empty($songs)) {
            return '';
        }

        $context = "Top songs: " . implode(', ', $songs);

        $this->cache->set($cache_key, $context, 86400); // 24 hours

        return $context;
    }

    /**
     * Build compact equipment context.
     *
     * @return string Equipment context
     */
    private function build_equipment_context() {
        $cache_key = 'equipment_v1';
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $context = "Jerry's gear: Alligator Strat, Tiger, Wolf, Rosebud, Lightning Bolt. ";
        $context .= "Wall of Sound (1974): massive PA system designed by Owsley Stanley. ";
        $context .= "Phil Lesh: Alembic basses, Guild Starfire. ";
        $context .= "Bob Weir: Gibson ES-335, Ibanez Cowboy. ";
        $context .= "Mickey Hart & Bill Kreutzmann: Gretsch, Remo, The Beast, The Beam.";

        $this->cache->set($cache_key, $context, 86400);

        return $context;
    }

    /**
     * Build compact band info context.
     *
     * @return string Band info context
     */
    private function build_band_info_context() {
        $cache_key = 'band_info_v1';
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $context = "Core members: Jerry Garcia (lead guitar, vocals, 1965-95), Bob Weir (rhythm guitar, vocals, 1965-95), ";
        $context .= "Phil Lesh (bass, vocals, 1965-95), Bill Kreutzmann (drums, 1965-95), Mickey Hart (drums, 1967-71, 1974-95). ";
        $context .= "Keyboardists: Pigpen/Ron McKernan (1965-72, died 1973), Tom Constanten (1968-70), ";
        $context .= "Keith Godchaux (1971-79, died 1980), Donna Jean Godchaux (vocals, 1972-79), ";
        $context .= "Brent Mydland (1979-90, died 1990), Vince Welnick (1990-95). ";
        $context .= "Lyricists: Robert Hunter, John Perry Barlow.";

        $this->cache->set($cache_key, $context, 86400);

        return $context;
    }

    /**
     * Build compact venues context.
     *
     * @return string Venues context
     */
    private function build_venues_context() {
        $cache_key = 'venues_v1';
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $context = "Key venues: Fillmore West/East (SF/NYC), Winterland (SF, 1968-78), ";
        $context .= "Red Rocks (Morrison CO), Madison Square Garden (NYC), ";
        $context .= "Barton Hall/Cornell (Ithaca NY, famous 5/8/77 show), ";
        $context .= "Capitol Theatre (Port Chester NY), Greek Theatre (Berkeley CA), ";
        $context .= "Shoreline Amphitheatre (Mountain View CA), Alpine Valley (East Troy WI), ";
        $context .= "Soldier Field (Chicago, final show 7/9/95), Oakland Coliseum (Oakland CA).";

        $this->cache->set($cache_key, $context, 86400);

        return $context;
    }

    /**
     * Build compact knowledge base context from Pinecone results.
     *
     * @param array $results Pinecone results
     * @param int $remaining_tokens Remaining token budget
     * @return string KB context
     */
    private function build_knowledge_base_context($results, $remaining_tokens) {
        if (empty($results['matches'])) {
            return '';
        }

        $context = '';
        $max_chunks = 3;
        $max_tokens_per_chunk = min(100, (int) ($remaining_tokens / $max_chunks));

        if ($max_tokens_per_chunk < 20) {
            return '';
        }

        foreach (array_slice($results['matches'], 0, $max_chunks) as $match) {
            // Handle multiple metadata key formats
            $chunk = '';
            if (isset($match['metadata']['text'])) {
                $chunk = $match['metadata']['text'];
            } elseif (isset($match['metadata']['content'])) {
                $chunk = $match['metadata']['content'];
            } elseif (isset($match['metadata']['chunk'])) {
                $chunk = $match['metadata']['chunk'];
            }

            if (empty($chunk)) {
                continue;
            }

            $truncated = GD_Token_Estimator::truncate($chunk, $max_tokens_per_chunk);
            $context .= $truncated . "\n\n";
        }

        return trim($context);
    }

    /**
     * Build compact web search context from Tavily results.
     *
     * @param array $results Tavily results
     * @param int $remaining_tokens Remaining token budget
     * @return string Web search context
     */
    private function build_web_search_context($results, $remaining_tokens) {
        if (empty($results['results'])) {
            return '';
        }

        $context = "## Web Search Results\n\n";
        $max_results = 2;
        $max_tokens_per_result = min(50, (int) ($remaining_tokens / $max_results));

        if ($max_tokens_per_result < 20) {
            return '';
        }

        foreach (array_slice($results['results'], 0, $max_results) as $result) {
            $title = isset($result['title']) ? $result['title'] : 'Untitled';
            $url = isset($result['url']) ? $result['url'] : '';
            $snippet = isset($result['content']) ? $result['content'] : '';

            $truncated_snippet = GD_Token_Estimator::truncate($snippet, $max_tokens_per_result);

            $context .= "**{$title}** ({$url}): {$truncated_snippet}\n\n";
        }

        return trim($context);
    }

    /**
     * Get priority for a source based on intent.
     *
     * @param string $source Source type
     * @param string $intent Query intent
     * @return int Priority level
     */
    private function get_source_priority($source, $intent) {
        // Intent-specific prioritization: primary source for the intent gets HIGH
        $primary_map = array(
            GD_Query_Optimizer::INTENT_SETLIST => GD_Query_Optimizer::SOURCE_SETLIST_DB,
            GD_Query_Optimizer::INTENT_SONG => GD_Query_Optimizer::SOURCE_SONG_GUIDE,
            GD_Query_Optimizer::INTENT_EQUIPMENT => GD_Query_Optimizer::SOURCE_EQUIPMENT,
            GD_Query_Optimizer::INTENT_BAND_MEMBER => GD_Query_Optimizer::SOURCE_BAND_INFO,
            GD_Query_Optimizer::INTENT_VENUE => GD_Query_Optimizer::SOURCE_VENUES,
            GD_Query_Optimizer::INTENT_TOUR => GD_Query_Optimizer::SOURCE_SETLIST_DB,
            GD_Query_Optimizer::INTENT_TRIVIA => GD_Query_Optimizer::SOURCE_SETLIST_DB,
            GD_Query_Optimizer::INTENT_ERA => GD_Query_Optimizer::SOURCE_KNOWLEDGE_BASE,
        );

        if (isset($primary_map[$intent]) && $primary_map[$intent] === $source) {
            return GD_Token_Budget_Manager::PRIORITY_HIGH;
        }

        // Default medium priority
        return GD_Token_Budget_Manager::PRIORITY_MEDIUM;
    }
}
