# AI Documentation Generator - Package Structure

## Overview

This package provides AI-powered documentation generation for Laravel applications with Vue.js frontends. It supports multiple AI providers and generates comprehensive, user-friendly documentation automatically.

## Directory Structure

```
packages/ai-documentation-generator/
├── config/
│   └── ai-docs.php                          # Main configuration file
├── src/
│   ├── Console/
│   │   ├── GenerateFrontendDocsCommand.php  # Command to generate docs
│   │   └── BuildDocVectorDbCommand.php      # Command to build vector DB
│   ├── Contracts/
│   │   └── AIProviderInterface.php          # AI provider interface
│   ├── Providers/
│   │   └── AIDocumentationServiceProvider.php # Laravel service provider
│   └── Services/
│       ├── AIProviderFactory.php            # Factory for AI providers
│       ├── DocumentationGenerator.php       # Main generator service
│       ├── DocumentationGeneratorExtension.php # Extension methods
│       ├── DocumentationGeneratorMethods.php   # Additional methods
│       └── Providers/
│           ├── AbstractAIProvider.php       # Base provider class
│           ├── OllamaProvider.php          # Ollama implementation
│           ├── OpenAIProvider.php          # OpenAI implementation
│           ├── ClaudeProvider.php          # Claude implementation
│           └── GeminiProvider.php          # Gemini implementation
├── composer.json                            # Package dependencies
├── LICENSE                                  # MIT License
├── README.md                               # Main documentation
├── USAGE.md                                # Usage guide
├── CHANGELOG.md                            # Version history
├── .env.example                            # Environment variables example
└── PACKAGE_STRUCTURE.md                    # This file
```

## Key Components

### 1. Configuration (`config/ai-docs.php`)
- Multi-provider settings (Ollama, OpenAI, Claude, Gemini)
- Layout file paths for context
- Generation settings (depth, chunk size, concurrency)
- Vector database configuration
- Route link settings
- Documentation structure customization

### 2. AI Provider System

#### Interface (`AIProviderInterface`)
Defines the contract for all AI providers:
- `chat(array $messages, string $modelSize): string`
- `getName(): string`
- `isAvailable(): bool`
- `getModel(string $modelSize): string`

#### Abstract Base (`AbstractAIProvider`)
Common functionality for all providers:
- Configuration management
- Model selection
- Availability checking
- Helper methods

#### Concrete Providers
- **OllamaProvider**: Local AI models (free, no API key)
- **OpenAIProvider**: GPT models (requires API key)
- **ClaudeProvider**: Anthropic Claude (requires API key)
- **GeminiProvider**: Google Gemini (requires API key)

### 3. Documentation Generator (`DocumentationGenerator`)

Main service that orchestrates documentation generation:

#### Route Parsing
- Parses Vue Router configuration
- Extracts route paths and components
- Handles nested routes
- Resolves component paths

#### Component Crawling
- Recursively crawls Vue components
- Extracts template and script content
- Follows import statements
- Respects depth limits

#### Context Processing
- Flattens component hierarchy
- Chunks content for AI processing
- Manages memory efficiently
- Removes technical jargon

#### Navigation Analysis
- Analyzes navigation components
- Builds navigation memory
- Provides role-specific instructions
- Caches for performance

#### Documentation Generation
- Generates comprehensive guides
- Includes route links
- Finds related pages
- Structures content properly

### 4. Console Commands

#### `ai-docs:generate`
Generates documentation from Vue frontend:
- Parses routes from router file
- Analyzes navigation structure
- Crawls Vue components
- Generates documentation with AI
- Stores in markdown and JSON formats

Options:
- `--routes`: Custom router file path
- `--max-depth`: Component crawl depth
- `--refresh-navigation`: Refresh navigation cache
- `--test-single`: Test single route
- `--concurrency`: Concurrent requests
- `--force`: Force regeneration

#### `ai-docs:build-vector-db`
Builds vector database from generated docs:
- Processes markdown files
- Extracts keywords and headings
- Builds document index
- Creates searchable vector store

Options:
- `--dir`: Documentation directory
- `--force`: Force rebuild
- `--limit`: Limit files processed

### 5. Service Provider (`AIDocumentationServiceProvider`)
- Registers services in Laravel container
- Publishes configuration
- Registers console commands
- Binds DocumentationGenerator as singleton

## Features

### Multi-Provider Support
Switch between AI providers with a single configuration change. Each provider has optimized implementations for their specific API.

### Navigation-Aware Documentation
Analyzes navigation components to provide accurate, role-specific navigation instructions in generated documentation.

### Route Link Generation
Automatically includes clickable route links in documentation, making it easy for users to navigate between related pages.

### Context-Rich Generation
Uses layout files, navigation components, and router configuration to provide comprehensive context to the AI.

### Intelligent Chunking
Breaks large components into manageable chunks for AI processing while maintaining context.

### Memory Management
Automatically trims conversation memory to prevent context overflow while keeping important information.

### Technical Term Removal
Post-processes generated documentation to remove technical jargon and make it user-friendly.

### Caching
Caches navigation analysis to improve performance on subsequent runs.

## Output Format

Generated documentation includes:

1. **Overview & Purpose**: What the page does and who uses it
2. **Navigation Instructions**: Role-specific, step-by-step navigation
3. **Page Layout**: Description of UI elements
4. **Step-by-Step Guide**: Detailed usage instructions
5. **Common Tasks**: Real-world examples
6. **Troubleshooting**: FAQ and problem solutions
7. **Related Pages**: Links to related documentation

Output formats:
- **Markdown** (`.md`): Human-readable format
- **JSON** (`.json`): Machine-readable with metadata

## Integration

### In Laravel Application

1. Add to `composer.json`:
```json
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
```

2. Install:
```bash
composer update
```

3. Publish config:
```bash
php artisan vendor:publish --tag=ai-docs-config
```

4. Configure and use!

## Extensibility

The package is designed to be extensible:

- **Add New Providers**: Implement `AIProviderInterface`
- **Custom Documentation Structure**: Modify config
- **Custom Post-Processing**: Extend `DocumentationGenerator`
- **Custom Commands**: Create new commands using the service

## Performance Considerations

- Use Ollama for free, local processing
- Adjust concurrency based on API limits
- Cache navigation analysis
- Limit component crawl depth
- Use appropriate model sizes

## Security

- API keys stored in environment variables
- No sensitive data in generated docs
- Configurable output directory
- Safe file path resolution

## Future Enhancements

- React and Angular support
- Multi-language documentation
- API documentation generation
- Real-time updates
- Documentation versioning
- Custom templates
- Enhanced vector search

