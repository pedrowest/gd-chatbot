/**
 * GD Chatbot - Frontend JavaScript
 * Handles chat interactions and UI
 */

(function($) {
    'use strict';

    // Chatbot Class
    class GDChatbot {
        constructor(container) {
            this.$container = $(container);
            this.$window = this.$container.find('.gd-chatbot-window');
            this.$toggle = this.$container.find('.gd-chatbot-toggle');
            this.$messages = this.$container.find('.gd-chatbot-messages');
            this.$typing = this.$container.find('.gd-chatbot-typing');
            this.$form = this.$container.find('.gd-chatbot-form');
            this.$input = this.$container.find('.gd-chatbot-input');
            this.$send = this.$container.find('.gd-chatbot-send');
            this.$clear = this.$container.find('.gd-chatbot-clear');
            this.$minimize = this.$container.find('.gd-chatbot-minimize');
            
            this.sessionId = this.$container.data('session') || this.generateSessionId();
            this.conversationHistory = [];
            this.isProcessing = false;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.autoResize();
            this.loadHistory();
        }
        
        bindEvents() {
            // Toggle chat window (floating mode)
            this.$toggle.on('click', () => this.toggleChat());
            this.$minimize.on('click', () => this.toggleChat());
            
            // Form submission
            this.$form.on('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
            
            // Input handling
            this.$input.on('input', () => {
                this.autoResize();
                this.updateSendButton();
            });
            
            // Enter to send (Shift+Enter for new line)
            this.$input.on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Clear conversation
            this.$clear.on('click', () => this.clearConversation());
        }
        
        toggleChat() {
            this.$container.toggleClass('gd-chatbot-open');
            this.$window.toggleClass('gd-chatbot-hidden');
            
            if (!this.$window.hasClass('gd-chatbot-hidden')) {
                this.$input.focus();
                this.scrollToBottom();
            }
        }
        
        autoResize() {
            const textarea = this.$input[0];
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }
        
        updateSendButton() {
            const hasText = this.$input.val().trim().length > 0;
            this.$send.prop('disabled', !hasText || this.isProcessing);
        }
        
        sendMessage() {
            const message = this.$input.val().trim();
            
            if (!message || this.isProcessing) {
                return;
            }
            
            // Add user message to UI
            this.addMessage(message, 'user');
            
            // Clear input
            this.$input.val('');
            this.autoResize();
            this.updateSendButton();
            
            // Send to server
            this.processMessage(message);
        }
        
        async processMessage(message) {
            this.isProcessing = true;
            this.showTyping();
            this.updateSendButton();
            
            // Create placeholder for streaming message
            const messageId = 'msg-' + Date.now();
            const $streamingMessage = this.addStreamingMessage(messageId);
            
            try {
                // Use streaming endpoint
                const formData = new FormData();
                formData.append('action', 'gd_chatbot_stream_message');
                formData.append('nonce', gdChatbot.nonce);
                formData.append('message', message);
                formData.append('session_id', this.sessionId);
                formData.append('history', JSON.stringify(this.conversationHistory.slice(-10)));
                
                const response = await fetch(gdChatbot.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let fullText = '';
                let sources = null;
                
                this.hideTyping();
                
                while (true) {
                    const {done, value} = await reader.read();
                    
                    if (done) {
                        break;
                    }
                    
                    buffer += decoder.decode(value, {stream: true});
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Keep incomplete line in buffer
                    
                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const data = JSON.parse(line.slice(6));
                            
                            if (data.type === 'sources') {
                                sources = data.sources;
                            } else if (data.type === 'content') {
                                fullText = data.full_text;
                                this.updateStreamingMessage(messageId, fullText, sources);
                            } else if (data.type === 'done') {
                                fullText = data.full_text;
                                this.finalizeStreamingMessage(messageId, fullText, sources);
                                
                                // Add to conversation history
                                this.conversationHistory.push(
                                    { role: 'user', content: message },
                                    { role: 'assistant', content: fullText }
                                );
                            } else if (data.type === 'error' || data.error) {
                                this.removeStreamingMessage(messageId);
                                this.addMessage(data.error || gdChatbot.i18n.error, 'assistant', null, true);
                            }
                        }
                    }
                }
                
            } catch (error) {
                this.hideTyping();
                this.removeStreamingMessage(messageId);
                console.error('Streaming error:', error);
                this.addMessage(gdChatbot.i18n.error, 'assistant', null, true);
            }
            
            this.isProcessing = false;
            this.updateSendButton();
            this.$input.focus();
        }
        
        addMessage(text, role, sources = null, isError = false) {
            const $message = $('<div>')
                .addClass('gd-chatbot-message')
                .addClass('gd-chatbot-message-' + role);
            
            // Avatar
            const avatarSvg = role === 'user' 
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>';
            
            $message.append($('<div>').addClass('message-avatar').html(avatarSvg));
            
            // Content
            const $content = $('<div>').addClass('message-content');
            const $text = $('<div>').addClass('message-text');
            
            if (isError) {
                $text.addClass('message-error');
            }
            
            // Render markdown for assistant messages
            if (role === 'assistant' && typeof marked !== 'undefined') {
                $text.html(marked.parse(text));
            } else {
                $text.text(text);
            }
            
            $content.append($text);
            
            // Add copy button for assistant messages
            if (role === 'assistant' && !isError) {
                const $copyBtn = this.createCopyButton(text);
                $content.append($copyBtn);
            }
            
            // Add sources if available
            if (sources && (sources.knowledge_base || sources.web_search)) {
                const $sources = this.renderSources(sources);
                $content.append($sources);
            }
            
            $message.append($content);
            this.$messages.append($message);
            
            this.scrollToBottom();
        }
        
        addStreamingMessage(messageId) {
            const $message = $('<div>')
                .addClass('gd-chatbot-message')
                .addClass('gd-chatbot-message-assistant')
                .attr('data-message-id', messageId);
            
            // Avatar
            const avatarSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>';
            
            $message.append($('<div>').addClass('message-avatar').html(avatarSvg));
            
            // Content
            const $content = $('<div>').addClass('message-content');
            const $text = $('<div>').addClass('message-text').html('<span class="streaming-cursor">▋</span>');
            
            $content.append($text);
            $message.append($content);
            this.$messages.append($message);
            
            this.scrollToBottom();
            
            return $message;
        }
        
        updateStreamingMessage(messageId, text, sources = null) {
            const $message = this.$messages.find(`[data-message-id="${messageId}"]`);
            
            if ($message.length === 0) {
                return;
            }
            
            const $content = $message.find('.message-content');
            const $text = $content.find('.message-text');
            
            // Render markdown
            if (typeof marked !== 'undefined') {
                $text.html(marked.parse(text) + '<span class="streaming-cursor">▋</span>');
            } else {
                $text.text(text).append('<span class="streaming-cursor">▋</span>');
            }
            
            // Add sources if available and not already added
            if (sources && !$content.find('.message-sources').length) {
                if (sources.knowledge_base || sources.web_search) {
                    const $sources = this.renderSources(sources);
                    $content.append($sources);
                }
            }
            
            this.scrollToBottom();
        }
        
        finalizeStreamingMessage(messageId, text, sources = null) {
            const $message = this.$messages.find(`[data-message-id="${messageId}"]`);
            
            if ($message.length === 0) {
                return;
            }
            
            const $content = $message.find('.message-content');
            const $text = $content.find('.message-text');
            
            // Render markdown without cursor
            if (typeof marked !== 'undefined') {
                $text.html(marked.parse(text));
            } else {
                $text.text(text);
            }
            
            // Add copy button if not already added
            if (!$content.find('.message-copy-btn').length) {
                const $copyBtn = this.createCopyButton(text);
                $content.append($copyBtn);
            }
            
            // Add sources if available and not already added
            if (sources && !$content.find('.message-sources').length) {
                if (sources.knowledge_base || sources.web_search) {
                    const $sources = this.renderSources(sources);
                    $content.append($sources);
                }
            }
            
            // Remove the message-id attribute as it's no longer needed
            $message.removeAttr('data-message-id');
            
            this.scrollToBottom();
        }
        
        removeStreamingMessage(messageId) {
            this.$messages.find(`[data-message-id="${messageId}"]`).remove();
        }
        
        createCopyButton(text) {
            const $copyBtn = $('<button>')
                .addClass('message-copy-btn')
                .attr('type', 'button')
                .attr('aria-label', 'Copy to clipboard')
                .attr('title', 'Copy to clipboard');
            
            // Copy icon SVG
            const copyIconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
            
            // Checkmark icon SVG
            const checkIconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
            
            $copyBtn.html(copyIconSvg);
            
            $copyBtn.on('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Copy text to clipboard
                navigator.clipboard.writeText(text).then(() => {
                    // Show success state
                    $copyBtn.addClass('copied').html(checkIconSvg);
                    
                    // Reset after 2 seconds
                    setTimeout(() => {
                        $copyBtn.removeClass('copied').html(copyIconSvg);
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text:', err);
                    // Fallback for older browsers
                    this.fallbackCopyText(text);
                });
            });
            
            return $copyBtn;
        }
        
        fallbackCopyText(text) {
            const $temp = $('<textarea>')
                .val(text)
                .css({
                    position: 'absolute',
                    left: '-9999px'
                })
                .appendTo('body');
            
            $temp[0].select();
            
            try {
                document.execCommand('copy');
                console.log('Text copied using fallback method');
            } catch (err) {
                console.error('Fallback copy failed:', err);
            }
            
            $temp.remove();
        }
        
        renderSources(sources) {
            // Sources section disabled - return null to hide
            return null;
        }
        
        showTyping() {
            this.$typing.removeClass('gd-chatbot-hidden');
            this.scrollToBottom();
        }
        
        hideTyping() {
            this.$typing.addClass('gd-chatbot-hidden');
        }
        
        scrollToBottom() {
            const messages = this.$messages[0];
            messages.scrollTop = messages.scrollHeight;
        }
        
        clearConversation() {
            if (!confirm('Clear all messages in this conversation?')) {
                return;
            }
            
            // Keep only the welcome message
            this.$messages.find('.gd-chatbot-message').not(':first').remove();
            this.conversationHistory = [];
            
            // Clear server-side history
            $.post(gdChatbot.ajaxUrl, {
                action: 'gd_chatbot_clear_history',
                nonce: gdChatbot.nonce,
                session_id: this.sessionId
            });
        }
        
        loadHistory() {
            // Optional: Load previous conversation from session
            // For now, we start fresh each page load
        }
        
        generateSessionId() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
    }
    
    // Initialize all chatbots on the page
    $(document).ready(function() {
        $('.gd-chatbot-container').each(function() {
            new GDChatbot(this);
        });
    });
    
})(jQuery);
