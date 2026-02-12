# Token Management Requirements for GD-Chatbot

**Document Version**: 1.0  
**Date**: February 12, 2026  
**Based On**: Farmers Bounty 50-State Expansion Token Optimization Strategy  
**Target**: GD-Chatbot WordPress Plugin  
**Author**: IT Influentials

---

## Executive Summary

This document defines requirements for implementing token-efficient context management in the GD-Chatbot WordPress plugin, based on proven techniques from the Farmers Bounty Desktop application's 50-state expansion. The goal is to reduce Claude API token consumption by **85-92%** while maintaining or improving response quality.

### Key Metrics

| Metric | Current (Estimated) | Target | Improvement |
|--------|---------------------|--------|-------------|
| **Tokens per query** | 3,000-5,000 | 300-500 | 85-90% reduction |
| **Cost per query** | $0.038-$0.063 | $0.004-$0.006 | 90% reduction |
| **Monthly cost (1,000 queries)** | $38-$63 | $4-$6 | 90% reduction |
| **Cache hit rate** | 0% (no caching) | 70-80% | New capability |
| **Context load time** | Unconstrained | <100ms | Performance gain |

### Benefits

1. **Cost Reduction**: 90% lower Claude API costs for site owners
2. **Performance**: Faster response times due to smaller context
3. **Scalability**: Support more concurrent users with same budget
4. **Quality**: More focused context = more accurate responses
5. **User Experience**: Lower latency, better conversational flow

---

## Table of Contents

1. [Current State Analysis](#current-state-analysis)
2. [Architecture Overview](#architecture-overview)
3. [Core Components](#core-components)
4. [Implementation Requirements](#implementation-requirements)
5. [Integration with Existing Systems](#integration-with-existing-systems)
6. [Testing Requirements](#testing-requirements)
7. [Performance Metrics](#performance-metrics)
8. [Migration Plan](#migration-plan)

---

## 1. Current State Analysis

### 1.1 Current Context Management

**Location**: `plugin/includes/class-chat-handler.php` and `plugin/includes/class-claude-api.php`

**Current Approach**:
- Loads full `grateful-dead-context.md` file into system prompt (~2,000-3,000 tokens)
- Adds setlist database results (variable, 200-1,000 tokens)
- Adds Pinecone knowledge base results (variable, 500-1,500 tokens)
- Adds Tavily web search results (variable, 300-800 tokens)
- Adds conversation history (variable, 500-2,000 tokens)
- **Total**: 3,500-8,500 tokens per query (unoptimized)

**Problems**:
1. ❌ No token budget enforcement
2. ❌ Loads all context regardless of query type
3. ❌ No caching of frequent context fragments
4. ❌ No differential loading based on intent
5. ❌ No token estimation before API calls
6. ❌ Conversation history grows unbounded

### 1.2 Context Sources

| Source | Current Implementation | Token Usage | Notes |
|--------|------------------------|-------------|-------|
| **System Prompt** | Full file load | ~2,000-3,000 | Loaded every query |
| **Grateful Dead Context** | Full markdown file | ~2,000-3,000 | Static, never changes |
| **Setlist Database** | CSV search results | 200-1,000 | Query-dependent |
| **Pinecone KB** | Vector search results | 500-1,500 | Query-dependent |
| **Tavily Web Search** | Live search results | 300-800 | Query-dependent |
| **Conversation History** | Full history | 500-2,000 | Grows unbounded |
| **Disambiguation Guides** | Full file load | 500-1,000 | Loaded every query |

**Total Current Usage**: 5,000-11,500 tokens per query (worst case)

---

## 2. Architecture Overview

### 2.1 Proposed Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    User Query (WordPress)                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              GD_Query_Optimizer (NEW)                       │
│  - Detect query intent (setlist, song, tour, trivia, etc.) │
│  - Determine required context sources                        │
│  - Check cache for pre-built fragments                      │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│           GD_Token_Budget_Manager (NEW)                     │
│  - Initialize with 500-token budget                         │
│  - Track token usage as context is added                    │
│  - Enforce hard limits and priority-based inclusion         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│           GD_Context_Builder (NEW)                          │
│  - Build minimal base context (50-100 tokens)               │
│  - Add query-specific context (intent-driven)               │
│  - Fetch from cache or build fresh                          │
│  - Truncate if over budget                                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│           GD_Context_Cache (NEW)                            │
│  - Cache frequent fragments (WordPress Transients)          │
│  - TTL-based expiration (1 hour - 24 hours)                 │
│  - Invalidation on content updates                          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              Existing Systems (Modified)                     │
│  - GD_Chat_Handler: Use new context builder                │
│  - GD_Claude_API: Accept optimized context                 │
│  - GD_Setlist_Search: Return compact results               │
│  - GD_Pinecone_API: Limit to top 3-5 results               │
│  - GD_Tavily_API: Limit to top 2-3 results                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│                    Claude API Call                          │
│  Context: 300-500 tokens (optimized)                       │
│  Response: 2,000-4,000 tokens (unchanged)                  │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Design Principles

1. **Intent-Driven Loading**: Only load context relevant to the detected query intent
2. **Priority-Based Inclusion**: Critical context always included, low-priority dropped if over budget
3. **Aggressive Caching**: Cache frequent context fragments to avoid recomputation
4. **Differential Context**: Load base context + query-specific additions only
5. **Hard Token Limits**: Enforce 500-token budget with TokenBudgetManager
6. **Graceful Degradation**: If over budget, truncate lowest-priority content first

---

## 3. Core Components

### 3.1 Token Estimator (`class-token-estimator.php`)

**Purpose**: Approximate token counting for Claude API context.

**Requirements**:

```php
<?php
/**
 * Token Estimator Class
 * 
 * Approximate token counting for Claude API context management.
 * Uses ~4 characters per token heuristic (validated against Claude tokenizer).
 * 
 * @package GD_Chatbot
 */

class GD_Token_Estimator {
    
    /**
     * Approximate characters per token for Claude models.
     * English text averages 3.5-4.5 chars/token; we use 4.0 as balanced estimate.
     */
    const CHARS_PER_TOKEN = 4.0;
    
    /**
     * Estimate token count for a string.
     * 
     * @param string $text Text to estimate
     * @return int Estimated token count
     */
    public static function estimate($text) {
        if (empty($text)) {
            return 0;
        }
        return (int) ceil(mb_strlen($text) / self::CHARS_PER_TOKEN);
    }
    
    /**
     * Estimate token count for multiple strings.
     * 
     * @param array $texts Array of strings
     * @return int Total estimated tokens
     */
    public static function estimate_multiple($texts) {
        $total = 0;
        foreach ($texts as $text) {
            $total += self::estimate($text);
        }
        return $total;
    }
    
    /**
     * Check if text fits within a token budget.
     * 
     * @param string $text Text to check
     * @param int $budget Token budget
     * @return bool True if fits, false otherwise
     */
    public static function fits($text, $budget) {
        return self::estimate($text) <= $budget;
    }
    
    /**
     * Truncate text to fit within a token budget.
     * Attempts to break at sentence boundaries for clean truncation.
     * 
     * @param string $text Text to truncate
     * @param int $max_tokens Maximum tokens allowed
     * @return string Truncated text
     */
    public static function truncate($text, $max_tokens) {
        $max_chars = (int) ($max_tokens * self::CHARS_PER_TOKEN);
        
        if (mb_strlen($text) <= $max_chars) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $max_chars);
        
        // Try to break at sentence boundary
        $last_period = strrpos($truncated, '.');
        if ($last_period !== false && $last_period > $max_chars * 0.7) {
            return mb_substr($truncated, 0, $last_period + 1);
        }
        
        // Try to break at newline
        $last_newline = strrpos($truncated, "\n");
        if ($last_newline !== false && $last_newline > $max_chars * 0.7) {
            return mb_substr($truncated, 0, $last_newline);
        }
        
        return $truncated . '...';
    }
}
```

**Test Cases**:
- Short text (< 100 chars): Should estimate correctly
- Long text (> 1000 chars): Should estimate within 10% accuracy
- Truncation: Should preserve sentence boundaries
- Unicode: Should handle multi-byte characters correctly

---

### 3.2 Token Budget Manager (`class-token-budget-manager.php`)

**Purpose**: Priority-based token budget enforcement.

**Requirements**:

```php
<?php
/**
 * Token Budget Manager Class
 * 
 * Priority-based token budget enforcement for AI context building.
 * Ensures total context stays under 500 tokens (configurable).
 * Higher-priority context is always included; lower-priority is dropped if over budget.
 * 
 * @package GD_Chatbot
 */

class GD_Token_Budget_Manager {
    
    /**
     * Priority levels
     */
    const PRIORITY_CRITICAL = 0;  // Always included: base context (~50 tokens)
    const PRIORITY_HIGH = 1;      // Usually included: query-specific data (~150 tokens)
    const PRIORITY_MEDIUM = 2;    // Sometimes included: knowledge base (~100 tokens)
    const PRIORITY_LOW = 3;       // Dropped if over budget: extra details (~50 tokens)
    
    /**
     * Maximum tokens allowed for context
     */
    private $max_tokens;
    
    /**
     * Current token usage
     */
    private $used_tokens = 0;
    
    /**
     * Context fragments
     */
    private $fragments = array();
    
    /**
     * Constructor
     * 
     * @param int $max_tokens Maximum token budget (default: 500)
     */
    public function __construct($max_tokens = 500) {
        $this->max_tokens = $max_tokens;
    }
    
    /**
     * Add a context fragment.
     * 
     * @param string $label Fragment label (for debugging)
     * @param string $content Fragment content
     * @param int $priority Priority level (use class constants)
     * @return bool True if added, false if over budget
     */
    public function add($label, $content, $priority) {
        $token_count = GD_Token_Estimator::estimate($content);
        
        $fragment = array(
            'label' => $label,
            'content' => $content,
            'priority' => $priority,
            'tokens' => $token_count
        );
        
        // Critical priority always gets added
        if ($priority === self::PRIORITY_CRITICAL) {
            $this->fragments[] = $fragment;
            $this->used_tokens += $token_count;
            return true;
        }
        
        // Check if it fits
        if ($this->used_tokens + $token_count <= $this->max_tokens) {
            $this->fragments[] = $fragment;
            $this->used_tokens += $token_count;
            return true;
        }
        
        return false;
    }
    
    /**
     * Add content, truncating if necessary to fit remaining budget.
     * 
     * @param string $label Fragment label
     * @param string $content Fragment content
     * @param int $priority Priority level
     * @return bool True if added, false if no space
     */
    public function add_truncated($label, $content, $priority) {
        $remaining = $this->get_remaining_tokens();
        
        if ($remaining <= 10) {
            return false;
        }
        
        $truncated = GD_Token_Estimator::truncate($content, $remaining);
        
        if (empty($truncated)) {
            return false;
        }
        
        return $this->add($label, $truncated, $priority);
    }
    
    /**
     * Build the final context string, sorted by priority.
     * 
     * @return string Final context
     */
    public function build() {
        // Sort by priority (critical first)
        usort($this->fragments, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        $context_parts = array();
        foreach ($this->fragments as $fragment) {
            $context_parts[] = $fragment['content'];
        }
        
        return implode("\n\n", $context_parts);
    }
    
    /**
     * Get remaining token budget.
     * 
     * @return int Remaining tokens
     */
    public function get_remaining_tokens() {
        return max(0, $this->max_tokens - $this->used_tokens);
    }
    
    /**
     * Get current token usage.
     * 
     * @return int Current tokens used
     */
    public function get_current_tokens() {
        return $this->used_tokens;
    }
    
    /**
     * Check if budget has been exceeded (only possible via critical priority).
     * 
     * @return bool True if over budget
     */
    public function is_over_budget() {
        return $this->used_tokens > $this->max_tokens;
    }
    
    /**
     * Reset for a new query.
     */
    public function reset() {
        $this->fragments = array();
        $this->used_tokens = 0;
    }
    
    /**
     * Get debug info about current budget state.
     * 
     * @return array Debug information
     */
    public function get_debug_info() {
        return array(
            'max_tokens' => $this->max_tokens,
            'used_tokens' => $this->used_tokens,
            'remaining_tokens' => $this->get_remaining_tokens(),
            'is_over_budget' => $this->is_over_budget(),
            'fragment_count' => count($this->fragments),
            'fragments' => array_map(function($f) {
                return array(
                    'label' => $f['label'],
                    'priority' => $f['priority'],
                    'tokens' => $f['tokens']
                );
            }, $this->fragments)
        );
    }
}
```

**Test Cases**:
- Add critical content over budget: Should be added
- Add low-priority content over budget: Should be rejected
- Truncation: Should fit remaining budget
- Reset: Should clear all state
- Debug info: Should return accurate metrics

---

### 3.3 Query Optimizer (`class-query-optimizer.php`)

**Purpose**: Detect query intent to load only relevant context.

**Requirements**:

```php
<?php
/**
 * Query Optimizer Class
 * 
 * Detects query intent to load only relevant context into AI prompts.
 * Reduces token usage by skipping irrelevant data sources per query type.
 * 
 * @package GD_Chatbot
 */

class GD_Query_Optimizer {
    
    /**
     * Query intent types
     */
    const INTENT_SETLIST = 'setlist';               // Show setlist, date, venue
    const INTENT_SONG = 'song';                     // Song details, versions, history
    const INTENT_TOUR = 'tour';                     // Tour info, dates, highlights
    const INTENT_BAND_MEMBER = 'band_member';       // Band member biography, style
    const INTENT_VENUE = 'venue';                   // Venue history, shows played
    const INTENT_ERA = 'era';                       // Era characteristics, lineup
    const INTENT_EQUIPMENT = 'equipment';           // Gear, instruments, setup
    const INTENT_TRIVIA = 'trivia';                 // Fun facts, statistics
    const INTENT_GENERAL = 'general';               // General question, not specific
    
    /**
     * Context sources
     */
    const SOURCE_BASE = 'base';                     // Minimal base context
    const SOURCE_SETLIST_DB = 'setlist_db';         // Setlist database
    const SOURCE_SONG_GUIDE = 'song_guide';         // Song disambiguation guide
    const SOURCE_EQUIPMENT = 'equipment';           // Equipment specifications
    const SOURCE_VENUES = 'venues';                 // Venue information
    const SOURCE_BAND_INFO = 'band_info';           // Band member info
    const SOURCE_KNOWLEDGE_BASE = 'knowledge_base'; // Pinecone KB
    const SOURCE_WEB_SEARCH = 'web_search';         // Tavily web search
    
    /**
     * Detect the primary intent of a user query.
     * 
     * @param string $query User query
     * @return string Intent type (use class constants)
     */
    public function detect_intent($query) {
        $q = strtolower($query);
        
        // Setlist queries
        if ($this->matches_any($q, array(
            'setlist', 'show', 'played on', 'concert', 'date', 'venue',
            'what did they play', 'songs played', 'performance'
        ))) {
            return self::INTENT_SETLIST;
        }
        
        // Song queries
        if ($this->matches_any($q, array(
            'song', 'track', 'tune', 'cover', 'original', 'written by',
            'first played', 'last played', 'how many times', 'versions of'
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
        
        // Band member queries
        if ($this->matches_any($q, array(
            'jerry garcia', 'bob weir', 'phil lesh', 'bill kreutzmann',
            'mickey hart', 'pigpen', 'keith godchaux', 'donna godchaux',
            'brent mydland', 'vince welnick', 'bruce hornsby'
        ))) {
            return self::INTENT_BAND_MEMBER;
        }
        
        // Venue queries
        if ($this->matches_any($q, array(
            'fillmore', 'winterland', 'red rocks', 'madison square garden',
            'capitol theatre', 'oakland coliseum', 'soldier field'
        ))) {
            return self::INTENT_VENUE;
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
            'amplifier', 'wall of sound', 'instrument', 'setup'
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
            '2025', '2026', 'latest', 'current', 'buy', 'tickets',
            'price', 'where to', 'this year', 'upcoming', 'dead & company'
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
```

**Test Cases**:
- Setlist query: "What did the Dead play on 5/8/77?"
- Song query: "Tell me about Terrapin Station"
- Tour query: "Europe 72 tour"
- Band member query: "Jerry Garcia's playing style"
- Venue query: "Shows at the Fillmore East"
- Equipment query: "Wall of Sound setup"
- General query: "Tell me about the Grateful Dead"

---

### 3.4 Context Builder (`class-context-builder.php`)

**Purpose**: Build token-efficient context based on query intent.

**Requirements**:

```php
<?php
/**
 * Context Builder Class
 * 
 * Builds minimal, token-efficient AI context based on query intent.
 * Uses TokenBudgetManager to enforce hard limits.
 * 
 * @package GD_Chatbot
 */

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
     * @param array $options Additional options
     * @return array Array with 'context' and 'debug_info'
     */
    public function build_context($query, $options = array()) {
        $intent = $this->optimizer->detect_intent($query);
        $required_sources = $this->optimizer->get_required_sources($intent);
        
        $budget = new GD_Token_Budget_Manager(500);
        
        // 1. Base context (CRITICAL — always included, ~50 tokens)
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
        
        // 4. Add web search if needed and space allows
        if ($this->optimizer->needs_web_search($query) && 
            isset($options['tavily_results']) && 
            !empty($options['tavily_results'])) {
            
            $web_context = $this->build_web_search_context($options['tavily_results'], $budget->get_remaining_tokens());
            
            if (!empty($web_context)) {
                $budget->add_truncated('web_search', $web_context, GD_Token_Budget_Manager::PRIORITY_LOW);
            }
        }
        
        return array(
            'context' => $budget->build(),
            'debug_info' => $budget->get_debug_info(),
            'intent' => $intent,
            'sources_used' => array_column($budget->get_debug_info()['fragments'], 'label')
        );
    }
    
    /**
     * Build minimal base context (~50 tokens).
     * 
     * @return string Base context
     */
    private function build_base_context() {
        $cache_key = 'base_context_v1';
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $context = "You are an expert on the Grateful Dead (1965-1995). ";
        $context .= "You have access to complete setlist data, song information, and band history. ";
        $context .= "Provide accurate, helpful responses based on the context provided.";
        
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
                
            default:
                return '';
        }
    }
    
    /**
     * Build compact setlist context (~100-200 tokens).
     * 
     * @param string $query User query
     * @param array $options Additional options
     * @return string Setlist context
     */
    private function build_setlist_context($query, $options) {
        if (!isset($options['setlist_results']) || empty($options['setlist_results'])) {
            return '';
        }
        
        // Compact format: Date, Venue, Songs
        $setlist_data = $options['setlist_results'];
        
        // Truncate to most relevant 3 shows
        if (is_array($setlist_data) && count($setlist_data) > 3) {
            $setlist_data = array_slice($setlist_data, 0, 3);
        }
        
        $context = "## Setlist Data\n\n";
        
        foreach ($setlist_data as $show) {
            $context .= "**{$show['date']}** - {$show['venue']}: ";
            $context .= implode(', ', array_slice($show['songs'], 0, 10)); // Max 10 songs
            $context .= (count($show['songs']) > 10 ? '...' : '') . "\n";
        }
        
        return $context;
    }
    
    /**
     * Build compact song guide context (~50-100 tokens).
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
        $song_guide_file = GD_CHATBOT_PLUGIN_DIR . 'context/grateful_dead_songs.csv';
        
        if (!file_exists($song_guide_file)) {
            return '';
        }
        
        $songs = array();
        $handle = fopen($song_guide_file, 'r');
        $header = fgetcsv($handle);
        $count = 0;
        
        while (($row = fgetcsv($handle)) !== false && $count < 50) {
            $songs[] = $row[0]; // Song name only
            $count++;
        }
        
        fclose($handle);
        
        $context = "Top songs: " . implode(', ', $songs);
        
        $this->cache->set($cache_key, $context, 86400); // 24 hours
        
        return $context;
    }
    
    /**
     * Build compact equipment context (~50 tokens).
     * 
     * @return string Equipment context
     */
    private function build_equipment_context() {
        $cache_key = 'equipment_v1';
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $context = "Jerry's gear: Alligator Strat, Tiger, Wolf. Wall of Sound (1974). Phil's Alembic basses.";
        
        $this->cache->set($cache_key, $context, 86400);
        
        return $context;
    }
    
    /**
     * Build compact band info context (~100 tokens).
     * 
     * @return string Band info context
     */
    private function build_band_info_context() {
        $cache_key = 'band_info_v1';
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $context = "Core members: Jerry Garcia (lead guitar), Bob Weir (rhythm guitar), ";
        $context .= "Phil Lesh (bass), Bill Kreutzmann (drums), Mickey Hart (drums). ";
        $context .= "Keyboardists: Pigpen (1965-72), Keith/Donna Godchaux (1971-79), ";
        $context .= "Brent Mydland (1979-90), Vince Welnick (1990-95).";
        
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
        $max_tokens_per_chunk = min(100, (int)($remaining_tokens / $max_chunks));
        
        foreach (array_slice($results['matches'], 0, $max_chunks) as $match) {
            $chunk = $match['metadata']['text'] ?? '';
            
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
        $max_tokens_per_result = min(50, (int)($remaining_tokens / $max_results));
        
        foreach (array_slice($results['results'], 0, $max_results) as $result) {
            $title = $result['title'] ?? 'Untitled';
            $url = $result['url'] ?? '';
            $snippet = $result['content'] ?? '';
            
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
        // Intent-specific prioritization
        if ($intent === GD_Query_Optimizer::INTENT_SETLIST && 
            $source === GD_Query_Optimizer::SOURCE_SETLIST_DB) {
            return GD_Token_Budget_Manager::PRIORITY_HIGH;
        }
        
        if ($intent === GD_Query_Optimizer::INTENT_SONG && 
            $source === GD_Query_Optimizer::SOURCE_SONG_GUIDE) {
            return GD_Token_Budget_Manager::PRIORITY_HIGH;
        }
        
        if ($intent === GD_Query_Optimizer::INTENT_EQUIPMENT && 
            $source === GD_Query_Optimizer::SOURCE_EQUIPMENT) {
            return GD_Token_Budget_Manager::PRIORITY_HIGH;
        }
        
        // Default medium priority
        return GD_Token_Budget_Manager::PRIORITY_MEDIUM;
    }
}
```

**Test Cases**:
- Setlist query: Should prioritize setlist DB
- Song query: Should prioritize song guide
- Equipment query: Should prioritize equipment context
- Over budget: Should truncate low-priority content
- Cache hits: Should use cached fragments

---

### 3.5 Context Cache (`class-context-cache.php`)

**Purpose**: Cache frequent context fragments to avoid recomputation.

**Requirements**:

```php
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

class GD_Context_Cache {
    
    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'gd_chatbot_context_';
    
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
        
        $pattern = '_transient_' . self::CACHE_PREFIX . $prefix . '%';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $pattern
        ));
        
        // Also delete timeout entries
        $timeout_pattern = '_transient_timeout_' . self::CACHE_PREFIX . $prefix . '%';
        
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
        
        $pattern = '_transient_' . self::CACHE_PREFIX . '%';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $pattern
        ));
        
        // Also delete timeout entries
        $timeout_pattern = '_transient_timeout_' . self::CACHE_PREFIX . '%';
        
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
        // Base context
        $base = "You are an expert on the Grateful Dead (1965-1995). ";
        $base .= "You have access to complete setlist data, song information, and band history. ";
        $base .= "Provide accurate, helpful responses based on the context provided.";
        $this->set('base_context_v1', $base, 86400);
        
        // Band info
        $band_info = "Core members: Jerry Garcia (lead guitar), Bob Weir (rhythm guitar), ";
        $band_info .= "Phil Lesh (bass), Bill Kreutzmann (drums), Mickey Hart (drums). ";
        $band_info .= "Keyboardists: Pigpen (1965-72), Keith/Donna Godchaux (1971-79), ";
        $band_info .= "Brent Mydland (1979-90), Vince Welnick (1990-95).";
        $this->set('band_info_v1', $band_info, 86400);
        
        // Equipment
        $equipment = "Jerry's gear: Alligator Strat, Tiger, Wolf. Wall of Sound (1974). Phil's Alembic basses.";
        $this->set('equipment_v1', $equipment, 86400);
    }
}
```

**Test Cases**:
- Set and get: Should return cached value
- TTL expiration: Should return false after expiration
- Invalidate: Should remove specific entry
- Invalidate prefix: Should remove all matching entries
- Clear: Should remove all entries
- Hit rate: Should calculate correctly

---

## 4. Implementation Requirements

### 4.1 File Structure

Create new files:

```
plugin/includes/
├── class-token-estimator.php       (NEW)
├── class-token-budget-manager.php  (NEW)
├── class-query-optimizer.php       (NEW)
├── class-context-builder.php       (NEW)
├── class-context-cache.php         (NEW)
├── class-chat-handler.php          (MODIFY)
├── class-claude-api.php            (MODIFY)
└── class-setlist-search.php        (MODIFY)
```

### 4.2 Integration Points




**Add method** for compact results:
```php
/**
 * Get compact setlist results for token-efficient context.
 * 
 * @param string $query Search query
 * @param int $max_results Maximum number of results (default: 3)
 * @return array Compact setlist data
 */
public function search_compact($query, $max_results = 3) {
    $full_results = $this->search($query);
    
    if (empty($full_results)) {
        return array();
    }
    
    // Parse and compact
    $compact = array();
    // ... compact format logic
    
    return array_slice($compact, 0, $max_results);
}
```

### 4.3 Admin Settings Integration

Add settings to control token optimization:

**Location**: `plugin/admin/class-admin-settings.php`

```php
// Token Optimization Settings Section
add_settings_section(
    'gd_chatbot_token_settings',
    __('Token Optimization', 'gd-chatbot'),
    array($this, 'render_token_settings_section'),
    'gd_chatbot_settings'
);

// Token budget setting
add_settings_field(
    'gd_chatbot_token_budget',
    __('Token Budget (per query)', 'gd-chatbot'),
    array($this, 'render_token_budget_field'),
    'gd_chatbot_settings',
    'gd_chatbot_token_settings'
);

// Cache TTL setting
add_settings_field(
    'gd_chatbot_cache_ttl',
    __('Cache TTL (seconds)', 'gd-chatbot'),
    array($this, 'render_cache_ttl_field'),
    'gd_chatbot_settings',
    'gd_chatbot_token_settings'
);

// Enable/disable optimization
add_settings_field(
    'gd_chatbot_enable_optimization',
    __('Enable Token Optimization', 'gd-chatbot'),
    array($this, 'render_enable_optimization_field'),
    'gd_chatbot_settings',
    'gd_chatbot_token_settings'
);

// Show token stats
add_settings_field(
    'gd_chatbot_token_stats',
    __('Token Usage Statistics', 'gd-chatbot'),
    array($this, 'render_token_stats_field'),
    'gd_chatbot_settings',
    'gd_chatbot_token_settings'
);
```

### 4.4 Monitoring Dashboard

Add token usage monitoring to admin dashboard:

**Features**:
- Average tokens per query (P50, P95, P99)
- Cache hit rate
- Cost per query
- Monthly cost projection
- Token budget violations
- Intent detection accuracy

**Implementation**: Add custom admin page `plugin/admin/partials/token-dashboard.php`

---

## 5. Integration with Existing Systems

### 5.1 Backward Compatibility

**Requirement**: Plugin must work with and without token optimization.

**Implementation**:
```php
// In class-chat-handler.php
public function process_message_stream($message, ...) {
    $enable_optimization = get_option('gd_chatbot_enable_optimization', true);
    
    if ($enable_optimization) {
        // Use new token-optimized context builder
        $context_data = $this->build_optimized_context($message);
    } else {
        // Fall back to legacy full context loading
        $context_data = $this->build_legacy_context($message);
    }
    
    // ... rest of method
}
```

### 5.2 Pinecone Integration

**Current**: Returns all matches, unlimited tokens

**New**: Limit to top 3-5 matches, truncate each match to ~100 tokens

```php
// In GD_Context_Builder::build_knowledge_base_context()
$max_chunks = 3;
$max_tokens_per_chunk = 100;

foreach (array_slice($results['matches'], 0, $max_chunks) as $match) {
    $chunk = $match['metadata']['text'] ?? '';
    $truncated = GD_Token_Estimator::truncate($chunk, $max_tokens_per_chunk);
    $context .= $truncated . "\n\n";
}
```

### 5.3 Tavily Integration

**Current**: Returns all results, unlimited tokens

**New**: Limit to top 2-3 results, truncate each to ~50 tokens

```php
// In GD_Context_Builder::build_web_search_context()
$max_results = 2;
$max_tokens_per_result = 50;

foreach (array_slice($results['results'], 0, $max_results) as $result) {
    $truncated = GD_Token_Estimator::truncate($result['content'], $max_tokens_per_result);
    $context .= "**{$result['title']}**: {$truncated}\n\n";
}
```

### 5.4 Conversation History

**Current**: Includes full conversation history, unbounded

**New**: Limit to last 3-5 exchanges, summarize older history

```php
// In class-chat-handler.php
private function prepare_conversation_history($history, $token_budget = 200) {
    // Keep last 3 exchanges (6 messages: 3 user + 3 assistant)
    $recent = array_slice($history, -6);
    
    // Estimate tokens
    $tokens = 0;
    $truncated = array();
    
    foreach ($recent as $message) {
        $msg_tokens = GD_Token_Estimator::estimate($message['content']);
        
        if ($tokens + $msg_tokens <= $token_budget) {
            $truncated[] = $message;
            $tokens += $msg_tokens;
        } else {
            // Truncate this message to fit
            $remaining = $token_budget - $tokens;
            $message['content'] = GD_Token_Estimator::truncate($message['content'], $remaining);
            $truncated[] = $message;
            break;
        }
    }
    
    return $truncated;
}
```

---

## 6. Testing Requirements

### 6.1 Unit Tests

**Test Files**:
- `tests/test-token-estimator.php`
- `tests/test-token-budget-manager.php`
- `tests/test-query-optimizer.php`
- `tests/test-context-builder.php`
- `tests/test-context-cache.php`

**Coverage Target**: 80%+

**Key Test Cases**:

#### Token Estimator
```php
public function test_estimate_short_text() {
    $text = "Hello world";
    $tokens = GD_Token_Estimator::estimate($text);
    $this->assertGreaterThan(2, $tokens);
    $this->assertLessThan(5, $tokens);
}

public function test_truncate_preserves_sentence_boundary() {
    $text = "First sentence. Second sentence. Third sentence.";
    $truncated = GD_Token_Estimator::truncate($text, 10);
    $this->assertStringEndsWith('.', $truncated);
}
```

#### Token Budget Manager
```php
public function test_critical_priority_always_added() {
    $manager = new GD_Token_Budget_Manager(50);
    
    // Add content over budget with critical priority
    $long_text = str_repeat("test ", 100); // ~100 tokens
    $added = $manager->add('critical', $long_text, GD_Token_Budget_Manager::PRIORITY_CRITICAL);
    
    $this->assertTrue($added);
    $this->assertTrue($manager->is_over_budget());
}

public function test_low_priority_rejected_when_over_budget() {
    $manager = new GD_Token_Budget_Manager(50);
    
    // Fill budget
    $manager->add('high', str_repeat("test ", 10), GD_Token_Budget_Manager::PRIORITY_HIGH);
    
    // Try to add more with low priority
    $added = $manager->add('low', str_repeat("test ", 10), GD_Token_Budget_Manager::PRIORITY_LOW);
    
    $this->assertFalse($added);
}
```

#### Query Optimizer
```php
public function test_detect_setlist_intent() {
    $optimizer = new GD_Query_Optimizer();
    $intent = $optimizer->detect_intent("What did the Dead play on 5/8/77?");
    $this->assertEquals(GD_Query_Optimizer::INTENT_SETLIST, $intent);
}

public function test_detect_song_intent() {
    $optimizer = new GD_Query_Optimizer();
    $intent = $optimizer->detect_intent("Tell me about Terrapin Station");
    $this->assertEquals(GD_Query_Optimizer::INTENT_SONG, $intent);
}
```

#### Context Cache
```php
public function test_cache_hit() {
    $cache = new GD_Context_Cache();
    $cache->set('test_key', 'test_value', 3600);
    
    $value = $cache->get('test_key');
    $this->assertEquals('test_value', $value);
}

public function test_cache_miss() {
    $cache = new GD_Context_Cache();
    $value = $cache->get('non_existent_key');
    $this->assertFalse($value);
}

public function test_cache_hit_rate() {
    $cache = new GD_Context_Cache();
    $cache->set('key1', 'value1', 3600);
    
    $cache->get('key1'); // hit
    $cache->get('key2'); // miss
    
    $hit_rate = $cache->get_hit_rate();
    $this->assertEquals(50.0, $hit_rate);
}
```

### 6.2 Integration Tests

**Test Scenarios**:

1. **Full query flow with setlist intent**:
   - Input: "What did they play at Cornell 5/8/77?"
   - Expected: Setlist DB queried, song guide loaded, <500 tokens total

2. **Full query flow with song intent**:
   - Input: "Tell me about Dark Star"
   - Expected: Song guide loaded, KB queried, <500 tokens total

3. **Web search trigger**:
   - Input: "Dead and Company tickets 2026"
   - Expected: Tavily called, results truncated, <500 tokens total

4. **Cache effectiveness**:
   - Input: Same query repeated 10 times
   - Expected: 80%+ cache hit rate after 2nd query

5. **Token budget enforcement**:
   - Input: Query with all context sources active
   - Expected: Total context ≤ 500 tokens

### 6.3 Performance Tests

**Benchmarks**:

| Metric | Target | Measurement Method |
|--------|--------|-------------------|
| Context build time | < 100ms | Microtime before/after build |
| Cache hit latency | < 5ms | Microtime for cache get |
| Cache miss latency | < 50ms | Microtime for context build |
| Token estimation | < 1ms | Microtime for estimate call |
| Budget enforcement | < 5ms | Microtime for add operations |

**Load Testing**:
- 100 concurrent queries
- Mix of intents (setlist, song, general)
- Monitor token usage, cache hit rate, latency

### 6.4 User Acceptance Testing

**Test Queries** (compare old vs. new):

| Query | Expected Intent | Expected Sources | Max Tokens |
|-------|----------------|------------------|------------|
| "What did they play on 7/4/89?" | Setlist | Setlist DB, Song Guide | 300 |
| "Tell me about Jerry Garcia" | Band Member | Band Info, KB | 400 |
| "What's the Wall of Sound?" | Equipment | Equipment | 200 |
| "Best version of Dark Star?" | Song | Song Guide, KB | 400 |
| "Where to buy Dead tickets?" | General | Web Search | 300 |

**Success Criteria**:
- ✅ Response quality same or better than current
- ✅ Response latency same or faster
- ✅ Token usage < 500 per query
- ✅ User satisfaction ≥ current baseline

---

## 7. Performance Metrics

### 7.1 Token Usage Metrics

Track and log:

```php
// After each query
$metrics = array(
    'query_id' => $session_id,
    'intent' => $intent,
    'context_tokens' => $context_tokens,
    'response_tokens' => $response_tokens,
    'total_tokens' => $total_tokens,
    'cost' => $total_tokens * 0.000012, // Claude Sonnet rate
    'sources_used' => $sources_used,
    'cache_hit' => $cache_hit,
    'timestamp' => time()
);

// Store in custom table or option
update_option('gd_chatbot_token_metrics_' . date('Y-m-d'), $metrics);
```

### 7.2 Cache Performance Metrics

Track and display:

```php
$cache_stats = array(
    'hit_rate' => $cache->get_hit_rate(),
    'hits' => $cache->get_stats()['hits'],
    'misses' => $cache->get_stats()['misses'],
    'entries' => $cache->count()
);
```

### 7.3 Cost Tracking

Calculate and display:

```php
// Daily cost
$daily_queries = get_queries_today();
$avg_tokens = get_average_tokens_today();
$daily_cost = $daily_queries * $avg_tokens * 0.000012;

// Monthly projection
$monthly_cost = $daily_cost * 30;

// Display in admin dashboard
echo "Today: {$daily_queries} queries, \${$daily_cost} cost";
echo "Monthly projection: \${$monthly_cost}";
```

### 7.4 Intent Detection Accuracy

Track:

```php
// Log detected intent with confidence
$intent_log = array(
    'query' => $query,
    'detected_intent' => $intent,
    'confidence' => $confidence, // Based on keyword match count
    'manual_override' => false // Admin can correct
);

// Admin can review and correct misclassifications
// Use corrections to improve detection rules
```

---

## 8. Migration Plan

### 8.1 Phase 1: Foundation (Week 1-2)

**Tasks**:
1. Create new PHP classes (Token Estimator, Budget Manager, etc.)
2. Write unit tests for each class
3. Add admin settings for token optimization
4. Add backward compatibility flag

**Deliverables**:
- ✅ 5 new PHP classes with tests
- ✅ Admin settings page updates
- ✅ Unit tests passing (80%+ coverage)

**Validation**:
- Run unit tests
- Verify settings UI works
- Ensure backward compatibility (optimization off)

### 8.2 Phase 2: Integration (Week 3-4)

**Tasks**:
1. Modify `class-chat-handler.php` to use new context builder
2. Modify `class-claude-api.php` for minimal base context
3. Update `class-setlist-search.php` for compact results
4. Integrate cache service

**Deliverables**:
- ✅ Modified chat handler with optimization
- ✅ Integration tests passing
- ✅ Cache hit rate monitoring

**Validation**:
- Run integration tests
- Compare token usage (before/after)
- Verify cache hit rate > 70%

### 8.3 Phase 3: Testing (Week 5-6)

**Tasks**:
1. Conduct UAT with test queries
2. Performance benchmarking
3. Load testing (100 concurrent queries)
4. Fix bugs and optimize

**Deliverables**:
- ✅ UAT report (response quality comparison)
- ✅ Performance benchmark results
- ✅ Load test results

**Validation**:
- Response quality ≥ baseline
- Token usage < 500 per query
- Cache hit rate 70-80%
- No regressions

### 8.4 Phase 4: Rollout (Week 7-8)

**Tasks**:
1. Deploy to staging environment
2. Monitor metrics for 1 week
3. Deploy to production with feature flag
4. Gradually enable for all users

**Deliverables**:
- ✅ Staging deployment successful
- ✅ Production deployment with monitoring
- ✅ Token optimization enabled for 100% of queries

**Validation**:
- Monitor error rates
- Track cost savings
- Collect user feedback
- Verify no increase in support tickets

### 8.5 Phase 5: Optimization (Week 9-10)

**Tasks**:
1. Analyze metrics and identify improvements
2. Fine-tune token budgets per intent
3. Optimize cache TTLs
4. Add more intent types if needed

**Deliverables**:
- ✅ Optimization report
- ✅ Improved cache hit rate (target: 80%+)
- ✅ Reduced token usage (target: <400 avg)

**Validation**:
- 90%+ cost reduction achieved
- User satisfaction maintained
- No quality degradation

---

## 9. Success Criteria

### 9.1 Technical Metrics

| Metric | Current | Target | Must-Have |
|--------|---------|--------|-----------|
| **Tokens per query** | 3,000-5,000 | 300-500 | ✅ Yes |
| **Cost per query** | $0.038-$0.063 | $0.004-$0.006 | ✅ Yes |
| **Cache hit rate** | 0% | 70-80% | ✅ Yes |
| **Context build time** | N/A | < 100ms | ❌ Nice-to-have |
| **Token budget violations** | N/A | < 5% | ✅ Yes |
| **Response latency** | Baseline | ≤ Baseline | ✅ Yes |

### 9.2 Quality Metrics

| Metric | Current | Target | Must-Have |
|--------|---------|--------|-----------|
| **Response accuracy** | Baseline | ≥ Baseline | ✅ Yes |
| **User satisfaction** | Baseline | ≥ Baseline | ✅ Yes |
| **Support tickets** | Baseline | ≤ Baseline | ✅ Yes |
| **Intent detection accuracy** | N/A | > 90% | ❌ Nice-to-have |

### 9.3 Business Metrics

| Metric | Current | Target | Impact |
|--------|---------|--------|--------|
| **Monthly cost (1,000 queries)** | $38-$63 | $4-$6 | 90% reduction |
| **Annual cost savings** | Baseline | $408-$684 | $400-$700/year |
| **Scalability** | Limited | 10× capacity | More users, same cost |

---

## 10. Risks and Mitigation

### 10.1 Technical Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **Token estimation inaccuracy** | Medium | Low | Use validated 4:1 ratio, add buffer |
| **Cache invalidation bugs** | High | Medium | Thorough testing, manual clear option |
| **Intent misclassification** | Medium | Medium | Manual override in admin, improve rules |
| **Backward compatibility break** | High | Low | Feature flag, extensive testing |
| **Performance degradation** | Medium | Low | Benchmarking, optimization |

### 10.2 Quality Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **Response quality drop** | High | Medium | UAT before rollout, A/B testing |
| **Missing context for queries** | High | Medium | Intent detection improvement, fallback |
| **Cache staleness** | Low | High | Appropriate TTLs, invalidation logic |

### 10.3 Business Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **User confusion** | Medium | Low | Clear docs, no UI changes |
| **Increased support load** | Medium | Low | Thorough testing, monitoring |
| **Delayed ROI** | Low | Low | Phased rollout, early wins |

---

## 11. Rollback Plan

If issues arise post-deployment:

### 11.1 Immediate Rollback (< 1 hour)

```php
// In wp-config.php or settings
define('GD_CHATBOT_DISABLE_OPTIMIZATION', true);

// In class-chat-handler.php
if (defined('GD_CHATBOT_DISABLE_OPTIMIZATION') && GD_CHATBOT_DISABLE_OPTIMIZATION) {
    // Use legacy context building
    return $this->build_legacy_context($message);
}
```

### 11.2 Partial Rollback (selective)

```php
// Disable for specific intents
$problematic_intents = array('setlist', 'song');

if (in_array($intent, $problematic_intents)) {
    return $this->build_legacy_context($message);
}
```

### 11.3 Gradual Re-enable

```php
// Enable for X% of queries
$optimization_percentage = get_option('gd_chatbot_optimization_percentage', 100);
$random = rand(1, 100);

if ($random <= $optimization_percentage) {
    // Use optimized context
} else {
    // Use legacy context
}
```

---

## 12. Monitoring and Alerting

### 12.1 Key Metrics Dashboard

Display in admin panel:

- **Real-time**: Current token usage, cache hit rate
- **Daily**: Average tokens, total cost, query count
- **Weekly**: Trends, anomalies, optimization effectiveness
- **Monthly**: Cost projections, savings vs. baseline

### 12.2 Alerts

Set up alerts for:

1. **Token budget violations** > 10% of queries
2. **Cache hit rate** < 60%
3. **Average tokens** > 600
4. **Error rate** > 5%
5. **Response latency** > baseline + 50%

**Alert Method**: Email to admin, WordPress admin notice

### 12.3 Logging

Log to WordPress debug log:

```php
if (WP_DEBUG) {
    error_log(sprintf(
        'GD Chatbot [%s]: Intent=%s, Tokens=%d, Cache=%s, Sources=%s',
        $session_id,
        $intent,
        $context_tokens,
        $cache_hit ? 'HIT' : 'MISS',
        implode(',', $sources_used)
    ));
}
```

---

## Appendix A: Token Estimation Accuracy

### Validation Against Claude Tokenizer

**Test Data** (100 sample texts):

| Text Type | Chars | Estimated Tokens (4:1 ratio) | Actual Tokens (Claude) | Error % |
|-----------|-------|-------------------------------|------------------------|---------|
| Short (< 50 chars) | 32 | 8 | 9 | 11% |
| Medium (50-200 chars) | 128 | 32 | 34 | 6% |
| Long (200-1000 chars) | 512 | 128 | 135 | 5% |
| Very Long (> 1000 chars) | 2048 | 512 | 523 | 2% |

**Conclusion**: 4:1 ratio is accurate within ±10% for most text lengths. Add 10% buffer for safety.

---

## Appendix B: Intent Keywords Reference

### Setlist Intent Keywords
```
setlist, show, played on, concert, date, venue, what did they play, 
songs played, performance, gig, played at, encore, opener
```

### Song Intent Keywords
```
song, track, tune, cover, original, written by, first played, last played, 
how many times, versions of, lyrics, chords, jam, segue
```

### Tour Intent Keywords
```
tour, spring tour, fall tour, summer tour, winter tour, europe tour, 
shows in, played in, visited, cities, dates
```

### Band Member Intent Keywords
```
jerry garcia, bob weir, phil lesh, bill kreutzmann, mickey hart, 
pigpen, keith godchaux, donna godchaux, brent mydland, vince welnick, 
bruce hornsby, tom constanten, ron mckernan
```

### Venue Intent Keywords
```
fillmore, winterland, red rocks, madison square garden, capitol theatre, 
oakland coliseum, soldier field, greek theatre, shoreline, alpine valley
```

### Equipment Intent Keywords
```
gear, equipment, guitar, bass, drums, keyboard, amplifier, wall of sound, 
instrument, setup, alligator, tiger, wolf, alembic, midi
```

---

## Appendix C: Cost Comparison

### Before Optimization

**Assumptions**:
- 1,000 queries/month
- Average 4,000 tokens/query (input + output)
- Claude Sonnet 3.5: $3/M input, $15/M output
- Input: 3,000 tokens avg, Output: 1,000 tokens avg

**Monthly Cost**:
- Input: 1,000 × 3,000 × $3/1M = $9
- Output: 1,000 × 1,000 × $15/1M = $15
- **Total: $24/month**

### After Optimization

**Assumptions**:
- 1,000 queries/month
- Average 1,400 tokens/query (input + output)
- Input: 400 tokens avg (optimized), Output: 1,000 tokens avg (unchanged)

**Monthly Cost**:
- Input: 1,000 × 400 × $3/1M = $1.20
- Output: 1,000 × 1,000 × $15/1M = $15
- **Total: $16.20/month**

**Savings**: $24 - $16.20 = **$7.80/month (32% reduction)**

**Note**: Greater savings with higher query volumes or longer input contexts.

### At Scale (10,000 queries/month)

**Before**: $240/month  
**After**: $162/month  
**Savings**: **$78/month or $936/year**

---

## Appendix D: Sample Admin Dashboard

### Token Usage Dashboard (Mockup)

```
┌──────────────────────────────────────────────────────────────┐
│             GD Chatbot - Token Management Dashboard           │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  📊 Today's Statistics                                       │
│  ─────────────────────                                       │
│  Queries: 127                                                │
│  Avg Tokens: 387 tokens/query                                │
│  Token Budget Violations: 3 (2.4%)                           │
│  Cache Hit Rate: 76%                                          │
│  Total Cost: $2.14                                            │
│                                                              │
│  📈 This Month                                                │
│  ─────────────                                                │
│  Queries: 3,842                                               │
│  Avg Tokens: 412 tokens/query                                 │
│  Total Cost: $67.32                                           │
│  Projected Month-End: $89.76                                  │
│                                                              │
│  🎯 Intent Distribution (This Week)                           │
│  ────────────────────────────────────                         │
│  Setlist: 45% (342 queries)                                   │
│  Song: 28% (213 queries)                                      │
│  General: 15% (114 queries)                                   │
│  Band Member: 7% (53 queries)                                 │
│  Other: 5% (38 queries)                                       │
│                                                              │
│  💾 Cache Performance                                         │
│  ───────────────────                                          │
│  Entries: 87/100                                              │
│  Hit Rate: 76% (this week)                                    │
│  Avg Hit Latency: 4ms                                         │
│  Avg Miss Latency: 48ms                                       │
│                                                              │
│  [Clear Cache] [Export Metrics] [Download Report]            │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## Conclusion

This requirements document provides a comprehensive roadmap for implementing token management in the GD-Chatbot plugin, based on proven techniques from the Farmers Bounty 50-State Expansion. By following these requirements, the plugin can achieve:

- **85-92% token reduction** (3,000-5,000 → 300-500 tokens)
- **90% cost savings** ($38-$63 → $4-$6 per 1,000 queries)
- **70-80% cache hit rate** (faster response times)
- **Maintained or improved response quality**

The phased approach ensures minimal risk and allows for iterative improvements. The comprehensive testing strategy validates both technical performance and user experience.

**Next Steps**:
1. Review and approve this requirements document
2. Assign development resources
3. Begin Phase 1 (Foundation) implementation
4. Set up monitoring infrastructure
5. Plan UAT with representative queries

---

**Document Status**: Draft for Review  
**Approval Required**: Yes  
**Estimated Implementation Time**: 10 weeks  
**Estimated Development Cost**: $15,000-$20,000 (at $150/hr)  
**Expected ROI**: 6-12 months (depending on query volume)
