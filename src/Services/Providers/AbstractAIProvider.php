<?php

namespace SchoolTry\AIDocumentationGenerator\Services\Providers;

use SchoolTry\AIDocumentationGenerator\Contracts\AIProviderInterface;

abstract class AbstractAIProvider implements AIProviderInterface
{
    protected array $config;
    protected string $providerName;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->providerName;
    }

    public function getModel(string $modelSize = 'standard'): string
    {
        return $this->config['models'][$modelSize] ?? $this->config['models']['standard'];
    }

    public function isAvailable(): bool
    {
        // Check if required configuration is present
        if (empty($this->config['base_url'])) {
            return false;
        }

        // Check if API key is required and present
        if ($this->requiresApiKey() && empty($this->config['api_key'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if this provider requires an API key
     *
     * @return bool
     */
    abstract protected function requiresApiKey(): bool;

    /**
     * Get the temperature setting
     *
     * @return float
     */
    protected function getTemperature(): float
    {
        return $this->config['temperature'] ?? 0.7;
    }

    /**
     * Get the max tokens setting
     *
     * @return int
     */
    protected function getMaxTokens(): int
    {
        return $this->config['max_tokens'] ?? 2500;
    }

    /**
     * Get the timeout setting
     *
     * @return int
     */
    protected function getTimeout(): int
    {
        return $this->config['timeout'] ?? 60;
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->config['base_url'], '/');
    }

    /**
     * Get the API key
     *
     * @return string|null
     */
    protected function getApiKey(): ?string
    {
        return $this->config['api_key'] ?? null;
    }
}

