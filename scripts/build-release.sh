#!/bin/bash
# Build Release Script for GD Chatbot WordPress Plugin
# Creates a clean, versioned ZIP file ready for WordPress installation
#
# Usage: ./build-release.sh [version]
# Example: ./build-release.sh 2.0.8
#
# If no version specified, reads from plugin header

set -e  # Exit on error

# Configuration
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_DIR="$PROJECT_ROOT/plugin"
RELEASES_DIR="$PROJECT_ROOT/releases"
PLUGIN_SLUG="gd-chatbot"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "================================================"
echo "GD Chatbot Plugin Build Script"
echo "================================================"
echo ""

# Change to project root
cd "$PROJECT_ROOT"

# Get version from argument or plugin header
if [ -n "$1" ]; then
    VERSION="$1"
else
    # Extract version from plugin main file
    VERSION=$(grep -m1 "Version:" "$PLUGIN_DIR/gd-chatbot.php" | sed 's/.*Version: *//' | tr -d '[:space:]')
fi

if [ -z "$VERSION" ]; then
    echo -e "${RED}Error: Could not determine version${NC}"
    echo "Usage: ./build-release.sh [version]"
    exit 1
fi

echo "Building version: $VERSION"
echo ""

# Create releases directory if it doesn't exist
mkdir -p "$RELEASES_DIR"

# Define output filename
ZIP_FILE="$RELEASES_DIR/${PLUGIN_SLUG}-${VERSION}.zip"

# Check if this version already exists
if [ -f "$ZIP_FILE" ]; then
    echo -e "${YELLOW}Warning: $ZIP_FILE already exists${NC}"
    read -p "Overwrite? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Build cancelled."
        exit 1
    fi
    rm -f "$ZIP_FILE"
fi

# Create a temporary build directory
BUILD_DIR=$(mktemp -d)
BUILD_PLUGIN_DIR="$BUILD_DIR/$PLUGIN_SLUG"

echo "Creating clean build in temporary directory..."
mkdir -p "$BUILD_PLUGIN_DIR"

# Copy plugin files (excluding development files)
echo "Copying plugin files..."

# Core plugin file
cp "$PLUGIN_DIR/gd-chatbot.php" "$BUILD_PLUGIN_DIR/"
cp "$PLUGIN_DIR/uninstall.php" "$BUILD_PLUGIN_DIR/"

# Documentation (essential only)
cp "$PLUGIN_DIR/readme.txt" "$BUILD_PLUGIN_DIR/"
cp "$PLUGIN_DIR/README.md" "$BUILD_PLUGIN_DIR/" 2>/dev/null || true
cp "$PLUGIN_DIR/CHANGELOG.md" "$BUILD_PLUGIN_DIR/" 2>/dev/null || true

# Admin directory
mkdir -p "$BUILD_PLUGIN_DIR/admin/css"
mkdir -p "$BUILD_PLUGIN_DIR/admin/js"
cp "$PLUGIN_DIR/admin/class-admin-settings.php" "$BUILD_PLUGIN_DIR/admin/"
cp "$PLUGIN_DIR/admin/css/"*.css "$BUILD_PLUGIN_DIR/admin/css/" 2>/dev/null || true
cp "$PLUGIN_DIR/admin/js/"*.js "$BUILD_PLUGIN_DIR/admin/js/" 2>/dev/null || true

# Includes directory
mkdir -p "$BUILD_PLUGIN_DIR/includes"
cp "$PLUGIN_DIR/includes/"*.php "$BUILD_PLUGIN_DIR/includes/"

# Public directory
mkdir -p "$BUILD_PLUGIN_DIR/public/css"
mkdir -p "$BUILD_PLUGIN_DIR/public/js"
cp "$PLUGIN_DIR/public/class-chatbot-public.php" "$BUILD_PLUGIN_DIR/public/"
cp "$PLUGIN_DIR/public/css/"*.css "$BUILD_PLUGIN_DIR/public/css/" 2>/dev/null || true
cp "$PLUGIN_DIR/public/js/"*.js "$BUILD_PLUGIN_DIR/public/js/" 2>/dev/null || true

# Context directory (essential knowledge base files)
mkdir -p "$BUILD_PLUGIN_DIR/context"

# Core context files
ESSENTIAL_CONTEXT=(
    "grateful-dead-context.md"
    "grateful_dead_disambiguation_guide.md"
    "grateful_dead_songs.csv"
    "grateful_dead_equipment.csv"
    "grateful_dead_interviews.md"
    "grateful_dead_interview_transcripts_complete.md"
    "jerry_garcia_equipment.md"
    "Grateful Dead Equipment List.md"
    "Grateful Dead Songs with Duplicate Titles - Summary List.md"
    "Music-Equipment-Disambiguations.md"
    "the_bahr_gallery.md"
    "A Comprehensive Guide to Grateful Dead Online Resources.md"
    "A Guide to Regional Music and Rock Art Galleries.md"
    "Comprehensive List of Grateful Dead Academic Research Papers with PDF Downloads.md"
    "GD-THEME.md"
    "Grateful Dead Chatbots and AI Tools.md"
    "UC Santa Cruz Grateful Dead Archive: Comprehensive Summary of Holdings.md"
    "dissertations_theses_list.md"
    "gds_volume1_articles.md"
    "grateful_dead_papers_findings.md"
    "reverb.com_news_the-gear-of-the-grateful-dead.md"
    "ucsc_gd_archive_notes.md"
    "www.deaddisc.com_GDFD_Songs_Perf.htm.md"
    "www.deaddisc.com_GDFD_RHSongs.htm.md"
    "www.deaddisc.com_GDFD_JPBCompositions.htm.md"
    "jerrybase.com_interviews_18.md"
    "tavily_trusted_domains.csv"
    "tavily_trusted_domains_list.csv"
    "tavily_trusted_domains_list.txt"
)

for file in "${ESSENTIAL_CONTEXT[@]}"; do
    if [ -f "$PLUGIN_DIR/context/$file" ]; then
        cp "$PLUGIN_DIR/context/$file" "$BUILD_PLUGIN_DIR/context/"
    fi
done

# Copy Deadshows setlist database
if [ -d "$PLUGIN_DIR/context/Deadshows" ]; then
    echo "Copying setlist database..."
    cp -r "$PLUGIN_DIR/context/Deadshows" "$BUILD_PLUGIN_DIR/context/"
fi

# Remove any .DS_Store files
find "$BUILD_PLUGIN_DIR" -name ".DS_Store" -delete 2>/dev/null || true

# Remove any __MACOSX directories
find "$BUILD_PLUGIN_DIR" -name "__MACOSX" -type d -exec rm -rf {} + 2>/dev/null || true

echo ""
echo "Creating ZIP archive..."

# Create the ZIP file
cd "$BUILD_DIR"
zip -r "$ZIP_FILE" "$PLUGIN_SLUG" -x "*.DS_Store" -x "*__MACOSX*"

# Clean up
rm -rf "$BUILD_DIR"

echo ""
echo -e "${GREEN}================================================${NC}"
echo -e "${GREEN}Build complete!${NC}"
echo -e "${GREEN}================================================${NC}"
echo ""
echo "Output: $ZIP_FILE"
echo ""

# Show file size
FILE_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
echo "File size: $FILE_SIZE"

# Count files in ZIP
FILE_COUNT=$(unzip -l "$ZIP_FILE" | tail -1 | awk '{print $2}')
echo "Files in archive: $FILE_COUNT"

echo ""
echo "To install:"
echo "  1. Go to WordPress Admin → Plugins → Add New → Upload Plugin"
echo "  2. Choose: $ZIP_FILE"
echo "  3. Click 'Install Now' then 'Activate'"
echo ""
