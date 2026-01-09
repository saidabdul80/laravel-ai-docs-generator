<?php

namespace SchoolTry\AIDocumentationGenerator\Services\Providers;

use SchoolTry\AIDocumentationGenerator\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SchoolTryAIProvider implements AIProviderInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $model;
    protected float $temperature;
    protected int $maxTokens;
    protected int $timeout;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->apiKey = $config['api_key'] ?? null;
        $this->model = $config['models']['standard'] ?? 'documentation';
        $this->temperature = $config['temperature'] ?? 0.7;
        $this->maxTokens = $config['max_tokens'] ?? 4000;
        $this->timeout = $config['timeout'] ?? 120;
    }

    /**
     * Send a chat completion request to SchoolTry AI Service
     */
    public function chat(array $messages, string $modelSize = 'standard'): string
    {
        $modelToUse = $this->getModel($modelSize);

        try {
            // Calculate total message length for logging
            $totalLength = array_sum(array_map(fn($m) => strlen($m['content'] ?? ''), $messages));

            Log::info('SchoolTry AI: Sending request', [
                'model' => $modelToUse,
                'messages_count' => count($messages),
                'total_length' => $totalLength,
            ]);

            $request = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]);

            // Add API key if configured
            if ($this->apiKey) {
                $request->withToken($this->apiKey);
            }

            $response = $request->post("{$this->baseUrl}/chat", [
                'messages' => $messages,
                'model' => $modelToUse,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
            ]);

            if (!$response->successful()) {
                Log::error('SchoolTry AI: Request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception(
                    "SchoolTry AI request failed: {$response->status()} - {$response->body()}"
                );
            }

            $data = $response->json();

            // Extract content from OpenAI-compatible response format
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                Log::error('SchoolTry AI: No content in response', [
                    'response' => $data,
                ]);

                throw new \Exception('No content in SchoolTry AI response');
            }

            Log::info('SchoolTry AI: Request successful', [
                'response_length' => strlen($content),
                'tokens_used' => $data['usage']['total_tokens'] ?? 'unknown',
            ]);

            return $content;

        } catch (\Exception $e) {
            Log::error('SchoolTry AI: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the model name for a given size
     */
    public function getModel(string $modelSize = 'standard'): string
    {
        return $this->model; // SchoolTry AI uses the same model for all sizes
    }

    /**
     * Check if the provider is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/models");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('SchoolTry AI: Availability check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'SchoolTry AI Service';
    }

}

