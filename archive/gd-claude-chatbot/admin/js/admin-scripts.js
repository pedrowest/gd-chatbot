/**
 * GD Claude Chatbot - Admin Scripts
 */

(function($) {
    'use strict';

    // Model information data
    var modelInfo = {
        'claude-opus-4-20250514': {
            name: 'Claude Opus 4',
            tier: 'opus',
            context_window: 200000,
            max_output: 32000,
            description: 'Most capable model for complex reasoning, analysis, and creative tasks',
            best_for: ['Complex analysis', 'Research', 'Code generation', 'Creative writing']
        },
        'claude-sonnet-4-20250514': {
            name: 'Claude Sonnet 4',
            tier: 'sonnet',
            context_window: 200000,
            max_output: 16000,
            description: 'Excellent balance of capability and speed',
            best_for: ['General assistance', 'Content creation', 'Customer support', 'Data analysis']
        },
        'claude-3-5-sonnet-20241022': {
            name: 'Claude 3.5 Sonnet',
            tier: 'sonnet',
            context_window: 200000,
            max_output: 8192,
            description: 'Strong performance with good speed',
            best_for: ['General tasks', 'Coding', 'Analysis']
        },
        'claude-3-5-haiku-20241022': {
            name: 'Claude 3.5 Haiku',
            tier: 'haiku',
            context_window: 200000,
            max_output: 8192,
            description: 'Fastest model for quick responses',
            best_for: ['Quick queries', 'Simple tasks', 'High volume']
        },
        'claude-3-opus-20240229': {
            name: 'Claude 3 Opus',
            tier: 'opus',
            context_window: 200000,
            max_output: 4096,
            description: 'Previous generation most capable model',
            best_for: ['Complex tasks', 'Detailed analysis']
        },
        'claude-3-sonnet-20240229': {
            name: 'Claude 3 Sonnet',
            tier: 'sonnet',
            context_window: 200000,
            max_output: 4096,
            description: 'Previous generation balanced model',
            best_for: ['General assistance']
        },
        'claude-3-haiku-20240307': {
            name: 'Claude 3 Haiku',
            tier: 'haiku',
            context_window: 200000,
            max_output: 4096,
            description: 'Previous generation fast model',
            best_for: ['Quick responses', 'Simple queries']
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        initTogglePassword();
        initTemperatureSlider();
        initColorPicker();
        initTestConnections();
        initCopyShortcode();
        initModelSelector();
        initClearCache();
    });

    /**
     * Toggle password visibility
     */
    function initTogglePassword() {
        $('.toggle-password').on('click', function() {
            var targetId = $(this).data('target');
            var $input = $('#' + targetId);
            var $icon = $(this).find('.dashicons');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
    }

    /**
     * Temperature slider with live value display
     */
    function initTemperatureSlider() {
        $('.temperature-slider').on('input', function() {
            $(this).siblings('.temperature-value').text($(this).val());
        });
    }

    /**
     * Color picker preview
     */
    function initColorPicker() {
        $('input[type="color"]').on('input', function() {
            $(this).siblings('.color-preview').css('background-color', $(this).val());
        });
    }

    /**
     * Test API connections
     */
    function initTestConnections() {
        $('.test-connection').on('click', function() {
            var $button = $(this);
            var api = $button.data('api');
            var $status = $button.siblings('.connection-status');

            // Show loading state
            $button.prop('disabled', true);
            $status.removeClass('success error').addClass('loading').text('Testing...');

            // Prepare request data based on API type
            var data = {
                action: 'gd_test_' + api + '_connection',
                nonce: gdChatbotAdmin.nonce
            };

            // Get relevant API key
            switch (api) {
                case 'claude':
                    data.api_key = $('#claude_api_key').val();
                    break;
                case 'tavily':
                    data.api_key = $('#tavily_api_key').val();
                    break;
                case 'pinecone':
                    data.api_key = $('#pinecone_api_key').val();
                    data.host = $('#pinecone_host').val();
                    break;
            }

            // Make AJAX request
            $.post(gdChatbotAdmin.ajaxUrl, data, function(response) {
                $button.prop('disabled', false);
                $status.removeClass('loading');

                if (response.success) {
                    $status.addClass('success').text('✓ Connected');
                } else {
                    $status.addClass('error').text('✗ ' + (response.data.message || 'Failed'));
                }

                // Clear status after 5 seconds
                setTimeout(function() {
                    $status.removeClass('success error').text('');
                }, 5000);
            }).fail(function() {
                $button.prop('disabled', false);
                $status.removeClass('loading').addClass('error').text('✗ Request failed');
            });
        });
    }

    /**
     * Copy shortcode to clipboard
     */
    function initCopyShortcode() {
        $('.copy-shortcode').on('click', function() {
            var shortcode = $(this).data('shortcode');
            var $button = $(this);

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    showCopySuccess($button);
                }).catch(function() {
                    fallbackCopy(shortcode, $button);
                });
            } else {
                fallbackCopy(shortcode, $button);
            }
        });
    }

    /**
     * Fallback copy method
     */
    function fallbackCopy(text, $button) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();

        try {
            document.execCommand('copy');
            showCopySuccess($button);
        } catch (err) {
            console.error('Copy failed:', err);
        }

        $temp.remove();
    }

    /**
     * Show copy success feedback
     */
    function showCopySuccess($button) {
        var originalHtml = $button.html();
        $button.html('<span class="dashicons dashicons-yes"></span> Copied!');

        setTimeout(function() {
            $button.html(originalHtml);
        }, 2000);
    }

    /**
     * Model selector with dynamic info display
     */
    function initModelSelector() {
        var $select = $('#claude_model');
        var $infoBox = $('#model-info');

        if (!$select.length) {
            return;
        }

        $select.on('change', function() {
            var selectedModel = $(this).val();
            var info = modelInfo[selectedModel];

            if (!info) {
                $infoBox.html('');
                return;
            }

            var isOpus = info.tier === 'opus';
            var html = '<div class="model-details ' + (isOpus ? 'opus-model' : '') + '">';

            if (isOpus) {
                html += '<span class="opus-badge">⭐ OPUS</span>';
            }

            html += '<p><strong>' + escapeHtml(info.name) + '</strong></p>';
            html += '<p>' + escapeHtml(info.description) + '</p>';
            html += '<p class="model-specs">';
            html += 'Context: ' + numberFormat(info.context_window) + ' tokens | ';
            html += 'Max Output: ' + numberFormat(info.max_output) + ' tokens';
            html += '</p>';
            html += '<p class="model-best-for">';
            html += '<strong>Best for:</strong> ' + escapeHtml(info.best_for.join(', '));
            html += '</p>';
            html += '</div>';

            $infoBox.html(html);

            // Update max tokens suggestion based on model
            var $maxTokens = $('#claude_max_tokens');
            if ($maxTokens.length) {
                var suggestedMax = Math.min(info.max_output, 8192);
                if ($maxTokens.val() > info.max_output) {
                    $maxTokens.val(suggestedMax);
                }
            }
        });
    }

    /**
     * Escape HTML for safe display
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Format number with commas
     */
    function numberFormat(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Clear Tavily cache
     */
    function initClearCache() {
        $('#clear-tavily-cache').on('click', function() {
            var $button = $(this);
            var $status = $('#cache-clear-result');
            
            if (!confirm('Are you sure you want to clear the Tavily cache? This will force fresh API calls for all queries.')) {
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true);
            $status.removeClass('success error').addClass('loading').text('Clearing...');
            
            // Make AJAX request
            $.post(gdChatbotAdmin.ajaxUrl, {
                action: 'gd_clear_tavily_cache',
                nonce: gdChatbotAdmin.nonce
            }, function(response) {
                $button.prop('disabled', false);
                $status.removeClass('loading');
                
                if (response.success) {
                    $status.addClass('success').text('✓ Cache cleared successfully');
                    // Reload page after 2 seconds to show updated stats
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $status.addClass('error').text('✗ ' + (response.data.message || 'Failed to clear cache'));
                }
                
                // Clear status after 5 seconds
                setTimeout(function() {
                    $status.removeClass('success error').text('');
                }, 5000);
            }).fail(function() {
                $button.prop('disabled', false);
                $status.removeClass('loading').addClass('error').text('✗ Request failed');
            });
        });
    }

})(jQuery);
