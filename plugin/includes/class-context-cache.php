<?php
/**
 * Context Cache Class
 *
 * In-memory cache for AI context fragments with TTL expiration.
 * Uses WordPress Transients API for persistence.
 * Target: 70-80% cache hit rate.
 *
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Context_Cache {

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'gd_chatbot_v2_ctx_';

    /**
     * Maximum cache entries
     */
    const MAX_ENTRIES = 100;

    /**
     * Statistics
     */
    private $hits = 0;
    private $misses = 0;

    /**
     * Get a cached context fragment.
     *
     * @param string $key Cache key
     * @return string|false Cached value or false if not found/expired
     */
    public function get($key) {
        $cache_key = self::CACHE_PREFIX . $key;
        $value = get_transient($cache_key);

        if ($value !== false) {
            $this->hits++;
            return $value;
        }

        $this->misses++;
        return false;
    }

    /**
     * Cache a context fragment with TTL.
     *
     * @param string $key Cache key
     * @param string $value Value to cache
     * @param int $ttl Time to live in seconds (default: 3600 = 1 hour)
     */
    public function set($key, $value, $ttl = 3600) {
        $cache_key = self::CACHE_PREFIX . $key;
        set_transient($cache_key, $value, $ttl);
    }

    /**
     * Invalidate a specific cache entry.
     *
     * @param string $key Cache key
     */
    public function invalidate($key) {
        $cache_key = self::CACHE_PREFIX . $key;
        delete_transient($cache_key);
    }

    /**
     * Invalidate all cache entries matching a prefix.
     *
     * @param string $prefix Key prefix
     */
    public function invalidate_prefix($prefix) {
        global $wpdb;

        $like_pattern = $wpdb->esc_like('_transient_' . self::CACHE_PREFIX . $prefix) . '%';

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $like_pattern
        ));

        // Also delete timeout entries
        $timeout_pattern = $wpdb->esc_like('_transient_timeout_' . self::CACHE_PREFIX . $prefix) . '%';

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $timeout_pattern
        ));
    }

    /**
     * Clear all cached context.
     */
    public function clear() {
        global $wpdb;

        $like_pattern = $wpdb->esc_like('_transient_' . self::CACHE_PREFIX) . '%';

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $like_pattern
        ));

        // Also delete timeout entries
        $timeout_pattern = $wpdb->esc_like('_transient_timeout_' . self::CACHE_PREFIX) . '%';

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $timeout_pattern
        ));

        $this->hits = 0;
        $this->misses = 0;
    }

    /**
     * Get cache hit rate as percentage.
     *
     * @return float Hit rate percentage
     */
    public function get_hit_rate() {
        $total = $this->hits + $this->misses;

        if ($total === 0) {
            return 0;
        }

        return ($this->hits / $total) * 100;
    }

    /**
     * Get cache statistics.
     *
     * @return array Statistics
     */
    public function get_stats() {
        return array(
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_rate' => $this->get_hit_rate(),
            'total_requests' => $this->hits + $this->misses
        );
    }

    /**
     * Pre-warm cache with common context fragments.
     */
    public function warm_cache() {
        $ttl = (int) get_option('gd_chatbot_v2_token_cache_ttl', 3600);

        // Base context
        $base = "You are an expert on the Grateful Dead (1965-1995). ";
        $base .= "You have access to complete setlist data, song information, and band history. ";
        $base .= "Provide accurate, helpful responses based on the context provided.";
        $this->set('base_context_v1', $base, max($ttl, 86400));

        // Band info
        $band_info = "Core members: Jerry Garcia (lead guitar), Bob Weir (rhythm guitar), ";
        $band_info .= "Phil Lesh (bass), Bill Kreutzmann (drums), Mickey Hart (drums). ";
        $band_info .= "Keyboardists: Pigpen (1965-72), Keith/Donna Godchaux (1971-79), ";
        $band_info .= "Brent Mydland (1979-90), Vince Welnick (1990-95).";
        $this->set('band_info_v1', $band_info, max($ttl, 86400));

        // Equipment
        $equipment = "Jerry's gear: Alligator Strat, Tiger, Wolf. Wall of Sound (1974). Phil's Alembic basses.";
        $this->set('equipment_v1', $equipment, max($ttl, 86400));
    }
}
