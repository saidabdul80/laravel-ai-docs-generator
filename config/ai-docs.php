<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which AI provider to use for documentation generation.
    | Supported: 'ollama', 'openai', 'claude', 'gemini', 'schooltry'
    |
    */
    'provider' => env('AI_DOCS_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Provider-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for each AI provider including endpoints, models, and API keys
    |
    */
    'providers' => [
        'ollama' => [
            'base_url' => env('AI_DOCS_OLLAMA_URL', 'http://localhost:11434'),
            'models' => [
                'lightweight' => env('AI_DOCS_OLLAMA_LIGHT_MODEL', 'qwen2.5:3b-instruct'),
                'standard' => env('AI_DOCS_OLLAMA_STANDARD_MODEL', 'qwen2.5:7b-instruct'),
                'heavy' => env('AI_DOCS_OLLAMA_HEAVY_MODEL', 'qwen2.5:14b'),
            ],
            'temperature' => 0.7,
            'max_tokens' => 2500,
            'timeout' => 120,
        ],

        'openai' => [
            'base_url' => env('AI_DOCS_OPENAI_URL', 'https://api.openai.com/v1'),
            'api_key' => env('AI_DOCS_OPENAI_KEY'),
            'models' => [
                'lightweight' => env('AI_DOCS_OPENAI_LIGHT_MODEL', 'gpt-3.5-turbo'),
                'standard' => env('AI_DOCS_OPENAI_STANDARD_MODEL', 'gpt-4'),
                'heavy' => env('AI_DOCS_OPENAI_HEAVY_MODEL', 'gpt-4-turbo'),
            ],
            'temperature' => 0.7,
            'max_tokens' => 2500,
            'timeout' => 60,
        ],

        'claude' => [
            'base_url' => env('AI_DOCS_CLAUDE_URL', 'https://api.anthropic.com/v1'),
            'api_key' => env('AI_DOCS_CLAUDE_KEY'),
            'models' => [
                'lightweight' => env('AI_DOCS_CLAUDE_LIGHT_MODEL', 'claude-3-haiku-20240307'),
                'standard' => env('AI_DOCS_CLAUDE_STANDARD_MODEL', 'claude-3-sonnet-20240229'),
                'heavy' => env('AI_DOCS_CLAUDE_HEAVY_MODEL', 'claude-3-opus-20240229'),
            ],
            'temperature' => 0.7,
            'max_tokens' => 2500,
            'timeout' => 60,
        ],

        'gemini' => [
            'base_url' => env('AI_DOCS_GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'api_key' => env('AI_DOCS_GEMINI_KEY'),
            'models' => [
                'lightweight' => env('AI_DOCS_GEMINI_LIGHT_MODEL', 'gemini-1.5-flash'),
                'standard' => env('AI_DOCS_GEMINI_STANDARD_MODEL', 'gemini-1.5-pro'),
                'heavy' => env('AI_DOCS_GEMINI_HEAVY_MODEL', 'gemini-2.0-flash'),
            ],
            'temperature' => 0.7,
            'max_tokens' => 2500,
            'timeout' => 60,
        ],

        'schooltry' => [
            'base_url' => env('AI_DOCS_SCHOOLTRY_URL', 'http://localhost:8000/api/docs'),
            'api_key' => env('AI_DOCS_SCHOOLTRY_KEY'), // Optional, for future authentication
            'models' => [
                'lightweight' => env('AI_DOCS_SCHOOLTRY_LIGHT_MODEL', 'documentation'),
                'standard' => env('AI_DOCS_SCHOOLTRY_STANDARD_MODEL', 'documentation'),
                'heavy' => env('AI_DOCS_SCHOOLTRY_HEAVY_MODEL', 'documentation'),
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'timeout' => 120,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout Files for Context
    |--------------------------------------------------------------------------
    |
    | These files will be passed to every prompt to provide better context
    | about the application structure and navigation
    |
    */
    'layout_files' => [
        'router' => env('AI_DOCS_ROUTER_FILE', 'resources/js/router/index.js'),
        'navigation' => env('AI_DOCS_NAVIGATION_DIR', 'resources/js/components/Navigation_modules'),
        'layouts' => env('AI_DOCS_LAYOUTS_DIR', 'resources/js/layouts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Generation Settings
    |--------------------------------------------------------------------------
    */
    'generation' => [
        'max_depth' => env('AI_DOCS_MAX_DEPTH', 5),
        'chunk_size' => env('AI_DOCS_CHUNK_SIZE', 2000),
        'concurrency' => env('AI_DOCS_CONCURRENCY', 1),
        'memory_limit' => env('AI_DOCS_MEMORY_LIMIT', 40),
        'output_dir' => env('AI_DOCS_OUTPUT_DIR', 'storage/app/ai_docs/frontend'),
        'cache_navigation' => env('AI_DOCS_CACHE_NAVIGATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Database Settings
    |--------------------------------------------------------------------------
    */
    'vector_db' => [
        'enabled' => env('AI_DOCS_VECTOR_DB_ENABLED', true),
        'store_path' => env('AI_DOCS_VECTOR_STORE_PATH', 'storage/app/ai_docs/frontend/neuron.store'),
        'chunk_size' => env('AI_DOCS_VECTOR_CHUNK_SIZE', 900),
        'overlap_size' => env('AI_DOCS_VECTOR_OVERLAP', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Link Generation
    |--------------------------------------------------------------------------
    |
    | Settings for generating route links in documentation
    |
    */
    'route_links' => [
        'enabled' => env('AI_DOCS_ROUTE_LINKS_ENABLED', true),
        'base_url' => env('APP_URL', 'http://localhost'),
        'include_related' => env('AI_DOCS_INCLUDE_RELATED_ROUTES', true),
        'max_related' => env('AI_DOCS_MAX_RELATED_ROUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Structure
    |--------------------------------------------------------------------------
    |
    | Define the structure and sections of generated documentation
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | AI Service Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for uploading generated documentation to the AI service
    | Enable this to automatically upload neuron.store and index.json
    |
    */
    'ai_service' => [
        'enabled' => env('AI_DOCS_UPLOAD_ENABLED', false),
        'url' => env('AI_SERVICE_URL', 'http://localhost:8000'),
        'api_key' => env('AI_SERVICE_API_KEY', ''),
        'agent' => env('AI_DOCS_AGENT_NAME', 'documentation'),
        'timeout' => env('AI_DOCS_UPLOAD_TIMEOUT', 60),
        'verify_ssl' => env('AI_DOCS_VERIFY_SSL', true),
    ],
];

