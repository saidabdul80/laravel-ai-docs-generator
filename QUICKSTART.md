# Quick Start Guide

Get up and running with AI Documentation Generator in 5 minutes!

## Prerequisites

- Laravel 10.x or higher
- PHP 8.1 or higher
- Vue.js frontend with Vue Router
- Ollama installed (or API key for OpenAI/Claude/Gemini)

## Step 1: Install Ollama (Recommended for Testing)

```bash
# macOS/Linux
curl https://ollama.ai/install.sh | sh

# Start Ollama
ollama serve

# In another terminal, pull models
ollama pull qwen2.5:3b-instruct
ollama pull qwen2.5:7b-instruct
```

## Step 2: Install Package

```bash
# Add to composer.json repositories section:
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/ai-documentation-generator"
        }
    ],
    "require": {
        "schooltry/ai-documentation-generator": "*"
    }
}

# Install
composer update laravel/ai-documentation-generator
```

## Step 3: Publish Configuration

```bash
php artisan vendor:publish --tag=ai-docs-config
```

## Step 4: Configure Environment

Add to your `.env`:

```env
AI_DOCS_PROVIDER=ollama
AI_DOCS_OLLAMA_URL=http://localhost:11434
AI_DOCS_OLLAMA_LIGHT_MODEL=qwen2.5:3b-instruct
AI_DOCS_OLLAMA_STANDARD_MODEL=qwen2.5:7b-instruct
```

## Step 5: Test with Single Route

```bash
php artisan ai-docs:generate --test-single=/dashboard
```

Expected output:
```
🚀 Starting AI Documentation Generation
🔍 Looking for routes file at: /path/to/router.js
✅ Routes file found
Found 1 routes to process
🧭 Analyzing navigation structure...
✅ Navigation analysis complete
 1/1 [============================] 100%

🎉 Documentation generation completed. Processed: 1/1
```

## Step 6: Check Output

```bash
# View generated markdown
cat storage/app/ai_docs/frontend/dashboard.md

# View generated JSON
cat storage/app/ai_docs/frontend/dashboard.json
```

## Step 7: Generate All Documentation

```bash
php artisan ai-docs:generate
```

This will:
- Parse all routes from your Vue Router
- Analyze navigation structure
- Crawl all Vue components
- Generate comprehensive documentation for each page
- Store as markdown and JSON

## Step 8: Build Vector Database

```bash
php artisan ai-docs:build-vector-db
```

This creates a searchable index of your documentation.

## Step 9: Upload to AI Service (Optional)

If you want to use the documentation with your AI service:

```bash
# Configure in .env
AI_DOCS_UPLOAD_ENABLED=true
AI_SERVICE_URL=http://localhost:8000
AI_SERVICE_API_KEY=your-api-key-here

# Upload
php artisan ai-docs:upload
```

Or build and upload in one step:

```bash
php artisan ai-docs:build-vector-db --upload
```

## Common Issues & Quick Fixes

### Issue: "Routes file not found"

**Fix:** Update `config/ai-docs.php`:
```php
'layout_files' => [
    'router' => 'resources/js/router/index.js', // Your actual path
],
```

### Issue: "Ollama connection failed"

**Fix:** Make sure Ollama is running:
```bash
ollama serve
```

### Issue: "Model not found"

**Fix:** Pull the model:
```bash
ollama pull qwen2.5:3b-instruct
```

### Issue: "Component file not found"

**Fix:** Check your Vue Router component paths are correct.

## Using Different AI Providers

### OpenAI

```env
AI_DOCS_PROVIDER=openai
AI_DOCS_OPENAI_KEY=sk-your-api-key-here
AI_DOCS_OPENAI_STANDARD_MODEL=gpt-4
```

### Claude

```env
AI_DOCS_PROVIDER=claude
AI_DOCS_CLAUDE_KEY=sk-ant-your-api-key-here
AI_DOCS_CLAUDE_STANDARD_MODEL=claude-3-sonnet-20240229
```

### Gemini

```env
AI_DOCS_PROVIDER=gemini
AI_DOCS_GEMINI_KEY=AIza-your-api-key-here
AI_DOCS_GEMINI_STANDARD_MODEL=gemini-1.5-pro
```

## Example Output

Your generated documentation will look like this:

```markdown
# Dashboard

**Page URL:** http://localhost/dashboard

## 1. Overview & Purpose
The Dashboard is your central hub for viewing key information...

## 2. How to Access This Page
**For Students:**
1. Log in to the Student Portal
2. You'll automatically land on the Dashboard
3. Or click "Dashboard" in the left sidebar

## 3. Page Layout & What You'll See
- Top section: Welcome message and quick stats
- Middle section: Recent activities
- Bottom section: Upcoming events

## 4. Step-by-Step User Guide
### Viewing Your Stats
1. Look at the top cards showing your progress
2. Click any card to see more details
...

## 5. Common Tasks & Real Examples
### Example 1: Checking Your Grades
1. Find the "Recent Grades" section
2. Click "View All Grades"
...

## 6. Troubleshooting & FAQ
**Q: Why don't I see any data?**
A: Make sure you're enrolled in at least one course...

## 7. Related Pages & Next Steps
- [/student/courses](http://localhost/student/courses)
- [/student/grades](http://localhost/student/grades)
```

## Next Steps

1. **Customize Configuration**: Edit `config/ai-docs.php` to match your needs
2. **Integrate Documentation**: Use generated docs in your help system
3. **Set Up Automation**: Add to CI/CD pipeline
4. **Explore Features**: Check out USAGE.md for advanced features

## Getting Help

- **Documentation**: See README.md and USAGE.md
- **Testing**: See TESTING.md
- **Issues**: Check TROUBLESHOOTING section in USAGE.md

## Tips for Best Results

1. **Start Small**: Test with one route before generating all
2. **Use Ollama**: Free and fast for development
3. **Cache Navigation**: Only refresh when navigation changes
4. **Review Output**: Check first few docs for quality
5. **Adjust Settings**: Tune chunk size and depth as needed

## That's It!

You're now generating AI-powered documentation for your Vue.js application! 🎉

For more details, see:
- **README.md** - Full documentation
- **USAGE.md** - Detailed usage guide
- **TESTING.md** - Testing guide
- **config/ai-docs.php** - All configuration options

