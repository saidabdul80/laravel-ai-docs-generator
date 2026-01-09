<?php

namespace SchoolTry\AIDocumentationGenerator\Services\Providers;

use Illuminate\Support\Facades\Http;
use Exception;

class OpenAIProvider extends AbstractAIProvider
{
    protected string $providerName = 'openai';

    protected function requiresApiKey(): bool
    {
        return true;
    }

    public function chat(array $messages, string $modelSize = 'standard'): string
    {
        $model = $this->getModel($modelSize);
        $url = $this->getBaseUrl() . '/chat/completions';

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $this->getTemperature(),
            'max_tokens' => $this->getMaxTokens(),
        ];

        try {
            $response = Http::timeout($this->getTimeout())
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Content-Type' => 'application/json',
                ])
                ->retry(3, 1000)
                ->post($url, $payload);

            if ($response->failed()) {
                throw new Exception("OpenAI request failed: HTTP " . $response->status());
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new Exception("Invalid response format from OpenAI");
            }

            return trim($data['choices'][0]['message']['content']);

        } catch (Exception $e) {
            throw new Exception("OpenAI API Error: " . $e->getMessage());
        }
    }
}

