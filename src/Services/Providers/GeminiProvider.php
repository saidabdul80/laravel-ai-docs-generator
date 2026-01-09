<?php

namespace SchoolTry\AIDocumentationGenerator\Services\Providers;

use Illuminate\Support\Facades\Http;
use Exception;

class GeminiProvider extends AbstractAIProvider
{
    protected string $providerName = 'gemini';

    protected function requiresApiKey(): bool
    {
        return true;
    }

    public function chat(array $messages, string $modelSize = 'standard'): string
    {
        $model = $this->getModel($modelSize);
        $url = $this->getBaseUrl() . "/models/{$model}:generateContent?key=" . $this->getApiKey();

        // Convert messages to Gemini format
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemInstruction = $message['content'];
            } else {
                $role = $message['role'] === 'assistant' ? 'model' : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $message['content']]
                    ]
                ];
            }
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $this->getTemperature(),
                'maxOutputTokens' => $this->getMaxTokens(),
            ],
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        try {
            $response = Http::timeout($this->getTimeout())
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->retry(3, 1000)
                ->post($url, $payload);

            if ($response->failed()) {
                throw new Exception("Gemini request failed: HTTP " . $response->status());
            }

            $data = $response->json();

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception("Invalid response format from Gemini");
            }

            return trim($data['candidates'][0]['content']['parts'][0]['text']);

        } catch (Exception $e) {
            throw new Exception("Gemini API Error: " . $e->getMessage());
        }
    }
}

