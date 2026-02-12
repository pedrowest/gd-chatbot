<?php
/**
 * Claude API Integration Class
 * 
 * Handles all communication with Anthropic's Claude API
 * 
 * @package GD_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class GD_Claude_API {
    
    /**
     * Claude API endpoint
     */
    const API_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Model to use
     */
    private $model;
    
    /**
     * Max tokens for response
     */
    private $max_tokens;
    
    /**
     * Temperature setting
     */
    private $temperature;
    
    /**
     * System prompt
     */
    private $system_prompt;
    
    /**
     * Constructor
     */
    public function __construct($api_key = null) {
        $this->api_key = $api_key ?: get_option('gd_chatbot_v2_claude_api_key', '');
        $this->model = get_option('gd_chatbot_v2_claude_model', 'claude-sonnet-4-20250514');
        $this->max_tokens = (int) get_option('gd_chatbot_v2_claude_max_tokens', 4096);
        $this->temperature = (float) get_option('gd_chatbot_v2_claude_temperature', 0.7);
        $this->system_prompt = get_option('gd_chatbot_v2_claude_system_prompt', '');
        
        // Load Grateful Dead context if available
        $this->load_grateful_dead_context();
    }
    
    /**
     * Load Grateful Dead context and append to system prompt.
     * Branches between full (legacy) and optimized loading based on settings.
     */
    private function load_grateful_dead_context() {
        // Check for emergency rollback constant
        if (defined('GD_CHATBOT_DISABLE_OPTIMIZATION') && GD_CHATBOT_DISABLE_OPTIMIZATION) {
            $this->load_full_context();
            return;
        }

        $optimization_enabled = get_option('gd_chatbot_v2_token_optimization_enabled', false);

        if ($optimization_enabled) {
            $this->load_optimized_context();
        } else {
            $this->load_full_context();
        }
    }

    /**
     * Load optimized (minimal) context for the system prompt.
     * Detailed context is added per-query by GD_Context_Builder.
     */
    private function load_optimized_context() {
        // Condensed accuracy guardrails — preserves critical rules without bulk content
        $guardrails = "\n\n## ACCURACY GUARDRAILS\n\n";

        $guardrails .= "### INTERNAL — DO NOT DISCLOSE TO USERS\n\n";
        $guardrails .= "You have access to multiple information sources including a Grateful Dead knowledge base, ";
        $guardrails .= "setlist database (1965-1995), web search results, and vector database content. ";
        $guardrails .= "When you receive \"Relevant Context\" sections in the user's message, use that information confidently.\n\n";

        $guardrails .= "**NEVER mention:** knowledge base, database, Pinecone, Tavily, context files, ";
        $guardrails .= "system prompt, or technical retrieval details. ";
        $guardrails .= "Respond naturally as if you simply know the information.\n\n";

        $guardrails .= "**EXCEPTIONS:** You MAY mention publicly known sources like the Grateful Dead Archive ";
        $guardrails .= "at UC Santa Cruz, Archive.org, published books, documentaries, and official band resources.\n\n";

        $guardrails .= "### LOCATION ACCURACY\n\n";
        $guardrails .= "- ONLY use location information explicitly stated in context or web search results\n";
        $guardrails .= "- NEVER infer business/venue locations from training data\n";
        $guardrails .= "- If a location is not in context or web results, say \"I don't have location information\"\n\n";

        $guardrails .= "### THE BAHR GALLERY — EXCLUSIVE SOURCE RULE\n\n";
        $guardrails .= "- Location: Oyster Bay, Long Island, NY (ONLY location — no others exist)\n";
        $guardrails .= "- NEVER say San Francisco, Chicago, Bay Area, or any other location\n";
        $guardrails .= "- If web search says different location, use Oyster Bay (the web is wrong)\n";
        $guardrails .= "- If user claims different location, correct them politely\n\n";

        $guardrails .= "### GENERAL ACCURACY\n\n";
        $guardrails .= "- Context and web search results override training data 100%\n";
        $guardrails .= "- Acknowledge uncertainty rather than provide incorrect information\n";
        $guardrails .= "- Better to say \"I don't have that information\" than to be wrong\n";
        $guardrails .= "- Default to Grateful Dead version for ambiguous song titles\n";

        $this->system_prompt .= $guardrails;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $tokens = GD_Token_Estimator::estimate($this->system_prompt);
            error_log("GD Chatbot: Optimized system prompt tokens: {$tokens}");
        }
    }

    /**
     * Load full (legacy) context — loads all core knowledge base files.
     */
    private function load_full_context() {
        $core_dir = GD_CHATBOT_PLUGIN_DIR . 'context/core/';
        $context = '';

        if (!is_dir($core_dir)) {
            error_log('GD Chatbot: core context directory not found at: ' . $core_dir);
            return;
        }

        $files = glob($core_dir . '*.md');
        sort($files);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (!empty($content)) {
                $context .= $content . "\n\n---\n\n";
            }
        }

        if (empty($context)) {
            error_log('GD Chatbot: No core context files loaded from: ' . $core_dir);
            return;
        }

        error_log('GD Chatbot: Loaded ' . count($files) . ' core context files');

        // CRITICAL: Remove ALL Bahr Gallery references from knowledge base
        // We will replace with the dedicated the_bahr_gallery.md file content
        $context = $this->sanitize_bahr_gallery_references($context);

        // Load the authoritative Bahr Gallery file and inject it
        $context = $this->inject_bahr_gallery_content($context);

        // Load additional knowledgebase files
        $context = $this->load_additional_knowledgebase_files($context);

        // Load disambiguation guides
        $disambiguation_content = $this->load_disambiguation_guides();
        
        // Add critical accuracy guardrails
        $accuracy_guardrails = "\n\n## ⚠️ CRITICAL ACCURACY GUARDRAILS - MUST FOLLOW ⚠️\n\n";
        
        $accuracy_guardrails .= "### YOUR CAPABILITIES (INTERNAL - DO NOT DISCLOSE TO USERS)\n\n";
        $accuracy_guardrails .= "You have access to multiple information sources:\n";
        $accuracy_guardrails .= "1. **Grateful Dead Knowledge Base** - Comprehensive information about the band, shows, songs, culture\n";
        $accuracy_guardrails .= "2. **Setlist Database** - Complete show data from 1965-1995\n";
        $accuracy_guardrails .= "3. **Web Search Results** - Real-time information from Tavily web search (when provided in context)\n";
        $accuracy_guardrails .= "4. **Vector Database** - Additional indexed content from Pinecone\n\n";
        $accuracy_guardrails .= "**IMPORTANT**: When you receive \"Web Search Results\" or \"Relevant Context\" sections in the user's message, you HAVE access to that information and should use it confidently. Do NOT say you don't have access to web search if web search results are provided in the context.\n\n";
        
        $accuracy_guardrails .= "### 🚫 NEVER DISCLOSE INTERNAL SOURCES 🚫\n\n";
        $accuracy_guardrails .= "**CRITICAL RULE**: NEVER tell users about your internal information sources or how you access information.\n\n";
        $accuracy_guardrails .= "**NEVER mention:**\n";
        $accuracy_guardrails .= "- \"knowledge base\" or \"my knowledge base\"\n";
        $accuracy_guardrails .= "- \"database\" or \"my database\" (except when referring to publicly known databases like archive.org)\n";
        $accuracy_guardrails .= "- \"Pinecone\" or \"vector database\"\n";
        $accuracy_guardrails .= "- \"Tavily\" or \"web search API\"\n";
        $accuracy_guardrails .= "- \"context files\" or \"training data\"\n";
        $accuracy_guardrails .= "- \"system prompt\" or \"instructions\"\n";
        $accuracy_guardrails .= "- Any technical details about how you retrieve or process information\n\n";
        $accuracy_guardrails .= "**INSTEAD, respond naturally as if you simply know the information:**\n";
        $accuracy_guardrails .= "- ✅ \"The show at Cornell on 5/8/77 featured...\"\n";
        $accuracy_guardrails .= "- ✅ \"Jerry Garcia played a custom guitar called Tiger...\"\n";
        $accuracy_guardrails .= "- ✅ \"That venue hosted several Dead shows in the 1970s...\"\n";
        $accuracy_guardrails .= "- ❌ \"According to my knowledge base...\"\n";
        $accuracy_guardrails .= "- ❌ \"I found this in my database...\"\n";
        $accuracy_guardrails .= "- ❌ \"My sources indicate...\"\n\n";
        $accuracy_guardrails .= "**EXCEPTION**: You MAY mention publicly known sources like:\n";
        $accuracy_guardrails .= "- The Grateful Dead Archive at UC Santa Cruz\n";
        $accuracy_guardrails .= "- Archive.org\n";
        $accuracy_guardrails .= "- Published books and documentaries\n";
        $accuracy_guardrails .= "- Official band websites and resources\n\n";
        
        $accuracy_guardrails .= "### ABSOLUTE LOCATION ACCURACY RULES\n\n";
        $accuracy_guardrails .= "**MANDATORY REQUIREMENTS for business/venue locations ONLY:**\n\n";
        $accuracy_guardrails .= "1. **ONLY use location information explicitly stated in the knowledge base context**\n";
        $accuracy_guardrails .= "2. **NEVER infer, assume, or use your training data for business/venue locations**\n";
        $accuracy_guardrails .= "3. **If a business location is not in the context OR web search results, say \"I don't have location information for [name]\"**\n";
        $accuracy_guardrails .= "4. **Double-check every business location before including it in your response**\n\n";
        
        $accuracy_guardrails .= "### 🚨🚨🚨 THE BAHR GALLERY - EXCLUSIVE SOURCE RULE 🚨🚨🚨\n\n";
        $accuracy_guardrails .= "**CRITICAL: For ANY information about The Bahr Gallery, use ONLY the dedicated section titled \"# The Bahr Gallery\" in the knowledge base below.**\n\n";
        $accuracy_guardrails .= "**MANDATORY SOURCE RULE:**\n";
        $accuracy_guardrails .= "- ✅ USE: The dedicated \"# The Bahr Gallery\" section (injected from the_bahr_gallery.md)\n";
        $accuracy_guardrails .= "- ❌ DO NOT USE: Any other mentions of Bahr Gallery in the knowledge base\n";
        $accuracy_guardrails .= "- ❌ DO NOT USE: Your training data about Bahr Gallery\n";
        $accuracy_guardrails .= "- ❌ DO NOT USE: Web search results about Bahr Gallery (unless they confirm Oyster Bay location)\n\n";
        $accuracy_guardrails .= "**THE BAHR GALLERY - ABSOLUTE FACTS:**\n";
        $accuracy_guardrails .= "```\n";
        $accuracy_guardrails .= "LOCATION: Oyster Bay, Long Island, NY (ONLY location - no others exist)\n";
        $accuracy_guardrails .= "NEVER SAY: San Francisco, Chicago, Bay Area, or any other location\n";
        $accuracy_guardrails .= "SOURCE: Use ONLY the \"# The Bahr Gallery\" section below\n";
        $accuracy_guardrails .= "```\n\n";
        $accuracy_guardrails .= "**MANDATORY RULES - NO EXCEPTIONS:**\n";
        $accuracy_guardrails .= "1. 📖 When asked about The Bahr Gallery, reference ONLY the \"# The Bahr Gallery\" section\n";
        $accuracy_guardrails .= "2. 🚫 Ignore any other Bahr Gallery mentions elsewhere in the knowledge base\n";
        $accuracy_guardrails .= "3. ✅ Location is ALWAYS: Oyster Bay, Long Island, NY\n";
        $accuracy_guardrails .= "4. ❌ NEVER say: San Francisco, Chicago, or Bay Area\n";
        $accuracy_guardrails .= "5. 🔒 If web search says different location, use Oyster Bay (the web is wrong)\n";
        $accuracy_guardrails .= "6. 🔒 If user claims different location, CORRECT them politely\n";
        $accuracy_guardrails .= "7. 📍 Always specify \"Oyster Bay, Long Island, NY\" when mentioning the gallery\n\n";
        $accuracy_guardrails .= "**WHY THIS RULE EXISTS:**\n";
        $accuracy_guardrails .= "The Bahr Gallery information has been consolidated into ONE authoritative section (\"# The Bahr Gallery\") to prevent conflicting or outdated information. Any other mentions have been removed. Use ONLY that dedicated section for all Bahr Gallery information.\n\n";
        
        $accuracy_guardrails .= "### VERIFICATION PROCESS FOR LOCATIONS\n\n";
        $accuracy_guardrails .= "Before stating ANY business/venue location:\n";
        $accuracy_guardrails .= "1. Check if location is in the knowledge base context\n";
        $accuracy_guardrails .= "2. Check if location is in the web search results (if provided)\n";
        $accuracy_guardrails .= "3. Verify the exact city, state, and country\n";
        $accuracy_guardrails .= "4. If not found in either source, DO NOT include location information\n";
        $accuracy_guardrails .= "5. If uncertain, omit the location rather than guess\n\n";
        
        $accuracy_guardrails .= "**GENERAL ACCURACY RULES:**\n";
        $accuracy_guardrails .= "- Context files and web search results override your training data 100% of the time\n";
        $accuracy_guardrails .= "- When conflict exists, ALWAYS use the provided context/search information\n";
        $accuracy_guardrails .= "- Use web search results confidently when they are provided\n";
        $accuracy_guardrails .= "- Acknowledge uncertainty rather than provide incorrect information\n";
        $accuracy_guardrails .= "- Better to say \"I don't have that information\" than to be wrong\n";
        
        // Append disambiguation rules BEFORE other context
        $accuracy_guardrails .= $disambiguation_content;
        
        // Append the comprehensive Grateful Dead knowledge to the system prompt
        $this->system_prompt .= $accuracy_guardrails . "\n\n## GRATEFUL DEAD KNOWLEDGE BASE\n\nThe following is comprehensive reference material about the Grateful Dead. Use this information to answer user questions accurately and in detail.\n\n" . $context;
    }
    
    /**
     * Load disambiguation guides for song titles
     */
    private function load_disambiguation_guides() {
        $disambiguation_text = "\n\n### 🎵 SONG TITLE DISAMBIGUATION RULES 🎵\n\n";
        $disambiguation_text .= "**CRITICAL**: Many Grateful Dead songs share titles with songs by other artists. Always disambiguate based on context.\n\n";
        
        // Load the comprehensive disambiguation guide
        $guide_file = GD_CHATBOT_PLUGIN_DIR . 'context/disambiguation/song-titles.md';
        if (file_exists($guide_file)) {
            $guide_content = file_get_contents($guide_file);
            if (!empty($guide_content)) {
                $disambiguation_text .= "#### Detailed Song Disambiguation Guide\n\n";
                $disambiguation_text .= $guide_content . "\n\n";
                error_log('GD Chatbot: Loaded disambiguation/song-titles.md');
            }
        } else {
            error_log('GD Chatbot: song-titles.md not found at: ' . $guide_file);
        }

        // Load the duplicate titles summary
        $summary_file = GD_CHATBOT_PLUGIN_DIR . 'context/disambiguation/duplicate-titles.md';
        if (file_exists($summary_file)) {
            $summary_content = file_get_contents($summary_file);
            if (!empty($summary_content)) {
                $disambiguation_text .= "#### Quick Reference - Songs with Duplicate Titles\n\n";
                $disambiguation_text .= $summary_content . "\n\n";
                error_log('GD Chatbot: Loaded disambiguation/duplicate-titles.md');
            }
        } else {
            error_log('GD Chatbot: duplicate-titles.md not found at: ' . $summary_file);
        }

        // Load music equipment disambiguation guide
        $equipment_file = GD_CHATBOT_PLUGIN_DIR . 'context/disambiguation/equipment-names.md';
        if (file_exists($equipment_file)) {
            $equipment_content = file_get_contents($equipment_file);
            if (!empty($equipment_content)) {
                $disambiguation_text .= "#### Music Equipment Name Disambiguation Guide\n\n";
                $disambiguation_text .= $equipment_content . "\n\n";
                error_log('GD Chatbot: Loaded disambiguation/equipment-names.md');
            }
        } else {
            error_log('GD Chatbot: equipment-names.md not found at: ' . $equipment_file);
        }

        // Add usage instructions
        $disambiguation_text .= "**HOW TO USE THESE DISAMBIGUATION RULES:**\n\n";
        $disambiguation_text .= "1. When a user mentions a song title from the lists above, check if they specify \"Grateful Dead\" or context clues\n";
        $disambiguation_text .= "2. If ambiguous, DEFAULT to the Grateful Dead version (since this is a GD-focused chatbot)\n";
        $disambiguation_text .= "3. For high-confusion songs (marked with **bold** in the summary), proactively clarify which version you're discussing\n";
        $disambiguation_text .= "4. Use the \"Key identifiers\" to help users understand which version\n";
        $disambiguation_text .= "5. If discussing both versions, clearly distinguish them\n";
        $disambiguation_text .= "6. For music equipment brand names (Fender, Marshall, Orange, Pearl, etc.), default to the music equipment meaning in this music-focused chatbot\n";
        $disambiguation_text .= "7. For equipment names marked 'can be', ask for clarification if context is truly ambiguous\n\n";
        $disambiguation_text .= "**EXAMPLES:**\n";
        $disambiguation_text .= "- User: \"Tell me about Loser\" → Assume GD version (Garcia/Hunter), but mention Beck's version exists\n";
        $disambiguation_text .= "- User: \"When did Beck release Loser?\" → They mean Beck's version, not GD\n";
        $disambiguation_text .= "- User: \"Comes a Time lyrics\" → Assume GD version (Garcia/Hunter), mention Neil Young has different song with same title\n";
        $disambiguation_text .= "- User: \"Fire on the Mountain Marshall Tucker\" → They specifically mean Marshall Tucker's version\n\n";
        
        return $disambiguation_text;
    }
    
    /**
     * Sanitize Bahr Gallery references - REMOVE ALL references from knowledge base
     * The authoritative content will be injected from the_bahr_gallery.md
     */
    private function sanitize_bahr_gallery_references($content) {
        $lines = explode("\n", $content);
        $sanitized_lines = array();
        
        foreach ($lines as $line) {
            // Remove ANY line that mentions Bahr Gallery or bahrgallery
            if (stripos($line, 'Bahr Gallery') !== false || stripos($line, 'bahrgallery') !== false) {
                error_log('GD Chatbot: Removed Bahr Gallery reference from knowledge base: ' . trim($line));
                continue;
            }
            
            $sanitized_lines[] = $line;
        }
        
        return implode("\n", $sanitized_lines);
    }
    
    /**
     * Inject authoritative Bahr Gallery content from dedicated file
     * This ensures ONLY the_bahr_gallery.md content is used
     */
    private function inject_bahr_gallery_content($context) {
        $bahr_file = GD_CHATBOT_PLUGIN_DIR . 'context/supplementary/bahr-gallery.md';
        
        if (!file_exists($bahr_file)) {
            error_log('GD Chatbot: the_bahr_gallery.md file not found at: ' . $bahr_file);
            return $context;
        }
        
        $bahr_content = file_get_contents($bahr_file);
        
        if (empty($bahr_content)) {
            error_log('GD Chatbot: the_bahr_gallery.md file is empty');
            return $context;
        }
        
        // Find the Art Galleries section and inject the Bahr Gallery content there
        // Look for the "Art Galleries & Museums" section
        $pattern = '/(## Art Galleries & Museums.*?### United States.*?#### New York)/s';
        
        if (preg_match($pattern, $context, $matches)) {
            // Inject the Bahr Gallery content after the New York header
            $injection = $matches[1] . "\n\n" . $bahr_content . "\n\n";
            $context = preg_replace($pattern, $injection, $context);
            error_log('GD Chatbot: Successfully injected the_bahr_gallery.md content into knowledge base');
        } else {
            // If pattern not found, append at the end
            $context .= "\n\n---\n\n" . $bahr_content;
            error_log('GD Chatbot: Appended the_bahr_gallery.md content to end of knowledge base');
        }
        
        return $context;
    }
    
    /**
     * Load additional knowledgebase files from supplementary directory.
     * Automatically picks up any .md files in context/supplementary/,
     * excluding bahr-gallery.md which is handled by inject_bahr_gallery_content().
     */
    private function load_additional_knowledgebase_files($context) {
        $supp_dir = GD_CHATBOT_PLUGIN_DIR . 'context/supplementary/';

        if (!is_dir($supp_dir)) {
            error_log('GD Chatbot: supplementary context directory not found at: ' . $supp_dir);
            return $context;
        }

        $files = glob($supp_dir . '*.md');
        sort($files);

        $additional_context = "\n\n---\n\n## ADDITIONAL GRATEFUL DEAD KNOWLEDGE BASE RESOURCES\n\n";
        $loaded_count = 0;

        foreach ($files as $file_path) {
            $filename = basename($file_path);

            // Skip bahr-gallery.md — handled separately by inject_bahr_gallery_content()
            if ($filename === 'bahr-gallery.md') {
                continue;
            }

            $file_content = file_get_contents($file_path);

            if (!empty($file_content)) {
                $additional_context .= "\n\n### " . str_replace('.md', '', $filename) . "\n\n";
                $additional_context .= $file_content . "\n";
                $loaded_count++;
                error_log('GD Chatbot: Loaded supplementary file: ' . $filename);
            }
        }

        if ($loaded_count > 0) {
            error_log("GD Chatbot: Successfully loaded {$loaded_count} supplementary knowledgebase files");
            $context .= $additional_context;
        } else {
            error_log('GD Chatbot: No supplementary knowledgebase files were loaded');
        }

        return $context;
    }
    
    /**
     * Set custom system prompt
     */
    public function set_system_prompt($prompt) {
        $this->system_prompt = $prompt;
    }
    
    /**
     * Append to system prompt (for RAG context)
     */
    public function append_to_system_prompt($content) {
        $this->system_prompt .= "\n\n" . $content;
    }
    
    /**
     * Send a message to Claude with streaming
     * 
     * @param string $user_message The user's message
     * @param array $conversation_history Previous messages in the conversation
     * @param string $additional_context Optional additional context (from Pinecone/Tavily)
     * @param callable $callback Function to call for each chunk
     * @return array|WP_Error Response data or error
     */
    public function send_message_stream($user_message, $conversation_history = array(), $additional_context = '', $callback = null) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Claude API key is not configured.');
        }
        
        // Build messages array
        $messages = array();
        
        // Add conversation history
        if (!empty($conversation_history)) {
            foreach ($conversation_history as $msg) {
                $messages[] = array(
                    'role' => $msg['role'],
                    'content' => $msg['content']
                );
            }
        }
        
        // Add current user message with optional context
        $final_user_message = $user_message;
        if (!empty($additional_context)) {
            $final_user_message = "## Relevant Context\n\n" . $additional_context . "\n\n## User Question\n\n" . $user_message;
        }
        
        $messages[] = array(
            'role' => 'user',
            'content' => $final_user_message
        );
        
        // Build request body
        $body = array(
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'messages' => $messages,
            'stream' => true
        );
        
        // Add system prompt if set
        if (!empty($this->system_prompt)) {
            $body['system'] = $this->system_prompt;
        }
        
        // Add temperature if not default
        if ($this->temperature != 1.0) {
            $body['temperature'] = $this->temperature;
        }
        
        // Use cURL for streaming
        $ch = curl_init(self::API_ENDPOINT);
        
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'x-api-key: ' . $this->api_key,
                'anthropic-version: 2023-06-01'
            ),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function($curl, $data) use ($callback) {
                static $buffer = '';
                static $full_text = '';
                static $usage = array('input_tokens' => 0, 'output_tokens' => 0);
                static $model = '';
                static $stop_reason = '';
                
                $buffer .= $data;
                $lines = explode("\n", $buffer);
                
                // Keep the last incomplete line in the buffer
                $buffer = array_pop($lines);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    
                    if (empty($line) || $line === 'event: ping') {
                        continue;
                    }
                    
                    // Remove "data: " prefix
                    if (strpos($line, 'data: ') === 0) {
                        $line = substr($line, 6);
                    }
                    
                    // Skip non-JSON lines
                    if (empty($line) || $line[0] !== '{') {
                        continue;
                    }
                    
                    $event = json_decode($line, true);
                    
                    if (!$event) {
                        continue;
                    }
                    
                    // Handle different event types
                    if (isset($event['type'])) {
                        switch ($event['type']) {
                            case 'message_start':
                                if (isset($event['message']['model'])) {
                                    $model = $event['message']['model'];
                                }
                                if (isset($event['message']['usage'])) {
                                    $usage['input_tokens'] = $event['message']['usage']['input_tokens'] ?? 0;
                                }
                                break;
                                
                            case 'content_block_delta':
                                if (isset($event['delta']['text'])) {
                                    $text = $event['delta']['text'];
                                    $full_text .= $text;
                                    
                                    if ($callback) {
                                        call_user_func($callback, array(
                                            'type' => 'content',
                                            'text' => $text,
                                            'full_text' => $full_text
                                        ));
                                    }
                                }
                                break;
                                
                            case 'message_delta':
                                if (isset($event['delta']['stop_reason'])) {
                                    $stop_reason = $event['delta']['stop_reason'];
                                }
                                if (isset($event['usage']['output_tokens'])) {
                                    $usage['output_tokens'] = $event['usage']['output_tokens'];
                                }
                                break;
                                
                            case 'message_stop':
                                if ($callback) {
                                    call_user_func($callback, array(
                                        'type' => 'done',
                                        'full_text' => $full_text,
                                        'model' => $model,
                                        'usage' => $usage,
                                        'stop_reason' => $stop_reason
                                    ));
                                }
                                break;
                                
                            case 'error':
                                if ($callback) {
                                    call_user_func($callback, array(
                                        'type' => 'error',
                                        'error' => $event['error']['message'] ?? 'Unknown error'
                                    ));
                                }
                                break;
                        }
                    }
                }
                
                return strlen($data);
            },
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true
        ));
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        if ($result === false) {
            return new WP_Error('curl_error', 'Connection error: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            $error_message = 'HTTP ' . $http_code;
            switch ($http_code) {
                case 401:
                    return new WP_Error('auth_error', 'Invalid Claude API key.');
                case 429:
                    return new WP_Error('rate_limit', 'Rate limit exceeded.');
                case 500:
                case 502:
                case 503:
                    return new WP_Error('server_error', 'Claude API temporarily unavailable.');
                default:
                    return new WP_Error('api_error', $error_message);
            }
        }
        
        return array('success' => true);
    }
    
    /**
     * Send a message to Claude
     * 
     * @param string $user_message The user's message
     * @param array $conversation_history Previous messages in the conversation
     * @param string $additional_context Optional additional context (from Pinecone/Tavily)
     * @return array|WP_Error Response data or error
     */
    public function send_message($user_message, $conversation_history = array(), $additional_context = '') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Claude API key is not configured.');
        }
        
        // Build messages array
        $messages = array();
        
        // Add conversation history
        if (!empty($conversation_history)) {
            foreach ($conversation_history as $msg) {
                $messages[] = array(
                    'role' => $msg['role'],
                    'content' => $msg['content']
                );
            }
        }
        
        // Add current user message with optional context
        $final_user_message = $user_message;
        if (!empty($additional_context)) {
            $final_user_message = "## Relevant Context\n\n" . $additional_context . "\n\n## User Question\n\n" . $user_message;
        }
        
        $messages[] = array(
            'role' => 'user',
            'content' => $final_user_message
        );
        
        // Build request body
        $body = array(
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'messages' => $messages,
        );
        
        // Add system prompt if set
        if (!empty($this->system_prompt)) {
            $body['system'] = $this->system_prompt;
        }
        
        // Add temperature if not default
        if ($this->temperature != 1.0) {
            $body['temperature'] = $this->temperature;
        }
        
        // Make API request
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 120,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
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
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            
            // Specific error handling
            switch ($response_code) {
                case 401:
                    return new WP_Error('auth_error', 'Invalid Claude API key. Please check your API key in settings.');
                case 429:
                    return new WP_Error('rate_limit', 'Rate limit exceeded. Please try again in a moment.');
                case 500:
                case 502:
                case 503:
                    return new WP_Error('server_error', 'Claude API is temporarily unavailable. Please try again.');
                default:
                    return new WP_Error('api_error', $error_message);
            }
        }
        
        // Extract the assistant's response
        if (!isset($data['content'][0]['text'])) {
            return new WP_Error('parse_error', 'Unable to parse Claude response.');
        }
        
        return array(
            'message' => $data['content'][0]['text'],
            'model' => $data['model'],
            'usage' => array(
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0
            ),
            'stop_reason' => $data['stop_reason'] ?? ''
        );
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API key is required.');
        }
        
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $this->model,
                'max_tokens' => 10,
                'messages' => array(
                    array('role' => 'user', 'content' => 'Hi')
                )
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
        $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Connection failed';
        
        return new WP_Error('connection_failed', $error_message);
    }
    
    /**
     * Get available models
     * Organized by model family with Opus models prominently featured
     */
    public static function get_available_models() {
        return array(
            // Claude 4 Models (Latest)
            'claude-opus-4-20250514' => 'Claude Opus 4 — Most Capable, Best for Complex Tasks',
            'claude-sonnet-4-20250514' => 'Claude Sonnet 4 — Balanced Performance (Recommended)',
            
            // Claude 3.5 Models
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet — Strong Performance',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku — Fast & Efficient',
            
            // Claude 3 Models (Legacy)
            'claude-3-opus-20240229' => 'Claude 3 Opus — Previous Gen Most Capable',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet — Previous Gen Balanced',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku — Previous Gen Fast'
        );
    }
    
    /**
     * Get model info with capabilities
     */
    public static function get_model_info($model_id) {
        $models = array(
            'claude-opus-4-20250514' => array(
                'name' => 'Claude Opus 4',
                'family' => 'claude-4',
                'tier' => 'opus',
                'context_window' => 200000,
                'max_output' => 32000,
                'description' => 'Most capable model for complex reasoning, analysis, and creative tasks',
                'best_for' => array('Complex analysis', 'Research', 'Code generation', 'Creative writing')
            ),
            'claude-sonnet-4-20250514' => array(
                'name' => 'Claude Sonnet 4',
                'family' => 'claude-4',
                'tier' => 'sonnet',
                'context_window' => 200000,
                'max_output' => 16000,
                'description' => 'Excellent balance of capability and speed',
                'best_for' => array('General assistance', 'Content creation', 'Customer support', 'Data analysis')
            ),
            'claude-3-5-sonnet-20241022' => array(
                'name' => 'Claude 3.5 Sonnet',
                'family' => 'claude-3.5',
                'tier' => 'sonnet',
                'context_window' => 200000,
                'max_output' => 8192,
                'description' => 'Strong performance with good speed',
                'best_for' => array('General tasks', 'Coding', 'Analysis')
            ),
            'claude-3-5-haiku-20241022' => array(
                'name' => 'Claude 3.5 Haiku',
                'family' => 'claude-3.5',
                'tier' => 'haiku',
                'context_window' => 200000,
                'max_output' => 8192,
                'description' => 'Fastest model for quick responses',
                'best_for' => array('Quick queries', 'Simple tasks', 'High volume')
            ),
            'claude-3-opus-20240229' => array(
                'name' => 'Claude 3 Opus',
                'family' => 'claude-3',
                'tier' => 'opus',
                'context_window' => 200000,
                'max_output' => 4096,
                'description' => 'Previous generation most capable model',
                'best_for' => array('Complex tasks', 'Detailed analysis')
            ),
            'claude-3-sonnet-20240229' => array(
                'name' => 'Claude 3 Sonnet',
                'family' => 'claude-3',
                'tier' => 'sonnet',
                'context_window' => 200000,
                'max_output' => 4096,
                'description' => 'Previous generation balanced model',
                'best_for' => array('General assistance')
            ),
            'claude-3-haiku-20240307' => array(
                'name' => 'Claude 3 Haiku',
                'family' => 'claude-3',
                'tier' => 'haiku',
                'context_window' => 200000,
                'max_output' => 4096,
                'description' => 'Previous generation fast model',
                'best_for' => array('Quick responses', 'Simple queries')
            )
        );
        
        return isset($models[$model_id]) ? $models[$model_id] : null;
    }
    
    /**
     * Check if model is an Opus tier model
     */
    public static function is_opus_model($model_id) {
        $info = self::get_model_info($model_id);
        return $info && $info['tier'] === 'opus';
    }
    
    /**
     * List models available from the Anthropic API.
     *
     * @param string|null $api_key Optional API key override.
     * @return array|WP_Error Array of model data on success, WP_Error on failure.
     */
    public function list_models($api_key = null) {
        $key = $api_key ?: $this->api_key;

        if (empty($key)) {
            return new WP_Error('missing_key', 'API key is required to list models.');
        }

        $response = wp_remote_get('https://api.anthropic.com/v1/models?limit=50', array(
            'headers' => array(
                'x-api-key'         => $key,
                'anthropic-version'  => '2023-06-01',
                'content-type'       => 'application/json',
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            $msg = isset($body['error']['message']) ? $body['error']['message'] : 'HTTP ' . $code;
            return new WP_Error('api_error', $msg);
        }

        if (!isset($body['data']) || !is_array($body['data'])) {
            return new WP_Error('invalid_response', 'Unexpected API response format.');
        }

        return $body['data'];
    }

    /**
     * Generate embeddings for text (for Pinecone integration)
     * Note: Claude doesn't have a direct embedding API, so this would need
     * to use a different service like OpenAI or Voyage AI for embeddings
     */
    public function generate_embedding($text) {
        // Placeholder - would integrate with embedding service
        return new WP_Error('not_implemented', 'Embedding generation requires separate embedding API configuration.');
    }
}
