#!/bin/bash

# AI Documentation Generator - Local Installation Script
# This script helps install the package locally for development

set -e

echo "🚀 AI Documentation Generator - Local Installation"
echo "=================================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PACKAGE_DIR="$SCRIPT_DIR"

echo "📦 Package directory: $PACKAGE_DIR"
echo ""

# Check if composer.json exists
if [ ! -f "$PACKAGE_DIR/composer.json" ]; then
    echo -e "${RED}❌ Error: composer.json not found in package directory${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Package composer.json found${NC}"
echo ""

# Ask for Laravel project path
read -p "Enter the path to your Laravel project (e.g., ../../schooltry-tertiary): " LARAVEL_PATH

# Resolve absolute path
if [[ "$LARAVEL_PATH" = /* ]]; then
    LARAVEL_DIR="$LARAVEL_PATH"
else
    LARAVEL_DIR="$SCRIPT_DIR/$LARAVEL_PATH"
fi

# Normalize path
LARAVEL_DIR="$(cd "$LARAVEL_DIR" 2>/dev/null && pwd)" || {
    echo -e "${RED}❌ Error: Laravel project directory not found: $LARAVEL_PATH${NC}"
    exit 1
}

echo "🎯 Laravel project: $LARAVEL_DIR"
echo ""

# Check if it's a Laravel project
if [ ! -f "$LARAVEL_DIR/artisan" ]; then
    echo -e "${RED}❌ Error: Not a Laravel project (artisan not found)${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Laravel project found${NC}"
echo ""

# Calculate relative path from Laravel to package
RELATIVE_PATH=$(python3 -c "import os.path; print(os.path.relpath('$PACKAGE_DIR', '$LARAVEL_DIR'))")

echo "📍 Relative path: $RELATIVE_PATH"
echo ""

# Backup composer.json
echo "💾 Backing up composer.json..."
cp "$LARAVEL_DIR/composer.json" "$LARAVEL_DIR/composer.json.backup"
echo -e "${GREEN}✅ Backup created: composer.json.backup${NC}"
echo ""

# Check if repositories section exists
if grep -q '"repositories"' "$LARAVEL_DIR/composer.json"; then
    echo -e "${YELLOW}⚠️  Repositories section already exists in composer.json${NC}"
    echo "Please manually add the following to your repositories array:"
    echo ""
    echo "{"
    echo "    \"type\": \"path\","
    echo "    \"url\": \"$RELATIVE_PATH\","
    echo "    \"options\": {"
    echo "        \"symlink\": true"
    echo "    }"
    echo "}"
    echo ""
else
    echo "📝 Adding repository configuration..."
    # This is a simplified approach - in production, use a proper JSON parser
    echo -e "${YELLOW}⚠️  Please manually add the repository configuration to composer.json${NC}"
    echo ""
    echo "Add this after the \"type\": \"project\" line:"
    echo ""
    echo "\"repositories\": ["
    echo "    {"
    echo "        \"type\": \"path\","
    echo "        \"url\": \"$RELATIVE_PATH\","
    echo "        \"options\": {"
    echo "            \"symlink\": true"
    echo "        }"
    echo "    }"
    echo "],"
    echo ""
fi

# Install the package
echo "📦 Installing package..."
echo ""
cd "$LARAVEL_DIR"

echo "Running: composer require schooltry/ai-documentation-generator:@dev"
echo ""

if composer require schooltry/ai-documentation-generator:@dev; then
    echo ""
    echo -e "${GREEN}✅ Package installed successfully!${NC}"
    echo ""
    
    # Publish configuration
    echo "📄 Publishing configuration..."
    if php artisan vendor:publish --tag=ai-docs-config --force; then
        echo -e "${GREEN}✅ Configuration published${NC}"
    else
        echo -e "${YELLOW}⚠️  Could not publish configuration automatically${NC}"
        echo "Run manually: php artisan vendor:publish --tag=ai-docs-config"
    fi
    
    echo ""
    echo "🎉 Installation complete!"
    echo ""
    echo "Next steps:"
    echo "1. Configure your AI provider in config/ai-docs.php"
    echo "2. Run: php artisan ai-docs:generate"
    echo "3. Run: php artisan ai-docs:build-vector-db"
    echo "4. Run: php artisan ai-docs:upload"
    echo ""
else
    echo ""
    echo -e "${RED}❌ Installation failed${NC}"
    echo ""
    echo "Restoring backup..."
    mv "$LARAVEL_DIR/composer.json.backup" "$LARAVEL_DIR/composer.json"
    echo ""
    echo "Please check the error messages above and try again."
    echo ""
    echo "Common issues:"
    echo "- Make sure the relative path is correct"
    echo "- Check Laravel version compatibility (10.x, 11.x, or 12.x)"
    echo "- Ensure PHP version is 8.1, 8.2, or 8.3"
    echo ""
    exit 1
fi

