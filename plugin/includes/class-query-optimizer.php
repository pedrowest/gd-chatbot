<?php
/**
 * Query Optimizer Class
 *
 * Detects query intent to load only relevant context into AI prompts.
 * Reduces token usage by skipping irrelevant data sources per query type.
 *
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Query_Optimizer {

    /**
     * Query intent types
     */
    const INTENT_SETLIST = 'setlist';
    const INTENT_SONG = 'song';
    const INTENT_TOUR = 'tour';
    const INTENT_BAND_MEMBER = 'band_member';
    const INTENT_VENUE = 'venue';
    const INTENT_ERA = 'era';
    const INTENT_EQUIPMENT = 'equipment';
    const INTENT_TRIVIA = 'trivia';
    const INTENT_BAHR_GALLERY = 'bahr_gallery';
    const INTENT_GENERAL = 'general';

    /**
     * Context sources
     */
    const SOURCE_BASE = 'base';
    const SOURCE_SETLIST_DB = 'setlist_db';
    const SOURCE_SONG_GUIDE = 'song_guide';
    const SOURCE_EQUIPMENT = 'equipment';
    const SOURCE_VENUES = 'venues';
    const SOURCE_BAND_INFO = 'band_info';
    const SOURCE_KNOWLEDGE_BASE = 'knowledge_base';
    const SOURCE_WEB_SEARCH = 'web_search';

    /**
     * Detect the primary intent of a user query.
     *
     * @param string $query User query
     * @return string Intent type (use class constants)
     */
    public function detect_intent($query) {
        $q = strtolower($query);

        // Bahr Gallery queries (check first — very specific)
        if ($this->matches_any($q, array(
            'bahr gallery', 'bahr', 'oyster bay gallery'
        ))) {
            return self::INTENT_BAHR_GALLERY;
        }

        // Band member queries (check before song/setlist to avoid false matches on "played")
        if ($this->matches_any($q, array(
            'jerry garcia', 'bob weir', 'phil lesh', 'bill kreutzmann',
            'mickey hart', 'pigpen', 'keith godchaux', 'donna godchaux',
            'brent mydland', 'vince welnick', 'bruce hornsby', 'tom constanten',
            'ron mckernan'
        ))) {
            return self::INTENT_BAND_MEMBER;
        }

        // Venue queries
        if ($this->matches_any($q, array(
            'fillmore', 'winterland', 'red rocks', 'madison square garden',
            'capitol theatre', 'oakland coliseum', 'soldier field',
            'greek theatre', 'shoreline', 'alpine valley', 'barton hall'
        ))) {
            return self::INTENT_VENUE;
        }

        // Setlist queries
        if ($this->matches_any($q, array(
            'setlist', 'set list', 'played on', 'concert', 'what did they play',
            'songs played', 'performance', 'what was the encore'
        ))) {
            return self::INTENT_SETLIST;
        }

        // Check for date patterns (strong setlist indicator)
        if (preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $q)) {
            return self::INTENT_SETLIST;
        }

        // Song queries
        if ($this->matches_any($q, array(
            'song', 'track', 'tune', 'cover', 'original', 'written by',
            'first played', 'last played', 'how many times', 'versions of',
            'lyrics', 'jam', 'segue'
        ))) {
            return self::INTENT_SONG;
        }

        // Tour queries
        if ($this->matches_any($q, array(
            'tour', 'spring tour', 'fall tour', 'summer tour', 'winter tour',
            'europe tour', 'shows in', 'played in'
        ))) {
            return self::INTENT_TOUR;
        }

        // Era queries
        if ($this->matches_any($q, array(
            'primal dead', 'anthem years', 'europe 72', 'hiatus',
            'donna era', 'brent era', 'early 70s', 'late 80s', '1970s', '1980s'
        ))) {
            return self::INTENT_ERA;
        }

        // Equipment queries
        if ($this->matches_any($q, array(
            'gear', 'equipment', 'guitar', 'bass', 'drums', 'keyboard',
            'amplifier', 'wall of sound', 'instrument', 'setup',
            'alligator', 'tiger', 'wolf', 'alembic', 'midi'
        ))) {
            return self::INTENT_EQUIPMENT;
        }

        // Trivia queries
        if ($this->matches_any($q, array(
            'how many', 'most played', 'least played', 'statistics',
            'fun fact', 'did you know', 'trivia', 'record'
        ))) {
            return self::INTENT_TRIVIA;
        }

        return self::INTENT_GENERAL;
    }

    /**
     * Get required context sources for a given intent.
     *
     * @param string $intent Intent type
     * @return array List of required sources
     */
    public function get_required_sources($intent) {
        $sources = array(self::SOURCE_BASE); // Always include base

        switch ($intent) {
            case self::INTENT_SETLIST:
                $sources[] = self::SOURCE_SETLIST_DB;
                $sources[] = self::SOURCE_SONG_GUIDE;
                break;

            case self::INTENT_SONG:
                $sources[] = self::SOURCE_SONG_GUIDE;
                $sources[] = self::SOURCE_SETLIST_DB;
                break;

            case self::INTENT_TOUR:
                $sources[] = self::SOURCE_SETLIST_DB;
                break;

            case self::INTENT_BAND_MEMBER:
                $sources[] = self::SOURCE_BAND_INFO;
                $sources[] = self::SOURCE_KNOWLEDGE_BASE;
                break;

            case self::INTENT_VENUE:
                $sources[] = self::SOURCE_VENUES;
                $sources[] = self::SOURCE_SETLIST_DB;
                break;

            case self::INTENT_ERA:
                $sources[] = self::SOURCE_KNOWLEDGE_BASE;
                $sources[] = self::SOURCE_BAND_INFO;
                break;

            case self::INTENT_EQUIPMENT:
                $sources[] = self::SOURCE_EQUIPMENT;
                break;

            case self::INTENT_TRIVIA:
                $sources[] = self::SOURCE_SETLIST_DB;
                break;

            case self::INTENT_BAHR_GALLERY:
                // Bahr Gallery is handled by system prompt guardrails only
                break;

            case self::INTENT_GENERAL:
                $sources[] = self::SOURCE_KNOWLEDGE_BASE;
                break;
        }

        return $sources;
    }

    /**
     * Check if query needs web search (current info, pricing, availability).
     *
     * @param string $query User query
     * @return bool True if web search recommended
     */
    public function needs_web_search($query) {
        $q = strtolower($query);
        return $this->matches_any($q, array(
            '2025', '2026', '2027', 'latest', 'current', 'buy', 'tickets',
            'price', 'where to', 'this year', 'upcoming', 'dead & company',
            'dead and company', 'dead & co'
        ));
    }

    /**
     * Check if query needs knowledge base (detailed reference material).
     *
     * @param string $query User query
     * @return bool True if KB recommended
     */
    public function needs_knowledge_base($query) {
        $q = strtolower($query);
        return $this->matches_any($q, array(
            'history', 'biography', 'story', 'background', 'influence',
            'legacy', 'documentary', 'book', 'interview'
        ));
    }

    /**
     * Helper: Check if text matches any keywords.
     *
     * @param string $text Text to check
     * @param array $keywords Keywords to match
     * @return bool True if any keyword matches
     */
    private function matches_any($text, $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
}
