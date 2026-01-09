# Changelog

All notable changes to the AI Documentation Generator package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-01-09

### Added
- AI Service upload functionality
- New `ai-docs:upload` command for uploading vector database to AI service
- `--upload` and `--no-upload` options for `ai-docs:build-vector-db` command
- AIServiceUploader service for handling uploads
- Configuration for AI service URL, API key, and agent name
- Status checking before upload
- Automatic file validation and error handling
- Upload progress and result reporting

### Changed
- Updated configuration to include AI service settings
- Enhanced BuildDocVectorDbCommand with upload capability
- Updated documentation with upload instructions

## [1.0.0] - 2026-01-09

### Added
- Initial release of AI Documentation Generator
- Multi-provider support (Ollama, OpenAI, Claude, Gemini)
- Automatic documentation generation from Vue.js components
- Navigation-aware documentation with role-specific instructions
- Route link generation in documentation
- Vector database building for semantic search
- Comprehensive configuration system
- Console commands for documentation generation and vector DB building
- Support for layout files and navigation components for better context
- Customizable documentation structure
- Markdown and JSON output formats
- Progress bars and detailed logging
- Navigation memory caching
- Related routes detection and linking

### Features
- **AI Provider Abstraction**: Clean interface for multiple AI providers
- **Smart Route Parsing**: Automatically parses Vue Router configurations
- **Component Crawling**: Recursively crawls Vue components with depth control
- **Context Chunking**: Intelligent content chunking for large components
- **Memory Management**: Automatic memory trimming to prevent context overflow
- **Technical Term Removal**: Automatically removes technical jargon from documentation
- **Comprehensive Documentation Structure**: 7-section documentation format
- **Configurable Output**: Control which sections to include in documentation

### Configuration
- Provider-specific settings for each AI service
- Layout file configuration for context
- Generation settings (depth, chunk size, concurrency)
- Vector database settings
- Route link configuration
- Documentation structure customization

### Commands
- `ai-docs:generate` - Generate documentation from Vue frontend
- `ai-docs:build-vector-db` - Build vector database from generated docs

### Documentation
- Comprehensive README with installation and usage instructions
- Configuration examples for all supported providers
- Command usage examples
- Environment variable documentation

## [Unreleased]

### Planned
- Support for React and Angular frontends
- Multi-language documentation generation
- API documentation generation
- Integration with popular documentation platforms
- Real-time documentation updates
- Documentation versioning
- Custom template support
- Enhanced vector search capabilities

