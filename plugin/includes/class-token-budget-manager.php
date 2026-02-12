<?php
/**
 * Token Budget Manager Class
 *
 * Priority-based token budget enforcement for AI context building.
 * Ensures total context stays under a configurable limit (default 500 tokens).
 * Higher-priority context is always included; lower-priority is dropped if over budget.
 *
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Token_Budget_Manager {

    /**
     * Priority levels
     */
    const PRIORITY_CRITICAL = 0;  // Always included: base context
    const PRIORITY_HIGH = 1;      // Usually included: query-specific data
    const PRIORITY_MEDIUM = 2;    // Sometimes included: knowledge base
    const PRIORITY_LOW = 3;       // Dropped if over budget: extra details

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
        usort($this->fragments, function ($a, $b) {
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
            'fragments' => array_map(function ($f) {
                return array(
                    'label' => $f['label'],
                    'priority' => $f['priority'],
                    'tokens' => $f['tokens']
                );
            }, $this->fragments)
        );
    }
}
