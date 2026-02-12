<?php
/**
 * Token Estimator Class
 *
 * Approximate token counting for Claude API context management.
 * Uses ~4 characters per token heuristic (validated against Claude tokenizer).
 *
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Token_Estimator {

    /**
     * Approximate characters per token for Claude models.
     * English text averages 3.5-4.5 chars/token; we use 4.0 as balanced estimate.
     */
    const CHARS_PER_TOKEN = 4.0;

    /**
     * Safety buffer multiplier (10% overestimate to prevent undercount).
     */
    const SAFETY_BUFFER = 1.10;

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
        return (int) ceil(mb_strlen($text) / self::CHARS_PER_TOKEN * self::SAFETY_BUFFER);
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
        $max_chars = (int) ($max_tokens * self::CHARS_PER_TOKEN / self::SAFETY_BUFFER);

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
