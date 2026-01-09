<?php

namespace SchoolTry\AIDocumentationGenerator\Services\Providers;

use Illuminate\Support\Facades\Http;
use Exception;

class ClaudeProvider extends AbstractAIProvider
{
    protected string $providerName = 'claude';

    protected function requiresApiKey(): bool
    {
        return true;
    }

    public function chat(array $messages, string $modelSize = 'standard'): string
    {
        $model = $this->getModel($modelSize);
        $url = $this->getBaseUrl() . '/messages';

        // Claude requires system message to be separate
        $systemMessage = '';
        $filteredMessages = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemMessage = $message['content'];
            } else {
                $filteredMessages[] = $message;
            }
        }

        $payload = [
            'model' => $model,
            'messages' => $filteredMessages,
            'max_tokens' => $this->getMaxTokens(),
            'temperature' => $this->getTemperature(),
        ];

        if ($systemMessage) {
            $payload['system'] = $systemMessage;
        }

        try {
            $response = Http::timeout($this->getTimeout())
                ->withHeaders([
                    'x-api-key' => $this->getApiKey(),
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->retry(3, 1000)
                ->post($url, $payload);

            if ($response->failed()) {
                throw new Exception("Claude request failed: HTTP " . $response->status());
            }

            $data = $response->json();

            if (!isset($data['content'][0]['text'])) {
                throw new Exception("Invalid response format from Claude");
            }

            return trim($data['content'][0]['text']);

        } catch (Exception $e) {
            throw new Exception("Claude API Error: " . $e->getMessage());
        }
    }
}

