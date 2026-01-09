#!/bin/bash

# AI Documentation Generator - Installation Script
# This script helps set up the package in your Laravel application

set -e

echo "🚀 AI Documentation Generator - Installation Script"
echo "=================================================="
echo ""

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This doesn't appear to be a Laravel project root."
    echo "   Please run this script from your Laravel project root directory."
    exit 1
fi

echo "✅ Laravel project detected"
echo ""

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found"
    exit 1
fi

echo "📦 Installing package..."
echo ""

# Add repository to composer.json if not already present
if ! grep -q "ai-documentation-generator" composer.json; then
    echo "Adding package repository to composer.json..."
    
    # Backup composer.json
    cp composer.json composer.json.backup
    
    # Add repository (this is a simplified version, adjust path as needed)
    echo "Please manually add the repository to composer.json:"
    echo ""
    echo '"repositories": ['
    echo '    {'
    echo '        "type": "path",'
    echo '        "url": "./packages/ai-documentation-generator"'
    echo '    }'
    echo ']'
    echo ""
    echo "And add to require:"
    echo '"schooltry/ai-documentation-generator": "*"'
    echo ""
    read -p "Press Enter after you've updated composer.json..."
fi

# Run composer update
echo "Running composer update..."
composer update laravel/ai-documentation-generator

echo ""
echo "📝 Publishing configuration..."
php artisan vendor:publish --tag=ai-docs-config --force

echo ""
echo "🔧 Setting up environment variables..."

# Check if .env exists
if [ -f ".env" ]; then
    # Check if AI_DOCS variables already exist
    if ! grep -q "AI_DOCS_PROVIDER" .env; then
        echo ""
        echo "Adding AI Documentation Generator variables to .env..."
        cat >> .env << 'EOF'

# AI Documentation Generator
AI_DOCS_PROVIDER=ollama
AI_DOCS_OLLAMA_URL=http://localhost:11434
AI_DOCS_OLLAMA_LIGHT_MODEL=qwen2.5:3b-instruct
AI_DOCS_OLLAMA_STANDARD_MODEL=qwen2.5:7b-instruct
AI_DOCS_OLLAMA_HEAVY_MODEL=qwen2.5:14b
EOF
        echo "✅ Environment variables added"
    else
        echo "⚠️  AI_DOCS variables already exist in .env"
    fi
else
    echo "⚠️  .env file not found. Please create one and add the required variables."
fi

echo ""
echo "🎉 Installation complete!"
echo ""
echo "Next steps:"
echo "1. Configure your AI provider in .env (default is Ollama)"
echo "2. If using Ollama, make sure it's running: ollama serve"
echo "3. Pull required models: ollama pull qwen2.5:3b-instruct"
echo "4. Generate documentation: php artisan ai-docs:generate"
echo ""
echo "For more information, see:"
echo "- README.md for overview"
echo "- USAGE.md for detailed usage instructions"
echo "- config/ai-docs.php for configuration options"
echo ""
echo "Happy documenting! 📚"

