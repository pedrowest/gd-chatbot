#!/bin/bash
# Cleanup script to remove unnecessary files before creating release ZIP
# This will remove development files, old zips, PDFs, and other non-essential files

PLUGIN_DIR="/Users/peterwesterman/Library/CloudStorage/GoogleDrive-peter@it-influentials.com/My Drive/ITI PRODUCTS/it-influentials.com/ITI WP Plugins/gd-claude-chatbot"

cd "$PLUGIN_DIR" || exit 1

echo "Starting cleanup of gd-claude-chatbot plugin..."
echo "================================================"

# Remove all old ZIP files
echo "Removing old ZIP files..."
rm -f *.zip
rm -f Installs/*.zip
echo "✓ Removed ZIP files"

# Remove entire training data folder (contains 100+ PDFs)
echo "Removing training data folder..."
rm -rf "training data"
echo "✓ Removed training data folder"

# Remove test files
echo "Removing test files..."
rm -f test-syntax.php
echo "✓ Removed test files"

# Remove development/documentation files (keep only essential docs)
echo "Removing development documentation..."
rm -f ACCURACY-SYSTEMS.html
rm -f AIPOWER-FILE-UPLOAD-SUPPORT.md
rm -f AIPOWER-INTEGRATION.md
rm -f AIPOWER-QUICK-START.md
rm -f BUSINESS-CONSULTING-PORTFOLIO-SUMMARY.md
rm -f "CCWP Certification Course Brochure May 2025.pdf"
rm -f CHANGES.md
rm -f CLONE-INFO.md
rm -f COMPREHENSIVE-DISAMBIGUATION.md
rm -f CONTEXT-DISAMBIGUATION-FIXES.md
rm -f CONTEXT-DISAMBIGUATION-VERIFICATION.md
rm -f CONTEXT-FILES-DISAMBIGUATION-COMPLETE.md
rm -f CONTEXT-FILES-INDEX.md
rm -f CONTEXT-FILES-INTEGRATION.md
rm -f CONTEXT-FILES-STATUS.md
rm -f CONTEXT-FILES-SUMMARY.md
rm -f CONTEXT-INTEGRATION-COMPLETE.md
rm -f CONTEXT-INTEGRATION.md
rm -f CSV-SUPPORT-ANSWER.md
rm -f "CWS Plan Outline.txt"
rm -f DISAMBIGUATION-FINAL-REPORT.md
rm -f DISAMBIGUATION-INTEGRATION-COMPLETE.md
rm -f DISAMBIGUATION-QUICK-STATUS.md
rm -f DISAMBIGUATION-SUMMARY.md
rm -f DOCUMENTATION-INDEX.md
rm -f FACEBOOK-ANNOUNCEMENT.md
rm -f GD-THEME.md
rm -f "Grateful Dead Wall of Sound — detailed o.ini"
rm -f "Here's a curated list of the most author.ini"
rm -f INSTALL-v1.8.2.md
rm -f INSTALL-v1.8.3.md
rm -f KB-LOADER-INTEGRATION.md
rm -f KNOWLEDGE-BASE-VERIFICATION.md
rm -f LINKEDIN-ANNOUNCEMENT.md
rm -f MATRIX-VENUE-FIX.md
rm -f PACKAGE-COMPLETE-1.4.0.md
rm -f PLUGIN-UPDATE-COMPLETE.md
rm -f PORTFOLIO-AGE-60-POSITIONING-STRATEGY.md
rm -f PORTFOLIO-UPDATE-SUMMARY.md
rm -f QUICK-REFERENCE.md
rm -f RELEASE-NOTES-1.4.0.md
rm -f RELEASE-NOTES-1.7.1.html
rm -f RELEASE-NOTES-1.7.1.md
rm -f RELEASE-NOTES-1.8.2.md
rm -f RELEASE-NOTES-1.8.3.md
rm -f SAFETY-GUARDRAILS-SUMMARY.md
rm -f SAFETY-GUARDRAILS.md
rm -f SAFETY-IMPLEMENTATION-NOTES.md
rm -f SAFETY-QUICK-REFERENCE.md
rm -f SETLIST-DATABASE.md
rm -f SIA-CONSULTING-PROPOSAL.md
rm -f STREAMING-SUMMARY.md
rm -f STREAMING.md
rm -f TAVILY-ENHANCEMENT-SUMMARY.md
rm -f TAVILY-ENHANCEMENTS-v1.8.2.md
rm -f TAVILY-QUICK-REFERENCE.md
rm -f TECHNICAL-PORTFOLIO-SHOWCASE-WP.html
rm -f TECHNICAL-PORTFOLIO-SHOWCASE.html
rm -f VERSION-1.8.3-SUMMARY.md
rm -f VERSION-HISTORY.html
rm -f WESTERMAN-EMPLOYMENT-HISTORY
rm -f GRATEFUL-DEAD-CONTEXT-REFACTOR-v1.9.0.md
rm -f ACCURACY-SYSTEMS.md
echo "✓ Removed development documentation"

# Remove Scripts folder if it exists
echo "Removing Scripts folder..."
rm -rf Scripts
echo "✓ Removed Scripts folder"

# Remove Installs folder
echo "Removing Installs folder..."
rm -rf Installs
echo "✓ Removed Installs folder"

# Clean up context folder - remove non-essential files
echo "Cleaning up context folder..."
cd context || exit 1
rm -f "A Comprehensive Guide to Grateful Dead Online Resources.md"
rm -f "A Guide to Regional Music and Rock Art Galleries.md"
rm -f "Comprehensive List of Grateful Dead Academic Research Papers with PDF Downloads.md"
rm -f dissertations_theses_list.md
rm -f gds_volume1_articles.md
rm -f "Grateful Dead Books"
rm -f "Grateful Dead Chatbots and AI Tools.md"
rm -f "Grateful Dead Competencies"
rm -f "Grateful Dead Context Requirements"
rm -f "Grateful Dead Scratch Pad"
rm -f "Grateful Dead Songs with Duplicate Titles - Summary List.md"
rm -f grateful_dead_papers_findings.md
rm -f LOCATION_VALIDATION_CRITICAL.md
rm -f "reverb.com_news_the-gear-of-the-grateful-dead.md"
rm -f the_bahr_gallery.md
rm -f "UC Santa Cruz Grateful Dead Archive: Comprehensive Summary of Holdings.md"
rm -f ucsc_gd_archive_notes.md
cd ..
echo "✓ Cleaned up context folder"

# Remove CSV files from root (keep only in context folder)
echo "Removing CSV files from root..."
rm -f domains.csv
rm -f grateful_dead_covers_archive_org_expanded.csv
rm -f grateful_dead_covers_archive_org.csv
rm -f grateful_dead_unique_songs.csv
rm -f grateful_dead_venues_merged_normalized.csv
echo "✓ Removed CSV files from root"

echo ""
echo "================================================"
echo "Cleanup complete!"
echo ""
echo "Files kept:"
echo "  - Core plugin files (PHP, CSS, JS)"
echo "  - README.md, CHANGELOG.md, USER-GUIDE.md"
echo "  - QUICKSTART-GUIDE.md and .html"
echo "  - context/Deadshows/ (setlist database)"
echo "  - Essential context files"
echo "  - uninstall.php"
echo ""
echo "Ready to create release ZIP!"
