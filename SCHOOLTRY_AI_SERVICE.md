# SchoolTry AI Service Integration

This guide explains how to use the SchoolTry AI Service as your documentation generation provider.

## Overview

The SchoolTry AI Service is a centralized AI service that provides documentation generation capabilities through a dedicated `DocumentationGeneratorAgent`. This agent is specifically optimized for creating end-user documentation from Vue.js components.

## Benefits

### 1. **Centralized AI Infrastructure**
- Single AI service for all SchoolTry applications
- Consistent documentation quality across projects
- Easier to maintain and update prompts

### 2. **Cost Effective**
- No external API costs (OpenAI, Claude, etc.)
- Uses your own infrastructure
- Control over resource usage

### 3. **Customizable**
- Dedicated DocumentationGeneratorAgent with specialized prompts
- Easy to modify system instructions
- Tailored for SchoolTry applications

### 4. **Integrated Ecosystem**
- Works seamlessly with other SchoolTry services
- Upload generated docs back to AI service for customer support
- Unified authentication and access control

## Architecture

```
┌─────────────────────────────────────┐
│  Laravel Application                │
│  (schooltry-tertiary)               │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ AI Documentation Generator    │ │
│  │ Package                       │ │
│  │                               │ │
│  │ Provider: schooltry           │ │
│  └───────────────┬───────────────┘ │
└──────────────────┼──────────────────┘
                   │
                   │ HTTP POST /api/docs/chat
                   │
                   ▼
┌─────────────────────────────────────┐
│  AI Service                         │
│  (ai-service)                       │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ DocsController                │ │
│  │   ↓                           │ │
│  │ DocumentationGeneratorAgent   │ │
│  │   ↓                           │ │
│  │ Ollama / OpenAI / etc.        │ │
│  └───────────────────────────────┘ │
└─────────────────────────────────────┘
```

## Setup

### Step 1: Configure AI Service

Make sure your AI service is running and accessible:

```bash
cd ai-service
php artisan serve --port=8000
```

The AI service should now be available at `http://localhost:8000`.

### Step 2: Configure Documentation Generator

In your Laravel application (e.g., `schooltry-tertiary`), set the provider to `schooltry`:

```env
# AI Documentation Generator Configuration
AI_DOCS_PROVIDER=schooltry
AI_DOCS_SCHOOLTRY_URL=http://localhost:8000/api/docs
AI_DOCS_SCHOOLTRY_KEY=  # Optional, for future authentication
```

### Step 3: Verify Configuration

Check if the AI service is accessible:

```bash
curl http://localhost:8000/api/docs/models
```

You should see:

```json
{
  "object": "list",
  "data": [
    {
      "id": "documentation",
      "object": "model",
      "created": 1704067200,
      "owned_by": "schooltry"
    }
  ]
}
```

### Step 4: Generate Documentation

```bash
php artisan ai-docs:generate
```

The package will now use the SchoolTry AI Service for documentation generation!

## API Endpoints

### POST /api/docs/chat

Generate documentation from component code.

**Request:**

```json
{
  "messages": [
    {
      "role": "user",
      "content": "Analyze this Vue component and generate user documentation..."
    }
  ],
  "model": "documentation",
  "temperature": 0.7,
  "max_tokens": 4000
}
```

**Response:**

```json
{
  "id": "chatcmpl-abc123",
  "object": "chat.completion",
  "created": 1704067200,
  "model": "documentation",
  "choices": [
    {
      "index": 0,
      "message": {
        "role": "assistant",
        "content": "# Student Dashboard\n\n## 1. Overview & Purpose..."
      },
      "finish_reason": "stop"
    }
  ],
  "usage": {
    "prompt_tokens": 150,
    "completion_tokens": 500,
    "total_tokens": 650
  }
}
```

### GET /api/docs/models

List available models.

**Response:**

```json
{
  "object": "list",
  "data": [
    {
      "id": "documentation",
      "object": "model",
      "created": 1704067200,
      "owned_by": "schooltry"
    }
  ]
}
```

## DocumentationGeneratorAgent

The `DocumentationGeneratorAgent` is specifically designed for generating end-user documentation. It includes:

### System Prompt Features

1. **User-Centric Approach**: Writes for non-technical end users
2. **Structured Template**: Follows consistent documentation format
3. **Comprehensive Coverage**: Documents all user-facing features
4. **Clear Instructions**: Step-by-step procedures with examples
5. **Context Awareness**: Considers navigation and access requirements

### Documentation Template

The agent generates documentation following this structure:

```markdown
# [Page/Feature Name]

## 1. Overview & Purpose
- What this page/feature does
- Who uses it
- When to use it

## 2. How to Access This Page
**For [User Role]:**
1. [Navigation step 1]
2. [Navigation step 2]

## 3. Page Layout & What You'll See
- Main sections
- Key information displayed

## 4. Step-by-Step User Guide
### [Task Name]
1. [Step 1]
2. [Step 2]

## 5. Common Tasks & Real Examples
### Example 1: [Task Name]
- Scenario: [When to use]
- Steps: [Quick steps]

## 6. Troubleshooting & FAQ
**Problem:** [Common issue]
**Solution:** [How to fix]

## 7. Related Pages & Next Steps
- [Link to related page 1]
```

## Customization

### Modify System Prompt

Edit `ai-service/app/AI/Agents/DocumentationGeneratorAgent.php`:

```php
public function instructions(): string
{
    return (string) new SystemPrompt(
        background: [<<<PROMPT
You are an expert technical writer...
// Customize your prompt here
PROMPT
        ],
        rules: [
            // Add your custom rules
        ]
    );
}
```

### Change AI Provider

The DocumentationGeneratorAgent uses Ollama by default. To change:

```php
public function provider(): AIProviderInterface
{
    return new Ollama(
        model: config('ai.ollama.model', 'qwen2.5:7b-instruct'),
        baseUrl: config('ai.ollama.base_url', 'http://localhost:11434'),
        temperature: 0.7,
        maxTokens: 4000,
    );
}
```

## Troubleshooting

### Connection Refused

**Problem:** Cannot connect to AI service

**Solutions:**
1. Check if AI service is running: `php artisan serve --port=8000`
2. Verify URL in `.env`: `AI_DOCS_SCHOOLTRY_URL=http://localhost:8000/api/docs`
3. Check firewall settings

### Empty Response

**Problem:** AI service returns empty content

**Solutions:**
1. Check AI service logs: `tail -f ai-service/storage/logs/laravel.log`
2. Verify Ollama is running (if using Ollama)
3. Check DocumentationGeneratorAgent configuration

### Timeout Errors

**Problem:** Requests timeout

**Solutions:**
1. Increase timeout in config: `'timeout' => 180`
2. Use a faster model (lightweight instead of heavy)
3. Reduce max_tokens

## Production Deployment

### 1. Use HTTPS

```env
AI_DOCS_SCHOOLTRY_URL=https://ai.yourcompany.com/api/docs
```

### 2. Add Authentication (Future)

```env
AI_DOCS_SCHOOLTRY_KEY=your-secure-api-key
```

### 3. Load Balancing

For high-volume documentation generation, deploy multiple AI service instances behind a load balancer.

### 4. Monitoring

Monitor AI service performance:

```bash
# Check logs
tail -f ai-service/storage/logs/laravel.log | grep "Docs chat"

# Monitor response times
grep "Docs chat request" ai-service/storage/logs/laravel.log | grep "response_length"
```

## Next Steps

1. Generate documentation: `php artisan ai-docs:generate`
2. Build vector database: `php artisan ai-docs:build-vector-db`
3. Upload to AI service: `php artisan ai-docs:upload`
4. Use in customer support with uploaded documentation

## Support

For issues or questions:

1. Check AI service logs
2. Verify configuration
3. Test endpoint manually with curl
4. Review DocumentationGeneratorAgent code

