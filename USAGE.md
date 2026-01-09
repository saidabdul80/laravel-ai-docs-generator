# Usage Guide

## Quick Start

### 1. Install and Configure

```bash
# Install the package
composer require laravel/ai-documentation-generator

# Publish configuration
php artisan vendor:publish --tag=ai-docs-config

# Configure your AI provider in .env
AI_DOCS_PROVIDER=ollama
AI_DOCS_OLLAMA_URL=http://localhost:11434
```

### 2. Generate Documentation

```bash
# Generate docs for all routes
php artisan ai-docs:generate

# Test with a single route first
php artisan ai-docs:generate --test-single=/admin/dashboard
```

### 3. Build Vector Database

```bash
# Build searchable vector database
php artisan ai-docs:build-vector-db
```

### 4. Upload to AI Service (Optional)

```bash
# Upload to AI service
php artisan ai-docs:upload
```

## Advanced Usage

### Custom Router File

If your router file is in a non-standard location:

```bash
php artisan ai-docs:generate --routes=resources/js/custom-router.js
```

### Controlling Component Depth

Limit how deep the crawler goes into nested components:

```bash
php artisan ai-docs:generate --max-depth=3
```

### Force Regeneration

Regenerate all documentation, even if it already exists:

```bash
php artisan ai-docs:generate --force
```

### Refresh Navigation Analysis

If you've updated your navigation components:

```bash
php artisan ai-docs:generate --refresh-navigation
```

## Configuration Examples

### Using Multiple Providers

You can switch between providers by changing the environment variable:

```bash
# Use Ollama (local, free)
AI_DOCS_PROVIDER=ollama

# Use OpenAI (requires API key)
AI_DOCS_PROVIDER=openai
AI_DOCS_OPENAI_KEY=sk-...

# Use Claude (requires API key)
AI_DOCS_PROVIDER=claude
AI_DOCS_CLAUDE_KEY=sk-ant-...

# Use Gemini (requires API key)
AI_DOCS_PROVIDER=gemini
AI_DOCS_GEMINI_KEY=AIza...
```

### Customizing Model Selection

Each provider has three model sizes: lightweight, standard, and heavy.

```env
# Ollama models
AI_DOCS_OLLAMA_LIGHT_MODEL=qwen2.5:3b-instruct
AI_DOCS_OLLAMA_STANDARD_MODEL=qwen2.5:7b-instruct
AI_DOCS_OLLAMA_HEAVY_MODEL=qwen2.5:14b

# OpenAI models
AI_DOCS_OPENAI_LIGHT_MODEL=gpt-3.5-turbo
AI_DOCS_OPENAI_STANDARD_MODEL=gpt-4
AI_DOCS_OPENAI_HEAVY_MODEL=gpt-4-turbo
```

### Adjusting Generation Settings

```env
# Process more components at once (faster but more memory)
AI_DOCS_CONCURRENCY=3

# Larger chunks (better context but slower)
AI_DOCS_CHUNK_SIZE=3000

# Deeper component crawling
AI_DOCS_MAX_DEPTH=7
```

### Configuring AI Service Upload

```env
# Enable automatic upload to AI service
AI_DOCS_UPLOAD_ENABLED=true

# AI service configuration
AI_SERVICE_URL=http://localhost:8000
AI_SERVICE_API_KEY=your-api-key-here
AI_DOCS_AGENT_NAME=documentation

# Upload settings
AI_DOCS_UPLOAD_TIMEOUT=60
AI_DOCS_VERIFY_SSL=true
```

## Programmatic Usage

You can also use the package programmatically in your code:

```php
use SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator;

class MyDocumentationService
{
    protected DocumentationGenerator $generator;

    public function __construct(DocumentationGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function generateForRoute(string $routePath)
    {
        // Parse routes
        $routes = $this->generator->parseRoutes(
            base_path('resources/js/router/index.js')
        );

        // Find specific route
        $route = collect($routes)->firstWhere('path', $routePath);

        if (!$route) {
            throw new \Exception("Route not found: {$routePath}");
        }

        // Analyze navigation (once)
        $this->generator->analyzeNavigation();

        // Crawl component
        $pageContext = $this->generator->crawlVueFile($route['component']);

        // Generate documentation
        $docs = $this->generator->generateDocumentation($route, $pageContext);

        return $docs;
    }
}
```

## Output Structure

Generated documentation follows this structure:

```markdown
# Page Title

**Page URL:** http://localhost/path/to/page

## 1. Overview & Purpose
- What the page does
- Who uses it
- When to use it

## 2. How to Access This Page
**For Students:**
1. Log in to Student Portal
2. Click "Dashboard"
3. Select "My Courses"

## 3. Page Layout & What You'll See
- Main sections
- Key information displayed

## 4. Step-by-Step User Guide
- Detailed instructions
- Button actions
- Form usage

## 5. Common Tasks & Real Examples
- Example 1: Enrolling in a course
- Example 2: Viewing grades

## 6. Troubleshooting & FAQ
- Common problems and solutions

## 7. Related Pages & Next Steps
- [/student/grades](http://localhost/student/grades)
- [/student/schedule](http://localhost/student/schedule)
```

## Tips and Best Practices

### 1. Start Small
Test with a single route before generating all documentation:
```bash
php artisan ai-docs:generate --test-single=/dashboard
```

### 2. Use Ollama for Development
Ollama is free and runs locally, perfect for development:
```bash
# Install Ollama
curl https://ollama.ai/install.sh | sh

# Pull models
ollama pull qwen2.5:3b-instruct
ollama pull qwen2.5:7b-instruct
```

### 3. Cache Navigation
Navigation analysis is slow. Cache it and only refresh when needed:
```bash
# First run analyzes navigation
php artisan ai-docs:generate

# Subsequent runs use cache
php artisan ai-docs:generate

# Refresh when navigation changes
php artisan ai-docs:generate --refresh-navigation
```

### 4. Monitor Progress
Use verbose output to see what's happening:
```bash
php artisan ai-docs:generate -v
```

### 5. Handle Errors Gracefully
Check logs if generation fails:
```bash
tail -f storage/logs/laravel.log
```

### 6. Upload to AI Service
After building the vector database, upload it to your AI service:
```bash
# Build and upload in one step
php artisan ai-docs:build-vector-db --upload

# Or upload separately
php artisan ai-docs:upload

# Check status before uploading
php artisan ai-docs:upload --check-status
```

## Troubleshooting

### "Routes file not found"
Make sure your router file path is correct in config:
```php
'layout_files' => [
    'router' => 'resources/js/router/index.js', // Check this path
],
```

### "AI provider not available"
Check your provider configuration and API keys:
```bash
# Test Ollama connection
curl http://localhost:11434/api/tags

# Verify API key is set
php artisan tinker
>>> config('ai-docs.providers.openai.api_key')
```

### "Out of memory"
Reduce concurrency and chunk size:
```env
AI_DOCS_CONCURRENCY=1
AI_DOCS_CHUNK_SIZE=1500
AI_DOCS_MAX_DEPTH=3
```

