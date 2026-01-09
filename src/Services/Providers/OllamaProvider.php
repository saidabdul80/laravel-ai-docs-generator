<?php

namespace SchoolTry\AIDocumentationGenerator\Services\Providers;

use Illuminate\Support\Facades\Http;
use Exception;

class OllamaProvider extends AbstractAIProvider
{
    protected string $providerName = 'ollama';

    protected function requiresApiKey(): bool
    {
        return false;
    }

    public function chat(array $messages, string $modelSize = 'standard'): string
    {
        $model = $this->getModel($modelSize);
        $url = $this->getBaseUrl() . '/api/chat';

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $this->getTemperature(),
            'stream' => false,
        ];

        try {
            $response = Http::timeout($this->getTimeout())
                ->retry(3, 1000)
                ->post($url, $payload);

            if ($response->failed()) {
                throw new Exception("Ollama request failed: HTTP " . $response->status());
            }

            $data = $response->json();

            if (!isset($data['message']['content'])) {
                throw new Exception("Invalid response format from Ollama");
            }

            return trim($data['message']['content']);

        } catch (Exception $e) {
            throw new Exception("Ollama API Error: " . $e->getMessage());
        }
    }
}

