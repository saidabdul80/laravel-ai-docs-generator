# AI Documentation Generator

A comprehensive Laravel package for generating end-user documentation from Vue.js frontend applications using AI. Supports multiple AI providers including Ollama, OpenAI, Claude, and Gemini.

## Features

- 🤖 **Multi-Provider Support**: Works with Ollama, OpenAI, Claude, Gemini, and SchoolTry AI Service
- 📝 **Automatic Documentation**: Generates comprehensive user guides from Vue components
- 🧭 **Navigation-Aware**: Analyzes navigation structure for accurate user instructions
- 🔗 **Route Links**: Automatically includes clickable route links in documentation
- 🎯 **Context-Rich**: Uses layout files and navigation components for better context
- 📊 **Vector Database**: Build searchable vector databases from generated docs
- ☁️ **Centralized AI**: Use SchoolTry AI Service for consistent documentation generation
- ⚙️ **Highly Configurable**: Extensive configuration options for customization

## Requirements

- PHP 8.1, 8.2, or 8.3
- Laravel 10.x, 11.x, or 12.x
- Composer

## Installation

### 1. Install via Composer

```bash
composer require laravel/ai-documentation-generator
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=ai-docs-config
```

This will create `config/ai-docs.php` in your application.

### 3. Configure Your AI Provider

Edit `config/ai-docs.php` or set environment variables:

#### For Ollama (Local)

```env
AI_DOCS_PROVIDER=ollama
AI_DOCS_OLLAMA_URL=http://localhost:11434
AI_DOCS_OLLAMA_LIGHT_MODEL=qwen2.5:3b-instruct
AI_DOCS_OLLAMA_STANDARD_MODEL=qwen2.5:7b-instruct
AI_DOCS_OLLAMA_HEAVY_MODEL=qwen2.5:14b
```

#### For OpenAI

```env
AI_DOCS_PROVIDER=openai
AI_DOCS_OPENAI_KEY=your-api-key-here
AI_DOCS_OPENAI_LIGHT_MODEL=gpt-3.5-turbo
AI_DOCS_OPENAI_STANDARD_MODEL=gpt-4
AI_DOCS_OPENAI_HEAVY_MODEL=gpt-4-turbo
```

#### For Claude

```env
AI_DOCS_PROVIDER=claude
AI_DOCS_CLAUDE_KEY=your-api-key-here
AI_DOCS_CLAUDE_LIGHT_MODEL=claude-3-haiku-20240307
AI_DOCS_CLAUDE_STANDARD_MODEL=claude-3-sonnet-20240229
AI_DOCS_CLAUDE_HEAVY_MODEL=claude-3-opus-20240229
```

#### For Gemini

```env
AI_DOCS_PROVIDER=gemini
AI_DOCS_GEMINI_KEY=your-api-key-here
AI_DOCS_GEMINI_LIGHT_MODEL=gemini-1.5-flash
AI_DOCS_GEMINI_STANDARD_MODEL=gemini-1.5-pro
AI_DOCS_GEMINI_HEAVY_MODEL=gemini-2.0-flash
```

#### For SchoolTry AI Service (Recommended)

```env
AI_DOCS_PROVIDER=schooltry
AI_DOCS_SCHOOLTRY_URL=http://localhost:8000/api/docs
AI_DOCS_SCHOOLTRY_KEY=your-api-key-here  # Optional
AI_DOCS_SCHOOLTRY_LIGHT_MODEL=documentation
AI_DOCS_SCHOOLTRY_STANDARD_MODEL=documentation
AI_DOCS_SCHOOLTRY_HEAVY_MODEL=documentation
```

**Benefits of SchoolTry AI Service:**
- Centralized AI service for consistent documentation
- Dedicated DocumentationGeneratorAgent optimized for user guides
- No external API costs (uses your own infrastructure)
- Customizable prompts and behavior
- Integrated with your SchoolTry ecosystem

## Usage

### Generate Documentation

Generate documentation for all routes in your application:

```bash
php artisan ai-docs:generate
```

#### Options

- `--routes=path/to/router.js` - Specify custom router file path
- `--max-depth=5` - Maximum depth for Vue component crawling
- `--refresh-navigation` - Refresh navigation analysis cache
- `--test-single=/path` - Test documentation generation for a single route
- `--concurrency=1` - Number of concurrent chunk requests
- `--force` - Regenerate docs even if they already exist

#### Examples

```bash
# Generate docs with custom router file
php artisan ai-docs:generate --routes=resources/js/router/index.js

# Test a single route
php artisan ai-docs:generate --test-single=/admin/dashboard

# Force regeneration of all docs
php artisan ai-docs:generate --force

# Refresh navigation analysis
php artisan ai-docs:generate --refresh-navigation
```

### Build Vector Database

After generating documentation, build a vector database for semantic search:

```bash
php artisan ai-docs:build-vector-db
```

#### Options

- `--dir=path/to/docs` - Directory containing markdown docs
- `--force` - Force rebuild of vector database
- `--limit=10` - Limit number of files to process
- `--upload` - Upload to AI service after building
- `--no-upload` - Skip upload even if enabled in config

### Upload to AI Service

Upload the built vector database to your AI service:

```bash
php artisan ai-docs:upload
```

#### Options

- `--dir=path/to/docs` - Directory containing neuron.store and index.json
- `--neuron-store=path` - Path to neuron.store file
- `--index=path` - Path to index.json file
- `--agent=name` - Agent name (overrides config)
- `--check-status` - Check AI service status before uploading

#### Configuration

Enable upload in your `.env`:

```env
AI_DOCS_UPLOAD_ENABLED=true
AI_SERVICE_URL=http://localhost:8000
AI_SERVICE_API_KEY=your-api-key-here
AI_DOCS_AGENT_NAME=documentation
```

## Configuration

### Layout Files

Configure which files provide context for documentation generation:

```php
'layout_files' => [
    'router' => 'resources/js/router/index.js',
    'navigation' => 'resources/js/components/Navigation_modules',
    'layouts' => 'resources/js/layouts',
],
```

### Generation Settings

```php
'generation' => [
    'max_depth' => 5,              // Max component crawl depth
    'chunk_size' => 2000,          // Characters per chunk
    'concurrency' => 1,            // Concurrent requests
    'memory_limit' => 40,          // Memory messages to keep
    'output_dir' => 'storage/app/ai_docs/frontend',
    'cache_navigation' => true,
],
```

### Route Links

```php
'route_links' => [
    'enabled' => true,
    'base_url' => env('APP_URL', 'http://localhost'),
    'include_related' => true,
    'max_related' => 5,
],
```

### Documentation Structure

Control which sections are included in generated documentation:

```php
'structure' => [
    'include_overview' => true,
    'include_navigation' => true,
    'include_layout' => true,
    'include_step_by_step' => true,
    'include_examples' => true,
    'include_troubleshooting' => true,
    'include_related_pages' => true,
    'include_route_links' => true,
],
```

## Output

Generated documentation is stored in:
- **Markdown**: `storage/app/ai_docs/frontend/{slug}.md`
- **JSON**: `storage/app/ai_docs/frontend/{slug}.json`

Each document includes:
1. Overview & Purpose
2. Navigation Instructions (role-specific)
3. Page Layout Description
4. Step-by-Step User Guide
5. Common Tasks & Examples
6. Troubleshooting & FAQ
7. Related Pages with Links

## License

MIT License

## Credits

Developed by SchoolTry Team

