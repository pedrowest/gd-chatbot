/**
 * GD Chatbot - Song Modal
 * Handles song link clicks and displays Archive.org recordings
 */

(function($) {
    'use strict';

    class GDSongModal {
        constructor() {
            this.$modal = null;
            this.currentSong = null;
            this.currentSource = 'archive';
            this.performanceCache = {};
            this.audioPlayer = null;
            this.connectedServices = [];
            this.availableSources = [];
            
            this.init();
        }
        
        init() {
            this.createModal();
            this.bindEvents();
        }
        
        createModal() {
            const modalHTML = `
                <div id="gd-song-modal" class="gd-modal" style="display: none;">
                    <div class="gd-modal-backdrop"></div>
                    <div class="gd-modal-dialog">
                        <div class="gd-modal-header">
                            <h3 id="gd-modal-song-title"></h3>
                            <button class="gd-modal-close" aria-label="Close">&times;</button>
                        </div>
                        
                        <!-- Source Tabs -->
                        <div class="gd-source-tabs" id="gd-source-tabs">
                            <!-- Tabs populated dynamically -->
                        </div>
                        
                        <div class="gd-modal-body">
                            <!-- Performance List -->
                            <div class="gd-performance-list">
                                <div class="gd-performance-filters">
                                    <label for="gd-archive-sort">Sort by:</label>
                                    <select id="gd-archive-sort">
                                        <option value="downloads">Most Popular</option>
                                        <option value="date">Date (Oldest First)</option>
                                        <option value="rating">Highest Rated</option>
                                    </select>
                                </div>
                                
                                <div class="gd-performance-scroll" id="gd-performance-scroll">
                                    <!-- Performances loaded via AJAX -->
                                </div>
                            </div>
                            
                            <!-- Audio Player -->
                            <div class="gd-audio-player" id="gd-audio-player" style="display: none;">
                                <div class="player-header">
                                    <img src="" alt="Show" class="player-thumb" id="player-thumb" />
                                    <div class="player-info">
                                        <h4 id="player-title"></h4>
                                        <p id="player-subtitle"></p>
                                    </div>
                                    <button class="player-close" id="player-close" aria-label="Close player">&times;</button>
                                </div>
                                
                                <audio id="gd-audio-element" controls>
                                    <source src="" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            this.$modal = $('#gd-song-modal');
            this.audioPlayer = document.getElementById('gd-audio-element');
        }
        
        bindEvents() {
            // Close modal
            this.$modal.find('.gd-modal-close').on('click', () => this.close());
            this.$modal.find('.gd-modal-backdrop').on('click', () => this.close());
            
            // Sort change
            $('#gd-archive-sort').on('change', (e) => {
                if (this.currentSong) {
                    this.loadPerformances(this.currentSong, $(e.target).val());
                }
            });
            
            // Source tab clicks (delegated)
            this.$modal.on('click', '.gd-source-tab', (e) => {
                const $tab = $(e.currentTarget);
                const source = $tab.data('source');
                this.switchSource(source);
            });
            
            // Play buttons (delegated)
            this.$modal.on('click', '.gd-play-btn', (e) => {
                const $btn = $(e.currentTarget);
                const identifier = $btn.data('identifier');
                const url = $btn.data('url');
                const title = $btn.data('title');
                const subtitle = $btn.data('subtitle');
                const thumb = $btn.data('thumb');
                const service = $btn.data('service') || 'archive';
                
                this.playPerformance(identifier, url, title, subtitle, thumb, service);
            });
            
            // Close player
            $('#player-close').on('click', () => this.closePlayer());
            
            // Song link clicks (delegated to document)
            $(document).on('click', '.gd-song-link', (e) => {
                e.preventDefault();
                const $link = $(e.currentTarget);
                const songData = {
                    id: $link.data('song-id'),
                    title: $link.data('song-title'),
                    author: $link.data('song-author')
                };
                this.open(songData);
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                if (this.$modal.is(':visible')) {
                    if (e.key === 'Escape') {
                        this.close();
                    }
                }
            });
        }
        
        open(songData) {
            this.currentSong = songData;
            $('#gd-modal-song-title').text(songData.title);
            this.$modal.fadeIn(200);
            $('body').addClass('gd-modal-open');
            
            // Check for connected services first
            this.checkConnectedServices().then(() => {
                // Build source tabs
                this.buildSourceTabs();
                
                // Load performances for default source
                this.loadPerformances(songData, 'downloads');
            });
        }
        
        close() {
            this.$modal.fadeOut(200);
            $('body').removeClass('gd-modal-open');
            this.closePlayer();
        }
        
        async loadPerformances(songData, sortBy) {
            // If we have connected services, use unified search
            if (this.connectedServices.length > 0) {
                this.loadUnifiedResults(songData, sortBy);
                return;
            }
            
            // Otherwise, use Archive.org only
            const cacheKey = `${songData.id}_${sortBy}`;
            
            // Check cache
            if (this.performanceCache[cacheKey]) {
                this.renderPerformances(this.performanceCache[cacheKey]);
                return;
            }
            
            // Show loading state
            const $scroll = $('#gd-performance-scroll');
            $scroll.html('<div class="gd-loading"><span class="spinner"></span> Loading performances...</div>');
            
            try {
                const response = await fetch(gdChatbot.ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'gd_chatbot_archive_search',
                        nonce: gdChatbot.nonce,
                        song_title: songData.title,
                        sort_by: sortBy
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.data) {
                    this.performanceCache[cacheKey] = data.data;
                    this.renderPerformances(data.data);
                } else {
                    throw new Error(data.data?.message || 'Failed to load performances');
                }
            } catch (error) {
                console.error('Archive search error:', error);
                $scroll.html(`<div class="gd-error">Error loading performances: ${error.message}</div>`);
            }
        }
        
        renderPerformances(performances) {
            const $scroll = $('#gd-performance-scroll');
            
            if (!performances || performances.length === 0) {
                $scroll.html('<div class="gd-no-results">No performances found on Archive.org</div>');
                return;
            }
            
            const html = performances.map(perf => {
                const date = this.formatDate(perf.date);
                const downloads = this.formatNumber(perf.downloads);
                const rating = perf.rating > 0 ? `★ ${perf.rating.toFixed(1)}` : '';
                
                return `
                    <div class="gd-performance-item">
                        <img src="${perf.thumbnail}" alt="Show" class="performance-thumb" 
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'80\\' height=\\'80\\'%3E%3Crect fill=\\'%23ddd\\' width=\\'80\\' height=\\'80\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' dy=\\'.3em\\' fill=\\'%23999\\'%3EGD%3C/text%3E%3C/svg%3E'" />
                        <div class="performance-info">
                            <h4>${this.escapeHtml(perf.venue || 'Unknown Venue')}</h4>
                            <p class="performance-date">${date}</p>
                            <p class="performance-location">${this.escapeHtml(perf.location || '')}</p>
                            <p class="performance-stats">
                                <span class="downloads">${downloads} downloads</span>
                                ${rating ? `<span class="rating">${rating}</span>` : ''}
                            </p>
                        </div>
                        <button class="gd-play-btn" 
                                data-identifier="${perf.identifier}"
                                data-url="${perf.archive_url}"
                                data-title="${this.escapeHtml(perf.venue || 'Unknown Venue')}"
                                data-subtitle="${date}"
                                data-thumb="${perf.thumbnail}">
                            ▶ Play
                        </button>
                    </div>
                `;
            }).join('');
            
            $scroll.html(html);
        }
        
        async playPerformance(identifier, archiveUrl, title, subtitle, thumb, service = 'archive') {
            // For streaming services, open in new tab (they handle playback)
            if (service !== 'archive') {
                window.open(archiveUrl, '_blank');
                return;
            }
            
            // Archive.org: Show player and try to get direct MP3 URL
            $('#gd-audio-player').slideDown(300);
            
            // Update player info
            $('#player-title').text(title);
            $('#player-subtitle').text(subtitle);
            $('#player-thumb').attr('src', thumb);
            
            // Try to get direct MP3 URL
            try {
                const response = await fetch(gdChatbot.ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'gd_chatbot_get_stream_url',
                        nonce: gdChatbot.nonce,
                        identifier: identifier
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.data.stream_url) {
                    // Play MP3 directly
                    this.audioPlayer.src = data.data.stream_url;
                    this.audioPlayer.load();
                    this.audioPlayer.play();
                } else {
                    // Fallback: open Archive.org page
                    window.open(archiveUrl, '_blank');
                }
            } catch (error) {
                console.error('Stream URL error:', error);
                // Fallback: open Archive.org page
                window.open(archiveUrl, '_blank');
            }
        }
        
        closePlayer() {
            if (this.audioPlayer) {
                this.audioPlayer.pause();
                this.audioPlayer.src = '';
            }
            $('#gd-audio-player').slideUp(300);
        }
        
        formatDate(dateStr) {
            if (!dateStr) return 'Unknown Date';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        formatNumber(num) {
            if (!num) return '0';
            return num.toLocaleString();
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Check for connected streaming services
         */
        checkConnectedServices() {
            return new Promise((resolve) => {
                // If user is not logged in, only show Archive.org
                if (typeof gdChatbotPublic === 'undefined' || !gdChatbotPublic.isLoggedIn) {
                    this.connectedServices = [];
                    this.availableSources = ['archive'];
                    resolve();
                    return;
                }
                
                $.ajax({
                    url: gdChatbotPublic.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'gd_chatbot_get_connection_status',
                        nonce: gdChatbotPublic.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            // Get list of connected services
                            this.connectedServices = [];
                            for (const [service, status] of Object.entries(response.data)) {
                                if (status.connected && !status.expired) {
                                    this.connectedServices.push(service);
                                }
                            }
                            
                            // Available sources = Archive.org + connected services
                            this.availableSources = ['archive', ...this.connectedServices];
                        } else {
                            this.connectedServices = [];
                            this.availableSources = ['archive'];
                        }
                        resolve();
                    },
                    error: () => {
                        this.connectedServices = [];
                        this.availableSources = ['archive'];
                        resolve();
                    }
                });
            });
        }
        
        /**
         * Build source tabs
         */
        buildSourceTabs() {
            const $tabsContainer = $('#gd-source-tabs');
            $tabsContainer.empty();
            
            if (this.availableSources.length === 1) {
                // Only Archive.org, no tabs needed
                $tabsContainer.hide();
                return;
            }
            
            $tabsContainer.show();
            
            const serviceLabels = {
                'archive': '🎸 Archive.org',
                'spotify': '🎵 Spotify',
                'apple_music': '🍎 Apple Music',
                'youtube_music': '📺 YouTube Music',
                'amazon_music': '📦 Amazon Music',
                'tidal': '🌊 Tidal'
            };
            
            this.availableSources.forEach(source => {
                const isActive = source === this.currentSource;
                const label = serviceLabels[source] || source;
                
                const $tab = $(`
                    <button class="gd-source-tab ${isActive ? 'active' : ''}" 
                            data-source="${source}">
                        ${label}
                    </button>
                `);
                
                $tabsContainer.append($tab);
            });
        }
        
        /**
         * Switch to a different source
         */
        switchSource(source) {
            if (source === this.currentSource) {
                return;
            }
            
            this.currentSource = source;
            
            // Update active tab
            $('.gd-source-tab').removeClass('active');
            $(`.gd-source-tab[data-source="${source}"]`).addClass('active');
            
            // Load performances for new source
            if (this.currentSong) {
                this.loadUnifiedResults(this.currentSong, $('#gd-archive-sort').val());
            }
        }
        
        /**
         * Load unified results (Archive.org + streaming services)
         */
        loadUnifiedResults(songData, sortBy) {
            const cacheKey = `${songData.title}_${sortBy}_unified`;
            
            // Check cache
            if (this.performanceCache[cacheKey]) {
                this.renderUnifiedResults(this.performanceCache[cacheKey]);
                return;
            }
            
            $('#gd-performance-scroll').html('<div class="gd-loading"><span class="spinner"></span> Loading...</div>');
            
            $.ajax({
                url: gdChatbotPublic.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gd_chatbot_search_streaming',
                    nonce: gdChatbotPublic.nonce,
                    song_title: songData.title,
                    artist: songData.author || 'Grateful Dead'
                },
                success: (response) => {
                    if (response.success) {
                        this.performanceCache[cacheKey] = response.data;
                        this.renderUnifiedResults(response.data);
                    } else {
                        $('#gd-performance-scroll').html('<p class="gd-error">Failed to load results.</p>');
                    }
                },
                error: () => {
                    $('#gd-performance-scroll').html('<p class="gd-error">Failed to load results.</p>');
                }
            });
        }
        
        /**
         * Render unified results based on current source
         */
        renderUnifiedResults(data) {
            const $scroll = $('#gd-performance-scroll');
            $scroll.empty();
            
            if (this.currentSource === 'archive') {
                // Render Archive.org results
                if (data.archive && data.archive.length > 0) {
                    this.renderPerformances(data.archive);
                } else {
                    $scroll.html('<p class="gd-no-results">No Archive.org recordings found.</p>');
                }
            } else {
                // Render streaming service results
                const serviceResults = data.streaming[this.currentSource];
                
                if (serviceResults && serviceResults.length > 0) {
                    this.renderStreamingResults(serviceResults);
                } else {
                    $scroll.html(`<p class="gd-no-results">No results found on ${this.getServiceLabel(this.currentSource)}.</p>`);
                }
            }
        }
        
        /**
         * Render streaming service results
         */
        renderStreamingResults(results) {
            const $scroll = $('#gd-performance-scroll');
            $scroll.empty();
            
            results.forEach(track => {
                const $item = $(`
                    <div class="gd-performance-item gd-streaming-item">
                        ${track.image ? `<img src="${this.escapeHtml(track.image)}" alt="Album art" class="gd-performance-thumb" />` : ''}
                        <div class="gd-performance-info">
                            <h4>${this.escapeHtml(track.title)}</h4>
                            <p class="gd-performance-meta">
                                ${this.escapeHtml(track.artist)}
                                ${track.album ? ' • ' + this.escapeHtml(track.album) : ''}
                                ${track.duration_ms ? ' • ' + this.formatDuration(track.duration_ms) : ''}
                            </p>
                            ${track.quality ? `<span class="gd-quality-badge">${this.escapeHtml(track.quality)}</span>` : ''}
                        </div>
                        <div class="gd-performance-actions">
                            ${track.popularity ? `<span class="gd-popularity">♥ ${track.popularity}</span>` : ''}
                            <button class="gd-play-btn" 
                                    data-identifier="${this.escapeHtml(track.id)}"
                                    data-url="${this.escapeHtml(track.url)}"
                                    data-title="${this.escapeHtml(track.title)}"
                                    data-subtitle="${this.escapeHtml(track.artist)}"
                                    data-thumb="${this.escapeHtml(track.image || '')}"
                                    data-service="${this.escapeHtml(track.service)}">
                                ▶ Play
                            </button>
                        </div>
                    </div>
                `);
                
                $scroll.append($item);
            });
        }
        
        /**
         * Format duration from milliseconds
         */
        formatDuration(ms) {
            const seconds = Math.floor(ms / 1000);
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
        
        /**
         * Get service label
         */
        getServiceLabel(service) {
            const labels = {
                'spotify': 'Spotify',
                'apple_music': 'Apple Music',
                'youtube_music': 'YouTube Music',
                'amazon_music': 'Amazon Music',
                'tidal': 'Tidal'
            };
            return labels[service] || service;
        }
    }

    // Initialize modal when document is ready
    $(document).ready(function() {
        window.gdSongModal = new GDSongModal();
    });

})(jQuery);
