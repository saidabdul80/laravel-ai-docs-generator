<?php

namespace SchoolTry\AIDocumentationGenerator\Services;

use SchoolTry\AIDocumentationGenerator\Contracts\AIProviderInterface;
use SchoolTry\AIDocumentationGenerator\Services\Providers\OllamaProvider;
use SchoolTry\AIDocumentationGenerator\Services\Providers\OpenAIProvider;
use SchoolTry\AIDocumentationGenerator\Services\Providers\ClaudeProvider;
use SchoolTry\AIDocumentationGenerator\Services\Providers\GeminiProvider;
use SchoolTry\AIDocumentationGenerator\Services\Providers\SchoolTryAIProvider;
use InvalidArgumentException;

class AIProviderFactory
{
    /**
     * Create an AI provider instance based on configuration
     *
     * @param string $provider Provider name
     * @param array $config Provider configuration
     * @return AIProviderInterface
     * @throws InvalidArgumentException
     */
    public static function make(string $provider, array $config): AIProviderInterface
    {
        return match ($provider) {
            'ollama' => new OllamaProvider($config),
            'openai' => new OpenAIProvider($config),
            'claude' => new ClaudeProvider($config),
            'gemini' => new GeminiProvider($config),
            'schooltry' => new SchoolTryAIProvider($config),
            default => throw new InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }

    /**
     * Get available providers
     *
     * @return array
     */
    public static function getAvailableProviders(): array
    {
        return ['ollama', 'openai', 'claude', 'gemini', 'schooltry'];
    }
}

