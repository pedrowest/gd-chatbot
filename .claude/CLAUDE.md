# ITI Product: GD Chatbot

**Project Type:** wordpress-product
**Product Prefix:** gd_chatbot
**Confidentiality:** INTERNAL (may become public)

## Product Overview

**Product Name:** GD Chatbot
**Description:** WordPress plugin providing an intelligent chatbot interface powered by Claude AI, with Grateful Dead domain knowledge
**Target Market:** WordPress site owners wanting AI-powered chatbots, Grateful Dead community sites
**Distribution:** Direct download, WordPress plugin repository (potential)

## WordPress Configuration

- **Minimum WP Version:** 5.9
- **PHP Compatibility:** 7.4 - 8.2
- **Tested Up To:** 6.4
- **License:** GPL-2.0+

## Product Features

### Core Features (v2.0.x)
- Claude AI integration for conversational responses
- Tavily search for real-time web information
- Pinecone vector database for semantic search
- Knowledge base integration (Knowledgebase Loader, AI Power)
- Customizable chatbot themes (GD theme, Professional theme)
- WordPress shortcode and floating widget support
- Conversation logging and analytics
- Streaming response support (SSE)
- Disambiguation system for song titles
- Setlist database search (1965-1995)

### Planned Features (Future)
- Multi-model support beyond Claude
- Enhanced analytics dashboard
- Additional theme options
- Multi-language support

## Architecture

- **Plugin Type:** Standard WordPress Plugin
- **Database Tables:** `wp_gd_chatbot_conversations`
- **External APIs:** 
  - Anthropic Claude API
  - Tavily Search API
  - Pinecone Vector Database
- **Dependencies:** None required (optional: Knowledgebase Loader, AI Power)

## Development Standards

### Coding Standards
- Follow WordPress.org plugin guidelines
- Security: Validate, sanitize, escape everything
- i18n: All strings translatable with `gd-chatbot` text domain
- Prefix all functions: `gd_chatbot_*`
- Prefix all classes: `GD_Chatbot_*`, `GD_*`

### File Path Constants
- `GD_CHATBOT_PLUGIN_DIR` - Plugin directory path
- `GD_CHATBOT_PLUGIN_URL` - Plugin URL
- `GD_CHATBOT_PLUGIN_BASENAME` - Plugin basename

### Key File Locations
- Main plugin: `plugin/gd-chatbot.php`
- Core classes: `plugin/includes/`
- Admin interface: `plugin/admin/`
- Frontend: `plugin/public/`
- Knowledge base: `plugin/context/` (core/, disambiguation/, reference/, supplementary/)
- Setlist data: `plugin/context/setlists/`

### Version Control
- **Repository:** Local git (potential GitHub)
- **Branch Strategy:** main/feature workflow
- **Releases:** Semantic versioning (2.0.x)

## Distribution Plan

- **WordPress.org:** Potential future submission
- **Premium Version:** No
- **Support:** Documentation in docs/guides/
- **Documentation:** docs/ directory

## Cross-Product Notes

This product can use patterns from other ITI PRODUCTS:
- ITI Chatbot (derivative)
- AI News Cafe chatbot (derivative)
- Scuba GPT chatbot (derivative)
- ITI Shared Libraries (common components)

## Project Structure

```
gd-chatbot/
├── CLAUDE.md              # Root project context
├── .claude/               # Claude configuration
│   ├── CLAUDE.md          # This file
│   └── settings.local.json
├── .github/               # GitHub configuration
├── plugin/                # Deployable WordPress plugin
├── docs/                  # All documentation
├── releases/              # ZIP packages
├── archive/               # Legacy code reference
└── scripts/               # Build automation
```

## Active Tasks

- [ ] Maintain disambiguation accuracy
- [ ] Keep context files updated
- [ ] Monitor API integration stability
- [ ] Document new features

## Known Issues

- `includes/music-disambiguations.md` was orphaned (not loaded by code) - consider integrating or removing
- Some context files may need periodic updates for accuracy

## Build Commands

```bash
# Create release ZIP
./scripts/build-release.sh 2.0.8

# Auto-detect version from plugin header
./scripts/build-release.sh
```

## Testing Checklist

- [ ] Test plugin activation/deactivation
- [ ] Test shortcode rendering
- [ ] Test floating widget
- [ ] Test Claude API integration
- [ ] Test Tavily search (if enabled)
- [ ] Test admin settings page
- [ ] Test conversation logging
- [ ] Test streaming responses

---

**Last Updated:** 2026-02-09
**Repository:** Local (potential GitHub)
