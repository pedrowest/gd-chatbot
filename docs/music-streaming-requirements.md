# Music Streaming Integration Requirements for GD-Chatbot

**Document Version**: 1.0  
**Date**: February 12, 2026  
**Target**: GD-Chatbot WordPress Plugin  
**Author**: IT Influentials

---

## Executive Summary

This document defines requirements for integrating music streaming capabilities into the GD-Chatbot WordPress plugin. The system will automatically detect song mentions in chatbot responses, create clickable hotlinks, and provide users with options to listen to performances via Archive.org or their preferred streaming service.

### Key Features

1. **Automatic Song Detection**: Parse chatbot responses to identify Grateful Dead song titles
2. **Archive.org Integration**: Direct access to live performance recordings from the Internet Archive
3. **Streaming Service Integration**: Support for top 5 US music streaming services with encrypted credentials
4. **Smart Performance Selection**: Ranked by popularity/stream count with scrollable modal interface
5. **In-Chat Audio Player**: Embedded playback controls with minimal user disruption

### User Experience Benefits

- Seamless music discovery during conversation
- No context switching to external sites
- Choice between archival recordings and studio versions
- Personalized streaming service integration
- Privacy-focused credential storage

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Song Detection System](#song-detection-system)
3. [Archive.org Integration](#archiveorg-integration)
4. [Streaming Service Integration](#streaming-service-integration)
5. [User Profile & Credential Management](#user-profile--credential-management)
6. [Database Schema Changes](#database-schema-changes)
7. [Frontend Components](#frontend-components)
8. [Security Requirements](#security-requirements)
9. [API Integration Requirements](#api-integration-requirements)
10. [Testing Requirements](#testing-requirements)
11. [Implementation Phases](#implementation-phases)

---

## 1. Architecture Overview

### 1.1 System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Chatbot Response Pipeline                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              GD_Song_Detector (NEW)                         │
│  - Parse response text for song titles                      │
│  - Match against songs.csv database                         │
│  - Handle disambiguation (duplicate titles)                 │
│  - Return song metadata and positions                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              GD_Response_Enricher (NEW)                     │
│  - Insert hotlink spans with data attributes                │
│  - Maintain original text formatting                        │
│  - Add unique identifiers for each song mention             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              Frontend: Song Hotlink Handler                  │
│  - Click event management                                    │
│  - User preference detection (streaming services)           │
│  - Modal display logic                                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ├─────────────────────────────┐
                       ↓                             ↓
┌──────────────────────────────────┐  ┌────────────────────────────────┐
│  GD_Archive_API (NEW)            │  │  GD_Streaming_API (NEW)        │
│  - Search Archive.org            │  │  - Query streaming APIs        │
│  - Fetch performance metadata    │  │  - Format results              │
│  - Return streaming URLs         │  │  - Handle authentication       │
└──────────────────────────────────┘  └────────────────────────────────┘
                       │                             │
                       └──────────────┬──────────────┘
                                      ↓
                       ┌────────────────────────────────┐
                       │  GD_Audio_Player_Modal (NEW)   │
                       │  - Display source picker       │
                       │  - Show ranked performance list│
                       │  - Embedded audio player       │
                       └────────────────────────────────┘
```

### 1.2 Data Flow

```
User Query → Claude Response → Song Detection → Text Enrichment → 
Frontend Display → User Click → Source Selection → Performance List → 
Audio Playback → Continue Conversation
```

---

## 2. Song Detection System

### 2.1 Song Database

**Source**: `plugin/context/reference/songs.csv`

**Format**:
```csv
Title,Author
(Baby) Hully Gully,Grateful Dead
Alabama Getaway,"Music: Jerry Garcia, Lyrics: Robert Hunter"
Bertha,"Music: Jerry Garcia, Lyrics: Robert Hunter"
...
```

**Requirements**:
- Load all 600+ songs into memory on plugin initialization
- Create normalized versions for matching (lowercase, remove punctuation)
- Build disambiguation index for songs with duplicate titles
- Cache compiled song list in WordPress transient (24-hour TTL)

### 2.2 Detection Algorithm

**Class**: `GD_Song_Detector`  
**Location**: `plugin/includes/class-song-detector.php`

**Methods**:

```php
/**
 * Detect song mentions in text
 * 
 * @param string $text The chatbot response text
 * @return array Array of detected songs with positions
 */
public function detect_songs($text);

/**
 * Load and cache song database
 * 
 * @return array Normalized song list
 */
private function load_songs();

/**
 * Match text against song database
 * 
 * @param string $text Text to search
 * @return array Matched songs with metadata
 */
private function match_songs($text);

/**
 * Handle disambiguation for duplicate titles
 * 
 * @param string $song_title Song title
 * @param string $context Surrounding text context
 * @return array Best match with confidence score
 */
private function disambiguate($song_title, $context);
```

**Detection Strategy**:

1. **Exact Phrase Matching**:
   - Search for exact song titles (case-insensitive)
   - Prioritize longer titles first (e.g., "Not Fade Away" before "Fade")
   - Use word boundaries to avoid partial matches

2. **Context Analysis**:
   - Look for contextual clues: "song", "played", "performed", "version of"
   - Detect possessive forms: "Bertha's" → "Bertha"
   - Handle abbreviations: "Sugar Mag" → "Sugaree/Sugar Magnolia"

3. **Disambiguation Rules**:
   - Use author/composer information from CSV
   - Consider era mentions (e.g., "1970s" → exclude post-1980 songs)
   - Use venue/date context if available
   - Assign confidence scores (0-100)

4. **False Positive Prevention**:
   - Maintain exclusion list (common words that are also song titles)
   - Require minimum confidence threshold (70%)
   - Skip detection in quoted text (unless discussing the song)

**Output Format**:

```php
array(
    array(
        'title' => 'Dark Star',
        'author' => 'Music: Grateful Dead, Lyrics: Robert Hunter',
        'start_pos' => 123,
        'end_pos' => 132,
        'confidence' => 95,
        'context' => '...the legendary Dark Star from...'
    ),
    // ... more songs
)
```

---

## 3. Archive.org Integration

### 3.1 Archive.org API Overview

**Base URL**: `https://archive.org/`  
**Advanced Search**: `https://archive.org/advancedsearch.php`  
**Metadata API**: `https://archive.org/metadata/{identifier}`

**No API Key Required**: Archive.org APIs are open and free

### 3.2 Search Implementation

**Class**: `GD_Archive_API`  
**Location**: `plugin/includes/api/class-archive-api.php`

**Methods**:

```php
/**
 * Search for Grateful Dead performances of a specific song
 * 
 * @param string $song_title Song title to search for
 * @param array $options Search options (year, venue, etc.)
 * @return array|WP_Error Array of performances or error
 */
public function search_performances($song_title, $options = array());

/**
 * Get streaming URL for a specific performance
 * 
 * @param string $identifier Archive.org identifier
 * @param string $format File format (mp3, ogg, flac)
 * @return string|WP_Error Streaming URL or error
 */
public function get_streaming_url($identifier, $format = 'mp3');

/**
 * Get performance metadata
 * 
 * @param string $identifier Archive.org identifier
 * @return array|WP_Error Performance details or error
 */
public function get_performance_metadata($identifier);

/**
 * Cache search results
 * 
 * @param string $cache_key Cache identifier
 * @param mixed $data Data to cache
 * @param int $ttl Time to live in seconds
 */
private function cache_result($cache_key, $data, $ttl = 3600);
```

### 3.3 Search Query Construction

**Advanced Search Query**:

```
collection:GratefulDead AND mediatype:etree AND
title:("{song_title}") AND
format:VBR MP3
```

**Sort Options**:
1. **By Popularity** (default): `&sort[]=downloads desc`
2. **By Date**: `&sort[]=date asc`
3. **By Rating**: `&sort[]=avg_rating desc`

**Example Query**:
```
https://archive.org/advancedsearch.php?q=collection:GratefulDead+AND+mediatype:etree+AND+title:("Dark Star")+AND+format:VBR+MP3&fl[]=identifier,title,date,venue,downloads,avg_rating&sort[]=downloads+desc&rows=50&output=json
```

### 3.4 Response Format

**Archive.org Returns**:

```json
{
  "responseHeader": {
    "status": 0,
    "QTime": 52
  },
  "response": {
    "numFound": 2347,
    "start": 0,
    "docs": [
      {
        "identifier": "gd1969-02-27.sbd.miller.97.sbeok.shnf",
        "title": "Grateful Dead Live at Fillmore West on 1969-02-27",
        "date": "1969-02-27T00:00:00Z",
        "venue": "Fillmore West",
        "downloads": 18543,
        "avg_rating": 4.8
      },
      // ... more performances
    ]
  }
}
```

**Formatted Output**:

```php
array(
    array(
        'identifier' => 'gd1969-02-27.sbd.miller.97.sbeok.shnf',
        'title' => 'Fillmore West, San Francisco, CA',
        'date' => '1969-02-27',
        'venue' => 'Fillmore West',
        'downloads' => 18543,
        'rating' => 4.8,
        'streaming_url' => 'https://archive.org/download/{identifier}/{filename}.mp3',
        'archive_url' => 'https://archive.org/details/{identifier}',
        'thumbnail' => 'https://archive.org/services/img/{identifier}'
    ),
    // ... top 50 performances
)
```

### 3.5 Caching Strategy

**Cache Duration**:
- Search results: 24 hours (songs don't change frequently)
- Performance metadata: 7 days (static data)
- Streaming URLs: 1 hour (may include auth tokens)

**Cache Keys**:
- `gd_archive_search_{song_slug}_{sort}`
- `gd_archive_metadata_{identifier}`
- `gd_archive_stream_{identifier}_{format}`

**Implementation**:
```php
// WordPress transient caching
$cache_key = 'gd_archive_search_' . sanitize_title($song_title) . '_downloads';
$results = get_transient($cache_key);

if (false === $results) {
    $results = $this->query_archive_api($song_title);
    set_transient($cache_key, $results, 24 * HOUR_IN_SECONDS);
}
```

---

## 4. Streaming Service Integration

### 4.1 Supported Streaming Services

**Top 5 US Music Streaming Services** (as of 2026):

1. **Spotify** - 36% market share
2. **Apple Music** - 25% market share
3. **YouTube Music** - 18% market share
4. **Amazon Music** - 13% market share
5. **Tidal** - 3% market share

### 4.2 API Integration Requirements

#### 4.2.1 Spotify API

**Authentication**: OAuth 2.0  
**Base URL**: `https://api.spotify.com/v1/`  
**Required Credentials**: Client ID, Client Secret, User OAuth Token

**Search Endpoint**:
```
GET https://api.spotify.com/v1/search?q=track:{song_title}+artist:Grateful Dead&type=track&limit=10
```

**Response Format**:
```json
{
  "tracks": {
    "items": [
      {
        "id": "2TjdnqlpwOjhijd3Ar7g9j",
        "name": "Dark Star",
        "external_urls": {
          "spotify": "https://open.spotify.com/track/2TjdnqlpwOjhijd3Ar7g9j"
        },
        "album": {
          "name": "Live/Dead",
          "images": [{"url": "..."}]
        },
        "popularity": 54
      }
    ]
  }
}
```

**Implementation Notes**:
- Requires Spotify Premium for playback
- Use Web Playback SDK for embedded player
- Store refresh tokens securely
- Handle token expiration (1 hour)

#### 4.2.2 Apple Music API

**Authentication**: Developer Token + User Music Token  
**Base URL**: `https://api.music.apple.com/v1/`  
**Required Credentials**: Developer Token, User Music Token

**Search Endpoint**:
```
GET https://api.music.apple.com/v1/catalog/us/search?term={song_title}+grateful+dead&types=songs&limit=10
```

**Response Format**:
```json
{
  "results": {
    "songs": {
      "data": [
        {
          "id": "1234567890",
          "attributes": {
            "name": "Dark Star",
            "artistName": "Grateful Dead",
            "url": "https://music.apple.com/us/song/dark-star/1234567890",
            "artwork": {"url": "..."}
          }
        }
      ]
    }
  }
}
```

**Implementation Notes**:
- Requires Apple Music subscription
- Use MusicKit JS for embedded playback
- Developer token expires every 6 months
- User token stored per user

#### 4.2.3 YouTube Music API

**Authentication**: OAuth 2.0  
**Base URL**: `https://www.googleapis.com/youtube/v3/`  
**Required Credentials**: API Key, OAuth Token

**Search Endpoint**:
```
GET https://www.googleapis.com/youtube/v3/search?part=snippet&q={song_title}+grateful+dead&type=video&videoCategoryId=10&maxResults=10
```

**Response Format**:
```json
{
  "items": [
    {
      "id": {"videoId": "dQw4w9WgXcQ"},
      "snippet": {
        "title": "Grateful Dead - Dark Star (Live)",
        "thumbnails": {"default": {"url": "..."}},
        "channelTitle": "Grateful Dead"
      }
    }
  ]
}
```

**Implementation Notes**:
- Free tier available (no subscription required)
- Use YouTube IFrame Player API
- API quota limits: 10,000 units/day
- Filter for official/quality videos

#### 4.2.4 Amazon Music API

**Authentication**: Login with Amazon  
**Base URL**: `https://api.amazon.com/`  
**Required Credentials**: API Key, User Access Token

**Search Endpoint**:
```
GET https://api.amazon.com/search?keywords={song_title}+grateful+dead&searchIndex=Music&responseGroup=ItemAttributes
```

**Implementation Notes**:
- Requires Amazon Music Unlimited subscription
- Limited public API documentation
- May require partnership agreement
- Use Amazon Music Web Player embed

#### 4.2.5 Tidal API

**Authentication**: OAuth 2.0  
**Base URL**: `https://api.tidal.com/v1/`  
**Required Credentials**: API Key, User Session ID

**Search Endpoint**:
```
GET https://api.tidal.com/v1/search/tracks?query={song_title}+grateful+dead&limit=10&countryCode=US
```

**Response Format**:
```json
{
  "items": [
    {
      "id": 123456789,
      "title": "Dark Star",
      "artist": {"name": "Grateful Dead"},
      "album": {"title": "Live/Dead"},
      "url": "https://tidal.com/browse/track/123456789"
    }
  ]
}
```

**Implementation Notes**:
- Requires Tidal HiFi subscription
- High-quality audio (FLAC, MQA)
- Use Tidal Web Player API
- Limited free tier

### 4.3 Streaming API Handler

**Class**: `GD_Streaming_API`  
**Location**: `plugin/includes/api/class-streaming-api.php`

**Methods**:

```php
/**
 * Search across all configured streaming services
 * 
 * @param string $song_title Song title
 * @param int $user_id WordPress user ID
 * @return array Results grouped by service
 */
public function search_all_services($song_title, $user_id);

/**
 * Search specific streaming service
 * 
 * @param string $service Service identifier (spotify, apple, youtube, amazon, tidal)
 * @param string $song_title Song title
 * @param string $access_token User's access token
 * @return array|WP_Error Search results or error
 */
public function search_service($service, $song_title, $access_token);

/**
 * Get user's configured streaming services
 * 
 * @param int $user_id WordPress user ID
 * @return array Array of service identifiers with credentials
 */
public function get_user_services($user_id);

/**
 * Refresh expired access token
 * 
 * @param string $service Service identifier
 * @param int $user_id WordPress user ID
 * @return string|WP_Error New access token or error
 */
public function refresh_token($service, $user_id);

/**
 * Format results for frontend display
 * 
 * @param array $results Raw API results
 * @param string $service Service identifier
 * @return array Normalized result format
 */
private function format_results($results, $service);
```

**Unified Output Format**:

```php
array(
    'service' => 'spotify',
    'results' => array(
        array(
            'id' => 'service-specific-id',
            'title' => 'Dark Star',
            'artist' => 'Grateful Dead',
            'album' => 'Live/Dead',
            'year' => '1969',
            'url' => 'https://open.spotify.com/track/...',
            'embed_url' => 'https://open.spotify.com/embed/track/...',
            'thumbnail' => 'https://...',
            'popularity' => 85, // Normalized 0-100
            'duration_ms' => 240000,
            'is_live' => false
        ),
        // ... more results
    )
)
```

---

## 5. User Profile & Credential Management

### 5.1 User Settings Interface

**Location**: User Profile page (`wp-admin/profile.php`) and frontend user dashboard

**UI Components**:

1. **Streaming Services Section**:
   - Collapsible panel: "Music Streaming Services"
   - Individual service cards with connect/disconnect buttons
   - Connection status indicators (green=connected, gray=disconnected)
   - Last connected timestamp

2. **Connection Flow**:
   ```
   User clicks "Connect Spotify" →
   OAuth popup opens →
   User authorizes →
   Callback handler receives token →
   Token encrypted and stored →
   UI updates to "Connected" state
   ```

3. **Service Cards**:
   ```html
   <div class="gd-streaming-service-card" data-service="spotify">
       <img src="spotify-logo.svg" alt="Spotify" />
       <h4>Spotify</h4>
       <p class="status">Not Connected</p>
       <button class="gd-connect-service">Connect</button>
       <p class="last-connected">Never</p>
   </div>
   ```

### 5.2 OAuth Integration

**OAuth Handler Class**: `GD_OAuth_Handler`  
**Location**: `plugin/includes/class-oauth-handler.php`

**Methods**:

```php
/**
 * Initiate OAuth flow for a streaming service
 * 
 * @param string $service Service identifier
 * @param int $user_id WordPress user ID
 * @return string|WP_Error Authorization URL or error
 */
public function initiate_oauth($service, $user_id);

/**
 * Handle OAuth callback
 * 
 * @param string $service Service identifier
 * @param string $code Authorization code
 * @param int $user_id WordPress user ID
 * @return bool|WP_Error Success or error
 */
public function handle_callback($service, $code, $user_id);

/**
 * Disconnect service
 * 
 * @param string $service Service identifier
 * @param int $user_id WordPress user ID
 * @return bool Success
 */
public function disconnect_service($service, $user_id);

/**
 * Check if service is connected
 * 
 * @param string $service Service identifier
 * @param int $user_id WordPress user ID
 * @return bool Connected status
 */
public function is_service_connected($service, $user_id);
```

**OAuth Configuration**:

```php
// In wp-config.php or plugin settings
define('GD_SPOTIFY_CLIENT_ID', 'your-client-id');
define('GD_SPOTIFY_CLIENT_SECRET', 'your-client-secret');
define('GD_APPLE_MUSIC_TEAM_ID', 'your-team-id');
define('GD_APPLE_MUSIC_KEY_ID', 'your-key-id');
define('GD_YOUTUBE_API_KEY', 'your-api-key');
define('GD_AMAZON_MUSIC_API_KEY', 'your-api-key');
define('GD_TIDAL_CLIENT_ID', 'your-client-id');
```

**Redirect URLs**:
```
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=spotify
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=apple
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=youtube
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=amazon
https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=tidal
```

### 5.3 Registration Form Updates

**Registration Form Fields** (optional, only if user wants to connect during signup):

```html
<fieldset class="gd-streaming-services-signup">
    <legend>Connect Music Streaming Services (Optional)</legend>
    <p>Connect your streaming accounts to listen to Grateful Dead music directly from the chatbot.</p>
    
    <div class="gd-service-checkboxes">
        <label>
            <input type="checkbox" name="connect_spotify" value="1" />
            <img src="spotify-icon.svg" /> Connect Spotify
        </label>
        <label>
            <input type="checkbox" name="connect_apple" value="1" />
            <img src="apple-music-icon.svg" /> Connect Apple Music
        </label>
        <!-- ... more services -->
    </div>
    
    <p class="note">You can connect services later from your profile settings.</p>
</fieldset>
```

**Post-Registration Hook**:
```php
add_action('user_register', 'gd_chatbot_handle_streaming_signup', 10, 1);

function gd_chatbot_handle_streaming_signup($user_id) {
    // Check if any services were selected
    $services_to_connect = array();
    
    if (!empty($_POST['connect_spotify'])) {
        $services_to_connect[] = 'spotify';
    }
    // ... check other services
    
    // Store pending connections
    if (!empty($services_to_connect)) {
        update_user_meta($user_id, 'gd_pending_streaming_connections', $services_to_connect);
        
        // Redirect to OAuth flow after registration completes
        // (handled in welcome email or onboarding flow)
    }
}
```

---

## 6. Database Schema Changes

### 6.1 New Tables

#### Table: `wp_gd_streaming_credentials`

**Purpose**: Store encrypted streaming service credentials per user

```sql
CREATE TABLE `wp_gd_streaming_credentials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `service` varchar(50) NOT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `service_user_id` varchar(255) DEFAULT NULL,
  `service_user_name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `connected_at` datetime NOT NULL,
  `last_refreshed_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_service` (`user_id`, `service`),
  KEY `user_id` (`user_id`),
  KEY `service` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns**:
- `id`: Auto-increment primary key
- `user_id`: WordPress user ID (foreign key)
- `service`: Service identifier (spotify, apple, youtube, amazon, tidal)
- `access_token`: Encrypted OAuth access token
- `refresh_token`: Encrypted OAuth refresh token
- `token_expires_at`: Token expiration timestamp
- `service_user_id`: User's ID on the streaming service
- `service_user_name`: User's display name on service
- `scopes`: JSON array of granted OAuth scopes
- `connected_at`: Initial connection timestamp
- `last_refreshed_at`: Last token refresh timestamp
- `is_active`: Whether connection is currently active

#### Table: `wp_gd_streaming_cache`

**Purpose**: Cache streaming service search results

```sql
CREATE TABLE `wp_gd_streaming_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `service` varchar(50) NOT NULL,
  `song_title` varchar(255) NOT NULL,
  `results` longtext NOT NULL,
  `cached_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`),
  KEY `service` (`service`),
  KEY `song_title` (`song_title`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns**:
- `id`: Auto-increment primary key
- `cache_key`: Hash of (service + song_title + filters)
- `service`: Service identifier
- `song_title`: Song title searched
- `results`: JSON-encoded search results
- `cached_at`: Cache creation timestamp
- `expires_at`: Cache expiration timestamp

#### Table: `wp_gd_archive_cache`

**Purpose**: Cache Archive.org search results

```sql
CREATE TABLE `wp_gd_archive_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `song_title` varchar(255) NOT NULL,
  `sort_by` varchar(50) NOT NULL DEFAULT 'downloads',
  `results` longtext NOT NULL,
  `result_count` int(11) NOT NULL DEFAULT 0,
  `cached_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`),
  KEY `song_title` (`song_title`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns**:
- `id`: Auto-increment primary key
- `cache_key`: Hash of (song_title + sort_by + filters)
- `song_title`: Song title searched
- `sort_by`: Sort method (downloads, date, rating)
- `results`: JSON-encoded performance list
- `result_count`: Number of results returned
- `cached_at`: Cache creation timestamp
- `expires_at`: Cache expiration timestamp

### 6.2 Table Migrations

**Migration Class**: `GD_Database_Migrations`  
**Location**: `plugin/includes/database/class-database-migrations.php`

**Migration Method**:

```php
/**
 * Run database migrations for streaming features
 * 
 * @return bool Success
 */
public function migrate_streaming_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create streaming credentials table
    $sql_credentials = "CREATE TABLE {$wpdb->prefix}gd_streaming_credentials (
        /* ... SQL from above ... */
    ) $charset_collate;";
    
    dbDelta($sql_credentials);
    
    // Create streaming cache table
    $sql_streaming_cache = "CREATE TABLE {$wpdb->prefix}gd_streaming_cache (
        /* ... SQL from above ... */
    ) $charset_collate;";
    
    dbDelta($sql_streaming_cache);
    
    // Create archive cache table
    $sql_archive_cache = "CREATE TABLE {$wpdb->prefix}gd_archive_cache (
        /* ... SQL from above ... */
    ) $charset_collate;";
    
    dbDelta($sql_archive_cache);
    
    // Update plugin version
    update_option('gd_chatbot_streaming_db_version', '1.0');
    
    return true;
}
```

**Activation Hook**:

```php
register_activation_hook(__FILE__, 'gd_chatbot_activate_streaming');

function gd_chatbot_activate_streaming() {
    $migrations = new GD_Database_Migrations();
    $migrations->migrate_streaming_tables();
}
```

---

## 7. Frontend Components

### 7.1 Response Enrichment

**Enriched Response Format**:

```html
<!-- Before Enrichment -->
<p>Dark Star is one of the most beloved Grateful Dead songs...</p>

<!-- After Enrichment -->
<p><span class="gd-song-link" 
         data-song-id="dark-star"
         data-song-title="Dark Star"
         data-song-author="Music: Grateful Dead, Lyrics: Robert Hunter"
         data-has-streaming="true">Dark Star</span> is one of the most beloved Grateful Dead songs...</p>
```

**CSS Styling**:

```css
/* Song link styling */
.gd-song-link {
    color: #4285f4;
    cursor: pointer;
    text-decoration: underline;
    text-decoration-style: dotted;
    text-underline-offset: 2px;
    position: relative;
    transition: color 0.2s ease;
}

.gd-song-link:hover {
    color: #1967d2;
    text-decoration-style: solid;
}

.gd-song-link::after {
    content: "♪";
    font-size: 0.8em;
    margin-left: 2px;
    opacity: 0.6;
}

/* Tooltip on hover */
.gd-song-link:hover::before {
    content: "Click to listen";
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    pointer-events: none;
    z-index: 1000;
}
```

### 7.2 Song Modal Interface

**Modal Structure**:

```html
<div id="gd-song-modal" class="gd-modal" style="display: none;">
    <div class="gd-modal-backdrop"></div>
    <div class="gd-modal-dialog">
        <div class="gd-modal-header">
            <h3 id="gd-modal-song-title">Dark Star</h3>
            <button class="gd-modal-close">&times;</button>
        </div>
        
        <!-- Source Picker (if user has streaming services) -->
        <div class="gd-source-picker">
            <label>Listen on:</label>
            <div class="gd-source-buttons">
                <button class="gd-source-btn active" data-source="archive">
                    <img src="archive-icon.svg" alt="Archive.org" />
                    <span>Archive.org</span>
                </button>
                <button class="gd-source-btn" data-source="spotify" data-connected="true">
                    <img src="spotify-icon.svg" alt="Spotify" />
                    <span>Spotify</span>
                </button>
                <button class="gd-source-btn" data-source="apple" data-connected="false" disabled>
                    <img src="apple-music-icon.svg" alt="Apple Music" />
                    <span>Not Connected</span>
                </button>
            </div>
        </div>
        
        <!-- Performance List (Archive.org) -->
        <div class="gd-performance-list" data-source="archive">
            <div class="gd-performance-filters">
                <label>Sort by:</label>
                <select id="gd-archive-sort">
                    <option value="downloads">Most Popular</option>
                    <option value="date">Date (Oldest First)</option>
                    <option value="rating">Highest Rated</option>
                </select>
            </div>
            
            <div class="gd-performance-scroll">
                <!-- Performance items loaded via AJAX -->
                <div class="gd-performance-item" data-identifier="gd1969-02-27">
                    <img src="thumbnail.jpg" alt="Show" class="performance-thumb" />
                    <div class="performance-info">
                        <h4>Fillmore West, San Francisco, CA</h4>
                        <p class="performance-date">February 27, 1969</p>
                        <p class="performance-stats">
                            <span class="downloads">18,543 downloads</span>
                            <span class="rating">★★★★★ 4.8</span>
                        </p>
                    </div>
                    <button class="gd-play-btn" data-url="https://archive.org/download/...">
                        ▶ Play
                    </button>
                </div>
                <!-- ... more performances -->
            </div>
        </div>
        
        <!-- Performance List (Streaming Service) -->
        <div class="gd-performance-list" data-source="spotify" style="display: none;">
            <div class="gd-streaming-scroll">
                <!-- Streaming results loaded via AJAX -->
                <div class="gd-streaming-item" data-track-id="2TjdnqlpwOjhijd3Ar7g9j">
                    <img src="album-art.jpg" alt="Album" class="track-thumb" />
                    <div class="track-info">
                        <h4>Dark Star</h4>
                        <p class="track-album">Live/Dead (1969)</p>
                        <p class="track-duration">23:44</p>
                    </div>
                    <button class="gd-play-btn" data-embed-url="https://open.spotify.com/embed/...">
                        ▶ Play on Spotify
                    </button>
                </div>
                <!-- ... more tracks -->
            </div>
        </div>
        
        <!-- Audio Player -->
        <div class="gd-audio-player" style="display: none;">
            <div class="player-header">
                <img src="current-thumb.jpg" alt="Now Playing" class="player-thumb" />
                <div class="player-info">
                    <h4 id="player-title">Fillmore West, San Francisco, CA</h4>
                    <p id="player-subtitle">February 27, 1969</p>
                </div>
            </div>
            
            <audio id="gd-audio-element" controls>
                <source src="" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
            
            <!-- Or for streaming services: embedded player -->
            <div id="gd-streaming-player" style="display: none;">
                <!-- Spotify/Apple/YouTube embed iframe -->
            </div>
        </div>
    </div>
</div>
```

**JavaScript Handler**:

```javascript
// In chatbot.js

class GDSongModal {
    constructor() {
        this.$modal = $('#gd-song-modal');
        this.$backdrop = this.$modal.find('.gd-modal-backdrop');
        this.$dialog = this.$modal.find('.gd-modal-dialog');
        this.$close = this.$modal.find('.gd-modal-close');
        this.$songTitle = $('#gd-modal-song-title');
        this.$sourceButtons = $('.gd-source-btn');
        this.$performanceList = $('.gd-performance-list');
        this.$audioPlayer = $('.gd-audio-player');
        this.$audio = $('#gd-audio-element');
        
        this.currentSong = null;
        this.currentSource = 'archive';
        this.performanceCache = {};
        
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        // Close modal
        this.$close.on('click', () => this.close());
        this.$backdrop.on('click', () => this.close());
        
        // Source picker
        this.$sourceButtons.on('click', (e) => {
            const $btn = $(e.currentTarget);
            if ($btn.attr('data-connected') === 'false') {
                this.showConnectPrompt($btn.data('source'));
                return;
            }
            this.switchSource($btn.data('source'));
        });
        
        // Sort change
        $('#gd-archive-sort').on('change', (e) => {
            this.loadArchivePerformances(this.currentSong, $(e.target).val());
        });
        
        // Play buttons (delegated)
        this.$performanceList.on('click', '.gd-play-btn', (e) => {
            const $btn = $(e.currentTarget);
            this.playPerformance($btn.data('identifier') || $btn.data('track-id'), $btn.data('url') || $btn.data('embed-url'));
        });
        
        // Audio events
        this.$audio.on('play', () => this.onAudioPlay());
        this.$audio.on('pause', () => this.onAudioPause());
        this.$audio.on('ended', () => this.onAudioEnd());
    }
    
    open(songData) {
        this.currentSong = songData;
        this.$songTitle.text(songData.title);
        this.$modal.fadeIn(200);
        $('body').addClass('gd-modal-open');
        
        // Load default source (archive)
        this.switchSource('archive');
    }
    
    close() {
        this.$modal.fadeOut(200);
        $('body').removeClass('gd-modal-open');
        this.$audio[0].pause();
        this.$audioPlayer.hide();
    }
    
    switchSource(source) {
        this.currentSource = source;
        
        // Update active button
        this.$sourceButtons.removeClass('active');
        this.$sourceButtons.filter(`[data-source="${source}"]`).addClass('active');
        
        // Show corresponding performance list
        this.$performanceList.hide();
        this.$performanceList.filter(`[data-source="${source}"]`).show();
        
        // Load performances if not cached
        if (!this.performanceCache[source]) {
            if (source === 'archive') {
                this.loadArchivePerformances(this.currentSong, 'downloads');
            } else {
                this.loadStreamingResults(source, this.currentSong);
            }
        }
    }
    
    async loadArchivePerformances(songData, sortBy) {
        const cacheKey = `${songData.id}_${sortBy}`;
        
        // Show loading state
        const $list = this.$performanceList.filter('[data-source="archive"]');
        $list.find('.gd-performance-scroll').html('<div class="loading">Loading performances...</div>');
        
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
            
            if (data.success) {
                this.performanceCache[cacheKey] = data.data;
                this.renderArchivePerformances(data.data);
            } else {
                throw new Error(data.data.message || 'Failed to load performances');
            }
        } catch (error) {
            console.error('Archive search error:', error);
            $list.find('.gd-performance-scroll').html(`<div class="error">${error.message}</div>`);
        }
    }
    
    renderArchivePerformances(performances) {
        const $scroll = this.$performanceList.filter('[data-source="archive"]').find('.gd-performance-scroll');
        
        if (performances.length === 0) {
            $scroll.html('<div class="no-results">No performances found</div>');
            return;
        }
        
        const html = performances.map(perf => `
            <div class="gd-performance-item" data-identifier="${perf.identifier}">
                <img src="${perf.thumbnail}" alt="Show" class="performance-thumb" />
                <div class="performance-info">
                    <h4>${perf.venue}</h4>
                    <p class="performance-date">${this.formatDate(perf.date)}</p>
                    <p class="performance-stats">
                        <span class="downloads">${this.formatNumber(perf.downloads)} downloads</span>
                        ${perf.rating ? `<span class="rating">★ ${perf.rating}</span>` : ''}
                    </p>
                </div>
                <button class="gd-play-btn" data-identifier="${perf.identifier}" data-url="${perf.streaming_url}">
                    ▶ Play
                </button>
            </div>
        `).join('');
        
        $scroll.html(html);
    }
    
    async loadStreamingResults(service, songData) {
        const $list = this.$performanceList.filter(`[data-source="${service}"]`);
        $list.find('.gd-streaming-scroll').html('<div class="loading">Loading tracks...</div>');
        
        try {
            const response = await fetch(gdChatbot.ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'gd_chatbot_streaming_search',
                    nonce: gdChatbot.nonce,
                    service: service,
                    song_title: songData.title
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.performanceCache[service] = data.data;
                this.renderStreamingResults(service, data.data);
            } else {
                throw new Error(data.data.message || 'Failed to load tracks');
            }
        } catch (error) {
            console.error('Streaming search error:', error);
            $list.find('.gd-streaming-scroll').html(`<div class="error">${error.message}</div>`);
        }
    }
    
    renderStreamingResults(service, results) {
        const $scroll = this.$performanceList.filter(`[data-source="${service}"]`).find('.gd-streaming-scroll');
        
        if (results.length === 0) {
            $scroll.html('<div class="no-results">No tracks found</div>');
            return;
        }
        
        const html = results.map(track => `
            <div class="gd-streaming-item" data-track-id="${track.id}">
                <img src="${track.thumbnail}" alt="Album" class="track-thumb" />
                <div class="track-info">
                    <h4>${track.title}</h4>
                    <p class="track-album">${track.album}${track.year ? ` (${track.year})` : ''}</p>
                    <p class="track-duration">${this.formatDuration(track.duration_ms)}</p>
                </div>
                <button class="gd-play-btn" data-track-id="${track.id}" data-embed-url="${track.embed_url}">
                    ▶ Play on ${this.capitalize(service)}
                </button>
            </div>
        `).join('');
        
        $scroll.html(html);
    }
    
    playPerformance(id, url) {
        // Show audio player
        this.$audioPlayer.show();
        
        if (this.currentSource === 'archive') {
            // Play MP3 directly
            this.$audio.attr('src', url);
            this.$audio[0].load();
            this.$audio[0].play();
            $('#gd-streaming-player').hide();
            this.$audio.show();
        } else {
            // Load streaming service embed
            this.$audio.hide();
            const $embed = $('#gd-streaming-player');
            $embed.html(`<iframe src="${url}" width="100%" height="80" frameborder="0" allow="encrypted-media"></iframe>`);
            $embed.show();
        }
    }
    
    onAudioPlay() {
        // Update UI
    }
    
    onAudioPause() {
        // Update UI
    }
    
    onAudioEnd() {
        // Auto-play next or show completion
    }
    
    formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }
    
    formatNumber(num) {
        return num.toLocaleString();
    }
    
    formatDuration(ms) {
        const minutes = Math.floor(ms / 60000);
        const seconds = ((ms % 60000) / 1000).toFixed(0);
        return `${minutes}:${seconds.padStart(2, '0')}`;
    }
    
    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    showConnectPrompt(service) {
        alert(`Please connect your ${this.capitalize(service)} account in your profile settings to use this feature.`);
    }
}

// Initialize modal
let gdSongModal;
$(document).ready(function() {
    gdSongModal = new GDSongModal();
    
    // Bind song link clicks
    $(document).on('click', '.gd-song-link', function(e) {
        e.preventDefault();
        const songData = {
            id: $(this).data('song-id'),
            title: $(this).data('song-title'),
            author: $(this).data('song-author')
        };
        gdSongModal.open(songData);
    });
});
```

### 7.3 Modal CSS

```css
/* Modal Overlay */
.gd-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: none;
}

.gd-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
}

.gd-modal-dialog {
    position: relative;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    margin: 5vh auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Modal Header */
.gd-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e0e0e0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.gd-modal-header h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.gd-modal-close {
    background: none;
    border: none;
    font-size: 32px;
    line-height: 1;
    color: white;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.gd-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Source Picker */
.gd-source-picker {
    padding: 20px 24px;
    border-bottom: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.gd-source-picker label {
    display: block;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.gd-source-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.gd-source-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 2px solid #ddd;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
}

.gd-source-btn img {
    width: 20px;
    height: 20px;
}

.gd-source-btn:hover:not(:disabled) {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.gd-source-btn.active {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.gd-source-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Performance List */
.gd-performance-list {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.gd-performance-filters {
    padding: 16px 24px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.gd-performance-filters label {
    font-weight: 500;
    color: #666;
}

.gd-performance-filters select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.gd-performance-scroll,
.gd-streaming-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 16px 24px;
}

/* Performance Item */
.gd-performance-item,
.gd-streaming-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}

.gd-performance-item:hover,
.gd-streaming-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.performance-thumb,
.track-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
}

.performance-info,
.track-info {
    flex: 1;
    min-width: 0;
}

.performance-info h4,
.track-info h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.performance-date,
.track-album {
    margin: 4px 0;
    font-size: 14px;
    color: #666;
}

.performance-stats,
.track-duration {
    margin: 4px 0;
    font-size: 13px;
    color: #999;
}

.performance-stats span {
    margin-right: 16px;
}

.gd-play-btn {
    padding: 10px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
    flex-shrink: 0;
}

.gd-play-btn:hover {
    background: #5568d3;
}

/* Audio Player */
.gd-audio-player {
    padding: 20px 24px;
    border-top: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.player-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.player-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.player-info h4 {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
}

.player-info p {
    margin: 0;
    font-size: 14px;
    color: #666;
}

#gd-audio-element {
    width: 100%;
    height: 40px;
}

/* Loading / Error States */
.loading,
.error,
.no-results {
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-size: 14px;
}

.error {
    color: #dc3545;
}

/* Prevent body scroll when modal open */
body.gd-modal-open {
    overflow: hidden;
}
```

---

## 8. Security Requirements

### 8.1 Encryption Standards

**Encryption Method**: AES-256-CBC with WordPress salt keys

**Implementation**:

```php
/**
 * Encrypt sensitive data
 * 
 * @param string $data Data to encrypt
 * @return string Encrypted data (base64 encoded)
 */
function gd_chatbot_encrypt($data) {
    if (empty($data)) {
        return '';
    }
    
    $key = wp_salt('auth');
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    
    // Return IV + encrypted data (both base64 encoded)
    return base64_encode($iv . '::' . $encrypted);
}

/**
 * Decrypt sensitive data
 * 
 * @param string $encrypted Encrypted data (base64 encoded)
 * @return string|false Decrypted data or false on failure
 */
function gd_chatbot_decrypt($encrypted) {
    if (empty($encrypted)) {
        return '';
    }
    
    $key = wp_salt('auth');
    $data = base64_decode($encrypted);
    
    if ($data === false) {
        return false;
    }
    
    list($iv, $encrypted_data) = explode('::', $data, 2);
    
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
}
```

**Usage**:

```php
// Storing credentials
$encrypted_token = gd_chatbot_encrypt($access_token);
$wpdb->insert(
    $wpdb->prefix . 'gd_streaming_credentials',
    array(
        'user_id' => $user_id,
        'service' => 'spotify',
        'access_token' => $encrypted_token,
        // ...
    )
);

// Retrieving credentials
$row = $wpdb->get_row($wpdb->prepare(
    "SELECT access_token FROM {$wpdb->prefix}gd_streaming_credentials WHERE user_id = %d AND service = %s",
    $user_id,
    'spotify'
));

$access_token = gd_chatbot_decrypt($row->access_token);
```

### 8.2 HTTPS Requirements

**Mandatory HTTPS**:
- All OAuth callbacks MUST use HTTPS
- Streaming credential storage REQUIRES HTTPS
- Display warning in admin if site is not HTTPS

**Admin Warning**:

```php
add_action('admin_notices', 'gd_chatbot_https_warning');

function gd_chatbot_https_warning() {
    if (!is_ssl() && current_user_can('manage_options')) {
        ?>
        <div class="notice notice-error">
            <p><strong>GD Chatbot Streaming:</strong> HTTPS is required for music streaming integration. Please install an SSL certificate.</p>
        </div>
        <?php
    }
}
```

### 8.3 Nonce Verification

**All AJAX Endpoints**:

```php
// Song detection endpoint
add_action('wp_ajax_gd_chatbot_archive_search', 'gd_chatbot_handle_archive_search');
add_action('wp_ajax_nopriv_gd_chatbot_archive_search', 'gd_chatbot_handle_archive_search');

function gd_chatbot_handle_archive_search() {
    check_ajax_referer('gd_chatbot_nonce', 'nonce');
    
    // ... handle request
}

// Streaming search endpoint
add_action('wp_ajax_gd_chatbot_streaming_search', 'gd_chatbot_handle_streaming_search');

function gd_chatbot_handle_streaming_search() {
    check_ajax_referer('gd_chatbot_nonce', 'nonce');
    
    // Must be logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Authentication required'));
        return;
    }
    
    // ... handle request
}
```

### 8.4 Rate Limiting

**API Rate Limiting**:

```php
/**
 * Check and enforce rate limit for streaming API calls
 * 
 * @param int $user_id WordPress user ID
 * @param string $service Service identifier
 * @return bool Whether request is allowed
 */
function gd_chatbot_check_rate_limit($user_id, $service) {
    $transient_key = "gd_rate_limit_{$user_id}_{$service}";
    $count = get_transient($transient_key);
    
    // Allow 10 requests per minute per service
    $limit = 10;
    $window = 60; // seconds
    
    if ($count === false) {
        set_transient($transient_key, 1, $window);
        return true;
    }
    
    if ($count >= $limit) {
        return false;
    }
    
    set_transient($transient_key, $count + 1, $window);
    return true;
}
```

### 8.5 Data Access Controls

**Credential Access**:

```php
/**
 * Get streaming credentials for a user
 * ONLY allow users to access their own credentials
 * 
 * @param int $user_id WordPress user ID
 * @param string $service Service identifier
 * @return array|null Credentials or null
 */
function gd_chatbot_get_user_credentials($user_id, $service) {
    // Security check: only allow users to access their own credentials
    if (get_current_user_id() !== $user_id && !current_user_can('manage_options')) {
        return null;
    }
    
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}gd_streaming_credentials 
         WHERE user_id = %d AND service = %s AND is_active = 1",
        $user_id,
        $service
    ));
    
    if (!$row) {
        return null;
    }
    
    // Decrypt tokens
    $row->access_token = gd_chatbot_decrypt($row->access_token);
    if ($row->refresh_token) {
        $row->refresh_token = gd_chatbot_decrypt($row->refresh_token);
    }
    
    return $row;
}
```

---

## 9. API Integration Requirements

### 9.1 Archive.org API

**Documentation**: https://archive.org/developers/

**Key Endpoints**:

1. **Advanced Search**:
   - Endpoint: `https://archive.org/advancedsearch.php`
   - Method: GET
   - Authentication: None required
   - Rate Limit: None enforced (but be respectful)

2. **Metadata API**:
   - Endpoint: `https://archive.org/metadata/{identifier}`
   - Method: GET
   - Authentication: None required

3. **Download/Streaming**:
   - Endpoint: `https://archive.org/download/{identifier}/{filename}`
   - Method: GET
   - Authentication: None required
   - Format: Direct file access

**Implementation Class**: `GD_Archive_API`

**Required Methods**:
- `search_performances($song_title, $sort_by = 'downloads')`
- `get_metadata($identifier)`
- `get_streaming_url($identifier, $song_title)`
- `parse_setlist($identifier)` (future enhancement)

**Error Handling**:
- Handle network timeouts (30s)
- Cache failures (retry after 5 minutes)
- Invalid identifiers (return empty array)

### 9.2 Spotify API

**Documentation**: https://developer.spotify.com/documentation/web-api/

**Key Endpoints**:

1. **Search**:
   - Endpoint: `https://api.spotify.com/v1/search`
   - Method: GET
   - Authentication: Bearer token
   - Rate Limit: 180 requests/minute

2. **Get Track**:
   - Endpoint: `https://api.spotify.com/v1/tracks/{id}`
   - Method: GET
   - Authentication: Bearer token

3. **Web Playback SDK**:
   - Documentation: https://developer.spotify.com/documentation/web-playback-sdk/
   - Requires Spotify Premium

**OAuth Scopes Required**:
- `user-read-email`
- `user-read-private`
- `streaming`
- `user-read-playback-state`
- `user-modify-playback-state`

**Implementation Class**: `GD_Spotify_API`

**Required Methods**:
- `search_tracks($song_title, $artist = 'Grateful Dead')`
- `get_track_details($track_id)`
- `get_embed_url($track_id)`
- `refresh_access_token($refresh_token)`

### 9.3 Apple Music API

**Documentation**: https://developer.apple.com/documentation/applemusicapi/

**Key Endpoints**:

1. **Search Catalog**:
   - Endpoint: `https://api.music.apple.com/v1/catalog/{storefront}/search`
   - Method: GET
   - Authentication: Developer Token + User Token
   - Rate Limit: 20 requests/second

2. **Get Song**:
   - Endpoint: `https://api.music.apple.com/v1/catalog/{storefront}/songs/{id}`
   - Method: GET
   - Authentication: Developer Token

**MusicKit JS Integration**:
- Load: `https://js-cdn.music.apple.com/musickit/v3/musickit.js`
- Configure with Developer Token
- Request User Token via OAuth

**Implementation Class**: `GD_Apple_Music_API`

**Required Methods**:
- `search_songs($song_title, $artist = 'Grateful Dead')`
- `get_song_details($song_id)`
- `get_embed_url($song_id)` (returns Apple Music web player URL)

### 9.4 YouTube Music API

**Documentation**: https://developers.google.com/youtube/v3/

**Key Endpoints**:

1. **Search**:
   - Endpoint: `https://www.googleapis.com/youtube/v3/search`
   - Method: GET
   - Authentication: API Key
   - Rate Limit: 10,000 quota units/day (search costs 100 units)

2. **Get Video Details**:
   - Endpoint: `https://www.googleapis.com/youtube/v3/videos`
   - Method: GET
   - Authentication: API Key

**Implementation Class**: `GD_YouTube_API`

**Required Methods**:
- `search_videos($song_title, $artist = 'Grateful Dead')`
- `get_video_details($video_id)`
- `get_embed_url($video_id)` (returns YouTube embed URL)
- `filter_official_content($results)` (prioritize official channels)

**Quota Management**:
- Cache search results aggressively (24 hours)
- Use batch requests where possible
- Monitor daily quota usage

### 9.5 Amazon Music API

**Documentation**: Limited public documentation

**Notes**:
- No official public API as of 2026
- May require Amazon Music Partner Program enrollment
- Alternative: Use Amazon Alexa Voice Service API
- Fallback: Deep link to Amazon Music web player

**Implementation Class**: `GD_Amazon_Music_API`

**Methods** (if API available):
- `search_tracks($song_title, $artist = 'Grateful Dead')`
- `get_track_details($track_id)`
- `get_web_player_url($track_id)` (deep link)

**Alternative Approach**:
- Use deep links: `https://music.amazon.com/search/{query}`
- No embedded playback, open in new tab

### 9.6 Tidal API

**Documentation**: https://developer.tidal.com/

**Key Endpoints**:

1. **Search**:
   - Endpoint: `https://api.tidal.com/v1/search/tracks`
   - Method: GET
   - Authentication: Session ID
   - Rate Limit: 300 requests/minute

2. **Get Track**:
   - Endpoint: `https://api.tidal.com/v1/tracks/{id}`
   - Method: GET
   - Authentication: Session ID

**Implementation Class**: `GD_Tidal_API`

**Required Methods**:
- `search_tracks($song_title, $artist = 'Grateful Dead')`
- `get_track_details($track_id)`
- `get_web_player_url($track_id)` (Tidal web player link)

**Notes**:
- Requires Tidal HiFi subscription for playback
- Limited embedded player support
- Focus on high-quality audio formats

---

## 10. Testing Requirements

### 10.1 Unit Tests

**Test Coverage**:

1. **Song Detection**:
   - Test exact title matching
   - Test case-insensitive matching
   - Test disambiguation logic
   - Test false positive prevention
   - Test special characters in titles
   - Test multiple songs in one response

2. **Encryption/Decryption**:
   - Test encrypt → decrypt cycle
   - Test with empty strings
   - Test with special characters
   - Test with long tokens
   - Test decryption of invalid data

3. **API Wrappers**:
   - Mock API responses
   - Test error handling
   - Test rate limiting
   - Test caching behavior
   - Test token refresh

**Test Framework**: PHPUnit

**Example Test**:

```php
class GD_Song_Detector_Test extends WP_UnitTestCase {
    
    public function test_detects_single_song() {
        $detector = new GD_Song_Detector();
        $text = "Dark Star is one of the most beloved Grateful Dead songs.";
        $songs = $detector->detect_songs($text);
        
        $this->assertCount(1, $songs);
        $this->assertEquals('Dark Star', $songs[0]['title']);
    }
    
    public function test_detects_multiple_songs() {
        $detector = new GD_Song_Detector();
        $text = "They played Dark Star into Uncle John's Band.";
        $songs = $detector->detect_songs($text);
        
        $this->assertCount(2, $songs);
    }
    
    public function test_avoids_false_positives() {
        $detector = new GD_Song_Detector();
        $text = "The morning star was visible in the sky.";
        $songs = $detector->detect_songs($text);
        
        $this->assertCount(0, $songs);
    }
}
```

### 10.2 Integration Tests

**Test Scenarios**:

1. **Archive.org Integration**:
   - Search for popular song (Dark Star)
   - Search for rare song (limited performances)
   - Search for non-existent song
   - Test different sort options
   - Test caching behavior

2. **Streaming Services**:
   - OAuth flow (manual test)
   - Search with valid credentials
   - Search with expired token (test refresh)
   - Search with revoked credentials
   - Test rate limiting

3. **End-to-End Flow**:
   - User asks about song
   - Response includes song mention
   - Song link is clickable
   - Modal opens with performances
   - Audio playback works

**Test Environment**:
- Staging site with test accounts
- Mock OAuth responses for automated tests
- Real API calls for manual QA

### 10.3 Performance Tests

**Benchmarks**:

1. **Song Detection Performance**:
   - Target: < 50ms for typical response (200 words)
   - Test with 5+ songs in response
   - Test with 1000-word response

2. **Archive.org Search**:
   - Target: < 2s for first search (uncached)
   - Target: < 100ms for cached search
   - Test with 50 results

3. **Streaming API Searches**:
   - Target: < 1.5s per service (uncached)
   - Target: < 50ms cached
   - Test with 5 concurrent searches

4. **Modal Load Time**:
   - Target: < 500ms to open modal
   - Target: < 1s to load performance list
   - Test on slow 3G connection

**Load Testing**:
- 100 concurrent users
- Song detection in every response
- Mix of cached and uncached searches

### 10.4 Security Tests

**Security Checklist**:

1. **Credential Security**:
   - ✅ Tokens stored encrypted
   - ✅ HTTPS enforced for OAuth
   - ✅ No credentials in logs
   - ✅ No credentials in JavaScript
   - ✅ Secure database queries (prepared statements)

2. **Access Control**:
   - ✅ Users can only access own credentials
   - ✅ Nonce verification on all AJAX
   - ✅ Capability checks for admin functions
   - ✅ Rate limiting prevents abuse

3. **Input Validation**:
   - ✅ Song titles sanitized
   - ✅ Service names whitelisted
   - ✅ User IDs validated
   - ✅ API responses validated

**Penetration Testing**:
- Attempt to access other users' credentials
- Attempt SQL injection in song search
- Attempt XSS in song titles
- Attempt CSRF on OAuth callback

### 10.5 Browser Compatibility

**Supported Browsers**:

| Browser | Version | Notes |
|---------|---------|-------|
| Chrome | 90+ | Full support |
| Firefox | 88+ | Full support |
| Safari | 14+ | Full support, test MusicKit JS |
| Edge | 90+ | Full support |
| Mobile Safari | iOS 14+ | Test touch interactions |
| Chrome Mobile | Android 10+ | Test audio playback |

**Test Cases**:
- Modal display and interactions
- Audio element controls
- Iframe embeds (streaming services)
- Touch events on mobile
- Keyboard navigation

**Accessibility**:
- Screen reader testing
- Keyboard-only navigation
- ARIA labels on interactive elements
- Color contrast compliance

---

## 11. Implementation Phases

### Phase 1: Foundation (Weeks 1-2)

**Goals**:
- Song detection system
- Database schema
- Basic UI components

**Deliverables**:

1. **Backend**:
   - `class-song-detector.php` (complete)
   - Database migration for streaming tables
   - songs.csv loading and caching

2. **Frontend**:
   - Song link styling
   - Basic modal structure
   - Click event handling

3. **Testing**:
   - Unit tests for song detection
   - Test with 100+ song mentions
   - Performance benchmarks

**Success Criteria**:
- Song detection accuracy > 95%
- Detection time < 50ms per response
- Modal opens/closes smoothly

---

### Phase 2: Archive.org Integration (Weeks 3-4)

**Goals**:
- Archive.org API integration
- Performance list display
- Audio playback

**Deliverables**:

1. **Backend**:
   - `class-archive-api.php` (complete)
   - Search endpoint (`gd_chatbot_archive_search`)
   - Caching implementation

2. **Frontend**:
   - Performance list rendering
   - Sort/filter options
   - Audio player controls
   - Loading states

3. **Testing**:
   - Integration tests with Archive.org
   - Test with 10+ songs
   - Cache hit/miss tracking
   - Audio playback on all browsers

**Success Criteria**:
- Search returns < 2s (uncached)
- Cache hit rate > 80%
- Audio plays on all supported browsers
- 50 performances load smoothly

---

### Phase 3: User Profile & OAuth (Weeks 5-6)

**Goals**:
- User settings interface
- OAuth integration framework
- Credential storage

**Deliverables**:

1. **Backend**:
   - `class-oauth-handler.php` (complete)
   - Profile page UI
   - Registration form updates
   - Encryption functions

2. **Frontend**:
   - Service connection cards
   - OAuth popup flow
   - Connection status indicators

3. **Testing**:
   - Encrypt/decrypt cycle tests
   - OAuth flow manual testing
   - HTTPS enforcement
   - Security audit

**Success Criteria**:
- Users can connect services
- Tokens stored encrypted
- OAuth callbacks work reliably
- HTTPS enforced

---

### Phase 4: Spotify Integration (Week 7)

**Goals**:
- Spotify API integration
- Spotify Web Playback SDK

**Deliverables**:

1. **Backend**:
   - `class-spotify-api.php` (complete)
   - Search endpoint for Spotify
   - Token refresh logic

2. **Frontend**:
   - Spotify source in modal
   - Track list rendering
   - Spotify embed player

3. **Testing**:
   - Search accuracy
   - Playback functionality
   - Token refresh handling

**Success Criteria**:
- Spotify search works
- Playback requires Premium
- Results sorted by popularity

---

### Phase 5: Additional Streaming Services (Weeks 8-10)

**Goals**:
- Apple Music integration
- YouTube Music integration
- Amazon Music (if API available)
- Tidal integration

**Deliverables**:

1. **Backend**:
   - `class-apple-music-api.php`
   - `class-youtube-api.php`
   - `class-amazon-music-api.php`
   - `class-tidal-api.php`
   - Unified search in `class-streaming-api.php`

2. **Frontend**:
   - Service selector in modal
   - Service-specific player embeds
   - Fallback for unsupported browsers

3. **Testing**:
   - Test each service independently
   - Test source switching
   - Test with no connected services

**Success Criteria**:
- All 5 services functional
- Graceful degradation if service down
- Consistent UI across services

---

### Phase 6: Polish & Optimization (Weeks 11-12)

**Goals**:
- Performance optimization
- UX refinements
- Documentation

**Deliverables**:

1. **Performance**:
   - Lazy load performance lists
   - Optimize cache sizes
   - Reduce API calls
   - Minify assets

2. **UX**:
   - Loading animations
   - Error messages
   - Empty states
   - Keyboard shortcuts
   - Mobile optimizations

3. **Documentation**:
   - User guide for connecting services
   - Admin guide for API keys
   - Developer documentation
   - Troubleshooting guide

4. **Testing**:
   - Full regression testing
   - Load testing
   - Accessibility audit
   - Browser compatibility check

**Success Criteria**:
- < 500ms modal open time
- < 1s to load performances
- Smooth scrolling with 100+ results
- All documentation complete

---

## 12. Admin Configuration

### 12.1 Admin Settings Page

**Location**: `wp-admin/admin.php?page=gd-chatbot-settings` → Streaming tab

**Settings Sections**:

1. **General Streaming Settings**:
   - Enable/disable streaming integration
   - Default music source (Archive.org or user's first connected service)
   - Autoplay behavior (on/off)
   - Performance list limit (10-100 results)

2. **Archive.org Settings**:
   - Default sort order (downloads/date/rating)
   - Cache duration (1-24 hours)
   - Result limit per search

3. **Streaming Service API Keys**:
   - Spotify Client ID/Secret
   - Apple Music Team ID/Key ID
   - YouTube API Key
   - Amazon Music API Key (if available)
   - Tidal Client ID/Secret

4. **Security Settings**:
   - Force HTTPS for OAuth
   - Token refresh interval
   - Rate limit per user (requests/minute)

5. **Cache Management**:
   - Clear Archive.org cache
   - Clear streaming service cache
   - Cache statistics (hits/misses, size)

**Settings Code**:

```php
// Register settings
add_action('admin_init', 'gd_chatbot_register_streaming_settings');

function gd_chatbot_register_streaming_settings() {
    register_setting('gd_chatbot_streaming', 'gd_chatbot_streaming_enabled', array(
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'absint'
    ));
    
    register_setting('gd_chatbot_streaming', 'gd_chatbot_archive_default_sort', array(
        'type' => 'string',
        'default' => 'downloads',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    // ... more settings
}
```

### 12.2 OAuth Credentials Storage

**Recommended Approach**: Store in `wp-config.php` (not database) for security

```php
// Add to wp-config.php
define('GD_SPOTIFY_CLIENT_ID', 'your-client-id-here');
define('GD_SPOTIFY_CLIENT_SECRET', 'your-client-secret-here');
define('GD_APPLE_MUSIC_TEAM_ID', 'your-team-id');
define('GD_APPLE_MUSIC_KEY_ID', 'your-key-id');
define('GD_YOUTUBE_API_KEY', 'your-api-key');
define('GD_TIDAL_CLIENT_ID', 'your-client-id');
define('GD_TIDAL_CLIENT_SECRET', 'your-client-secret');
```

**Admin UI**: Show masked values with "Update" buttons

```php
<tr>
    <th scope="row">Spotify Client ID</th>
    <td>
        <?php if (defined('GD_SPOTIFY_CLIENT_ID') && GD_SPOTIFY_CLIENT_ID): ?>
            <code><?php echo substr(GD_SPOTIFY_CLIENT_ID, 0, 10); ?>...</code>
            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
            <p class="description">Configured in wp-config.php</p>
        <?php else: ?>
            <span class="dashicons dashicons-warning" style="color: orange;"></span>
            <p class="description">Not configured. Add <code>define('GD_SPOTIFY_CLIENT_ID', 'your-id');</code> to wp-config.php</p>
        <?php endif; ?>
    </td>
</tr>
```

---

## 13. User Documentation

### 13.1 User Guide: Connecting Streaming Services

**Title**: How to Connect Your Music Streaming Services

**Content**:

> **Why Connect?**
> 
> By connecting your streaming service accounts, you can instantly listen to Grateful Dead music directly from the chatbot. Just click any song mention in the chat!
> 
> **Supported Services**:
> - Spotify (requires Premium)
> - Apple Music (requires subscription)
> - YouTube Music (free or premium)
> - Amazon Music (requires Unlimited)
> - Tidal (requires HiFi subscription)
> 
> **How to Connect**:
> 
> 1. Go to your **Profile** page (click your name in top-right corner)
> 2. Scroll to **Music Streaming Services** section
> 3. Click **Connect** next to your preferred service
> 4. You'll be redirected to the service to authorize access
> 5. After authorizing, you'll be redirected back to your profile
> 6. The service will now show as **Connected** ✓
> 
> **Listening to Music**:
> 
> 1. Ask the chatbot about any Grateful Dead song
> 2. Song titles in responses will be underlined and clickable
> 3. Click a song title to open the music player
> 4. Choose your source: Archive.org (free) or your connected service
> 5. Browse performances/tracks and click **Play**
> 6. Enjoy the music!
> 
> **Privacy & Security**:
> 
> - Your credentials are encrypted and stored securely
> - We never share your listening history
> - You can disconnect any service at any time
> - We only request permissions needed for playback

### 13.2 Admin Guide: Setting Up Streaming Integration

**Title**: Admin Guide: Music Streaming Setup

**Content**:

> **Prerequisites**:
> 
> 1. ✅ WordPress site must use HTTPS (SSL certificate required)
> 2. ✅ PHP 7.4+ with OpenSSL extension
> 3. ✅ Developer accounts with streaming services
> 
> **Step 1: Register Developer Apps**:
> 
> **Spotify**:
> 1. Go to https://developer.spotify.com/dashboard
> 2. Create a new app
> 3. Add redirect URI: `https://yoursite.com/wp-admin/admin-ajax.php?action=gd_oauth_callback&service=spotify`
> 4. Copy Client ID and Client Secret
> 
> **Apple Music**:
> 1. Go to https://developer.apple.com/account/
> 2. Create a MusicKit identifier
> 3. Generate a MusicKit private key
> 4. Note your Team ID and Key ID
> 
> **YouTube**:
> 1. Go to https://console.cloud.google.com/
> 2. Create a new project
> 3. Enable YouTube Data API v3
> 4. Create API key
> 5. Add redirect URI for OAuth
> 
> *(Similar instructions for Amazon Music and Tidal)*
> 
> **Step 2: Add Credentials to wp-config.php**:
> 
> ```php
> // Music Streaming API Keys
> define('GD_SPOTIFY_CLIENT_ID', 'your-client-id');
> define('GD_SPOTIFY_CLIENT_SECRET', 'your-client-secret');
> define('GD_APPLE_MUSIC_TEAM_ID', 'your-team-id');
> define('GD_APPLE_MUSIC_KEY_ID', 'your-key-id');
> define('GD_YOUTUBE_API_KEY', 'your-api-key');
> define('GD_TIDAL_CLIENT_ID', 'your-client-id');
> define('GD_TIDAL_CLIENT_SECRET', 'your-client-secret');
> ```
> 
> **Step 3: Enable Streaming in Plugin Settings**:
> 
> 1. Go to **Settings → GD Chatbot → Streaming**
> 2. Check **Enable Music Streaming Integration**
> 3. Configure default sort order (Most Popular recommended)
> 4. Set cache duration (24 hours recommended)
> 5. Save settings
> 
> **Step 4: Test Integration**:
> 
> 1. Create a test user account
> 2. Connect Spotify (or your service) from profile page
> 3. Ask chatbot: "Tell me about Dark Star"
> 4. Click "Dark Star" in response
> 5. Verify modal opens with performances
> 6. Verify audio playback works
> 
> **Troubleshooting**:
> 
> - **"HTTPS Required" error**: Install SSL certificate
> - **"OAuth Failed" error**: Check redirect URIs match exactly
> - **"API Error" error**: Verify API keys are correct
> - **No performances found**: Check Archive.org cache (may need to clear)
> - **Playback fails**: Verify user has active subscription for service

---

## 14. Future Enhancements

### 14.1 Phase 7+ Features

**Advanced Features** (post-launch):

1. **Playlist Creation**:
   - Save favorite performances
   - Create custom playlists
   - Share playlists with other users

2. **Performance Ratings**:
   - Users can rate performances
   - Aggregate ratings across users
   - Personalized recommendations

3. **Lyrics Integration**:
   - Display lyrics alongside audio
   - Highlight current line during playback
   - Lyrics from Grateful Dead official sources

4. **Song History Visualization**:
   - Timeline of performances
   - Venue map
   - Era-based filtering

5. **Social Features**:
   - Share currently playing with friends
   - Comment on performances
   - Live listening parties

6. **Enhanced Search**:
   - Filter by venue, year, tour
   - Search within performance (specific song segments)
   - "Similar performances" recommendations

7. **Mobile App Integration**:
   - Native iOS/Android playback
   - Background audio
   - Offline downloads (Archive.org only)

8. **AI-Powered Discovery**:
   - "Find me the best Dark Star from the 70s"
   - Mood-based recommendations
   - "More like this" suggestions

---

## 15. Appendices

### Appendix A: Song Database Format

**File**: `plugin/context/reference/songs.csv`

**Format**:
```
Title,Author
Song Title,"Composer/Lyricist Information"
```

**Special Cases**:
- Covers: List original artist in Author field
- Co-writes: Use semicolon separator
- Multiple versions: Create separate entries with (Version) suffix

**Maintenance**:
- Update when new official releases include previously unreleased songs
- Add disambiguation notes in separate file
- Maintain alphabetical order for performance

---

### Appendix B: API Rate Limits Summary

| Service | Rate Limit | Quota | Cost |
|---------|-----------|-------|------|
| Archive.org | No enforced limit | Unlimited | Free |
| Spotify | 180 req/min | Unlimited | Free (requires Premium for playback) |
| Apple Music | 20 req/sec | Unlimited | Free (requires subscription) |
| YouTube | 10,000 units/day | 100 units/search | Free tier available |
| Amazon Music | Unknown | Unknown | May require partnership |
| Tidal | 300 req/min | Unlimited | Free (requires HiFi for playback) |

---

### Appendix C: Encryption Implementation

**Algorithm**: AES-256-CBC  
**Key Source**: WordPress `AUTH_KEY` salt  
**IV**: Random, prepended to ciphertext  

**Security Considerations**:
- Rotate `AUTH_KEY` annually
- Use `NONCE_KEY` for additional entropy
- Store IV with ciphertext (not separately)
- Never log or display decrypted tokens

---

### Appendix D: AJAX Endpoint Reference

**Public Endpoints** (require nonce):
- `gd_chatbot_archive_search` - Search Archive.org for song performances
- `gd_chatbot_streaming_search` - Search streaming service (requires login)

**User Endpoints** (require login + nonce):
- `gd_chatbot_connect_service` - Initiate OAuth flow
- `gd_chatbot_disconnect_service` - Disconnect streaming service
- `gd_oauth_callback` - OAuth callback handler

**Admin Endpoints** (require admin capability):
- `gd_chatbot_clear_cache` - Clear API caches
- `gd_chatbot_test_api` - Test API credentials

---

## 16. Conclusion

This requirements document provides a comprehensive blueprint for integrating music streaming capabilities into the GD-Chatbot plugin. The implementation will:

1. ✅ Automatically detect song mentions in chatbot responses
2. ✅ Provide instant access to live performances via Archive.org
3. ✅ Support user's preferred streaming services with encrypted credentials
4. ✅ Display ranked, scrollable performance lists with popularity sorting
5. ✅ Enable in-chat audio playback with professional player controls

**Key Success Factors**:
- Seamless user experience (no context switching)
- Strong security (encrypted credentials, HTTPS enforcement)
- High performance (aggressive caching, < 2s search times)
- Broad compatibility (5 major streaming services + Archive.org)
- Graceful degradation (works without streaming services)

**Estimated Timeline**: 12 weeks from start to production release

**Next Steps**:
1. Review and approve requirements
2. Set up development environment
3. Register developer accounts with streaming services
4. Begin Phase 1 implementation

---

**Document Approval**:

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Product Owner | | | |
| Lead Developer | | | |
| Security Review | | | |
| QA Lead | | | |

---

**Change Log**:

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-02-12 | IT Influentials | Initial requirements document |

---

**END OF REQUIREMENTS DOCUMENT**
