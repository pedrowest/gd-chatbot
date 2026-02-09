# Changelog

All notable changes to the GD Chatbot plugin will be documented in this file.

## [2.0.6] - 2026-01-12

### Changed
- **Visual Theme**: Switched default theme from Professional to Grateful Dead Psychedelic theme
  - Enabled the full Grateful Dead themed CSS (`gd-theme.css`)
  - Updated container class from `gd-theme-professional` to `gd-theme-grateful-dead`
  - Features psychedelic colors, Steal Your Face skull icons, dancing bears, roses, and lightning bolts
  - Includes animated effects: pulsing glow, dancing bears typing indicator, rotating skull
  - Retro fonts: Permanent Marker, Righteous, Concert One
  - Color palette inspired by classic Grateful Dead concert posters
  - Professional theme still available (commented out in code)

### Technical Details
- Modified `public/class-chatbot-public.php` to load `gd-theme.css` instead of `professional-theme.css`
- Changed default container class to use Grateful Dead theme styling
- All configuration settings preserved (API keys, prompts, etc.)

## [2.0.5] - 2026-01-12

### Fixed
- **CRITICAL BUG**: Fixed shortcode asset loading issue where CSS and JavaScript were not being enqueued when using `[gd_chatbot_v2]` shortcode
  - The `should_load_assets()` method was checking for `gd_chatbot` instead of `gd_chatbot_v2`
  - This caused the chatbot HTML to render but without any styling or functionality
  - Changed line 118 in `class-chatbot-public.php` to check for the correct shortcode name
  - See `BUGFIX-SHORTCODE-RENDERING.md` for full details

## [2.0.4] - 2026-01-12

### Added
- **Enhanced Knowledge Base**: Copied 14 additional knowledgebase files from gd-claude-chatbot
  - A Comprehensive Guide to Grateful Dead Online Resources.md
  - A Guide to Regional Music and Rock Art Galleries.md
  - Comprehensive List of Grateful Dead Academic Research Papers with PDF Downloads.md
  - GD-THEME.md
  - Grateful Dead Chatbots and AI Tools.md
  - UC Santa Cruz Grateful Dead Archive: Comprehensive Summary of Holdings.md
  - dissertations_theses_list.md
  - gds_volume1_articles.md
  - grateful_dead_papers_findings.md
  - reverb.com_news_the-gear-of-the-grateful-dead.md
  - ucsc_gd_archive_notes.md
  - grateful-dead-context.md (updated with Phil Lesh and Bob Weir death information)
  - Grateful Dead Songs with Duplicate Titles - Summary List.md
  - the_bahr_gallery.md
- New method `load_additional_knowledgebase_files()` in Claude API class to automatically load all knowledgebase files
- Comprehensive documentation in README.md about all context files and how they're loaded

### Changed
- Updated `grateful-dead-context.md` with accurate information about band member deaths:
  - Phil Lesh: Died October 25, 2024 at age 84
  - Bob Weir: Died January 10, 2026 at age 78
  - Bill Kreutzmann and Mickey Hart noted as surviving members
- Updated context file path in `class-claude-api.php` from root directory to `context/` subdirectory
- Enhanced README with detailed list of all context files and loading process

### Improved
- Knowledge base now matches gd-claude-chatbot's comprehensive coverage
- Better organization of specialized knowledge topics
- More accurate responses about academic resources, galleries, archives, and AI tools

## [2.0.3] - 2026-01-12

### Fixed
- Changed admin menu icon from `dashicons-format-chat` to `dashicons-admin-comments` for visual distinction
- Changed menu position from 30 to 31 to prevent WordPress from consolidating menus
- Ensures both plugins appear as truly separate top-level menus in WordPress admin

## [2.0.2] - 2026-01-12

### Fixed
- **CRITICAL**: Changed admin menu slug from `gd-chatbot` to `gd-chatbot-v2` to create distinct parent menu
- Both plugins now appear as separate top-level menu items in WordPress admin
- Prevents menu items from both plugins appearing under the same parent menu

## [2.0.1] - 2026-01-12

### Fixed
- **CRITICAL**: Changed WordPress option prefix from `gd_chatbot_` to `gd_chatbot_v2_` to prevent settings conflicts with gd-claude-chatbot
- Both plugins can now run side-by-side with completely independent settings
- Fixes accuracy degradation caused by shared database options between v1.7.1 and v2.0.0

### Changed
- All `get_option()` calls now use `gd_chatbot_v2_` prefix
- Updated all core classes: Claude API, Tavily API, Pinecone API, Chat Handler, KB Integration, AI Power Integration

## [2.0.0] - 2026-01-12

### Major Release - Full Rebrand for Side-by-Side Installation

### Changed
- **Version bumped to 2.0.0** to reflect major rebrand
- **Plugin Name**: Updated to "GD Chatbot v2" for clear differentiation
- **Admin Menu**: Now displays as "GD Chatbot v2" in WordPress admin
- **Shortcode**: Changed from `[gd_chatbot]` to `[gd_chatbot_v2]` to avoid conflicts
- **PHP Helper Function**: Changed from `gd_render_chatbot()` to `gd_render_chatbot_v2()`
- **All zip files rebuilt**: Internal folder structure changed from `gd-claude-chatbot/` to `gd-chatbot/`
- **Main plugin file renamed**: `gd-claude-chatbot.php` → `gd-chatbot.php` in all historical versions
- **Admin settings page slug**: Changed from `gd-claude-chatbot` to `gd-chatbot`

### Fixed
- WordPress installation conflict: Plugin now installs to separate directory (`gd-chatbot/` instead of `gd-claude-chatbot/`)
- Can now be installed side-by-side with gd-claude-chatbot without conflicts
- All 21 historical zip files rebuilt with correct internal structure

### Technical Details
- Database prefix: `gd_chatbot_` (separate from gd-claude-chatbot)
- Plugin directory: `wp-content/plugins/gd-chatbot/`
- Settings namespace: `gd_chatbot_` options
- AJAX actions: `gd_chatbot_*` (no conflicts)

## [1.7.1] - 2026-01-10

### Created
- Initial release based on gd-claude-chatbot v1.7.1 (last stable version)
- Forked from gd-claude-chatbot to create standalone plugin

### Changed
- Plugin name changed from "GD Claude Chatbot" to "GD Chatbot"
- Text domain changed from `gd-claude-chatbot` to `gd-chatbot`
- Package references updated from `GD_Claude_Chatbot` to `GD_Chatbot`
- Main class renamed from `GD_Claude_Chatbot` to `GD_Chatbot`
- Updated all function and hook references accordingly

### Added
- Comprehensive README.md with installation and usage instructions
- CLAUDE.md context file for AI development assistance
- Enhanced context files with current versions where 1.7.1 didn't have them
- Grateful Dead disambiguation guide (from current version)
- Additional interview and equipment documentation files

### Preserved
- All PHP class files from gd-claude-chatbot v1.7.1
- Complete CSS and JavaScript assets
- All historical .zip installation files (v1.3.0 through v1.9.5)
- Core functionality: Claude API, Tavily search, Pinecone integration
- Database schema and option structure
- Admin interface and settings
- Frontend themes (Professional and Psychedelic)
- Context files from v1.7.1
- Setlist search functionality
- Knowledge base integrations (Knowledgebase Loader, AI Power)

### Features Included
- Claude AI integration (Anthropic API)
- Streaming response support via SSE
- Tavily web search integration
- Pinecone vector database support
- WordPress Knowledgebase Loader integration
- AI Power plugin integration
- CSV-based setlist search (1965-1995)
- Customizable appearance and themes
- Conversation logging and history
- WordPress shortcode support: `[gd_chatbot]`
- Floating widget option
- Admin settings interface
- Connection testing for all APIs
- Configurable system prompts
- Multi-source context aggregation

### Technical Details
- WordPress 5.0+ compatibility
- PHP 7.4+ requirement
- Custom database table: `wp_gd_chatbot_conversations`
- Settings prefix: `gd_chatbot_`
- AJAX endpoints for chat and streaming
- Nonce-based security
- Proper sanitization and escaping

### Migration Notes
- Based on stable v1.7.1 codebase
- Can run alongside gd-claude-chatbot if needed
- Compatible with existing gd-claude-chatbot integrations
- Manual settings migration required if switching from gd-claude-chatbot

### File Structure
```
gd-chatbot/
├── gd-chatbot/
│   ├── gd-chatbot.php (v1.7.1)
│   ├── admin/
│   │   ├── class-admin-settings.php
│   │   ├── css/admin-styles.css
│   │   └── js/admin-scripts.js
│   ├── includes/
│   │   ├── class-aipower-integration.php
│   │   ├── class-chat-handler.php
│   │   ├── class-claude-api.php
│   │   ├── class-kb-integration.php
│   │   ├── class-pinecone-api.php
│   │   ├── class-setlist-search.php
│   │   └── class-tavily-api.php
│   ├── public/
│   │   ├── class-chatbot-public.php
│   │   ├── css/
│   │   │   ├── chatbot-styles.css
│   │   │   ├── gd-theme.css
│   │   │   └── professional-theme.css
│   │   └── js/chatbot.js
│   ├── context/
│   │   ├── Deadshows/ (CSV files 1965-1995)
│   │   ├── grateful_dead_disambiguation_guide.md
│   │   ├── grateful_dead_songs.csv
│   │   ├── grateful_dead_equipment.csv
│   │   └── [additional context files]
│   ├── uninstall.php
│   ├── README.md
│   └── CHANGELOG.md
└── plugin-installs/
    └── [21 historical .zip files from gd-claude-chatbot]
```

### Known Issues
- None at release (inherited stable v1.7.1 codebase)

### Dependencies
- WordPress 5.0+
- PHP 7.4+
- Anthropic Claude API key (required)
- Tavily API key (optional, for web search)
- Pinecone credentials (optional, for vector database)

### Credits
- Based on gd-claude-chatbot v1.7.1
- Developed by IT Influentials
- Original Grateful Dead context and knowledge base

---

## Future Releases

Future changes will be documented here as the plugin evolves beyond v1.7.1.

### Planned Features
- Multi-language support
- Enhanced analytics dashboard
- Additional theme options
- Extended API integration options
- Performance optimizations
- Additional knowledge base connectors

---

**Note**: This changelog follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format and adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
