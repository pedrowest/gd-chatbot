# Cleanup Instructions for gd-claude-chatbot Plugin

Your plugin is 262.6MB because it contains many unnecessary development files. Follow these steps to reduce it to ~5-10MB:

## CRITICAL: Delete These Folders/Files

### 1. Delete the ENTIRE "training data" folder
**Location:** `gd-claude-chatbot/training data/`
**Size:** ~200MB (contains 100+ PDF files)
**Why:** These PDFs were only for development reference. The plugin doesn't use them.

### 2. Delete ALL old ZIP files (23 files!)
Delete these files from `gd-claude-chatbot/`:
- gd-claude-chatbot-1.4.0.zip through gd-claude-chatbot-1.9.2.zip
- gd-claude-chatbot-v1.9.3.zip
- gd-claude-chatbot-v1.9.4.zip
- gd-claude-chatbot.zip

Also delete: `gd-claude-chatbot/Installs/` folder (contains more old ZIPs)

### 3. Delete Development Documentation Files
Delete these from `gd-claude-chatbot/`:
- ACCURACY-SYSTEMS.html
- ACCURACY-SYSTEMS.md
- AIPOWER-FILE-UPLOAD-SUPPORT.md
- AIPOWER-INTEGRATION.md
- AIPOWER-QUICK-START.md
- BUSINESS-CONSULTING-PORTFOLIO-SUMMARY.md
- CCWP Certification Course Brochure May 2025.pdf
- CHANGES.md
- CLONE-INFO.md
- COMPREHENSIVE-DISAMBIGUATION.md
- CONTEXT-DISAMBIGUATION-FIXES.md
- CONTEXT-DISAMBIGUATION-VERIFICATION.md
- CONTEXT-FILES-DISAMBIGUATION-COMPLETE.md
- CONTEXT-FILES-INDEX.md
- CONTEXT-FILES-INTEGRATION.md
- CONTEXT-FILES-STATUS.md
- CONTEXT-FILES-SUMMARY.md
- CONTEXT-INTEGRATION-COMPLETE.md
- CONTEXT-INTEGRATION.md
- CSV-SUPPORT-ANSWER.md
- CWS Plan Outline.txt
- DISAMBIGUATION-FINAL-REPORT.md
- DISAMBIGUATION-INTEGRATION-COMPLETE.md
- DISAMBIGUATION-QUICK-STATUS.md
- DISAMBIGUATION-SUMMARY.md
- DOCUMENTATION-INDEX.md
- FACEBOOK-ANNOUNCEMENT.md
- GD-THEME.md
- Grateful Dead Wall of Sound — detailed o.ini
- grateful-dead-context.md
- GRATEFUL-DEAD-CONTEXT-REFACTOR-v1.9.0.md
- Here's a curated list of the most author.ini
- INSTALL-v1.8.2.md
- INSTALL-v1.8.3.md
- KB-LOADER-INTEGRATION.md
- KNOWLEDGE-BASE-VERIFICATION.md
- LINKEDIN-ANNOUNCEMENT.md
- MATRIX-VENUE-FIX.md
- PACKAGE-COMPLETE-1.4.0.md
- PLUGIN-UPDATE-COMPLETE.md
- PORTFOLIO-AGE-60-POSITIONING-STRATEGY.md
- PORTFOLIO-UPDATE-SUMMARY.md
- QUICK-REFERENCE.md
- RELEASE-NOTES-1.4.0.md
- RELEASE-NOTES-1.7.1.html
- RELEASE-NOTES-1.7.1.md
- RELEASE-NOTES-1.8.2.md
- RELEASE-NOTES-1.8.3.md
- SAFETY-GUARDRAILS-SUMMARY.md
- SAFETY-GUARDRAILS.md
- SAFETY-IMPLEMENTATION-NOTES.md
- SAFETY-QUICK-REFERENCE.md
- SETLIST-DATABASE.md
- SIA-CONSULTING-PROPOSAL.md
- STREAMING-SUMMARY.md
- STREAMING.md
- TAVILY-ENHANCEMENT-SUMMARY.md
- TAVILY-ENHANCEMENTS-v1.8.2.md
- TAVILY-QUICK-REFERENCE.md
- TECHNICAL-PORTFOLIO-SHOWCASE-WP.html
- TECHNICAL-PORTFOLIO-SHOWCASE.html
- VERSION-1.8.3-SUMMARY.md
- VERSION-HISTORY.html
- WESTERMAN-EMPLOYMENT-HISTORY
- test-syntax.php
- cleanup-for-release.sh

### 4. Delete Scripts Folder
Delete: `gd-claude-chatbot/Scripts/` (entire folder)

### 5. Delete Root CSV Files
Delete these from `gd-claude-chatbot/`:
- domains.csv
- grateful_dead_covers_archive_org_expanded.csv
- grateful_dead_covers_archive_org.csv
- grateful_dead_unique_songs.csv
- grateful_dead_venues_merged_normalized.csv

(These CSVs are duplicates - the plugin uses the ones in the context folder)

### 6. Clean Up Context Folder
Delete these from `gd-claude-chatbot/context/`:
- A Comprehensive Guide to Grateful Dead Online Resources.md
- A Guide to Regional Music and Rock Art Galleries.md
- Comprehensive List of Grateful Dead Academic Research Papers with PDF Downloads.md
- dissertations_theses_list.md
- gds_volume1_articles.md
- Grateful Dead Books
- Grateful Dead Chatbots and AI Tools.md
- Grateful Dead Competencies
- Grateful Dead Context Requirements
- Grateful Dead Scratch Pad
- Grateful Dead Songs with Duplicate Titles - Summary List.md
- grateful_dead_papers_findings.md
- LOCATION_VALIDATION_CRITICAL.md
- reverb.com_news_the-gear-of-the-grateful-dead.md
- the_bahr_gallery.md
- UC Santa Cruz Grateful Dead Archive: Comprehensive Summary of Holdings.md
- ucsc_gd_archive_notes.md

## KEEP These Files (Essential for Plugin)

### Core Plugin Files:
- gd-claude-chatbot.php (main plugin file)
- uninstall.php
- admin/ (folder)
- includes/ (folder)
- public/ (folder)

### Essential Documentation:
- README.md
- CHANGELOG.md
- USER-GUIDE.md
- QUICKSTART-GUIDE.md
- QUICKSTART-GUIDE.html

### Essential Context Files:
- context/Deadshows/ (folder - contains setlist database CSV files)
- context/grateful_dead_disambiguation_guide.md
- context/grateful_dead_equipment.csv
- context/grateful_dead_interview_transcripts_complete.md
- context/grateful_dead_interviews.md
- context/grateful_dead_songs.csv
- context/jerry_garcia_equipment.md
- context/jerrybase.com_interviews_18.md
- context/Grateful Dead Equipment List.md
- context/www.deaddisc.com_GDFD_JPBCompositions.htm.md
- context/www.deaddisc.com_GDFD_RHSongs.htm.md
- context/www.deaddisc.com_GDFD_Songs_Perf.htm.md

## After Cleanup

Once you've deleted all the unnecessary files, the plugin should be ~5-10MB instead of 262MB.

Then create the ZIP file:
```bash
cd "/Users/peterwesterman/Library/CloudStorage/GoogleDrive-peter@it-influentials.com/My Drive/ITI PRODUCTS/it-influentials.com/ITI WP Plugins"
zip -r gd-claude-chatbot-v1.9.4-clean.zip gd-claude-chatbot -x "*.DS_Store" -x "*__MACOSX*"
```

Or use Finder: Right-click the `gd-claude-chatbot` folder → Compress
