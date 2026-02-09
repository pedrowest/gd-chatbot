# GD Chatbot v2.0.4 - Release Notes

**Release Date:** January 12, 2026  
**File:** `gd-chatbot-v2.0.4.zip`  
**Size:** 333 KB  
**Total Files:** 92

---

## 🎉 What's New in v2.0.4

### 📚 Major Knowledge Base Enhancement

This release significantly expands the plugin's knowledge base by adding **14 new comprehensive knowledgebase files** from gd-claude-chatbot, making both plugins feature-identical in terms of content coverage.

### 📄 New Documentation

**SYSTEM-PROMPT.md** (NEW)
- Comprehensive documentation of the system prompt
- Explains role, tone, and response guidelines
- Details web search behavior and content priority
- Technical implementation details
- Customization guide for administrators
- 7.8 KB reference document

### ✨ New Knowledgebase Files Added

1. **A Comprehensive Guide to Grateful Dead Online Resources.md**
   - Complete directory of official websites, archives, and online resources
   - Setlist databases, streaming platforms, fan communities
   - Academic resources and research tools

2. **A Guide to Regional Music and Rock Art Galleries.md**
   - Comprehensive gallery and museum directory
   - Regional coverage: US and International locations
   - Specialization in rock poster art and music memorabilia

3. **Comprehensive List of Grateful Dead Academic Research Papers with PDF Downloads.md**
   - Curated collection of academic research papers
   - Direct PDF download links where available
   - Organized by topic and research area

4. **GD-THEME.md**
   - Theme and styling guidelines for Grateful Dead content
   - Visual design principles and color schemes
   - UI/UX recommendations

5. **Grateful Dead Chatbots and AI Tools.md**
   - Overview of AI-powered Grateful Dead tools
   - Chatbot implementations and features
   - Jerry Garcia AI voice and other innovations

6. **UC Santa Cruz Grateful Dead Archive: Comprehensive Summary of Holdings.md**
   - Detailed documentation of the official GD Archive
   - Collection descriptions and access information
   - Research resources and special collections

7. **dissertations_theses_list.md**
   - Academic dissertations and theses about the Grateful Dead
   - University sources and research topics
   - Full bibliographic information

8. **gds_volume1_articles.md**
   - Grateful Dead Studies journal articles
   - Academic analysis and cultural studies
   - Scholarly perspectives on the band

9. **grateful_dead_papers_findings.md**
   - Research paper findings and summaries
   - Key insights from academic studies
   - Synthesized research conclusions

10. **reverb.com_news_the-gear-of-the-grateful-dead.md**
    - Comprehensive equipment and gear information
    - Jerry Garcia's guitars (Wolf, Tiger, Rosebud, Lightning Bolt)
    - Phil Lesh's basses, Bob Weir's guitars
    - Amplification and effects systems

11. **ucsc_gd_archive_notes.md**
    - Additional UC Santa Cruz archive documentation
    - Collection notes and research tips
    - Access procedures and guidelines

12. **the_bahr_gallery.md**
    - Authoritative information about The Bahr Gallery
    - Location: Oyster Bay, Long Island, NY
    - Specialization in psychedelic rock poster art

13. **Grateful Dead Songs with Duplicate Titles - Summary List.md**
    - Song disambiguation guide
    - Identifies songs with duplicate titles across artists
    - Helps clarify which version is being discussed

14. **grateful-dead-context.md** (Updated)
    - Main comprehensive knowledge base file
    - **CRITICAL UPDATE:** Now includes accurate information about band member deaths:
      - Phil Lesh: Died October 25, 2024 at age 84
      - Bob Weir: Died January 10, 2026 at age 78
      - Bill Kreutzmann and Mickey Hart noted as surviving members

---

## 🔧 Technical Improvements

### Enhanced Context Loading System

**New Method:** `load_additional_knowledgebase_files()`
- Automatically loads all 11 additional knowledgebase markdown files
- Appends them to the Claude system prompt with proper formatting
- Provides detailed logging for debugging
- Handles missing files gracefully with error logging

### Context Loading Process (5 Steps)

1. **Main Context** - Loads `grateful-dead-context.md` as base knowledge
2. **Disambiguation** - Loads song title conflict resolution guides
3. **Additional Knowledge** - Automatically loads 11 specialized topic files
4. **Bahr Gallery** - Special injection with location verification
5. **System Prompt** - Combines everything into Claude's system prompt

### File Path Updates

- Fixed context file path in `class-claude-api.php`
- Changed from root directory to `context/` subdirectory
- Ensures proper file organization and loading

---

## 📖 Documentation Updates

### README.md Enhancements

Added comprehensive "Context Files" section with:
- **Core Context Files** (3 files) - Main knowledge base files
- **Specialized Knowledge Files** (11 files) - Topic-specific resources
- **Data Files** - CSV setlists, song catalogs, equipment specs
- **How Context is Loaded** - Detailed explanation of the 5-step process

### CHANGELOG.md

- Documented all 14 new knowledgebase files
- Listed technical improvements and new methods
- Noted band member death information updates
- Version history maintained

---

## 🎯 Benefits of This Release

### 1. **Comprehensive Knowledge Coverage**
- Matches gd-claude-chatbot's extensive knowledge base
- No knowledge gaps between the two plugins
- Complete coverage of all Grateful Dead topics

### 2. **Improved Accuracy**
- Up-to-date band member information (deaths of Phil Lesh and Bob Weir)
- More specialized information for academic resources
- Better gallery and museum information
- Enhanced archive documentation

### 3. **Better User Experience**
- More detailed and accurate responses
- Access to academic research and papers
- Gallery and museum recommendations
- Comprehensive online resource directory

### 4. **Automatic Loading**
- All files load automatically - no configuration needed
- Seamless integration with existing context
- Efficient memory management
- Proper error handling and logging

### 5. **Maintainability**
- Easy to add new knowledgebase files
- Clear documentation of all resources
- Organized file structure
- Version-controlled updates

---

## 📊 Plugin Statistics

- **Total Context Files:** 31 (in `/context/` directory)
- **Markdown Files:** 24
- **CSV Files:** 32 (setlists 1965-1995 + catalogs)
- **Documentation Files:** 5 (README, CHANGELOG, SYSTEM-PROMPT, etc.)
- **Total Plugin Files:** 92
- **Compressed Size:** 333 KB
- **Uncompressed Size:** ~1.4 MB

---

## 🔄 Compatibility

### WordPress Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher

### API Requirements
- Anthropic Claude API key (required)
- Tavily API key (optional, for web search)
- Pinecone credentials (optional, for vector database)

### Plugin Compatibility
- Can run side-by-side with gd-claude-chatbot
- Uses separate settings namespace (`gd_chatbot_v2_`)
- Independent database tables and options
- No conflicts with other chatbot plugins

---

## 📥 Installation

### New Installation

1. Upload `gd-chatbot-v2.0.4.zip` to WordPress
2. Activate the plugin
3. Go to **Settings → GD Chatbot v2**
4. Enter your Claude API key
5. Configure optional features (Tavily, Pinecone)
6. Add shortcode `[gd_chatbot_v2]` to any page

### Upgrading from Previous Version

1. Deactivate the old version
2. Upload and activate `gd-chatbot-v2.0.4.zip`
3. Your settings will be preserved
4. New knowledgebase files load automatically
5. No additional configuration needed

**Note:** If upgrading from v2.0.3 or earlier, the plugin will automatically load all new knowledgebase files on first initialization.

---

## 🐛 Bug Fixes

None in this release - focused on feature enhancement and knowledge base expansion.

---

## 🔮 Coming Soon

### Planned Features for Future Releases

- Multi-language support
- Enhanced analytics dashboard
- Additional theme options
- Extended API integration options
- Performance optimizations
- Additional knowledge base connectors
- Custom context file management UI

---

## 📞 Support

For support, feature requests, or bug reports:
- **Developer:** IT Influentials
- **Website:** https://it-influentials.com
- **Documentation:** See README.md in plugin directory

---

## 📝 Version History

- **v2.0.4** (2026-01-12) - Enhanced knowledge base with 14 new files
- **v2.0.3** (2026-01-12) - Admin menu improvements
- **v2.0.2** (2026-01-12) - Menu slug fix for side-by-side installation
- **v2.0.1** (2026-01-12) - Settings namespace separation
- **v2.0.0** (2026-01-12) - Major rebrand for independent installation
- **v1.7.1** (2026-01-10) - Initial fork from gd-claude-chatbot

---

## 🙏 Acknowledgments

- Based on gd-claude-chatbot v1.7.1 (last stable version)
- Grateful Dead context and knowledge base by IT Influentials
- Powered by Anthropic's Claude AI
- Web search by Tavily
- Vector database by Pinecone

---

## 📄 License

GPL-2.0+

---

**Happy Chatting! 🎸⚡💀🌹**
