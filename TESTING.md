# Testing Guide

This guide helps you test the AI Documentation Generator package to ensure it's working correctly.

## Prerequisites

Before testing, ensure you have:

1. Laravel application with Vue.js frontend
2. Vue Router configuration file
3. AI provider configured (Ollama recommended for testing)
4. Package installed and configured

## Quick Test

### 1. Verify Installation

```bash
# Check if commands are available
php artisan list | grep ai-docs

# You should see:
# ai-docs:build-vector-db
# ai-docs:generate
```

### 2. Test Configuration

```bash
php artisan tinker
```

```php
// Check if service is registered
app(\SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator::class);

// Check configuration
config('ai-docs.provider');
config('ai-docs.providers.ollama');

// Exit tinker
exit
```

### 3. Test Single Route

```bash
# Test with a simple route first
php artisan ai-docs:generate --test-single=/dashboard -v
```

Expected output:
- ✅ Routes file found
- ✅ Found routes to process
- ✅ Navigation analysis complete (or loaded from cache)
- ✅ Progress bar showing processing
- ✅ Documentation generation completed

### 4. Check Output

```bash
# List generated files
ls -la storage/app/ai_docs/frontend/

# View generated markdown
cat storage/app/ai_docs/frontend/dashboard.md

# View generated JSON
cat storage/app/ai_docs/frontend/dashboard.json
```

## Comprehensive Testing

### Test 1: Route Parsing

```bash
php artisan tinker
```

```php
$generator = app(\SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator::class);
$routes = $generator->parseRoutes(base_path('resources/js/router/index.js'));
dd($routes);
```

Expected: Array of routes with `path` and `component` keys.

### Test 2: Component Crawling

```php
$generator = app(\SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator::class);
$context = $generator->crawlVueFile(base_path('resources/js/pages/Dashboard.vue'));
dd($context);
```

Expected: Array with `file`, `content`, `imports`, and `raw_content` keys.

### Test 3: Navigation Analysis

```bash
php artisan ai-docs:generate --refresh-navigation --test-single=/dashboard
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

Expected: No errors, navigation memory built successfully.

### Test 4: Full Generation

```bash
# Generate docs for all routes
php artisan ai-docs:generate --force
```

Monitor:
- Progress bar advancement
- No errors in output
- Files created in output directory

### Test 5: Vector Database

```bash
# Build vector database
php artisan ai-docs:build-vector-db

# Check output
ls -la storage/app/ai_docs/frontend/
cat storage/app/ai_docs/frontend/index.json
```

Expected: `index.json` file with document metadata.

## Testing Different Providers

### Test Ollama

```bash
# Check Ollama is running
curl http://localhost:11434/api/tags

# Set provider
AI_DOCS_PROVIDER=ollama php artisan ai-docs:generate --test-single=/dashboard
```

### Test OpenAI

```bash
# Set provider and API key
AI_DOCS_PROVIDER=openai \
AI_DOCS_OPENAI_KEY=sk-your-key \
php artisan ai-docs:generate --test-single=/dashboard
```

### Test Claude

```bash
AI_DOCS_PROVIDER=claude \
AI_DOCS_CLAUDE_KEY=sk-ant-your-key \
php artisan ai-docs:generate --test-single=/dashboard
```

### Test Gemini

```bash
AI_DOCS_PROVIDER=gemini \
AI_DOCS_GEMINI_KEY=AIza-your-key \
php artisan ai-docs:generate --test-single=/dashboard
```

## Troubleshooting Tests

### Issue: "Routes file not found"

**Test:**
```bash
ls -la resources/js/router/index.js
```

**Fix:**
Update `config/ai-docs.php`:
```php
'layout_files' => [
    'router' => 'path/to/your/router.js',
],
```

### Issue: "AI provider not available"

**Test:**
```bash
# For Ollama
curl http://localhost:11434/api/tags

# For others, check API key
echo $AI_DOCS_OPENAI_KEY
```

**Fix:**
- Start Ollama: `ollama serve`
- Set correct API key in `.env`

### Issue: "Component file not found"

**Test:**
```bash
php artisan tinker
```

```php
$generator = app(\SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator::class);
$routes = $generator->parseRoutes(base_path('resources/js/router/index.js'));
foreach ($routes as $route) {
    if (!file_exists($route['component'])) {
        echo "Missing: {$route['component']}\n";
    }
}
```

**Fix:**
Check component paths in router configuration.

### Issue: "Memory exhausted"

**Test:**
```bash
php -d memory_limit=512M artisan ai-docs:generate --test-single=/dashboard
```

**Fix:**
Reduce settings in `config/ai-docs.php`:
```php
'generation' => [
    'max_depth' => 3,
    'chunk_size' => 1500,
    'concurrency' => 1,
],
```

## Performance Testing

### Measure Generation Time

```bash
time php artisan ai-docs:generate --test-single=/dashboard
```

### Measure Full Generation

```bash
time php artisan ai-docs:generate --force
```

### Test Concurrency

```bash
# Test with different concurrency levels
AI_DOCS_CONCURRENCY=1 time php artisan ai-docs:generate --force
AI_DOCS_CONCURRENCY=3 time php artisan ai-docs:generate --force
```

## Quality Testing

### Check Documentation Quality

1. **Completeness**: Does it have all 7 sections?
2. **Navigation**: Are navigation instructions specific?
3. **Clarity**: Is it written for non-technical users?
4. **Accuracy**: Does it match the actual page?
5. **Links**: Are route links working?

### Manual Review Checklist

```bash
# Generate docs
php artisan ai-docs:generate --test-single=/dashboard

# Review output
cat storage/app/ai_docs/frontend/dashboard.md
```

Check:
- [ ] Title is clear
- [ ] Overview explains purpose
- [ ] Navigation instructions are step-by-step
- [ ] Page layout is described
- [ ] User guide is detailed
- [ ] Examples are practical
- [ ] Troubleshooting is helpful
- [ ] Related pages are linked
- [ ] No technical jargon (Vue, components, props, etc.)
- [ ] Route links are clickable

## Automated Testing

### Unit Tests (Future)

Create `tests/Unit/DocumentationGeneratorTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use SchoolTry\AIDocumentationGenerator\Services\DocumentationGenerator;

class DocumentationGeneratorTest extends TestCase
{
    public function test_can_parse_routes()
    {
        $generator = app(DocumentationGenerator::class);
        $routes = $generator->parseRoutes(base_path('resources/js/router/index.js'));
        
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
    }
    
    public function test_can_crawl_vue_file()
    {
        $generator = app(DocumentationGenerator::class);
        $context = $generator->crawlVueFile(base_path('resources/js/pages/Dashboard.vue'));
        
        $this->assertArrayHasKey('file', $context);
        $this->assertArrayHasKey('content', $context);
    }
}
```

Run:
```bash
php artisan test --filter=DocumentationGeneratorTest
```

## Continuous Testing

### Daily Test Script

Create `test-ai-docs.sh`:

```bash
#!/bin/bash
echo "Testing AI Documentation Generator..."
php artisan ai-docs:generate --test-single=/dashboard --force
if [ $? -eq 0 ]; then
    echo "✅ Test passed"
else
    echo "❌ Test failed"
    exit 1
fi
```

### CI/CD Integration

Add to `.github/workflows/test.yml`:

```yaml
- name: Test AI Documentation Generator
  run: |
    php artisan ai-docs:generate --test-single=/dashboard
```

## Reporting Issues

When reporting issues, include:

1. Laravel version: `php artisan --version`
2. PHP version: `php -v`
3. Package version: Check `composer.json`
4. Provider used: Check `.env`
5. Error message: From terminal or `storage/logs/laravel.log`
6. Command run: Full command with options
7. Configuration: Relevant parts of `config/ai-docs.php`

Example:
```
Laravel: 10.x
PHP: 8.2
Package: 1.0.0
Provider: Ollama
Error: "Routes file not found"
Command: php artisan ai-docs:generate
Config: router path set to 'resources/js/router/index.js'
```

