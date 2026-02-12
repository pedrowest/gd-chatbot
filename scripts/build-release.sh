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
mkdir -p "$BUILD_PLUGIN_DIR/admin/partials"
cp "$PLUGIN_DIR/admin/class-admin-settings.php" "$BUILD_PLUGIN_DIR/admin/"
cp "$PLUGIN_DIR/admin/css/"*.css "$BUILD_PLUGIN_DIR/admin/css/" 2>/dev/null || true
cp "$PLUGIN_DIR/admin/js/"*.js "$BUILD_PLUGIN_DIR/admin/js/" 2>/dev/null || true
cp "$PLUGIN_DIR/admin/partials/"*.php "$BUILD_PLUGIN_DIR/admin/partials/" 2>/dev/null || true

# Includes directory
mkdir -p "$BUILD_PLUGIN_DIR/includes"
mkdir -p "$BUILD_PLUGIN_DIR/includes/oauth"
cp "$PLUGIN_DIR/includes/"*.php "$BUILD_PLUGIN_DIR/includes/"
cp "$PLUGIN_DIR/includes/oauth/"*.php "$BUILD_PLUGIN_DIR/includes/oauth/" 2>/dev/null || true

# Public directory
mkdir -p "$BUILD_PLUGIN_DIR/public/css"
mkdir -p "$BUILD_PLUGIN_DIR/public/js"
cp "$PLUGIN_DIR/public/class-chatbot-public.php" "$BUILD_PLUGIN_DIR/public/"
cp "$PLUGIN_DIR/public/css/"*.css "$BUILD_PLUGIN_DIR/public/css/" 2>/dev/null || true
cp "$PLUGIN_DIR/public/js/"*.js "$BUILD_PLUGIN_DIR/public/js/" 2>/dev/null || true

# Context directory (knowledge base organized in subdirectories)
echo "Copying knowledge base..."

# Copy core context files
if [ -d "$PLUGIN_DIR/context/core" ]; then
    mkdir -p "$BUILD_PLUGIN_DIR/context/core"
    cp "$PLUGIN_DIR/context/core/"*.md "$BUILD_PLUGIN_DIR/context/core/" 2>/dev/null || true
fi

# Copy disambiguation guides
if [ -d "$PLUGIN_DIR/context/disambiguation" ]; then
    mkdir -p "$BUILD_PLUGIN_DIR/context/disambiguation"
    cp "$PLUGIN_DIR/context/disambiguation/"*.md "$BUILD_PLUGIN_DIR/context/disambiguation/" 2>/dev/null || true
fi

# Copy reference data (CSV files)
if [ -d "$PLUGIN_DIR/context/reference" ]; then
    mkdir -p "$BUILD_PLUGIN_DIR/context/reference"
    cp "$PLUGIN_DIR/context/reference/"*.csv "$BUILD_PLUGIN_DIR/context/reference/" 2>/dev/null || true
fi

# Copy supplementary knowledge base files
if [ -d "$PLUGIN_DIR/context/supplementary" ]; then
    mkdir -p "$BUILD_PLUGIN_DIR/context/supplementary"
    cp "$PLUGIN_DIR/context/supplementary/"*.md "$BUILD_PLUGIN_DIR/context/supplementary/" 2>/dev/null || true
fi

# Copy setlist database (CSV files per year)
if [ -d "$PLUGIN_DIR/context/setlists" ]; then
    echo "Copying setlist database..."
    mkdir -p "$BUILD_PLUGIN_DIR/context/setlists"
    cp "$PLUGIN_DIR/context/setlists/"*.csv "$BUILD_PLUGIN_DIR/context/setlists/" 2>/dev/null || true
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
