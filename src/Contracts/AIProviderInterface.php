<?php

namespace SchoolTry\AIDocumentationGenerator\Contracts;

interface AIProviderInterface
{
    /**
     * Send a chat completion request to the AI provider
     *
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param string $modelSize Model size: 'lightweight', 'standard', or 'heavy'
     * @return string The AI response content
     * @throws \Exception
     */
    public function chat(array $messages, string $modelSize = 'standard'): string;

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if the provider is available/configured
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get the model name for a given size
     *
     * @param string $modelSize
     * @return string
     */
    public function getModel(string $modelSize = 'standard'): string;
}

