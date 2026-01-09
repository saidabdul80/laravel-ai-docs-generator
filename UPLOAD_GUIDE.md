# AI Service Upload Guide

This guide explains how to upload your generated documentation vector database to the AI service.

## Overview

After generating documentation and building the vector database, you can upload the `neuron.store` and `index.json` files to your AI service. This allows the AI service to use your documentation for answering user queries.

## Prerequisites

1. Generated documentation (`php artisan ai-docs:generate`)
2. Built vector database (`php artisan ai-docs:build-vector-db`)
3. AI service running and accessible
4. Valid API key for the AI service

## Configuration

### 1. Environment Variables

Add these to your `.env` file:

```env
# Enable upload functionality
AI_DOCS_UPLOAD_ENABLED=true

# AI service URL (without trailing slash)
AI_SERVICE_URL=http://localhost:8000

# Your AI service API key
AI_SERVICE_API_KEY=your-api-key-here

# Agent name (namespace for your documentation)
AI_DOCS_AGENT_NAME=documentation

# Upload timeout in seconds
AI_DOCS_UPLOAD_TIMEOUT=60

# Verify SSL certificates (set to false for local development)
AI_DOCS_VERIFY_SSL=true
```

### 2. Get Your API Key

To get an API key from your AI service:

1. Log in to the AI service admin panel
2. Navigate to Clients section
3. Create or select a client
4. Generate an API key
5. Copy the key to your `.env` file

## Usage

### Method 1: Build and Upload Together

The easiest way is to build and upload in one command:

```bash
php artisan ai-docs:build-vector-db --upload
```

This will:
1. Build the vector database
2. Create neuron.store and index.json
3. Automatically upload to the AI service

### Method 2: Upload Separately

If you've already built the vector database:

```bash
php artisan ai-docs:upload
```

### Method 3: Upload with Options

```bash
# Upload with custom agent name
php artisan ai-docs:upload --agent=my-custom-agent

# Upload from custom directory
php artisan ai-docs:upload --dir=storage/app/custom_docs

# Upload specific files
php artisan ai-docs:upload \
  --neuron-store=path/to/neuron.store \
  --index=path/to/index.json

# Check status before uploading
php artisan ai-docs:upload --check-status
```

## Command Options

### `ai-docs:build-vector-db`

- `--upload` - Upload to AI service after building
- `--no-upload` - Skip upload even if enabled in config

### `ai-docs:upload`

- `--dir=path` - Directory containing neuron.store and index.json
- `--neuron-store=path` - Path to neuron.store file
- `--index=path` - Path to index.json file
- `--agent=name` - Agent name (overrides config)
- `--check-status` - Check AI service status before uploading

## What Gets Uploaded

The upload process sends two files to the AI service:

1. **neuron.store** - The vector database containing embeddings
2. **index.json** - Metadata about the documentation (titles, keywords, etc.)

These files are uploaded to:
```
POST {AI_SERVICE_URL}/api/knowledge
```

With the following form data:
- `neuron_store` (file)
- `index` (file)
- `agent` (string)

## AI Service Storage

On the AI service, files are stored at:
```
storage/app/ai_docs/client_{CLIENT_ID}/{AGENT_NAME}/
├── neuron.store
└── index.json
```

Where:
- `CLIENT_ID` is determined by your API key
- `AGENT_NAME` is from your configuration (default: "documentation")

## Checking Upload Status

To check if documentation exists on the AI service:

```bash
php artisan ai-docs:upload --check-status
```

This will show:
- Whether a knowledge base exists for your agent
- Client ID
- Agent name

## Troubleshooting

### "AI service upload is not enabled"

**Solution:** Set `AI_DOCS_UPLOAD_ENABLED=true` in your `.env` file

### "Configuration errors: AI service URL is not configured"

**Solution:** Set `AI_SERVICE_URL` in your `.env` file
```env
AI_SERVICE_URL=http://localhost:8000
```

### "Configuration errors: AI service API key is not configured"

**Solution:** Set `AI_SERVICE_API_KEY` in your `.env` file
```env
AI_SERVICE_API_KEY=your-api-key-here
```

### "Neuron store file not found"

**Solution:** Build the vector database first:
```bash
php artisan ai-docs:build-vector-db
```

### "Upload failed: Invalid API key"

**Solutions:**
1. Verify your API key is correct
2. Check if the API key is active (not revoked)
3. Ensure the API key belongs to an active client

### "Upload failed: 401 Unauthorized"

**Solution:** Your API key is invalid or expired. Generate a new one from the AI service admin panel.

### "Upload failed: Connection refused"

**Solutions:**
1. Check if the AI service is running
2. Verify the `AI_SERVICE_URL` is correct
3. Check firewall settings

### SSL Certificate Errors

**Solution:** For local development, disable SSL verification:
```env
AI_DOCS_VERIFY_SSL=false
```

**Warning:** Only disable SSL verification in development. Always use SSL in production.

## Best Practices

### 1. Use Different Agents for Different Apps

If you have multiple applications, use different agent names:

```env
# App 1
AI_DOCS_AGENT_NAME=student-portal

# App 2
AI_DOCS_AGENT_NAME=admin-panel
```

### 2. Automate with CI/CD

Add to your deployment pipeline:

```bash
# In your deploy script
php artisan ai-docs:generate
php artisan ai-docs:build-vector-db --upload
```

### 3. Version Your Documentation

Include version in agent name:

```env
AI_DOCS_AGENT_NAME=documentation-v2.0
```

### 4. Monitor Upload Success

Check logs after upload:

```bash
tail -f storage/logs/laravel.log | grep "Documentation uploaded"
```

### 5. Test Before Production

Always test upload in staging first:

```bash
# Staging
AI_SERVICE_URL=https://staging-ai.example.com
php artisan ai-docs:upload --check-status
php artisan ai-docs:upload
```

## Security Considerations

1. **API Keys**: Never commit API keys to version control
2. **SSL**: Always use HTTPS in production
3. **Access Control**: Ensure only authorized systems can upload
4. **File Validation**: The AI service validates uploaded files
5. **Rate Limiting**: Be aware of API rate limits

## Integration with AI Service

Once uploaded, your documentation can be used by the AI service for:

1. **Contextual Responses**: AI uses documentation to answer user questions
2. **Semantic Search**: Users can search documentation semantically
3. **Role-Based Access**: Different agents for different user roles
4. **Multi-Tenant**: Each client has isolated documentation

## Example Workflow

Complete workflow from generation to upload:

```bash
# 1. Generate documentation
php artisan ai-docs:generate

# 2. Build vector database and upload
php artisan ai-docs:build-vector-db --upload

# 3. Verify upload
php artisan ai-docs:upload --check-status

# Output:
# ✅ Knowledge base exists for agent: documentation
```

## API Response Examples

### Successful Upload

```json
{
  "status": "success",
  "message": "Knowledge base uploaded successfully",
  "client": "My Application",
  "agent": "documentation"
}
```

### Failed Upload

```json
{
  "message": "The neuron store field is required."
}
```

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review logs: `storage/logs/laravel.log`
3. Verify AI service is accessible
4. Test with `--check-status` flag
5. Check AI service documentation

## Next Steps

After successful upload:

1. Test AI service with documentation queries
2. Monitor AI service logs for usage
3. Set up automated uploads in CI/CD
4. Configure multiple agents if needed
5. Implement documentation versioning

